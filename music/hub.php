<?php
/**
 * Hitune Hub — community home for Charts, Short Clips, Radio,
 * Contests, Listening Parties and the Tips & Wallet economy.
 * Data comes from /api/hub/* (public read) + /api/htx/party, /api/dist/tip
 * (session-gated via distAuth headers).
 */
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HiTune Hub | Charts, Clips, Radio, Parties</title>
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css">
<style>
:root{
  --bg:#06060e; --bg2:#0b0b18; --panel:rgba(255,255,255,.035); --panel2:rgba(255,255,255,.06);
  --line:rgba(255,255,255,.08); --line2:rgba(139,92,246,.35);
  --txt:#f4f4fb; --mut:#8d8dab; --cy:#00b7ff; --pu:#8b5cf6; --pk:#fa2d48; --gd:#ffb020;
  --grad:linear-gradient(135deg,#00b7ff,#8b5cf6 55%,#fa2d48 130%);
  --grad-soft:linear-gradient(135deg,#00b7ff1f,#8b5cf61f);
}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Figtree,system-ui,sans-serif;background:var(--bg);color:var(--txt);min-height:100vh}
button{font-family:inherit}
::-webkit-scrollbar{width:9px;height:9px}
::-webkit-scrollbar-thumb{background:#ffffff14;border-radius:8px}
::selection{background:#8b5cf655}
.ambient{position:fixed;inset:0;pointer-events:none;z-index:0;
  background:radial-gradient(60% 40% at 80% 0%,#8b5cf614,transparent 60%),
             radial-gradient(50% 35% at 0% 100%,#00b7ff10,transparent 60%);}
.wrap{position:relative;z-index:1;display:flex;min-height:100vh}

/* rail */
.rail{width:236px;flex:none;padding:26px 16px;border-right:1px solid var(--line);
  background:rgba(9,9,20,.6);backdrop-filter:blur(16px);position:sticky;top:0;height:100vh;display:flex;flex-direction:column}
.brand{display:flex;align-items:center;gap:10px;padding:0 8px 20px;border-bottom:1px solid var(--line);margin-bottom:16px;text-decoration:none}
.brand .lg{width:36px;height:36px;border-radius:11px;background:var(--grad);display:grid;place-items:center;box-shadow:0 6px 18px -4px #8b5cf688}
.brand .lg .mdi{color:#fff;font-size:20px}
.brand b{font-size:16px;font-weight:900;letter-spacing:-.3px;color:#fff}
.brand span{display:block;font-size:9.5px;color:var(--mut);letter-spacing:1.6px;text-transform:uppercase;font-weight:800}
.nav-lbl{font-size:9.5px;font-weight:800;letter-spacing:1.6px;text-transform:uppercase;color:#55556f;padding:14px 10px 8px}
.nav-it{position:relative;display:flex;align-items:center;gap:11px;padding:12px 12px;border-radius:12px;cursor:pointer;color:var(--mut);font-size:13.5px;font-weight:600;border:1px solid transparent;transition:.16s;margin-bottom:3px}
.nav-it .ic{width:30px;height:30px;border-radius:9px;background:var(--panel2);border:1px solid var(--line);display:grid;place-items:center;flex:none}
.nav-it .ic .mdi{font-size:16px}
.nav-it:hover{color:#fff;background:#ffffff06}
.nav-it.on{color:#fff;background:linear-gradient(120deg,#00b7ff1a,#8b5cf61f);border-color:#8b5cf640;box-shadow:0 4px 18px -6px #8b5cf655}
.nav-it.on:before{content:'';position:absolute;left:-14px;top:20%;bottom:20%;width:3px;border-radius:3px;background:var(--grad)}
.nav-it.on .ic{background:var(--grad);border-color:transparent}
.nav-it.on .ic .mdi{color:#fff}
.t-charts .ic .mdi{color:#ffb020}.t-clips .ic .mdi{color:#fa2d48}.t-radio .ic .mdi{color:#00b7ff}
.t-contests .ic .mdi{color:#2ee6a8}.t-parties .ic .mdi{color:#8b5cf6}.t-wallet .ic .mdi{color:#f472b6}
.rail-foot{margin-top:auto;padding-top:14px}
.rail-foot a{display:block;text-align:center;padding:11px;border-radius:11px;border:1px solid var(--line);color:var(--mut);font-size:12px;font-weight:700;text-decoration:none;transition:.16s}
.rail-foot a:hover{color:#fff;border-color:#8b5cf655}

/* workspace */
.work{flex:1;min-width:0;padding:30px 38px 70px;max-width:1100px}
.work h1{font-size:27px;font-weight:900;letter-spacing:-.5px;display:flex;align-items:center;gap:12px}
.work h1 .hic{width:46px;height:46px;border-radius:14px;background:var(--grad);display:grid;place-items:center;box-shadow:0 8px 26px -4px #8b5cf688;flex:none}
.work h1 .hic .mdi{font-size:24px;color:#fff}
.work .sub{color:var(--mut);font-size:13.5px;margin-top:8px;max-width:600px;line-height:1.55}
.chips{display:flex;flex-wrap:wrap;gap:7px;margin-top:16px}
.chip{padding:7px 15px;border-radius:20px;background:#ffffff08;border:1px solid var(--line);color:var(--mut);font-size:12px;font-weight:700;cursor:pointer;transition:.16s}
.chip:hover{border-color:var(--pu);color:#fff}
.chip.on{background:var(--grad);color:#fff;border-color:transparent;box-shadow:0 4px 14px -2px #8b5cf688}

.card{background:var(--panel);border:1px solid var(--line);border-radius:20px;padding:22px;margin-top:20px;backdrop-filter:blur(14px);position:relative}
.card:before{content:'';position:absolute;top:0;left:22px;right:22px;height:1px;background:linear-gradient(90deg,transparent,#ffffff22,transparent);pointer-events:none}
.grid2{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px}
.grid3{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:14px}

.tk-row{display:flex;align-items:center;gap:13px;padding:11px 10px;border-radius:13px;transition:.15s;border:1px solid transparent}
.tk-row:hover{background:#ffffff07;border-color:var(--line)}
.tk-rank{width:34px;font-size:19px;font-weight:900;color:var(--mut);text-align:center;flex:none;font-variant-numeric:tabular-nums}
.tk-rank.top{background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent}
.tk-cov{width:48px;height:48px;border-radius:11px;object-fit:cover;background:#ffffff10;flex:none}
.tk-cov.ph{display:grid;place-items:center;color:#55556f}
.tk-name{font-size:13.5px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.tk-art{font-size:11.5px;color:var(--mut);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.tk-meta{margin-left:auto;display:flex;align-items:center;gap:8px;flex:none}
.badge{font-size:9px;font-weight:800;letter-spacing:.6px;padding:3px 9px;border-radius:12px;text-transform:uppercase;flex:none}
.badge.ai{background:#8b5cf628;color:#c4b5fd;border:1px solid #8b5cf650}
.badge.clip{background:#fa2d4822;color:#fda4af;border:1px solid #fa2d4845}
.badge.live{background:#0e3a24;color:#52e39a;border:1px solid #52e39a33}
.badge.live:before{content:'● ';animation:blink 1.4s infinite}
@keyframes blink{50%{opacity:.35}}
.lnk{font-size:11px;font-weight:800;color:var(--cy);text-decoration:none;padding:6px 13px;border-radius:10px;border:1px solid #00b7ff35;background:#00b7ff12;transition:.15s;white-space:nowrap;cursor:pointer}
.lnk:hover{background:#00b7ff28;color:#fff}
.lnk.tip{color:#f9a8d4;border-color:#f472b640;background:#f472b612}
.lnk.tip:hover{background:#f472b628;color:#fff}
.empty{padding:44px 20px;text-align:center;color:var(--mut);font-size:13px}
.empty .mdi{font-size:34px;display:block;margin-bottom:10px;opacity:.5}
.skel{height:62px;border-radius:13px;background:linear-gradient(90deg,#ffffff07,#ffffff10,#ffffff07);background-size:200% 100%;animation:sh 1.4s infinite;margin-bottom:9px}
@keyframes sh{to{background-position:-200% 0}}

.sta-card{background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:18px;cursor:pointer;transition:.18s;position:relative;overflow:hidden}
.sta-card:hover{transform:translateY(-2px);border-color:#00b7ff55;box-shadow:0 12px 30px -12px #00b7ff44}
.sta-card .ic{width:44px;height:44px;border-radius:12px;background:var(--grad);display:grid;place-items:center;margin-bottom:12px}
.sta-card .ic .mdi{color:#fff;font-size:22px}
.sta-card b{display:block;font-size:14.5px}
.sta-card span{font-size:11.5px;color:var(--mut);margin-top:3px;display:block}
.sta-card.on{border-color:var(--cy);box-shadow:0 10px 30px -10px #00b7ff66}
.sta-card.on:after{content:'● LIVE';position:absolute;top:12px;right:12px;font-size:8.5px;font-weight:800;letter-spacing:1px;color:#52e39a}

.ct-head{display:flex;align-items:center;gap:13px;margin-bottom:8px}
.ct-prize{margin-left:auto;font-size:12px;font-weight:800;color:var(--gd);background:#ffb02014;border:1px solid #ffb02035;padding:6px 13px;border-radius:11px}
.ct-dates{font-size:11px;color:var(--mut);margin-bottom:10px}

.wallet-row{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-top:20px}
.w-card{background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:20px;text-align:center}
.w-card b{font-size:26px;font-weight:900;display:block}
.w-card.bal b{background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent}
.w-card span{font-size:10px;color:var(--mut);letter-spacing:1.2px;text-transform:uppercase;font-weight:800}
.field{margin-bottom:14px}
.field label{display:block;font-size:10.5px;font-weight:800;letter-spacing:1.2px;text-transform:uppercase;color:var(--mut);margin-bottom:7px}
.field input{width:100%;background:rgba(0,0,0,.34);border:1px solid var(--line);border-radius:12px;color:var(--txt);padding:12px 14px;font-size:14px;font-family:inherit}
.field input:focus{border-color:var(--pu);outline:none}
.big-btn{display:inline-flex;align-items:center;gap:8px;padding:13px 28px;border:0;border-radius:13px;background:var(--grad);color:#fff;font-weight:800;font-size:14px;cursor:pointer;transition:.16s;box-shadow:0 8px 24px -6px #8b5cf6aa}
.big-btn:hover{transform:translateY(-2px)}
.big-btn:disabled{opacity:.45;cursor:not-allowed}
.note{font-size:12px;color:var(--mut);margin-top:12px;line-height:1.6}
.loginbox{text-align:center;padding:50px 20px}
.loginbox .mdi{font-size:44px;color:var(--pu);display:block;margin-bottom:14px}
.loginbox p{color:var(--mut);font-size:13.5px;margin-bottom:20px}
.toast{position:fixed;bottom:26px;left:50%;transform:translateX(-50%);background:#14142a;border:1px solid var(--line2);color:#fff;padding:13px 26px;border-radius:13px;font-size:13.5px;font-weight:700;box-shadow:0 16px 40px -10px #000d;z-index:99;animation:fadein .3s}
@keyframes fadein{from{opacity:0;transform:translate(-50%,10px)}to{opacity:1;transform:translate(-50%,0)}}
@media(max-width:860px){
  .rail{position:fixed;bottom:0;top:auto;left:0;right:0;width:100%;height:auto;flex-direction:row;overflow-x:auto;padding:8px;border-right:0;border-top:1px solid var(--line);z-index:10}
  .brand,.nav-lbl,.rail-foot{display:none}
  .nav-it{flex-direction:column;gap:5px;font-size:10px;padding:8px 10px;margin:0}
  .work{padding:22px 16px 110px}
  .wallet-row{grid-template-columns:1fr}
}

/* ---------- light theme ---------- */
body.light{
  --bg:#eef0f7; --bg2:#f7f8fc; --panel:rgba(255,255,255,.75); --panel2:#fff;
  --line:rgba(20,20,50,.10); --line2:rgba(109,72,220,.4);
  --txt:#17172b; --mut:#5c5c7d;
}
body.light .ambient{background:radial-gradient(60% 40% at 80% 0%,#8b5cf610,transparent 60%),radial-gradient(50% 35% at 0% 100%,#00b7ff0d,transparent 60%)}
body.light .rail{background:rgba(255,255,255,.68)}
body.light .brand b{color:var(--txt)}
body.light .nav-it{color:#4a4a68}
body.light .nav-it:hover{color:#17172b;background:#8b5cf60d}
body.light .nav-it.on{color:#17172b}
body.light .nav-it .ic{background:#fff;border-color:rgba(20,20,50,.10)}
body.light .chip{background:#fff}
body.light .chip.on{color:#fff}
body.light .card{background:var(--panel);box-shadow:0 14px 34px -18px #2b2b6b2a}
body.light .field input{background:#fff;color:var(--txt)}
body.light .tk-row:hover{background:#8b5cf60a}
body.light .skel{background:linear-gradient(90deg,#00000008,#00000010,#00000008);background-size:200% 100%}
body.light .sta-card{background:#fff}
body.light .w-card{background:#fff}
body.light .rail-foot a{background:#fff}
body.light .toast{background:#fff;color:#17172b;border-color:var(--line2)}
body.light .loginbox .mdi{color:var(--pu)}
.theme-tog{display:flex;align-items:center;gap:10px;margin-top:10px;padding:9px 12px;border-radius:11px;background:var(--panel);border:1px solid var(--line);cursor:pointer;color:var(--mut);font-size:12px;font-weight:700;transition:.16s;user-select:none}
.theme-tog:hover{color:var(--txt);border-color:var(--line2)}
.theme-tog .mdi{font-size:17px}
</style>
</head>
<body>
<div class="ambient"></div>
<div class="wrap">
  <nav class="rail">
    <a class="brand" href="/home"><img src="/files/logo/26/09/22/6ab284f5cd5bd/hitune_icon_blue_6ab2810b469b5.png" alt="Hitune" style="width:38px;height:38px;border-radius:11px;box-shadow:0 6px 18px -4px #00b7ff66"><div><b>HiTune Hub</b><span>Community</span></div></a>
    <div class="nav-lbl">Discover</div>
    <div class="nav-it t-charts" data-tab="charts"><div class="ic"><span class="mdi mdi-chart-line"></span></div>Charts</div>
    <div class="nav-it t-clips" data-tab="clips"><div class="ic"><span class="mdi mdi-movie-open"></span></div>Short Clips</div>
    <div class="nav-it t-radio" data-tab="radio"><div class="ic"><span class="mdi mdi-radio"></span></div>Radio</div>
    <div class="nav-lbl">Community</div>
    <div class="nav-it t-contests" data-tab="contests"><div class="ic"><span class="mdi mdi-trophy"></span></div>Contests</div>
    <div class="nav-it t-parties" data-tab="parties"><div class="ic"><span class="mdi mdi-account-group"></span></div>Listening Parties</div>
    <div class="nav-lbl">Earn</div>
    <div class="nav-it t-wallet" data-tab="wallet"><div class="ic"><span class="mdi mdi-wallet"></span></div>Tips &amp; Wallet</div>
    <div class="rail-foot"><a href="/ai-studio">Open AI Studio →</a>
      <div class="theme-tog" id="theme-tog"><span class="mdi mdi-white-balance-sunny" id="theme-ic"></span><span id="theme-txt">Light mode</span></div>
    </div>
  </nav>
  <main class="work" id="work"></main>
</div>

<script src="/dist-auth.js"></script>
<script>
var apiHeaders = {
  'Content-Type': 'application/x-www-form-urlencoded',
  'x-bof-request-code': 'BusyOwlFrameWorkVersion201',
  'x-bof-platform': 'web',
  'x-bof-version': '2098'
};
if (window.distAuth) distAuth.headers(apiHeaders);
var TAB = location.hash.replace('#','') || 'charts';
var CHART = 'viral';
var TIP_HASH = '', TIP_LABEL = '';

function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g, c=>'&#'+c.charCodeAt(0)+';'); }
function toast(m){ var t=document.createElement('div');t.className='toast';t.textContent=m;document.body.appendChild(t);setTimeout(()=>t.remove(),2800); }
function post(url, body, cb){
  fetch('/api/'+url, { method:'POST', headers:apiHeaders, body:new URLSearchParams(body||{}) })
    .then(r=>r.json()).then(cb).catch(()=>cb({message:'network_error'}));
}
function getj(url, cb){
  fetch('/api/'+url, { headers:apiHeaders }).then(r=>r.json()).then(cb).catch(()=>cb({message:'network_error'}));
}
function dataOf(d){ return (d && d.data && typeof d.data==='object') ? Object.assign({}, d.data, d) : (d||{}); }
function is403(d){ return d && (d.message==='403' || (d.messages||[]).indexOf('403')>=0 || d.error==='access_denied' || (d.data&&d.data.error==='access_denied')); }
function trackUrl(h){ return '/track/'+h; }
function mmss(s){ s=Math.round(s||0); return Math.floor(s/60)+':'+String(s%60).padStart(2,'0'); }

function head(icon,title,sub){
  return '<h1><span class="hic"><span class="mdi '+icon+'"></span></span>'+title+'</h1><p class="sub">'+sub+'</p>';
}
function skeletons(n){ var s=''; while(n--) s+='<div class="skel"></div>'; return s; }
function emptyState(ic,txt){ return '<div class="empty"><span class="mdi '+ic+'"></span>'+txt+'</div>'; }
function loginBox(what){
  return '<div class="loginbox"><span class="mdi mdi-account-lock-outline"></span><p>Login needed to '+what+' on HiTune.</p>'+
    '<a class="big-btn" href="'+distAuth.loginUrl()+'"><span class="mdi mdi-login"></span>Login to HiTune</a></div>';
}

function tkRow(t, rank, extra){
  var cov = t.cover ? '<img class="tk-cov" src="'+esc(t.cover)+'" loading="lazy" onerror="this.outerHTML=\'<div class=tk-cov><span class=mdi mdi-music-note></span></div>\'">'
                    : '<div class="tk-cov ph"><span class="mdi mdi-music-note"></span></div>';
  return '<div class="tk-row">'+
    (rank!=null?'<div class="tk-rank'+(rank<=3?' top':'')+'">'+rank+'</div>':'')+cov+
    '<div style="min-width:0;flex:1"><div class="tk-name">'+esc(t.title)+'</div><div class="tk-art">'+esc(t.sub_title||t.sub_data||t.artist||'')+'</div></div>'+
    '<div class="tk-meta">'+(t.ai_pct>0?'<span class="badge ai">AI</span>':'')+
    (extra||'')+
    '<a class="lnk" href="'+trackUrl(t.hash)+'"><span class="mdi mdi-play"></span> Play</a>'+
    '<button class="lnk tip" onclick="tipFor(\''+esc(t.hash)+'\',\''+esc(t.title)+'\')"><span class="mdi mdi-heart"></span> Tip</button>'+
    '</div></div>';
}

function tipFor(hash,label){
  TIP_HASH=hash; TIP_LABEL=label; switchTab('wallet');
  toast('Tipping '+label);
}

/* ---------------- CHARTS ---------------- */
function renderCharts(){
  var w=document.getElementById('work');
  w.innerHTML = head('mdi-chart-line','Weekly Charts','Top 50 AI Songs, Top 100 Indie Stars aur Viral HiTune Tracks — har week updated leaderboards.')+
    '<div class="chips">'+
      '<span class="chip'+(CHART==='viral'?' on':'')+'" data-c="viral">Viral Tracks</span>'+
      '<span class="chip'+(CHART==='top_ai'?' on':'')+'" data-c="top_ai">Top 50 AI Songs</span>'+
      '<span class="chip'+(CHART==='indie'?' on':'')+'" data-c="indie">Top 100 Indie</span>'+
    '</div><div class="card" id="chart-body">'+skeletons(8)+'</div>';
  w.querySelectorAll('.chip').forEach(c=>c.onclick=()=>{CHART=c.dataset.c;renderCharts();});
  post('hub/charts',{chart:CHART,limit:50},function(d){
    d=dataOf(d);
    var items=d.items||[];
    document.getElementById('chart-body').innerHTML =
      items.length ? items.map(t=>tkRow(t,t.chart_rank)).join('') : emptyState('mdi-chart-line','No chart data yet — songs play hone par chart bharega.');
  });
}

/* ---------------- CLIPS ---------------- */
function renderClips(){
  var w=document.getElementById('work');
  w.innerHTML = head('mdi-movie-open','Short Clips','Gaano ke 15–30 second hooks — swipe style discovery. Clip sunna hai to track kholo; reel banani hai to AI Studio ka Clip Maker use karo.')+
    '<div class="card" id="clip-body"><div class="grid3">'+skeletons(9)+'</div></div>';
  post('hub/clips',{limit:24},function(d){
    d=dataOf(d);
    var items=d.items||[];
    if(!items.length){ document.getElementById('clip-body').innerHTML=emptyState('mdi-movie-open','Abhi koi clip nahi — catalog grow hone par yahan hooks dikhenge.'); return; }
    document.getElementById('clip-body').innerHTML='<div class="grid3">'+items.map(t=>{
      var cov=t.cover?'<img src="'+esc(t.cover)+'" style="width:100%;height:150px;object-fit:cover;display:block" loading="lazy" onerror="this.style.display=\'none\'">':'';
      return '<div style="border:1px solid var(--line);border-radius:15px;overflow:hidden;background:#ffffff05;transition:.15s">'+
        '<div style="position:relative;background:#ffffff08;height:150px;display:grid;place-items:center">'+
          cov+
          '<span class="badge clip" style="position:absolute;bottom:9px;left:9px">'+(t.clip?t.clip.duration+'s':'30s')+' clip</span>'+
          (t.ai_pct>0?'<span class="badge ai" style="position:absolute;top:9px;right:9px">AI</span>':'')+
        '</div><div style="padding:12px">'+
        '<div class="tk-name">'+esc(t.title)+'</div><div class="tk-art">'+esc(t.sub_title||'')+'</div>'+
        '<div style="display:flex;gap:8px;margin-top:11px">'+
          '<a class="lnk" style="flex:1;text-align:center" href="'+trackUrl(t.hash)+'"><span class="mdi mdi-play"></span> Full Song</a>'+
          '<a class="lnk tip" href="/ai-studio"><span class="mdi mdi-movie-open-outline"></span></a>'+
        '</div></div></div>';
    }).join('')+'</div>';
  });
}

/* ---------------- RADIO ---------------- */
var STATION=null;
function renderRadio(){
  var w=document.getElementById('work');
  w.innerHTML = head('mdi-radio','HiTune Radio','24×7 stations — seed, artist, genre ya chart pe based. Station chuno, queue milegi.')+
    '<div class="card"><div class="grid2" id="stations">'+skeletons(4)+'</div></div>'+
    '<div class="card" id="queue" style="display:none"></div>';
  post('hub/radio',{mode:'list'},function(d){
    d=dataOf(d);
    var st=d.stations||[];
    var icons={viral:'mdi-fire',indie:'mdi-guitar-acoustic',top_ai:'mdi-robot',mixed:'mdi-radio-tower'};
    document.getElementById('stations').innerHTML = st.map(s=>
      '<div class="sta-card" data-seed="'+s.seed+'"><div class="ic"><span class="mdi '+(icons[s.seed]||'mdi-radio')+'"></span></div><b>'+esc(s.name)+'</b><span>'+esc(s.desc)+'</span></div>'
    ).join('');
    document.querySelectorAll('.sta-card').forEach(c=>c.onclick=()=>loadStation(c.dataset.seed,c));
  });
}
function loadStation(seed,el){
  document.querySelectorAll('.sta-card').forEach(x=>x.classList.remove('on'));
  el.classList.add('on');
  var q=document.getElementById('queue');
  q.style.display='block'; q.innerHTML=skeletons(6);
  post('hub/radio',{chart:seed,limit:30},function(d){
    d=dataOf(d);
    var items=d.items||[];
    q.innerHTML='<h3 style="font-size:14px;font-weight:800;margin-bottom:12px"><span class="mdi mdi-playlist-play" style="color:var(--cy)"></span> '+esc(d.station?d.station.name:'Station')+' — up next</h3>'+
      (items.length?items.map(t=>tkRow(t,null,'<span class="badge" style="background:#ffffff10;color:var(--mut);border:1px solid var(--line)">'+mmss(t.duration)+'</span>')).join(''):emptyState('mdi-radio','Is station pe abhi tracks nahi.'));
  });
}

/* ---------------- CONTESTS ---------------- */
function renderContests(){
  var w=document.getElementById('work');
  w.innerHTML = head('mdi-trophy','Contests','Monthly competitions — Best AI Song, Best Indie Track. Winners ko free distribution + homepage feature.')+
    '<div id="ct-body">'+skeletons(4)+'</div>';
  post('hub/contests',{},function(d){
    d=dataOf(d);
    var cs=d.contests||[];
    if(!cs.length){ document.getElementById('ct-body').innerHTML='<div class="card">'+emptyState('mdi-trophy','Abhi koi active contest nahi — naya contest jald aa raha hai.')+'</div>'; return; }
    document.getElementById('ct-body').innerHTML = cs.map(function(c){
      var lb=(c.leaderboard||[]).map(r=>tkRow({hash:r.hash,title:r.title,sub_title:r.artist,ai_pct:r.ai_pct,plays:r.plays},r.rank,
        '<span class="badge" style="background:#ffffff10;color:var(--mut);border:1px solid var(--line)">'+(r.plays||0)+' plays</span>')).join('');
      return '<div class="card"><div class="ct-head"><span class="mdi mdi-trophy-variant" style="font-size:26px;color:var(--gd)"></span>'+
        '<div><b style="font-size:16px">'+esc(c.title)+'</b><div class="ct-dates">'+
        (c.starts_at?('Starts '+esc(String(c.starts_at).slice(0,10))+' · '):'')+(c.ends_at?('Ends '+esc(String(c.ends_at).slice(0,10))):'Ongoing')+'</div></div>'+
        (c.prize?'<div class="ct-prize"><span class="mdi mdi-gift"></span> '+esc(c.prize)+'</div>':'')+
        '</div>'+(lb||emptyState('mdi-chart-line','Leaderboard abhi khali hai.'))+'</div>';
    }).join('');
  });
}

/* ---------------- PARTIES ---------------- */
function renderParties(){
  var w=document.getElementById('work');
  w.innerHTML = head('mdi-account-group','Listening Parties','Live rooms jahan fans saath me gaane sunte hain — host queue control karta hai.')+
    '<div class="card" id="party-body">'+skeletons(4)+'</div>'+
    '<div class="card" id="party-new" style="display:none"><div class="field"><label>New party name</label><input id="p-name" placeholder="e.g. Friday Night Vibes" maxlength="60"></div>'+
    '<button class="big-btn" id="p-create"><span class="mdi mdi-plus"></span>Start a Party</button>'+
    '<p class="note">Party host banoge — friends join karke saath sunege.</p></div>';
  getj('hub/party?action=list',function(d){
    d=dataOf(d);
    var ps=d.parties||[];
    var body=document.getElementById('party-body');
    if(is403(d)){ body.innerHTML=loginBox('join listening parties'); return; }
    body.innerHTML = ps.length ? ps.map(p=>
      '<div class="tk-row"><div class="tk-rank"><span class="mdi mdi-account-music" style="font-size:20px"></span></div>'+
      '<div style="flex:1;min-width:0"><div class="tk-name">'+esc(p.name)+'</div><div class="tk-art">Hosted by '+esc(p.host||'?')+(p.track?' · <span class="mdi mdi-music" style="font-size:11px"></span> '+esc(p.track.title||''):'')+'</div></div>'+
      '<div class="tk-meta"><span class="badge live">'+(p.listeners!=null?p.listeners+' listening':'LIVE')+'</span>'+
      '<button class="lnk" onclick="joinParty(\''+esc(p.hash)+'\')"><span class="mdi mdi-login-variant"></span> Join</button></div></div>'
    ).join('') : emptyState('mdi-account-group','Abhi koi live party nahi — pehli party tum start karo!');
    var isLogged = !is403(d);
    if(d && d.message!=='403') document.getElementById('party-new').style.display='block';
    var btn=document.getElementById('p-create');
    if(btn) btn.onclick=function(){
      var name=document.getElementById('p-name').value.trim();
      if(!name) return toast('Party name daalo');
      post('htx/party?action=create',{name:name},function(r){
        r=dataOf(r);
        if(is403(r)) return location.href=distAuth.loginUrl();
        if(r.hash||r.success){ toast('Party live!'); renderParties(); } else toast('Party create nahi hui');
      });
    };
  });
}
function joinParty(h){
  post('htx/party?action=join&hash='+encodeURIComponent(h),{},function(r){
    r=dataOf(r);
    if(is403(r)) return location.href=distAuth.loginUrl();
    if(r.party||r.success){ toast('Party join ho gayi — sync playback app me!'); renderParties(); }
    else toast('Join nahi ho paya');
  });
}

/* ---------------- WALLET ---------------- */
function renderWallet(){
  var w=document.getElementById('work');
  w.innerHTML = head('mdi-wallet','Tips &amp; Wallet','Apne favourite artists ko direct tip do — ₹10 se start. 100% royalties Creator Day pe.')+
    '<div id="wal-body">'+skeletons(4)+'</div>';
  getj('dist/tip?action=wallet',function(d){
    d=dataOf(d);
    var b=document.getElementById('wal-body');
    if(is403(d)){ b.innerHTML='<div class="card">'+loginBox('use your tips wallet')+'</div>'; return; }
    var bal=d.balance!=null?d.balance:(d.wallet&&d.wallet.balance)||0;
    b.innerHTML =
      '<div class="wallet-row">'+
        '<div class="w-card bal"><b>₹'+esc(bal)+'</b><span>Wallet Balance</span></div>'+
        '<div class="w-card"><b>₹'+esc(d.earned!=null?d.earned:0)+'</b><span>Tips Earned</span></div>'+
        '<div class="w-card"><b>₹'+esc(d.tipped!=null?d.tipped:0)+'</b><span>Tips Sent</span></div>'+
      '</div>'+
      '<div class="card"><h3 style="font-size:14px;font-weight:800;margin-bottom:14px"><span class="mdi mdi-plus-circle" style="color:var(--cy)"></span> Add funds</h3>'+
        '<div class="field"><label>Amount (₹10 – ₹1,00,000)</label><input id="t-amt" type="number" value="100" min="10"></div>'+
        '<button class="big-btn" id="t-top"><span class="mdi mdi-credit-card"></span>Top-up via Razorpay</button>'+
        '<p class="note">Secure Razorpay payment — UPI/cards/netbanking.</p></div>'+
      '<div class="card"><h3 style="font-size:14px;font-weight:800;margin-bottom:14px"><span class="mdi mdi-heart" style="color:var(--pk)"></span> Send a tip</h3>'+
        (TIP_HASH?'<p class="note" style="margin:0 0 12px"><span class="mdi mdi-music"></span> Tipping track: <b style="color:#fff">'+esc(TIP_LABEL||TIP_HASH)+'</b></p>':'')+
        '<div class="field"><label>Track hash'+(TIP_HASH?'':' (open Charts to pick)')+'</label><input id="tip-hash" value="'+esc(TIP_HASH)+'" placeholder="track hash" '+(TIP_HASH?'':'')+'></div>'+
        '<div class="field"><label>Amount (₹)</label><input id="tip-amt" type="number" value="10" min="1"></div>'+
        '<div class="field"><label>Note (optional)</label><input id="tip-note" maxlength="120" placeholder="Great track!"></div>'+
        '<button class="big-btn" id="tip-go"><span class="mdi mdi-heart"></span>Send Tip</button></div>'+
      '<div class="card" id="tip-hist"><h3 style="font-size:14px;font-weight:800;margin-bottom:8px"><span class="mdi mdi-history" style="color:var(--mut)"></span> Recent activity</h3>'+skeletons(3)+'</div>';
    document.getElementById('t-top').onclick=function(){
      var amt=parseFloat(document.getElementById('t-amt').value||0);
      post('dist/tip?action=topup',{amount:amt},function(r){
        r=dataOf(r);
        if(is403(r)) return location.href=distAuth.loginUrl();
        if(r.payment_url) location.href=r.payment_url; else toast('Top-up start nahi hua');
      });
    };
    document.getElementById('tip-go').onclick=function(){
      var hash=document.getElementById('tip-hash').value.trim();
      var amt=parseFloat(document.getElementById('tip-amt').value||0);
      if(!hash) return toast('Track hash daalo (Charts se pick karo)');
      post('dist/tip',{amount:amt,track_hash:hash,note:document.getElementById('tip-note').value},function(r){
        r=dataOf(r);
        if(is403(r)) return location.href=distAuth.loginUrl();
        if(r.error==='insufficient_funds') return toast('Balance kam hai — pehle top-up karo');
        if(r.success||r.message==='tip_sent'){ toast('Tip bhej di gayi!'); renderWallet(); }
        else toast('Tip send nahi hui — '+(r.message||'error'));
      });
    };
    getj('dist/tip?action=history',function(h){
      h=dataOf(h);
      var rows=[];
      (h.sent||[]).forEach(x=>rows.push('<div class="tk-row"><div class="tk-art" style="flex:1">Sent ₹'+esc(x.amount)+' to '+esc(x.to||x.artist||x.label||'artist')+'</div><span class="badge" style="background:#ffffff10;color:var(--mut);border:1px solid var(--line)">'+esc(String(x.time||x.created_at||'').slice(0,10))+'</span></div>'));
      (h.received||[]).forEach(x=>rows.push('<div class="tk-row"><div class="tk-art" style="flex:1">Received ₹'+esc(x.amount)+' from '+esc(x.from||x.user||'fan')+'</div><span class="badge live">'+(x.amount?'+₹'+esc(x.amount):'')+'</span></div>'));
      document.getElementById('tip-hist').innerHTML='<h3 style="font-size:14px;font-weight:800;margin-bottom:8px"><span class="mdi mdi-history" style="color:var(--mut)"></span> Recent activity</h3>'+
        (rows.length?rows.slice(0,15).join(''):emptyState('mdi-history','Abhi koi activity nahi.'));
    });
  });
}

var TABS={charts:renderCharts,clips:renderClips,radio:renderRadio,contests:renderContests,parties:renderParties,wallet:renderWallet};
function switchTab(t){ location.hash=t; TAB=t; render(); }
function render(){
  document.querySelectorAll('.nav-it').forEach(n=>n.classList.toggle('on',n.dataset.tab===TAB));
  (TABS[TAB]||renderCharts)();
}
document.querySelectorAll('.nav-it').forEach(n=>n.onclick=()=>switchTab(n.dataset.tab));
window.addEventListener('hashchange',()=>{TAB=location.hash.replace('#','')||'charts';render();});
var _m=(location.pathname.match(/^\/hub\/([a-z]+)/i)||[])[1];
if(_m && TABS[_m]){ TAB=_m; history.replaceState(null,'','/hub#'+_m); }
else if(location.hash){ var _h=location.hash.replace('#',''); if(TABS[_h]) TAB=_h; }

/* theme toggle */
function applyTheme(m){
  document.body.classList.toggle('light',m==='light');
  var ic=document.getElementById('theme-ic'),tx=document.getElementById('theme-txt');
  if(ic) ic.className='mdi '+(m==='light'?'mdi-weather-night':'mdi-white-balance-sunny');
  if(tx) tx.textContent=(m==='light'?'Dark mode':'Light mode');
}
applyTheme(localStorage.getItem('htx_studio_theme')||'dark');
var tg=document.getElementById('theme-tog');
if(tg) tg.onclick=function(){
  var m=document.body.classList.contains('light')?'dark':'light';
  localStorage.setItem('htx_studio_theme',m); applyTheme(m);
};
render();
</script>
</body>
</html>
