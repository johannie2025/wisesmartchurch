/**
 * Wise Smart Church — Electron Preload
 * Expose secure IPC bridge to renderer (wismachv5.html)
 */
const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('electronBridge', {
  /**
   * Broadcast a JSON string to all connected WebSocket clients
   * Called from wismachv5.html mqttPub()
   */
  broadcast(jsonStr) {
    ipcRenderer.send('wsc-broadcast', jsonStr);
  },

  /**
   * Get network info: IPs, ports, client count
   */
  async getNetworkInfo() {
    return ipcRenderer.invoke('get-network-info');
  },

  /**
   * Listen for incoming WS messages (from remote screens → relayed to window)
   */
  onBroadcast(callback) {
    ipcRenderer.on('ws-broadcast', (_event, msg) => {
      try { callback(JSON.parse(msg)); } catch(e) {}
    });
  },

  platform: process.platform,
  isElectron: true,
});
