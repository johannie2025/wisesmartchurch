/* Génère android/app/src/main/assets/index.html compatible anciens WebView (Android TV / boîtiers anciens) :
   - JS réduit en ES2017 via TypeScript (?. ?? catch{} spread…)
   - CSS : `inset:` remplacé par top/right/bottom/left
   Usage : node tools/make-android-html.js   (nécessite `typescript` : npm i -g typescript) */
const fs=require('fs'),path=require('path'),cp=require('child_process'),os=require('os');
const root=path.join(__dirname,'..');
let html=fs.readFileSync(path.join(root,'web/index.html'),'utf8');
const i0=html.lastIndexOf('<script>'),i1=html.indexOf('</script>',i0);
if(i0<0||i1<0)throw new Error('script introuvable');
const m=[null,html.slice(i0+8,i1)];
const tmp=fs.mkdtempSync(path.join(os.tmpdir(),'wc-'));
fs.writeFileSync(path.join(tmp,'in.js'),m[1]);
let tsc='tsc';try{cp.execSync('tsc -v',{stdio:'ignore'})}catch{tsc='npx --yes typescript tsc'}
try{cp.execSync(`${tsc} --allowJs --target ES2017 --lib ES2020,DOM --outDir ${tmp}/out ${tmp}/in.js`,{stdio:'ignore'})}catch{/* erreurs de typage ignorées : l'émission se fait quand même */}
let js=fs.readFileSync(path.join(tmp,'out/in.js'),'utf8').replace(/<\/script/gi,'<\\/script');
html=html.slice(0,i0)+'<script>'+js+'</script>'+html.slice(i1+9);
html=html.replace(/([{;\s])inset:\s*(-?[\w.%]+)\s*(?=[;}])/g,(a,p,v)=>`${p}top:${v};right:${v};bottom:${v};left:${v}`);
fs.writeFileSync(path.join(root,'android/app/src/main/assets/index.html'),html);
console.log('assets/index.html généré ('+Math.round(html.length/1024)+' Ko)');
