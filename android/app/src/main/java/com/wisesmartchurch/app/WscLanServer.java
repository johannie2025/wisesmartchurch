package com.wisesmartchurch.app;

import android.content.Context;
import android.net.wifi.WifiManager;
import android.util.Log;

import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.net.InetAddress;
import java.net.NetworkInterface;
import java.net.ServerSocket;
import java.net.Socket;
import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;
import java.util.*;
import java.util.concurrent.CopyOnWriteArrayList;

/**
 * WiseSmartChurch Android LAN WebSocket Server
 * Embedded in the APK — no internet required
 * Broadcasts projection messages to all connected screens on local WiFi
 *
 * Usage: new WscLanServer(context, 9000).start();
 */
public class WscLanServer extends Thread {

    private static final String TAG = "WscLanServer";
    private final int port;
    private final Context context;
    private ServerSocket serverSocket;
    private final List<WsClient> clients = new CopyOnWriteArrayList<>();
    private volatile boolean running = true;

    /* Listener for messages received from screens */
    public interface MessageListener {
        void onMessage(String clientId, String msg);
    }
    private MessageListener listener;

    public WscLanServer(Context context, int port) {
        this.context = context;
        this.port    = port;
        setDaemon(true);
        setName("WscLanServer");
    }

    public void setMessageListener(MessageListener l) { this.listener = l; }

    /* ── Get device IP ── */
    public static String getLocalIp(Context ctx) {
        try {
            WifiManager wm = (WifiManager) ctx.getApplicationContext().getSystemService(Context.WIFI_SERVICE);
            if (wm != null) {
                int ip = wm.getConnectionInfo().getIpAddress();
                if (ip != 0) {
                    return ((ip & 0xff) + "." + ((ip >> 8) & 0xff) + "."
                          + ((ip >> 16) & 0xff) + "." + ((ip >> 24) & 0xff));
                }
            }
            // Fallback: iterate network interfaces
            for (Enumeration<NetworkInterface> en = NetworkInterface.getNetworkInterfaces(); en.hasMoreElements(); ) {
                NetworkInterface intf = en.nextElement();
                for (Enumeration<InetAddress> enumIpAddr = intf.getInetAddresses(); enumIpAddr.hasMoreElements(); ) {
                    InetAddress addr = enumIpAddr.nextElement();
                    if (!addr.isLoopbackAddress() && addr instanceof java.net.Inet4Address) {
                        return addr.getHostAddress();
                    }
                }
            }
        } catch (Exception e) { Log.e(TAG, "getLocalIp", e); }
        return "127.0.0.1";
    }

    public int getPort()   { return port; }
    public int getClientCount() { return clients.size(); }

    @Override
    public void run() {
        try {
            serverSocket = new ServerSocket(port);
            Log.i(TAG, "WS Server démarré: ws://" + getLocalIp(context) + ":" + port);
            while (running) {
                Socket socket = serverSocket.accept();
                WsClient client = new WsClient(socket);
                clients.add(client);
                client.start();
            }
        } catch (IOException e) {
            if (running) Log.e(TAG, "Server error", e);
        }
    }

    public void stopServer() {
        running = false;
        try { if (serverSocket != null) serverSocket.close(); } catch (IOException e) { /* ignore */ }
        for (WsClient c : clients) c.close();
        clients.clear();
    }

    /** Broadcast a JSON string to all connected clients */
    public void broadcast(String json) {
        for (WsClient c : clients) {
            if (c.isOpen()) try { c.send(json); } catch (Exception e) { /* ignore */ }
        }
    }

    /* ═══════════════════════════════════════════
       INNER CLASS: WebSocket Client Handler
    ═══════════════════════════════════════════ */
    private class WsClient extends Thread {
        private final Socket socket;
        private InputStream  in;
        private OutputStream out;
        private boolean handshakeDone = false;
        private final String id = UUID.randomUUID().toString().substring(0, 8);

        WsClient(Socket socket) {
            this.socket = socket;
            setDaemon(true);
            setName("WsClient-" + id);
        }

        boolean isOpen() { return socket.isConnected() && !socket.isClosed(); }

        @Override
        public void run() {
            try {
                in  = socket.getInputStream();
                out = socket.getOutputStream();
                doHandshake();
                handshakeDone = true;
                // Send server info
                send("{\"type\":\"server_info\",\"ip\":\"" + getLocalIp(context) + "\",\"wsPort\":" + port + "}");
                // Read loop
                while (isOpen() && running) {
                    String msg = readFrame();
                    if (msg == null) break;
                    // Broadcast to others
                    for (WsClient other : clients) {
                        if (other != this && other.isOpen() && other.handshakeDone) {
                            try { other.send(msg); } catch (Exception e) { /* ignore */ }
                        }
                    }
                    // Notify listener
                    if (listener != null) listener.onMessage(id, msg);
                }
            } catch (Exception e) {
                Log.d(TAG, "Client " + id + " error: " + e.getMessage());
            } finally {
                clients.remove(this);
                close();
            }
        }

        private void doHandshake() throws Exception {
            byte[] buf = new byte[4096];
            int len = in.read(buf);
            String request = new String(buf, 0, len, StandardCharsets.UTF_8);
            String key = "";
            for (String line : request.split("\r\n")) {
                if (line.startsWith("Sec-WebSocket-Key:")) {
                    key = line.split(":")[1].trim();
                }
            }
            String acceptKey = Base64.getEncoder().encodeToString(
                MessageDigest.getInstance("SHA-1")
                    .digest((key + "258EAFA5-E914-47DA-95CA-C5AB0DC85B11").getBytes(StandardCharsets.UTF_8))
            );
            String response = "HTTP/1.1 101 Switching Protocols\r\n"
                + "Upgrade: websocket\r\n"
                + "Connection: Upgrade\r\n"
                + "Sec-WebSocket-Accept: " + acceptKey + "\r\n\r\n";
            out.write(response.getBytes(StandardCharsets.UTF_8));
            out.flush();
        }

        private String readFrame() throws IOException {
            int b0 = in.read(); if (b0 < 0) return null;
            int b1 = in.read(); if (b1 < 0) return null;
            int opcode  = b0 & 0x0f;
            boolean masked = (b1 & 0x80) != 0;
            long payloadLen = b1 & 0x7f;
            if (payloadLen == 126) {
                payloadLen = ((in.read() & 0xff) << 8) | (in.read() & 0xff);
            } else if (payloadLen == 127) {
                payloadLen = 0;
                for (int i = 0; i < 8; i++) payloadLen = (payloadLen << 8) | (in.read() & 0xff);
            }
            byte[] mask = new byte[4];
            if (masked) { for (int i = 0; i < 4; i++) mask[i] = (byte) in.read(); }
            byte[] data = new byte[(int) payloadLen];
            int read = 0;
            while (read < payloadLen) {
                int r = in.read(data, read, (int) payloadLen - read);
                if (r < 0) break;
                read += r;
            }
            if (masked) for (int i = 0; i < read; i++) data[i] ^= mask[i % 4];
            if (opcode == 8) return null; // close
            return new String(data, 0, read, StandardCharsets.UTF_8);
        }

        void send(String msg) throws IOException {
            byte[] payload = msg.getBytes(StandardCharsets.UTF_8);
            int len = payload.length;
            if (len <= 125) {
                out.write(new byte[]{ (byte)0x81, (byte) len });
            } else if (len <= 65535) {
                out.write(new byte[]{ (byte)0x81, (byte)126, (byte)(len >> 8), (byte)(len & 0xff) });
            } else {
                out.write(new byte[]{ (byte)0x81, (byte)127, 0, 0, 0, 0,
                    (byte)(len >> 24), (byte)(len >> 16), (byte)(len >> 8), (byte)(len & 0xff) });
            }
            out.write(payload);
            out.flush();
        }

        void close() {
            try { socket.close(); } catch (IOException e) { /* ignore */ }
        }
    }
}
