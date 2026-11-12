(function(){
const $=(s,r=document)=>r.querySelector(s),$$=(s,r=document)=>[...r.querySelectorAll(s)];
/* reveal */
const io=new IntersectionObserver(es=>es.forEach(e=>{if(e.isIntersecting){e.target.classList.add('in');io.unobserve(e.target)}}),{threshold:.12});
$$('.rv').forEach(e=>io.observe(e));
/* démo : scènes en boucle */
const sc=$$('.scene'),dots=$$('.dots b');let i=0,tm;
function show(n){i=n;sc.forEach((s,k)=>s.classList.toggle('on',k===n));dots.forEach((d,k)=>d.classList.toggle('on',k===n));
  const c=$('#cd');if(n===0&&c){let v=10;c.textContent='00:10';clearInterval(c._t);c._t=setInterval(()=>{v--;if(v<0){clearInterval(c._t);return}c.textContent='00:'+String(v).padStart(2,'0')},550)}
  clearTimeout(tm);tm=setTimeout(()=>show((n+1)%sc.length),4800)}
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
