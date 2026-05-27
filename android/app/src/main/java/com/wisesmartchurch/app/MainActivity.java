package com.wisesmartchurch.app;

import android.app.admin.DevicePolicyManager;
import android.content.Context;
import android.app.UiModeManager;
import android.content.pm.PackageManager;
import android.content.res.Configuration;
import android.os.Build;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.view.WindowInsetsController;
import android.view.WindowManager;
import android.webkit.PermissionRequest;
import android.webkit.WebChromeClient;
import android.webkit.WebSettings;
import android.webkit.WebView;
import com.getcapacitor.BridgeActivity;

/**
 * Wise Smart Church — MainActivity
 * ✅ WebChromeClient : accorde automatiquement caméra + micro au WebView
 * ✅ WscLanServer embarqué : fonctionne en WiFi / hotspot / sans internet
 */
public class MainActivity extends BridgeActivity {
    private static final String TAG = "WscMain";
    private WscLanServer wsServer;
    private boolean      isTvMode = false;

    /* ── Détection TV Box (fausses boxes chinoises incluses) ── */
    private boolean detectTV() {
        UiModeManager uim = (UiModeManager) getSystemService(UI_MODE_SERVICE);
        if (uim != null && uim.getCurrentModeType() == Configuration.UI_MODE_TYPE_TELEVISION) return true;
        if (getPackageManager().hasSystemFeature(PackageManager.FEATURE_LEANBACK)) return true;
        if (!getPackageManager().hasSystemFeature(PackageManager.FEATURE_TOUCHSCREEN)) return true;
        android.util.DisplayMetrics dm = new android.util.DisplayMetrics();
        getWindowManager().getDefaultDisplay().getMetrics(dm);
        if (dm.widthPixels >= 1920 && !getPackageManager().hasSystemFeature(PackageManager.FEATURE_TOUCHSCREEN)) return true;
        return false;
    }

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        isTvMode = detectTV();
        Log.i(TAG, isTvMode ? "📺 TV Box détectée" : "📱 Phone/Tablet");

        if (isTvMode) {
            getWindow().addFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON);
            enableImmersiveMode();
            startKioskMode();
        }

        // ── Serveur WebSocket LAN embarqué (WiFi / hotspot / sans internet) ──
        final int wsPort = 9000;
        wsServer = new WscLanServer(this, wsPort);
        wsServer.setMessageListener((clientId, msg) -> runOnUiThread(() -> {
            String safe = msg.replace("\\", "\\\\").replace("'", "\\'");
            String js = "try{handleMqttMsg(JSON.parse('" + safe + "'));}catch(e){console.error('WS msg err',e);}";
            bridge.getWebView().evaluateJavascript(js, null);
        }));
        wsServer.start();
        Log.i(TAG, "🌐 WS Server démarré port " + wsPort);

        // ── WebView config ──
        WebView wv = bridge.getWebView();
        WebSettings ws = wv.getSettings();
        ws.setMediaPlaybackRequiresUserGesture(false);
        ws.setAllowFileAccess(true);
        ws.setAllowContentAccess(true);
        ws.setDomStorageEnabled(true);
        ws.setDatabaseEnabled(true);
        ws.setJavaScriptEnabled(true);
        ws.setMixedContentMode(WebSettings.MIXED_CONTENT_ALWAYS_ALLOW);
        ws.setLoadWithOverviewMode(true);
        ws.setUseWideViewPort(true);

        // ── ✅ WebChromeClient : accorder caméra + micro automatiquement ──
        wv.setWebChromeClient(new com.getcapacitor.android.WebChromeClient(this.bridge) {
            @Override
            public void onPermissionRequest(final PermissionRequest request) {
                runOnUiThread(() -> {
                    Log.i(TAG, "📷 Permission WebView accordée: " + String.join(", ", request.getResources()));
                    request.grant(request.getResources());
                });
            }
        });

        // ── Injection infos réseau dans l'HTML ──
        String localIp = WscLanServer.getLocalIp(this);
        wv.post(() -> {
            String js = String.format(
                "window._wscServerIp='%s';" +
                "window._wscServerPort=%d;" +
                "window._wscIsTvMode=%b;" +
                "window._wscIsAndroid=true;" +
                "window.electronBridge={" +
                "  isTvMode:%b,isAndroid:true," +
                "  broadcast:function(m){try{Android.broadcast(m);}catch(e){}}," +
                "  getNetworkInfo:function(){return{ip:'%s',wsPort:%d,isTvMode:%b,isAndroid:true};}" +
                "};",
                localIp, wsPort, isTvMode,
                isTvMode, localIp, wsPort, isTvMode
            );
            wv.evaluateJavascript(js, null);
        });

        // ── Bridge JS → Java ──
        wv.addJavascriptInterface(new Object() {
            @android.webkit.JavascriptInterface
            public void broadcast(String msg) {
                if (wsServer != null) wsServer.broadcast(msg);
            }
            @android.webkit.JavascriptInterface
            public String getServerIp() {
                return WscLanServer.getLocalIp(MainActivity.this);
            }
            @android.webkit.JavascriptInterface
            public boolean isTvMode() { return isTvMode; }
        }, "Android");
    }

    /* ── Plein écran immersif ── */
    private void enableImmersiveMode() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R) {
            getWindow().setDecorFitsSystemWindows(false);
            WindowInsetsController c = getWindow().getInsetsController();
            if (c != null) {
                c.hide(android.view.WindowInsets.Type.statusBars() |
                       android.view.WindowInsets.Type.navigationBars());
                c.setSystemBarsBehavior(
                    WindowInsetsController.BEHAVIOR_SHOW_TRANSIENT_BARS_BY_SWIPE);
            }
        } else {
            //noinspection deprecation
            getWindow().getDecorView().setSystemUiVisibility(
                View.SYSTEM_UI_FLAG_IMMERSIVE_STICKY |
                View.SYSTEM_UI_FLAG_FULLSCREEN |
                View.SYSTEM_UI_FLAG_HIDE_NAVIGATION |
                View.SYSTEM_UI_FLAG_LAYOUT_STABLE);
        }
    }

    /* ── Kiosk Lock Task ── */
    private void startKioskMode() {
        DevicePolicyManager dpm =
            (DevicePolicyManager) getSystemService(Context.DEVICE_POLICY_SERVICE);
        if (dpm != null && dpm.isLockTaskPermitted(getPackageName())) {
            startLockTask();
            Log.i(TAG, "🔒 Kiosk Lock Task activé");
        }
    }

    @Override
    public void onWindowFocusChanged(boolean hasFocus) {
        super.onWindowFocusChanged(hasFocus);
        if (hasFocus && isTvMode) enableImmersiveMode();
    }

    @Override
    public void onDestroy() {
        if (wsServer != null) wsServer.stopServer();
        super.onDestroy();
    }
}