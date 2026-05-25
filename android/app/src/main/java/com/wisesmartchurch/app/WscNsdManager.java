package com.wisesmartchurch.app;

import android.content.Context;
import android.net.nsd.NsdManager;
import android.net.nsd.NsdServiceInfo;
import android.util.Log;

import java.util.ArrayList;
import java.util.List;
import java.util.concurrent.CopyOnWriteArrayList;

/**
 * WscNsdManager — mDNS / Network Service Discovery
 *
 * TV Box (écran) : publie "_wsc._tcp" sur le réseau local
 * Régie (phone/PC) : découvre tous les écrans publiés
 *
 * Protocole de découverte :
 *   Service type : _wsc._tcp
 *   Service name : WiseSmartChurch-<deviceId>
 *   Port         : 9000 (WS) + attribut txtRecord "httpPort=9001"
 *   TxtRecord    : role=screen|regie, name=<label>, ip=<ip>
 */
public class WscNsdManager {

    private static final String TAG         = "WscNsdManager";
    public  static final String SERVICE_TYPE = "_wsc._tcp.";

    private final Context    context;
    private final NsdManager nsdManager;
    private NsdManager.RegistrationListener  registrationListener;
    private NsdManager.DiscoveryListener     discoveryListener;
    private NsdManager.ResolveListener       resolveListener;

    /* Callback : écran découvert */
    public interface ScreenDiscoveryListener {
        void onScreenFound(ScreenInfo screen);
        void onScreenLost(String serviceName);
    }

    /* Info d'un écran découvert */
    public static class ScreenInfo {
        public String serviceName;
        public String host;
        public int    wsPort;
        public int    httpPort;
        public String role;    // "screen" ou "regie"
        public String label;   // nom affiché

        @Override
        public String toString() {
            return label + " @ " + host + ":" + wsPort + " (" + role + ")";
        }
    }

    private ScreenDiscoveryListener listener;
    private final List<ScreenInfo> discoveredScreens = new CopyOnWriteArrayList<>();

    public WscNsdManager(Context context) {
        this.context    = context;
        this.nsdManager = (NsdManager) context.getSystemService(Context.NSD_SERVICE);
    }

    public void setDiscoveryListener(ScreenDiscoveryListener l) {
        this.listener = l;
    }

    public List<ScreenInfo> getDiscoveredScreens() {
        return new ArrayList<>(discoveredScreens);
    }

    /* ── PUBLICATION (TV Box / écran) ── */
    public void registerAsScreen(String deviceLabel, int wsPort, int httpPort, String localIp) {
        if (nsdManager == null) return;

        NsdServiceInfo serviceInfo = new NsdServiceInfo();
        serviceInfo.setServiceName("WiseSmartChurch-" + android.os.Build.SERIAL);
        serviceInfo.setServiceType(SERVICE_TYPE);
        serviceInfo.setPort(wsPort);

        // Attributs additionnels (TXT record)
        serviceInfo.setAttribute("role",     "screen");
        serviceInfo.setAttribute("name",     deviceLabel);
        serviceInfo.setAttribute("httpPort", String.valueOf(httpPort));
        serviceInfo.setAttribute("ip",       localIp);
        serviceInfo.setAttribute("v",        "5");

        registrationListener = new NsdManager.RegistrationListener() {
            @Override
            public void onServiceRegistered(NsdServiceInfo info) {
                Log.i(TAG, "✅ Écran publié: " + info.getServiceName());
            }
            @Override public void onRegistrationFailed(NsdServiceInfo info, int errorCode) {
                Log.e(TAG, "❌ Publication échouée: " + errorCode);
            }
            @Override public void onServiceUnregistered(NsdServiceInfo info) {
                Log.i(TAG, "📴 Service dépublié: " + info.getServiceName());
            }
            @Override public void onUnregistrationFailed(NsdServiceInfo info, int errorCode) {
                Log.e(TAG, "❌ Dépublication échouée: " + errorCode);
            }
        };

        nsdManager.registerService(serviceInfo, NsdManager.PROTOCOL_DNS_SD, registrationListener);
        Log.i(TAG, "📡 Publication NSD démarrée: " + deviceLabel + " ws=" + wsPort);
    }

    /* ── DÉCOUVERTE (Régie phone/PC) ── */
    public void startDiscovery() {
        if (nsdManager == null) return;

        discoveredScreens.clear();

        discoveryListener = new NsdManager.DiscoveryListener() {
            @Override
            public void onDiscoveryStarted(String regType) {
                Log.i(TAG, "🔍 Découverte NSD démarrée: " + regType);
            }

            @Override
            public void onServiceFound(NsdServiceInfo serviceInfo) {
                Log.i(TAG, "📺 Service trouvé: " + serviceInfo.getServiceName());
                if (serviceInfo.getServiceType().equals(SERVICE_TYPE)) {
                    resolveService(serviceInfo);
                }
            }

            @Override
            public void onServiceLost(NsdServiceInfo serviceInfo) {
                String name = serviceInfo.getServiceName();
                Log.i(TAG, "📴 Service perdu: " + name);
                discoveredScreens.removeIf(s -> s.serviceName.equals(name));
                if (listener != null) listener.onScreenLost(name);
            }

            @Override public void onDiscoveryStopped(String serviceType) {
                Log.i(TAG, "🛑 Découverte arrêtée");
            }
            @Override public void onStartDiscoveryFailed(String serviceType, int errorCode) {
                Log.e(TAG, "❌ Démarrage découverte échoué: " + errorCode);
            }
            @Override public void onStopDiscoveryFailed(String serviceType, int errorCode) {
                Log.e(TAG, "❌ Arrêt découverte échoué: " + errorCode);
            }
        };

        nsdManager.discoverServices(SERVICE_TYPE, NsdManager.PROTOCOL_DNS_SD, discoveryListener);
    }

    private void resolveService(NsdServiceInfo serviceInfo) {
        nsdManager.resolveService(serviceInfo, new NsdManager.ResolveListener() {
            @Override
            public void onResolveFailed(NsdServiceInfo info, int errorCode) {
                Log.w(TAG, "⚠️ Résolution échouée pour " + info.getServiceName() + " code=" + errorCode);
            }

            @Override
            public void onServiceResolved(NsdServiceInfo info) {
                ScreenInfo screen = new ScreenInfo();
                screen.serviceName = info.getServiceName();
                screen.host        = info.getHost() != null ? info.getHost().getHostAddress() : "";
                screen.wsPort      = info.getPort();

                // Lire les TXT records
                try {
                    byte[] httpPortBytes = info.getAttributes().get("httpPort");
                    screen.httpPort = httpPortBytes != null
                        ? Integer.parseInt(new String(httpPortBytes)) : 9001;

                    byte[] roleBytes = info.getAttributes().get("role");
                    screen.role = roleBytes != null ? new String(roleBytes) : "screen";

                    byte[] nameBytes = info.getAttributes().get("name");
                    screen.label = nameBytes != null ? new String(nameBytes) : screen.host;

                    byte[] ipBytes = info.getAttributes().get("ip");
                    if (ipBytes != null && !screen.host.isEmpty() == false) {
                        screen.host = new String(ipBytes);
                    }
                } catch (Exception e) {
                    screen.httpPort = 9001;
                    screen.role     = "screen";
                    screen.label    = screen.host;
                }

                Log.i(TAG, "✅ Écran résolu: " + screen);

                // Dédupliquer
                discoveredScreens.removeIf(s -> s.serviceName.equals(screen.serviceName));
                discoveredScreens.add(screen);

                if (listener != null) listener.onScreenFound(screen);
            }
        });
    }

    /* ── ARRÊT ── */
    public void stopDiscovery() {
        if (nsdManager != null && discoveryListener != null) {
            try { nsdManager.stopServiceDiscovery(discoveryListener); } catch (Exception e) { /* ignore */ }
        }
    }

    public void unregister() {
        if (nsdManager != null && registrationListener != null) {
            try { nsdManager.unregisterService(registrationListener); } catch (Exception e) { /* ignore */ }
        }
    }
}
