#!/usr/bin/env bash
# Télécharge three.js (master) depuis GitHub et copie les fichiers de build dans web/vendor/three/
# Utilisé par GitHub Actions ; en cas d'échec l'app retombe sur son moteur WebGL intégré.
set -u
D=web/vendor/three; mkdir -p "$D"
if curl -fsSL --retry 3 -o /tmp/three.zip https://github.com/mrdoob/three.js/archive/refs/heads/master.zip && unzip -q -o /tmp/three.zip -d /tmp/three-src; then
  B=$(ls -d /tmp/three-src/three.js-*/build | head -1)
  for f in three.module.js three.core.js three.module.min.js three.core.min.js; do [ -f "$B/$f" ] && cp "$B/$f" "$D/$f"; done
  echo "three.js copié :"; ls -la "$D"
else
  echo "three.js non téléchargé : moteur WebGL intégré utilisé"
fi
exit 0
