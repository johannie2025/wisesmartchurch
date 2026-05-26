package com.wisesmartchurch.app;

import android.app.ActivityManager;
import android.app.UiModeManager;
import android.content.pm.PackageManager;
import android.content.res.Configuration;
import android.os.Build;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.view.WindowInsetsController;
import android.view.WindowManager;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.app.admin.DevicePolicyManager;

import com.getcapacitor.BridgeActivity;

import org.json.JSONArray;
import org.json.JSONObject;

/**
 * Wise Smart Church — MainActivity
 *
 * Comportement adaptatif :
 *   📺 Android TV Box / Smart TV  → Kiosk plein écran + boot auto + NSD publication
 *   📱 Phone / Tablet             → Mode normal + NSD découverte des écrans
 */
public class MainActivity extends BridgeActivity {

    private static final String TAG = "WscMainActivity";

    private WscLanServer  wsServer;
    private WscNsdManager nsdManager;
    private boolean       isTvMode = false;

    /* ─────────────────────────────────────────── */
    /*  DÉTECTION TV vs PHONE                      */
    /* ─────────────────────────────────────────── */
    private boolean isAndroidTV() {
        UiModeManager uiModeManager = (UiModeManager) getSystemService(UI_MODE_SERVICE);
        if (uiModeManager != null &&
            uiModeManager.getCurrentModeType() == Configuration.UI_MODE_TYPE_TELEVISION) {
            return true;
        }
        return getPackageManager().hasSystemFeature(PackageManager.FEATURE_LEANBACK);
    }

    /* ─────────────────────────────────────────── */
    /*  LIFECYCLE                                   */
    /* ─────────────────────────────────────────── */
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        isTvMode = isAndroidTV();
        Log.i(TAG, isTvMode ? "📺 Mode TV Kiosk" : "📱 Mode Phone/Tablet");

        // ── Mode TV : plein écran + kiosk + écran toujours allumé ──
        if (isTvMode) {
            getWindow().addFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON);
            enableImmersiveMode();
            startKioskMode();
        }

        // ── Démarrer le serveur WebSocket LAN ──
        int wsPort   = 9000;
        int httpPort = 9001;
        wsServer = new WscLanServer(this, wsPort);
        wsServer.setMessageListener((clientId, msg) -> {
            runOnUiThread(() -> {
                String js = "if(window.handleMqttMsg)try{window.handleMqttMsg(JSON.parse('"
                    + msg.replace("'", "\\'").replace("\\", "\\\\") + "'));}catch(e){}";
                bridge.getWebView().evaluateJavascript(js, null);
            });
        });
        wsServer.start();

        // ── NSD : TV publie sa présence, Phone découvre les écrans ──
        String localIp = WscLanServer.getLocalIp(this);
        nsdManager = new WscNsdManager(this);

        if (isTvMode) {
            // TV Box : se rendre visible sur le réseau
            String deviceLabel = android.os.Build.MODEL;
            nsdManager.registerAsScreen(deviceLabel, wsPort, httpPort, localIp);
        } else {
            // Phone/Tablet (régie) : découvrir les écrans
            nsdManager.setDiscoveryListener(new WscNsdManager.ScreenDiscoveryListener() {
                @Override
                public void onScreenFound(WscNsdManager.ScreenInfo screen) {
                    Log.i(TAG, "📺 Écran trouvé: " + screen);
                    notifyWebViewScreenFound(screen);
                }
                @Override
                public void onScreenLost(String serviceName) {
                    Log.i(TAG, "📴 Écran perdu: " + serviceName);
                    notifyWebViewScreenLost(serviceName);
                }
            });
            nsdManager.startDiscovery();
        }

        // ── Configurer la WebView ──
        WebView webView = bridge.getWebView();
        WebSettings settings = webView.getSettings();
        settings.setMediaPlaybackRequiresUserGesture(false);
        settings.setAllowFileAccess(true);
        settings.setAllowContentAccess(true);
        settings.setDomStorageEnabled(true);
        settings.setDatabaseEnabled(true);
        settings.setJavaScriptEnabled(true);
        settings.setJavaScriptCanOpenWindowsAutomatically(true);
        settings.setLoadWithOverviewMode(true);
        settings.setUseWideViewPort(true);
        settings.setMixedContentMode(WebSettings.MIXED_CONTENT_ALWAYS_ALLOW);

        // ── Injecter les infos dans l'app HTML ──
        webView.post(() -> {
            String injectJs = String.format(
                "window._wscServerIp='%s';" +
                "window._wscServerPort=%d;" +
                "window._wscHttpPort=%d;" +
                "window._wscIsAndroid=true;" +
                "window._wscIsTvMode=%b;" +
                "window.electronBridge={" +
                "  broadcast:function(msg){try{Android.broadcast(msg);}catch(e){}}," +
                "  isAndroid:true," +
                "  isTvMode:%b," +
                "  getNetworkInfo:function(){" +
                "    return {ip:'%s',wsPort:%d,httpPort:%d,isTvMode:%b,isAndroid:true};" +
                "  }," +
                "  startScreenDiscovery:function(){try{Android.startScreenDiscovery();}catch(e){}}," +
                "  getDiscoveredScreens:function(){try{return JSON.parse(Android.getDiscoveredScreens());}catch(e){return[];}}" +
                "};",
                localIp, wsPort, httpPort, isTvMode,
                isTvMode, localIp, wsPort, httpPort, isTvMode
            );
            webView.evaluateJavascript(injectJs, null);
        });

        // ── Interface JavaScript → Java ──
        webView.addJavascriptInterface(new Object() {

            @android.webkit.JavascriptInterface
            public void broadcast(String jsonMsg) {
                if (wsServer != null) wsServer.broadcast(jsonMsg);
            }

            @android.webkit.JavascriptInterface
            public String getServerIp() { return WscLanServer.getLocalIp(MainActivity.this); }

            @android.webkit.JavascriptInterface
            public int getWsPort()   { return wsPort; }

            @android.webkit.JavascriptInterface
            public boolean isTvMode() { return isTvMode; }

            // Régie : forcer un refresh de découverte
            @android.webkit.JavascriptInterface
            public void startScreenDiscovery() {
                if (!isTvMode && nsdManager != null) {
                    nsdManager.stopDiscovery();
                    nsdManager.startDiscovery();
                }
            }

            // Régie : obtenir la liste des écrans découverts (JSON)
            @android.webkit.JavascriptInterface
            public String getDiscoveredScreens() {
                if (nsdManager == null) return "[]";
                try {
                    JSONArray arr = new JSONArray();
                    for (WscNsdManager.ScreenInfo s : nsdManager.getDiscoveredScreens()) {
                        JSONObject obj = new JSONObject();
                        obj.put("serviceName", s.serviceName);
                        obj.put("host",        s.host);
                        obj.put("wsPort",      s.wsPort);
                        obj.put("httpPort",    s.httpPort);
                        obj.put("role",        s.role);
                        obj.put("label",       s.label);
                        arr.put(obj);
                    }
                    return arr.toString();
                } catch (Exception e) { return "[]"; }
            }

        }, "Android");
    }

    /* ─────────────────────────────────────────── */
    /*  NOTIFIER LA WEBVIEW des écrans             */
    /* ─────────────────────────────────────────── */
    private void notifyWebViewScreenFound(WscNsdManager.ScreenInfo screen) {
        try {
            JSONObject obj = new JSONObject();
            obj.put("type",        "screen_found");
            obj.put("serviceName", screen.serviceName);
            obj.put("host",        screen.host);
            obj.put("wsPort",      screen.wsPort);
            obj.put("httpPort",    screen.httpPort);
            obj.put("label",       screen.label);
            String js = "if(window.onWscScreenFound)window.onWscScreenFound(" + obj.toString() + ");";
            runOnUiThread(() -> bridge.getWebView().evaluateJavascript(js, null));
        } catch (Exception e) { Log.e(TAG, "notifyScreenFound", e); }
    }

    private void notifyWebViewScreenLost(String serviceName) {
        String js = "if(window.onWscScreenLost)window.onWscScreenLost('" + serviceName + "');";
        runOnUiThread(() -> bridge.getWebView().evaluateJavascript(js, null));
    }

    /* ─────────────────────────────────────────── */
    /*  KIOSK / IMMERSIVE (TV uniquement)           */
    /* ─────────────────────────────────────────── */
    private void enableImmersiveMode() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R) {
            getWindow().setDecorFitsSystemWindows(false);
            WindowInsetsController ctrl = getWindow().getInsetsController();
            if (ctrl != null) {
                ctrl.hide(
                    android.view.WindowInsets.Type.statusBars() |
                    android.view.WindowInsets.Type.navigationBars()
                );
                ctrl.setSystemBarsBehavior(
                    WindowInsetsController.BEHAVIOR_SHOW_TRANSIENT_BARS_BY_SWIPE
                );
            }
        } else {
            //noinspection deprecation
            getWindow().getDecorView().setSystemUiVisibility(
                View.SYSTEM_UI_FLAG_IMMERSIVE_STICKY
                | View.SYSTEM_UI_FLAG_FULLSCREEN
                | View.SYSTEM_UI_FLAG_HIDE_NAVIGATION
                | View.SYSTEM_UI_FLAG_LAYOUT_FULLSCREEN
                | View.SYSTEM_UI_FLAG_LAYOUT_HIDE_NAVIGATION
                | View.SYSTEM_UI_FLAG_LAYOUT_STABLE
            );
        }
    }

    private void startKioskMode() {
	   DevicePolicyManager dpm = (DevicePolicyManager) getSystemService(Context.DEVICE_POLICY_SERVICE);
	if (dpm != null && dpm.isLockTaskPermitted(getPackageName())) {
            startLockTask();
            Log.i(TAG, "🔒 Lock Task activé");
        } else {
            Log.w(TAG, "⚠️ Lock Task non autorisé (besoin Device Owner via ADB)");
        }
    }

    @Override
    public void onWindowFocusChanged(boolean hasFocus) {
        super.onWindowFocusChanged(hasFocus);
        if (hasFocus && isTvMode) enableImmersiveMode();
    }

    /* ─────────────────────────────────────────── */
    /*  DESTRUCTION                                 */
    /* ─────────────────────────────────────────── */
    @Override
    public void onDestroy() {
        if (wsServer  != null) wsServer.stopServer();
        if (nsdManager != null) {
            nsdManager.stopDiscovery();
            nsdManager.unregister();
        }
        super.onDestroy();
    }
}
