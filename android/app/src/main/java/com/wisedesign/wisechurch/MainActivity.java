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
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;

/** Coque native : WebView plein écran (mobile + Android TV), relais LAN et mDNS _wisechurch._tcp. */
public class MainActivity extends Activity {
    private static final String SERVICE_TYPE = "_wisechurch._tcp.";
    private WebView web;
    private NsdManager nsd;
    private RelayServer relay;
    private NsdManager.RegistrationListener regListener;
    private NsdManager.DiscoveryListener discListener;
    private PermissionRequest pendingPerm;
    private String lastRelay = "";

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
            @Override public void onPermissionRequest(final PermissionRequest r) {
                runOnUiThread(() -> {
                    boolean cam = false;
                    for (String res : r.getResources()) if (PermissionRequest.RESOURCE_VIDEO_CAPTURE.equals(res)) cam = true;
                    if (cam && checkSelfPermission(Manifest.permission.CAMERA) != PackageManager.PERMISSION_GRANTED) {
                        pendingPerm = r;
                        requestPermissions(new String[]{Manifest.permission.CAMERA}, 7);
                    } else r.grant(r.getResources());
                });
            }
        });
        web.addJavascriptInterface(new Bridge(), "WiseNative");
        web.loadUrl("file:///android_asset/index.html");
        nsd = (NsdManager) getSystemService(Context.NSD_SERVICE);
        goImmersive();
        discover();
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
            relay = new RelayServer();
            relay.start();
            NsdServiceInfo info = new NsdServiceInfo();
            info.setServiceName("WiseChurch");
            info.setServiceType(SERVICE_TYPE);
            info.setPort(RelayServer.PORT);
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

    public class Bridge {
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
            if (res.length > 0 && res[0] == PackageManager.PERMISSION_GRANTED) pendingPerm.grant(pendingPerm.getResources());
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
    }
}
