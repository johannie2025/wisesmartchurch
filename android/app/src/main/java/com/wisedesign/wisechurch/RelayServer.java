package com.wisedesign.wisechurch;

import org.java_websocket.WebSocket;
import org.java_websocket.handshake.ClientHandshake;
import org.java_websocket.server.WebSocketServer;
import org.json.JSONObject;

import java.net.InetSocketAddress;
import java.util.Map;
import java.util.Set;
import java.util.concurrent.ConcurrentHashMap;

/** Relais LAN : {"type":"join","room":"X"} abonne le client à la salle X ; tout autre message est
 *  transmis aux autres abonnés de la salle indiquée par son champ "code". */
public class RelayServer extends WebSocketServer {
    /** Ports essayés dans l'ordre ; le premier libre est utilisé. */
    public static final int[] PORTS = {8787, 8788, 8789, 8790, 8791, 8800, 8880, 9787, 18787, 28787};
    private final Map<WebSocket, Set<String>> rooms = new ConcurrentHashMap<>();

    public RelayServer(int port) {
        super(new InetSocketAddress(port));
        setReuseAddr(true);
    }

    @Override public void onOpen(WebSocket c, ClientHandshake h) { rooms.put(c, ConcurrentHashMap.newKeySet()); }

    @Override public void onClose(WebSocket c, int code, String reason, boolean remote) { rooms.remove(c); }

    @Override public void onMessage(WebSocket c, String msg) {
        try {
            JSONObject o = new JSONObject(msg);
            if ("join".equals(o.optString("type"))) {
                Set<String> s = rooms.get(c);
                if (s != null) s.add(o.optString("room").toUpperCase());
                return;
            }
            String code = o.optString("code").toUpperCase();
            if (code.isEmpty()) return;
            for (Map.Entry<WebSocket, Set<String>> e : rooms.entrySet()) {
                if (e.getKey() != c && e.getKey().isOpen() && e.getValue().contains(code)) e.getKey().send(msg);
            }
        } catch (Exception ignored) {}
    }

    @Override public void onError(WebSocket c, Exception ex) {}

    @Override public void onStart() { setConnectionLostTimeout(30); }
}
