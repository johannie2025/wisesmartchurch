// Wise Church Studio — hôte desktop.
// Relais WebSocket LAN (routage par code de salle) + annonce/détection mDNS "_wisechurch._tcp".
#![cfg_attr(not(debug_assertions), windows_subsystem = "windows")]

use futures_util::{SinkExt, StreamExt};
use mdns_sd::{ServiceDaemon, ServiceEvent, ServiceInfo};
use serde::Deserialize;
use std::{
    collections::{HashMap, HashSet},
    sync::Arc,
    time::Duration,
};
use tokio::{
    net::TcpListener,
    sync::{mpsc, Mutex},
};
use tokio_tungstenite::tungstenite::Message;

const PORT: u16 = 8787;
type Tx = mpsc::UnboundedSender<Message>;
type Peers = Arc<Mutex<HashMap<u64, (HashSet<String>, Tx)>>>;

#[derive(Deserialize)]
struct Head {
    #[serde(rename = "type")]
    t: Option<String>,
    room: Option<String>,
    code: Option<String>,
}

async fn relay(peers: Peers) {
    let listener = TcpListener::bind(("0.0.0.0", PORT)).await.expect("bind relay");
    let mut next_id: u64 = 0;
    while let Ok((stream, _)) = listener.accept().await {
        next_id += 1;
        let id = next_id;
        let peers = peers.clone();
        tokio::spawn(async move {
            let Ok(ws) = tokio_tungstenite::accept_async(stream).await else { return };
            let (mut sink, mut src) = ws.split();
            let (tx, mut rx) = mpsc::unbounded_channel::<Message>();
            tokio::spawn(async move {
                while let Some(m) = rx.recv().await {
                    if sink.send(m).await.is_err() {
                        break;
                    }
                }
            });
            peers.lock().await.insert(id, (HashSet::new(), tx));
            while let Some(Ok(msg)) = src.next().await {
                let Message::Text(txt) = msg else { continue };
                let Ok(h) = serde_json::from_str::<Head>(&txt) else { continue };
                let mut g = peers.lock().await;
                if h.t.as_deref() == Some("join") {
                    if let Some(r) = h.room {
                        if let Some(p) = g.get_mut(&id) {
                            p.0.insert(r.to_uppercase());
                        }
                    }
                    continue;
                }
                if let Some(c) = h.code {
                    let c = c.to_uppercase();
                    for (pid, (rooms, ptx)) in g.iter() {
                        if *pid != id && rooms.contains(&c) {
                            let _ = ptx.send(Message::Text(txt.clone()));
                        }
                    }
                }
            }
            peers.lock().await.remove(&id);
        });
    }
}

fn announce_mdns() {
    if let Ok(d) = ServiceDaemon::new() {
        if let Ok(info) = ServiceInfo::new(
            "_wisechurch._tcp.local.",
            "WiseChurch",
            "wisechurch.local.",
            "",
            PORT,
            None::<HashMap<String, String>>,
        ) {
            let _ = d.register(info.enable_addr_auto());
        }
        std::mem::forget(d);
    }
}

/// Adresse IPv4 locale utilisée pour sortir sur le réseau (aucun paquet envoyé).
#[tauri::command]
fn lan_ip() -> String {
    std::net::UdpSocket::bind("0.0.0.0:0")
        .and_then(|s| {
            s.connect("8.8.8.8:80")?;
            s.local_addr()
        })
        .map(|a| a.ip().to_string())
        .unwrap_or_default()
}

/// Cherche un relais Wise Church annoncé en mDNS (3 s max).
#[tauri::command]
async fn discover_relay() -> Option<String> {
    let d = ServiceDaemon::new().ok()?;
    let rx = d.browse("_wisechurch._tcp.local.").ok()?;
    let found = tokio::time::timeout(Duration::from_secs(3), async {
        loop {
            match rx.recv_async().await {
                Ok(ServiceEvent::ServiceResolved(i)) => {
                    if let Some(ip) = i.get_addresses().iter().find(|a| a.is_ipv4()) {
                        return Some(format!("ws://{}:{}", ip, i.get_port()));
                    }
                }
                Ok(_) => {}
                Err(_) => return None,
            }
        }
    })
    .await
    .ok()
    .flatten();
    let _ = d.shutdown();
    found
}

fn main() {
    let rt = tokio::runtime::Runtime::new().expect("tokio");
    let peers: Peers = Arc::new(Mutex::new(HashMap::new()));
    rt.spawn(relay(peers));
    announce_mdns();
    std::mem::forget(rt);
    tauri::Builder::default()
        .invoke_handler(tauri::generate_handler![lan_ip, discover_relay])
        .run(tauri::generate_context!())
        .expect("erreur Tauri");
}
