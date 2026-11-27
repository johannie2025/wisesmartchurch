(function(){
const $=(s,r=document)=>r.querySelector(s),$$=(s,r=document)=>[...r.querySelectorAll(s)];
/* reveal */
const io=new IntersectionObserver(es=>es.forEach(e=>{if(e.isIntersecting){e.target.classList.add('in');io.unobserve(e.target)}}),{threshold:.12});
$$('.rv').forEach(e=>io.observe(e));
const DEFBG=[
 ['Aurore',[['l',160,[[0,'#0b1b4d'],[.55,'#1f4fd8'],[1,'#7c5cff']]],['r',.2,.15,.55,[[0,'rgba(80,200,255,.55)'],[1,'rgba(80,200,255,0)']]]]],
 ['Nuit étoilée',[['l',180,[[0,'#02030a'],[1,'#0d1a3d']]],['r',.75,.2,.35,[[0,'rgba(255,255,255,.35)'],[1,'rgba(255,255,255,0)']]],['r',.2,.7,.4,[[0,'rgba(91,140,255,.25)'],[1,'rgba(91,140,255,0)']]]]],
 ['Or royal',[['l',150,[[0,'#2a1a05'],[.55,'#8a5a10'],[1,'#f2c14e']]],['r',.5,.0,.6,[[0,'rgba(255,240,180,.45)'],[1,'rgba(255,240,180,0)']]]]],
 ['Pourpre',[['l',155,[[0,'#2b0a3d'],[.6,'#7b1fa2'],[1,'#ff6f91']]]]],
 ['Forêt',[['l',165,[[0,'#04210f'],[1,'#0f6b3a']]],['r',.8,.1,.5,[[0,'rgba(180,255,200,.3)'],[1,'rgba(180,255,200,0)']]]]],
 ['Océan',[['l',170,[[0,'#021a2e'],[.6,'#0a6fa8'],[1,'#7fe0ff']]]]],
 ['Flamme',[['l',170,[[0,'#2a0600'],[.6,'#c62828'],[1,'#ffb74d']]]]],
 ['Grâce (clair)',[['l',160,[[0,'#f5f7ff'],[1,'#b9ccff']]]]],
 ['Noir élégant',[['r',.5,.4,.8,[[0,'#1a2338'],[1,'#000']]]]],
 ['Rayons de lumière',[['l',180,[[0,'#06102b'],[1,'#10306e']]],['r',.5,-.1,.9,[[0,'rgba(255,255,255,.65)'],[.5,'rgba(160,200,255,.2)'],[1,'rgba(160,200,255,0)']]]]]
];
function bgCss(spec){return spec.map(l=>l[0]==='l'?`linear-gradient(${l[1]}deg,${l[2].map(x=>x[1]+' '+x[0]*100+'%').join(',')})`:`radial-gradient(circle at ${l[1]*100}% ${l[2]*100}%,${l[4].map(x=>x[1]+' '+x[0]*l[3]*100+'%').join(',')})`).reverse().join(',')}

$$('.scene[data-bg]').forEach(e=>{e.style.background=bgCss(DEFBG[+e.dataset.bg][1])});
/* démo : scènes en boucle */
const sc=$$('.scene'),dots=$$('.dots b');let i=0,tm;
function show(n){i=n;sc.forEach((s,k)=>s.classList.toggle('on',k===n));dots.forEach((d,k)=>d.classList.toggle('on',k===n));
  const c=$('#cd');if(n===0&&c){let v=10;c.textContent='00:10';const bar=$('#cdbar');if(bar)bar.style.width='0';clearInterval(c._t);c._t=setInterval(()=>{v--;if(v<0){clearInterval(c._t);return}c.textContent='00:'+String(v).padStart(2,'0');if(bar)bar.style.width=((10-v)*10)+'%'},550)}
  clearTimeout(tm);tm=setTimeout(()=>show((n+1)%sc.length),5200)}
dots.forEach((d,k)=>d.onclick=()=>show(k));if(sc.length)show(0);
/* particules */
const cv=$('#fx');if(cv&&!matchMedia('(prefers-reduced-motion:reduce)').matches){
 const x=cv.getContext('2d');let W,H,P=[];const rs=()=>{W=cv.width=cv.offsetWidth;H=cv.height=cv.offsetHeight;P=Array.from({length:Math.min(70,W/18|0)},()=>({x:Math.random()*W,y:Math.random()*H,r:Math.random()*2+.5,v:Math.random()*.4+.1,a:Math.random()*.6+.2}))};
 addEventListener('resize',rs);rs();(function f(){x.clearRect(0,0,W,H);P.forEach(p=>{p.y-=p.v;if(p.y<0){p.y=H;p.x=Math.random()*W}x.fillStyle='rgba(80,170,255,'+p.a+')';x.beginPath();x.arc(p.x,p.y,p.r,0,7);x.fill()});requestAnimationFrame(f)})()}
/* lightbox */
const lb=$('#lb');$$('.shots figure').forEach(f=>f.onclick=()=>{$('img',lb).src=$('img',f).src;lb.classList.add('on')});if(lb)lb.onclick=()=>lb.classList.remove('on');
/* formulaires */
function toast(m){const t=$('#toast');t.textContent=m;t.style.display='block';setTimeout(()=>t.style.display='none',4500)}
$$('form[data-ajax]').forEach(f=>f.addEventListener('submit',async e=>{e.preventDefault();
 try{const r=await fetch('contact.php',{method:'POST',body:new FormData(f)});const j=await r.json();
  if(j.ok){toast(f.dataset.ok);f.reset()}else toast(f.dataset.err)}catch(_){toast(f.dataset.err)}}));
/* préremplir le formulaire de contact depuis un plan */
$$('[data-plan]').forEach(a=>a.addEventListener('click',()=>{const s=$('#leadplan');if(s)s.value=a.dataset.plan}));
})();
