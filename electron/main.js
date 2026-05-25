/**
 * Wise Smart Church — Electron Main Process
 * Embedded WebSocket server (port 9000) + HTTP server (port 9001)
 * Works 100% offline on local network
 */

const { app, BrowserWindow, ipcMain, Menu, Tray, nativeImage } = require('electron');
const path = require('path');
const http = require('http');
const fs   = require('fs');
const { WebSocketServer, WebSocket } = require('ws');
const os   = require('os');

const WS_PORT   = 9000;
const HTTP_PORT = 9001;

let mainWindow   = null;
let wsServer     = null;
let httpServer   = null;
let tray         = null;
const wsClients  = new Set();

/* ── Helper: get local IP addresses ── */
function getLocalIPs() {
  const ifaces = os.networkInterfaces();
  const ips = [];
  for (const name of Object.keys(ifaces)) {
    for (const iface of ifaces[name]) {
      if (iface.family === 'IPv4' && !iface.internal) {
        ips.push({ name, ip: iface.address });
      }
    }
  }
  return ips;
}

/* ── Start WebSocket server (LAN relay) ── */
function startWsServer() {
  wsServer = new WebSocketServer({ port: WS_PORT });

  wsServer.on('connection', (ws, req) => {
    const clientIp = req.socket.remoteAddress;
    console.log(`[WS] Client connecté: ${clientIp}`);
    wsClients.add(ws);

    // Envoyer l'IP serveur au client
    try {
      ws.send(JSON.stringify({ type: 'server_info', ips: getLocalIPs(), wsPort: WS_PORT }));
    } catch (e) {}

    ws.on('message', (data) => {
      // Relayer à tous les autres clients (broadcast)
      const msg = data.toString();
      for (const client of wsClients) {
        if (client !== ws && client.readyState === WebSocket.OPEN) {
          try { client.send(msg); } catch (e) {}
        }
      }
      // Relayer aussi à la fenêtre Electron principale
      if (mainWindow && !mainWindow.isDestroyed()) {
        mainWindow.webContents.send('ws-broadcast', msg);
      }
    });

    ws.on('close', () => {
      wsClients.delete(ws);
      console.log(`[WS] Client déconnecté: ${clientIp} (${wsClients.size} restants)`);
    });

    ws.on('error', () => wsClients.delete(ws));
  });

  wsServer.on('error', (err) => {
    console.error('[WS] Erreur serveur:', err.message);
  });

  wsServer.on('listening', () => {
    const ips = getLocalIPs();
    console.log(`[WS] Serveur démarré sur port ${WS_PORT}`);
    ips.forEach(({ name, ip }) => console.log(`  → ws://${ip}:${WS_PORT}  (${name})`));
  });
}

/* ── HTTP server to serve wismachv5.html on the LAN ── */
function startHttpServer() {
  const htmlPath = path.join(__dirname, '..', 'wismachv5.html');

  httpServer = http.createServer((req, res) => {
    // CORS
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Headers', '*');

    if (req.url === '/api/info') {
      res.writeHead(200, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ ips: getLocalIPs(), wsPort: WS_PORT, httpPort: HTTP_PORT }));
      return;
    }

    // Serve the app HTML
    fs.readFile(htmlPath, (err, data) => {
      if (err) {
        res.writeHead(404);
        res.end('App not found');
        return;
      }
      res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
      res.end(data);
    });
  });

  httpServer.listen(HTTP_PORT, '0.0.0.0', () => {
    const ips = getLocalIPs();
    console.log(`[HTTP] Serveur démarré sur port ${HTTP_PORT}`);
    ips.forEach(({ name, ip }) => console.log(`  → http://${ip}:${HTTP_PORT}  (${name})`));
  });
}

/* ── Electron bridge: window → WS server broadcast ── */
function setupElectronBridge() {
  // La fenêtre principale envoie un message IPC → on le broadcast à tous les WS clients
  ipcMain.on('wsc-broadcast', (_event, msgStr) => {
    for (const client of wsClients) {
      if (client.readyState === WebSocket.OPEN) {
        try { client.send(msgStr); } catch (e) {}
      }
    }
  });

  // Demande d'info réseau
  ipcMain.handle('get-network-info', () => ({
    ips: getLocalIPs(),
    wsPort: WS_PORT,
    httpPort: HTTP_PORT,
    clientCount: wsClients.size,
  }));
}

/* ── Preload script bridge ── */
function createPreload() {
  return path.join(__dirname, 'preload.js');
}

/* ── Main window ── */
function createWindow() {
  mainWindow = new BrowserWindow({
    width:  1280,
    height: 800,
    minWidth: 900,
    minHeight: 600,
    titleBarStyle: process.platform === 'darwin' ? 'hiddenInset' : 'default',
    backgroundColor: '#080a0f',
    icon: path.join(__dirname, 'assets', 'icon.png'),
    webPreferences: {
      preload:            createPreload(),
      contextIsolation:   true,
      nodeIntegration:    false,
      webSecurity:        false,   // allow local file:// loads (images, fonts)
    },
  });

  // Désactiver menu par défaut en production
  if (app.isPackaged) {
    Menu.setApplicationMenu(null);
  }

  // Charger l'app HTML
  const htmlPath = path.join(__dirname, '..', 'wismachv5.html');
  mainWindow.loadFile(htmlPath).catch(e => {
    // Fallback HTTP
    mainWindow.loadURL(`http://localhost:${HTTP_PORT}`);
  });

  mainWindow.on('closed', () => { mainWindow = null; });

  // Ouvrir DevTools en développement
  if (!app.isPackaged) {
    mainWindow.webContents.openDevTools({ mode: 'detach' });
  }
}

/* ── Tray icon ── */
function createTray() {
  try {
    const iconPath = path.join(__dirname, 'assets', 'tray.png');
    const icon = nativeImage.createFromPath(iconPath);
    tray = new Tray(icon.isEmpty() ? nativeImage.createEmpty() : icon);
    const ips = getLocalIPs().map(x => x.ip).join(', ') || 'localhost';
    tray.setToolTip(`Wise Smart Church — ${ips}:${HTTP_PORT}`);
    const contextMenu = Menu.buildFromTemplate([
      { label: `📡 IPs: ${ips}`, enabled: false },
      { label: `🔌 WS Port: ${WS_PORT}`, enabled: false },
      { label: `🌐 HTTP Port: ${HTTP_PORT}`, enabled: false },
      { type: 'separator' },
      { label: '🖥 Afficher', click: () => mainWindow?.show() },
      { label: '❌ Quitter', click: () => app.quit() },
    ]);
    tray.setContextMenu(contextMenu);
    tray.on('click', () => mainWindow?.show());
  } catch (e) {
    console.warn('Tray non disponible:', e.message);
  }
}

/* ── App lifecycle ── */
app.whenReady().then(() => {
  startWsServer();
  startHttpServer();
  setupElectronBridge();
  createWindow();
  createTray();

  app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) createWindow();
  });
});

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') app.quit();
});

app.on('before-quit', () => {
  if (wsServer)   try { wsServer.close(); }   catch(e) {}
  if (httpServer) try { httpServer.close(); } catch(e) {}
});
