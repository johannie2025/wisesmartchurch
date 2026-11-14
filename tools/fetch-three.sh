#!/usr/bin/env bash
# Télécharge three.js (master) depuis GitHub, extrait UNIQUEMENT les .js de build
# vers web/vendor/three/, puis supprime archive et dossiers temporaires.
# Fonctionne sous Linux, macOS et Windows (Git Bash) : l'extraction utilise Python (zipfile), sans `unzip`.
# En cas d'échec : avertissement, build non bloqué, l'app utilise son moteur WebGL intégré.
set -u
# Fichiers déjà fournis dans le dépôt (three.module.js + three.core.js valides) : rien à télécharger.
if [ -f web/vendor/three/three.module.js ] && [ -f web/vendor/three/three.core.js ] && grep -q 'WebGLRenderer' web/vendor/three/three.module.js && ! head -c 1 web/vendor/three/three.module.js | grep -q '<'; then
  echo "three.js déjà présent dans web/vendor/three : téléchargement ignoré"; ls -la web/vendor/three; exit 0
fi
PY=$(command -v python3 || command -v python || true)
TMP=$(mktemp -d); trap 'rm -rf "$TMP"' EXIT
D=web/vendor/three; mkdir -p "$D"
GOT=0
if [ -n "$PY" ]; then
  # branche dev d'abord (c'est là que se trouvent three.module.js / three.core.js), puis master
  for BR in dev master; do
    if curl -fsSL --retry 3 -o "$TMP/three.zip" "https://github.com/mrdoob/three.js/archive/refs/heads/$BR.zip"; then GOT=1; echo "branche $BR téléchargée"; break; fi
  done
fi
if [ "$GOT" = 1 ]; then
  "$PY" - "$TMP/three.zip" "$D" <<'PYEOF'
import sys,zipfile,os,re
z=zipfile.ZipFile(sys.argv[1]);out=sys.argv[2];n=0
for i in z.namelist():
    m=re.match(r'^[^/]+/build/([^/]+\.js)$',i)
    if m:
        open(os.path.join(out,m.group(1)),'wb').write(z.read(i));n+=1
print("fichiers three.js extraits :",n)
sys.exit(0 if os.path.exists(os.path.join(out,'three.module.js')) else 1)
PYEOF
  if [ $? -eq 0 ]; then
    # ne garder que les fichiers utiles (module + core)
    find "$D" -type f ! -name 'three.module*.js' ! -name 'three.core*.js' -delete
    # contrôle : un vrai module JS (pas une page HTML) contenant WebGLRenderer
    if head -c 1 "$D/three.module.js" | grep -q '<' || ! grep -q 'WebGLRenderer' "$D/three.module.js"; then
      echo "::warning::three.module.js invalide : supprimé (moteur WebGL intégré utilisé)"; rm -f "$D"/*.js
    fi
    echo "three.js prêt :"; ls -la "$D"
  else
    echo "::warning::three.module.js introuvable dans l'archive : moteur WebGL intégré utilisé"
  fi
else
  echo "::warning::three.js non téléchargé : moteur WebGL intégré utilisé"
fi
exit 0
