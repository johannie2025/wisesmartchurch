// Wise Church Studio — hôte desktop.
// Relais WebSocket LAN (routage par code de salle) + annonce/détection mDNS "_wisechurch._tcp".
#![cfg_attr(not(debug_assertions), windows_subsystem = "windows")]

use futures_util::{SinkExt, StreamExt};
use mdns_sd::{ServiceDaemon, ServiceEvent, ServiceInfo};
use serde::Deserialize;
use std::{
    collections::{HashMap, HashSet},
    sync::{
        atomic::{AtomicU32, Ordering},
        Arc,
    },
    time::Duration,
};
use tauri::{
    AppHandle, Manager, PhysicalPosition, PhysicalSize, Position, Size, WebviewUrl,
    WebviewWindowBuilder, WindowEvent,
};
use std::io::Write;
use std::process::{Child, Command, Stdio};
use std::sync::Mutex as StdMutex;
use tokio::{
    net::TcpListener,
    sync::{mpsc, Mutex},
};
use tokio_tungstenite::tungstenite::Message;

const PORT: u16 = 8787;
static FF: StdMutex<Option<Child>> = StdMutex::new(None);
static WIN_N: AtomicU32 = AtomicU32::new(1);
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


/// Script injecté dans les fenêtres d'écran : l'interface lit `window.__WC` (aucune saisie, aucun code).
fn init_script(vid: &str, role: &str) -> String {
    format!(
        "window.__WC={{mode:'viewer',vid:'{}',role:'{}',relay:'ws://127.0.0.1:{}',auto:'1'}};",
        vid, role, PORT
    )
}

/// Projection automatique : une fenêtre plein écran sur chaque moniteur externe
/// (HDMI, VGA, DVI, DisplayPort…), créée, recalée ou fermée à chaud selon les écrans branchés.
/// Moniteur 1 = Program, 2 = Stage, 3 = Confidence, suivants = Side.
#[tauri::command]
async fn sync_outputs(app: AppHandle) -> Result<usize, String> {
    let main = app.get_webview_window("main").ok_or("fenêtre principale introuvable")?;
    let all = main.available_monitors().map_err(|e| e.to_string())?;
    let cur = main.current_monitor().map_err(|e| e.to_string())?;
    let cur_pos = cur.map(|m| (m.position().x, m.position().y));
    let mut ext: Vec<_> = all
        .into_iter()
        .filter(|m| Some((m.position().x, m.position().y)) != cur_pos)
        .collect();
    ext.sort_by_key(|m| (m.position().x, m.position().y));

    let wanted: HashSet<String> = (1..=ext.len()).map(|i| format!("out{}", i)).collect();
    for (label, w) in app.webview_windows() {
        if label.starts_with("out") && !wanted.contains(&label) {
            let _ = w.close();
        }
    }

    for (i, m) in ext.iter().enumerate() {
        let label = format!("out{}", i + 1);
        let pos = *m.position();
        let size = *m.size();
        if let Some(w) = app.get_webview_window(&label) {
            let same = w
                .current_monitor()
                .ok()
                .flatten()
                .map(|cm| (cm.position().x, cm.position().y) == (pos.x, pos.y))
                .unwrap_or(false);
            if !same {
                let _ = w.set_fullscreen(false);
                let _ = w.set_position(Position::Physical(PhysicalPosition::new(pos.x, pos.y)));
                let _ = w.set_size(Size::Physical(PhysicalSize::new(size.width, size.height)));
                let _ = w.set_fullscreen(true);
            }
            continue;
        }
        let role = ["program", "stage", "confidence", "side"][i.min(3)];
        let script = init_script(&format!("OUT{}", i + 1), role);
        let win = WebviewWindowBuilder::new(&app, &label, WebviewUrl::App("index.html".into()))
            .title(format!("Wise Design Smart Church System — Sortie {}", i + 1))
            .decorations(false)
            .skip_taskbar(true)
            .focused(false)
            .inner_size(800.0, 450.0)
            .initialization_script(&script)
            .build()
            .map_err(|e| e.to_string())?;
        let _ = win.set_position(Position::Physical(PhysicalPosition::new(pos.x, pos.y)));
        let _ = win.set_size(Size::Physical(PhysicalSize::new(size.width, size.height)));
        let _ = win.set_fullscreen(true);
        let _ = win.set_cursor_visible(false);
    }
    Ok(ext.len())
}

/// Ferme toutes les sorties automatiques (bouton « sorties auto » désactivé).
#[tauri::command]
async fn close_outputs(app: AppHandle) -> Result<(), String> {
    for (label, w) in app.webview_windows() {
        if label.starts_with("out") {
            let _ = w.close();
        }
    }
    Ok(())
}

/// Fenêtre d'écran supplémentaire (en fenêtre, sur le moniteur principal).
#[tauri::command]
async fn open_viewer_window(app: AppHandle) -> Result<(), String> {
    let n = WIN_N.fetch_add(1, Ordering::SeqCst);
    WebviewWindowBuilder::new(&app, format!("win{}", n), WebviewUrl::App("index.html".into()))
        .title(format!("Wise Design Smart Church System — Écran {}", n))
        .inner_size(1280.0, 720.0)
        .initialization_script(&init_script(&format!("WIN{}", n), "program"))
        .build()
        .map_err(|e| e.to_string())?;
    Ok(())
}


fn ffmpeg_path(app: &AppHandle) -> String {
    if let Ok(d) = app.path().resource_dir() {
        for rel in ["bin/ffmpeg.exe", "bin/ffmpeg", "resources/bin/ffmpeg.exe"] {
            let c = d.join(rel);
            if c.exists() {
                return c.to_string_lossy().to_string();
            }
        }
    }
    "ffmpeg".to_string()
}

/// Diffusion multi-flux RTMP + enregistrement MP4 via FFmpeg (entrée WebM sur stdin).
#[tauri::command]
fn stream_start(app: AppHandle, urls: Vec<String>, record: Option<String>) -> Result<(), String> {
    stream_stop();
    let mut outs: Vec<String> = urls
        .iter()
        .filter(|u| !u.trim().is_empty())
        .map(|u| format!("[f=flv:onfail=ignore]{}", u.trim()))
        .collect();
    if let Some(mut r) = record {
        if r == "auto" {
            let home = std::env::var("USERPROFILE").or_else(|_| std::env::var("HOME")).unwrap_or_default();
            let ts = std::time::SystemTime::now().duration_since(std::time::UNIX_EPOCH).map(|d| d.as_secs()).unwrap_or(0);
            let dir = format!("{}/Videos", home.replace('\\', "/"));
            let _ = std::fs::create_dir_all(&dir);
            r = format!("{}/WiseChurch-{}.mp4", dir, ts);
        }
        outs.push(format!("[f=mp4:movflags=frag_keyframe+empty_moov]{}", r.replace('\\', "/")));
    }
    if outs.is_empty() {
        return Err("aucune destination".into());
    }
    let child = Command::new(ffmpeg_path(&app))
        .args(["-loglevel", "error", "-i", "pipe:0", "-c:v", "libx264", "-preset", "veryfast", "-tune", "zerolatency",
               "-pix_fmt", "yuv420p", "-g", "60", "-b:v", "4000k", "-c:a", "aac", "-b:a", "128k", "-ar", "44100",
               "-map", "0:v", "-map", "0:a?", "-f", "tee", &outs.join("|")])
        .stdin(Stdio::piped())
        .stdout(Stdio::null())
        .stderr(Stdio::null())
        .spawn()
        .map_err(|e| format!("FFmpeg introuvable : {}", e))?;
    *FF.lock().unwrap() = Some(child);
    Ok(())
}

#[tauri::command]
fn stream_write(request: tauri::ipc::Request) -> Result<(), String> {
    if let tauri::ipc::InvokeBody::Raw(b) = request.body() {
        if let Some(c) = FF.lock().unwrap().as_mut() {
            if let Some(i) = c.stdin.as_mut() {
                i.write_all(b).map_err(|e| e.to_string())?;
            }
        }
    }
    Ok(())
}

#[tauri::command]
fn stream_stop() {
    if let Some(mut c) = FF.lock().unwrap().take() {
        drop(c.stdin.take());
        let _ = c.wait();
    }
}

fn main() {
    let rt = tokio::runtime::Runtime::new().expect("tokio");
    let peers: Peers = Arc::new(Mutex::new(HashMap::new()));
    rt.spawn(relay(peers));
    announce_mdns();
    std::mem::forget(rt);
    tauri::Builder::default()
        .setup(|app| {
            // quand la régie se ferme, toutes les sorties se ferment aussi
            let handle = app.handle().clone();
            if let Some(w) = app.get_webview_window("main") {
                w.on_window_event(move |e| {
                    if let WindowEvent::CloseRequested { .. } = e {
                        handle.exit(0);
                    }
                });
            }
            Ok(())
        })
        .invoke_handler(tauri::generate_handler![
            lan_ip,
            discover_relay,
            sync_outputs,
            close_outputs,
            open_viewer_window,
            stream_start,
            stream_write,
            stream_stop
        ])
        .run(tauri::generate_context!())
        .expect("erreur Tauri");
}
