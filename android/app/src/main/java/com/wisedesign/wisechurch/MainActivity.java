package com.wisedesign.wisechurch;

import android.app.Activity;
import android.content.Context;
import android.net.nsd.NsdManager;
import android.net.nsd.NsdServiceInfo;
import android.Manifest;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.os.Bundle;
import android.view.View;
import android.webkit.JavascriptInterface;
import android.webkit.PermissionRequest;
import android.webkit.WebChromeClient;
import android.webkit.ValueCallback;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.content.ContentValues;
import android.net.Uri;
import android.os.Build;
import android.os.Environment;
import android.provider.MediaStore;
import android.util.Base64;
import java.io.File;
import java.io.FileOutputStream;
import java.io.OutputStream;
import java.net.InetSocketAddress;
import java.net.NetworkInterface;
import java.net.Socket;
import java.util.ArrayList;
import java.util.Collections;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.concurrent.TimeUnit;

/** Coque native : WebView plein écran (mobile + Android TV), relais LAN et mDNS _wisechurch._tcp. */
public class MainActivity extends Activity {
    private static final String SERVICE_TYPE = "_wisechurch._tcp.";
    private WebView web;
    private NsdManager nsd;
    private RelayServer relay;
    private int relayPort = RelayServer.PORTS[0];
    private NsdManager.RegistrationListener regListener;
    private NsdManager.DiscoveryListener discListener;
    private PermissionRequest pendingPerm;
    private ValueCallback<Uri[]> fileCb;
    private String lastRelay = "";
    private android.net.wifi.WifiManager.MulticastLock mlock;
    private volatile boolean scanning = false;
    private OutputStream recOut;
    private Uri recUri;
    private String recPath = "";
    private ArrayList<String> pendingRes = new ArrayList<>();

    @Override
    protected void onCreate(Bundle b) {
        super.onCreate(b);
        web = new WebView(this);
        setContentView(web);
        WebSettings s = web.getSettings();
        s.setJavaScriptEnabled(true);
        s.setDomStorageEnabled(true);
        s.setMediaPlaybackRequiresUserGesture(false);
        s.setAllowFileAccess(true);
        web.setWebViewClient(new WebViewClient() {
            @Override public void onPageFinished(WebView v, String url) {
                if (!lastRelay.isEmpty()) v.evaluateJavascript("if(window.useRelay)useRelay('" + lastRelay + "')", null);
                handleIntent(getIntent());
            }
        });
        web.setWebChromeClient(new WebChromeClient() {
            /* Sélecteur de fichiers (<input type=file>) : indispensable pour importer photos/vidéos/audio/chants du téléphone */
            @Override public boolean onShowFileChooser(WebView w, ValueCallback<Uri[]> cb, FileChooserParams p) {
                if (fileCb != null) fileCb.onReceiveValue(null);
                fileCb = cb;
                try {
                    Intent it = p.createIntent();
                    it.addCategory(Intent.CATEGORY_OPENABLE);
                    startActivityForResult(it, 55);
                } catch (Exception e) {
                    fileCb = null;
                    return false;
                }
                return true;
            }
            @Override public void onPermissionRequest(final PermissionRequest r) {
                runOnUiThread(() -> {
                    ArrayList<String> need = new ArrayList<>();
                    for (String res : r.getResources()) {
                        if (PermissionRequest.RESOURCE_VIDEO_CAPTURE.equals(res) && checkSelfPermission(Manifest.permission.CAMERA) != PackageManager.PERMISSION_GRANTED) need.add(Manifest.permission.CAMERA);
                        if (PermissionRequest.RESOURCE_AUDIO_CAPTURE.equals(res) && checkSelfPermission(Manifest.permission.RECORD_AUDIO) != PackageManager.PERMISSION_GRANTED) need.add(Manifest.permission.RECORD_AUDIO);
                    }
                    if (!need.isEmpty()) {
                        pendingPerm = r;
                        requestPermissions(need.toArray(new String[0]), 7);
                    } else r.grant(r.getResources());
                });
            }
        });
        web.addJavascriptInterface(new Bridge(), "WiseNative");
        web.loadUrl("file:///android_asset/index.html");
        nsd = (NsdManager) getSystemService(Context.NSD_SERVICE);
        try {
            android.net.wifi.WifiManager wm = (android.net.wifi.WifiManager) getApplicationContext().getSystemService(Context.WIFI_SERVICE);
            mlock = wm.createMulticastLock("wisechurch");
            mlock.setReferenceCounted(false);
            mlock.acquire();
        } catch (Exception ignored) {}
        goImmersive();
        discover();
    }

    @Override protected void onActivityResult(int req, int res, Intent data) {
        if (req == 55) {
            if (fileCb != null) {
                Uri[] r = null;
                try { r = WebChromeClient.FileChooserParams.parseResult(res, data); } catch (Exception ignored) {}
                fileCb.onReceiveValue(r);
                fileCb = null;
            }
            return;
        }
        super.onActivityResult(req, res, data);
    }

    private void goImmersive() {
        getWindow().getDecorView().setSystemUiVisibility(
                View.SYSTEM_UI_FLAG_IMMERSIVE_STICKY | View.SYSTEM_UI_FLAG_FULLSCREEN
                | View.SYSTEM_UI_FLAG_HIDE_NAVIGATION | View.SYSTEM_UI_FLAG_LAYOUT_STABLE);
    }

    /** Appelé depuis la page (mode Régie) pour devenir hôte : relais + annonce mDNS. */
    private void startHost() {
        if (relay != null) return;
        try {
            RelayServer rs = null;
            for (int p : RelayServer.PORTS) {
                try (java.net.ServerSocket probe = new java.net.ServerSocket()) {
                    probe.setReuseAddress(true);
                    probe.bind(new InetSocketAddress(p));
                } catch (Exception busy) { continue; }
                rs = new RelayServer(p);
                relayPort = p;
                break;
            }
            if (rs == null) return;
            relay = rs;
            relay.start();
            NsdServiceInfo info = new NsdServiceInfo();
            info.setServiceName("WiseChurch");
            info.setServiceType(SERVICE_TYPE);
            info.setPort(relayPort);
            regListener = new NsdManager.RegistrationListener() {
                public void onRegistrationFailed(NsdServiceInfo i, int e) {}
                public void onUnregistrationFailed(NsdServiceInfo i, int e) {}
                public void onServiceRegistered(NsdServiceInfo i) {}
                public void onServiceUnregistered(NsdServiceInfo i) {}
            };
            nsd.registerService(info, NsdManager.PROTOCOL_DNS_SD, regListener);
        } catch (Exception ignored) {}
    }

    /** Détection automatique des hôtes sur le LAN, puis la page se branche sur le relais trouvé. */
    private void discover() {
        discListener = new NsdManager.DiscoveryListener() {
            public void onStartDiscoveryFailed(String t, int e) {}
            public void onStopDiscoveryFailed(String t, int e) {}
            public void onDiscoveryStarted(String t) {}
            public void onDiscoveryStopped(String t) {}
            public void onServiceLost(NsdServiceInfo i) {}
            public void onServiceFound(NsdServiceInfo found) {
                nsd.resolveService(found, new NsdManager.ResolveListener() {
                    public void onResolveFailed(NsdServiceInfo i, int e) {}
                    public void onServiceResolved(NsdServiceInfo i) {
                        final String url = "ws://" + i.getHost().getHostAddress() + ":" + i.getPort();
                        lastRelay = url;
                        runOnUiThread(() -> web.evaluateJavascript("if(window.useRelay)useRelay('" + url + "')", null));
                    }
                });
            }
        };
        try { nsd.discoverServices(SERVICE_TYPE, NsdManager.PROTOCOL_DNS_SD, discListener); } catch (Exception ignored) {}
    }

    /** IPv4 locale (Wi-Fi ou Ethernet — les box TV sont souvent en câble). */
    /** Toutes les adresses IPv4 privées (Wi‑Fi + Ethernet : les box TV en ont souvent plusieurs). */
    private ArrayList<String> localIps() {
        ArrayList<String> l = new ArrayList<>();
        try {
            for (NetworkInterface ni : Collections.list(NetworkInterface.getNetworkInterfaces())) {
                if (!ni.isUp() || ni.isLoopback()) continue;
                for (java.net.InetAddress a : Collections.list(ni.getInetAddresses()))
                    if (a instanceof java.net.Inet4Address && a.isSiteLocalAddress()) l.add(a.getHostAddress());
            }
        } catch (Exception ignored) {}
        return l;
    }

    private String localIp() {
        try {
            for (NetworkInterface ni : Collections.list(NetworkInterface.getNetworkInterfaces())) {
                if (!ni.isUp() || ni.isLoopback()) continue;
                for (java.net.InetAddress a : Collections.list(ni.getInetAddresses())) {
                    if (a instanceof java.net.Inet4Address && a.isSiteLocalAddress()) return a.getHostAddress();
                }
            }
        } catch (Exception ignored) {}
        return "";
    }

    /** Repli quand mDNS ne passe pas : sonde les 254 adresses du sous-réseau sur le port du relais. */
    private void scanLan() {
        if (scanning) return;
        scanning = true;
        new Thread(() -> {
            try {
                ArrayList<String> ips = localIps();
                if (ips.isEmpty()) return;
                final String[] found = {null};
                ExecutorService ex = Executors.newFixedThreadPool(64);
                for (int port : RelayServer.PORTS) {
                    if (found[0] != null) break;
                    final int fp = port;
                    ArrayList<java.util.concurrent.Future<?>> fs = new ArrayList<>();
                    for (String ip : ips) {
                        final String base = ip.substring(0, ip.lastIndexOf('.') + 1);
                        for (int i = 1; i < 255; i++) {
                            final String h = base + i;
                            if (h.equals(ip)) continue;
                            fs.add(ex.submit(() -> {
                                if (found[0] != null) return;
                                try (Socket s = new Socket()) {
                                    s.connect(new InetSocketAddress(h, fp), 400);
                                    found[0] = h + ":" + fp;
                                } catch (Exception ignored) {}
                            }));
                        }
                    }
                    for (java.util.concurrent.Future<?> f : fs) { try { f.get(); } catch (Exception ignored) {} }
                }
                ex.shutdown();
                ex.awaitTermination(20, TimeUnit.SECONDS);
                if (found[0] != null) {
                    final String url = "ws://" + found[0];
                    lastRelay = url;
                    runOnUiThread(() -> web.evaluateJavascript("if(window.useRelay)useRelay('" + url + "')", null));
                }
            } catch (Exception ignored) {
            } finally { scanning = false; }
        }).start();
    }

    public class Bridge {
        @JavascriptInterface public String getRelay() { return lastRelay; }
        @JavascriptInterface public int getRelayPort() { return relayPort; }
        @JavascriptInterface public void scanLan() { MainActivity.this.scanLan(); }

        /** Enregistrement caméra → mémoire du téléphone (Films/WiseDesign). */
        @JavascriptInterface public boolean recOpen(String name, String mime) {
            try {
                recClose();
                if (Build.VERSION.SDK_INT >= 29) {
                    ContentValues cv = new ContentValues();
                    cv.put(MediaStore.Video.Media.DISPLAY_NAME, name);
                    cv.put(MediaStore.Video.Media.MIME_TYPE, mime);
                    cv.put(MediaStore.Video.Media.RELATIVE_PATH, "Movies/WiseDesign");
                    cv.put(MediaStore.Video.Media.IS_PENDING, 1);
                    recUri = getContentResolver().insert(MediaStore.Video.Media.EXTERNAL_CONTENT_URI, cv);
                    if (recUri == null) return false;
                    recOut = getContentResolver().openOutputStream(recUri);
                    recPath = "Movies/WiseDesign/" + name;
                } else {
                    File dir = getExternalFilesDir(Environment.DIRECTORY_MOVIES);
                    if (dir == null) return false;
                    File f = new File(dir, name);
                    recOut = new FileOutputStream(f);
                    recUri = null;
                    recPath = f.getAbsolutePath();
                }
                return recOut != null;
            } catch (Exception e) { return false; }
        }
        @JavascriptInterface public void recWrite(String b64) {
            try { if (recOut != null) recOut.write(Base64.decode(b64, Base64.DEFAULT)); } catch (Exception ignored) {}
        }
        @JavascriptInterface public String recClose() {
            try { if (recOut != null) { recOut.flush(); recOut.close(); } } catch (Exception ignored) {}
            recOut = null;
            try {
                if (recUri != null) {
                    ContentValues cv = new ContentValues();
                    cv.put(MediaStore.Video.Media.IS_PENDING, 0);
                    getContentResolver().update(recUri, cv, null, null);
                }
            } catch (Exception ignored) {}
            recUri = null;
            return recPath;
        }

        @JavascriptInterface public void startHost() { runOnUiThread(MainActivity.this::startHost); }
        @JavascriptInterface public String getIp() {
            try {
                android.net.wifi.WifiManager w = (android.net.wifi.WifiManager) getApplicationContext().getSystemService(Context.WIFI_SERVICE);
                int ip = w.getConnectionInfo().getIpAddress();
                if (ip == 0) return "";
                return (ip & 0xff) + "." + ((ip >> 8) & 0xff) + "." + ((ip >> 16) & 0xff) + "." + ((ip >> 24) & 0xff);
            } catch (Exception e) { return ""; }
        }
        @JavascriptInterface public boolean isTv() {
            return getPackageManager().hasSystemFeature("android.software.leanback");
        }
    }

    /** Lien wisechurch://join?code=…&relay=… (QR scanné avec l'appareil photo du téléphone). */
    private void handleIntent(Intent i) {
        if (i == null || i.getData() == null || web == null) return;
        String d = i.getDataString().replace("\\", "\\\\").replace("'", "%27");
        web.evaluateJavascript("if(window.handleDeepLink)handleDeepLink('" + d + "')", null);
        i.setData(null);
    }

    @Override protected void onNewIntent(Intent i) {
        super.onNewIntent(i);
        setIntent(i);
        handleIntent(i);
    }

    @Override public void onRequestPermissionsResult(int code, String[] perms, int[] res) {
        super.onRequestPermissionsResult(code, perms, res);
        if (pendingPerm != null) {
            boolean ok = res.length > 0; for (int g : res) if (g != PackageManager.PERMISSION_GRANTED) ok = false;
            if (ok) pendingPerm.grant(pendingPerm.getResources());
            else pendingPerm.deny();
            pendingPerm = null;
        }
    }

    @Override public void onBackPressed() {
        if (web.canGoBack()) web.goBack(); else super.onBackPressed();
    }

    @Override protected void onDestroy() {
        super.onDestroy();
        try { if (relay != null) relay.stop(); } catch (Exception ignored) {}
        try { if (regListener != null) nsd.unregisterService(regListener); } catch (Exception ignored) {}
        try { if (discListener != null) nsd.stopServiceDiscovery(discListener); } catch (Exception ignored) {}
        try { if (mlock != null && mlock.isHeld()) mlock.release(); } catch (Exception ignored) {}
    }
}
