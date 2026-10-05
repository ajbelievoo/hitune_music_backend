<?php
/**
 * Hitune AI Studio — full studio UI for /api/ai_studio.
 * Session comes from the BOF login (distAuth sess headers / hitune cookies).
 */
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AI Studio | Hitune Music</title>
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
html,body{height:100%}
body{font-family:Figtree,system-ui,sans-serif;background:var(--bg);color:var(--txt);overflow:hidden}
button{font-family:inherit}
::-webkit-scrollbar{width:9px;height:9px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:#ffffff14;border-radius:8px;border:2px solid var(--bg)}
::-webkit-scrollbar-thumb:hover{background:#ffffff28}
::selection{background:#8b5cf655}

/* ambient background */
.ambient{position:fixed;inset:0;pointer-events:none;z-index:0;overflow:hidden}
.ambient:before,.ambient:after{content:'';position:absolute;border-radius:50%;filter:blur(120px);opacity:.5}
.ambient:before{width:560px;height:560px;background:radial-gradient(circle,#00b7ff33,transparent 65%);top:-180px;left:8%;animation:drift 22s ease-in-out infinite alternate}
.ambient:after{width:640px;height:640px;background:radial-gradient(circle,#8b5cf62e,transparent 65%);bottom:-260px;right:-120px;animation:drift 26s ease-in-out infinite alternate-reverse}
.ambient .orb3{position:absolute;width:420px;height:420px;border-radius:50%;background:radial-gradient(circle,#fa2d4822,transparent 65%);top:35%;left:45%;filter:blur(130px);animation:drift 30s ease-in-out infinite alternate}
@keyframes drift{to{transform:translate(60px,40px) scale(1.08)}}

.studio{display:grid;grid-template-columns:248px 1fr 372px;height:100vh;position:relative;z-index:1}

/* ---------- left rail ---------- */
.rail{background:rgba(9,9,20,.72);backdrop-filter:blur(18px);border-right:1px solid var(--line);display:flex;flex-direction:column;padding:20px 14px;overflow-y:auto}
.brand{display:flex;align-items:center;gap:11px;padding:2px 6px 20px;text-decoration:none;color:#fff;border-bottom:1px solid var(--line);margin-bottom:8px}
.brand .lg-wrap{position:relative;width:44px;height:44px;flex:none}
.brand .lg-wrap:before{content:'';position:absolute;inset:-6px;border-radius:50%;background:radial-gradient(circle,#00b7ff66,transparent 70%);filter:blur(6px);animation:logoglow 3s ease-in-out infinite alternate}
@keyframes logoglow{to{opacity:.55;transform:scale(1.12)}}
.brand img{width:44px;height:44px;border-radius:12px;position:relative;box-shadow:0 4px 18px #00b7ff44}
.brand .txt b{font-size:17px;font-weight:900;letter-spacing:-.2px;display:block;line-height:1.15}
.brand .txt span{font-size:9.5px;font-weight:800;letter-spacing:2.4px;background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent}
.nav-label{font-size:9.5px;letter-spacing:1.8px;color:var(--mut);text-transform:uppercase;margin:16px 10px 7px;font-weight:800;opacity:.75}
.nav-it{display:flex;align-items:center;gap:11px;padding:9px 10px;border-radius:12px;color:var(--mut);cursor:pointer;font-size:13.5px;font-weight:600;border:1px solid transparent;transition:.18s;text-decoration:none;position:relative;margin-bottom:2px}
.nav-it .ic{width:32px;height:32px;border-radius:9px;display:grid;place-items:center;flex:none;background:#ffffff0a;border:1px solid var(--line);transition:.18s}
.nav-it .ic .mdi{font-size:17px}
.nav-it:hover{color:#fff;background:#ffffff08}
.nav-it:hover .ic{border-color:var(--line2)}
.nav-it.on{color:#fff;background:linear-gradient(120deg,#00b7ff1a,#8b5cf61f);border-color:#8b5cf640;box-shadow:0 4px 18px -6px #8b5cf655}
.nav-it.on:before{content:'';position:absolute;left:-14px;top:20%;bottom:20%;width:3px;border-radius:3px;background:var(--grad)}
.nav-it.on .ic{background:var(--grad);border-color:transparent;box-shadow:0 4px 14px #00b7ff55}
.nav-it.on .ic .mdi{color:#fff}
.nav-it .lock{margin-left:auto;font-size:13px;color:var(--gd);text-shadow:0 0 10px #ffb02066}
.t-song_gen .ic .mdi{color:#00b7ff}.t-cover_art .ic .mdi{color:#8b5cf6}.t-clip_video .ic .mdi{color:#fa2d48}
.t-karaoke .ic .mdi{color:#2ee6a8}.t-master .ic .mdi{color:#ffb020}.t-lyrics .ic .mdi{color:#f472b6}
.nav-it.on .ic .mdi{color:#fff!important}
.rail-foot{margin-top:auto;padding-top:16px}
.credits{background:linear-gradient(160deg,#ffffff09,#ffffff03);border:1px solid var(--line);border-radius:16px;padding:14px;font-size:12px;position:relative;overflow:hidden}
.credits:before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,#00b7ff88,transparent)}
.credits b{font-size:22px;font-weight:900;background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent}
.credits .bar{height:6px;border-radius:5px;background:#ffffff10;margin-top:9px;overflow:hidden}
.credits .bar i{display:block;height:100%;background:var(--grad);border-radius:5px;transition:width .6s ease;box-shadow:0 0 12px #00b7ff88}
.credits .planbadge{display:inline-block;margin-top:9px;font-size:9.5px;font-weight:800;letter-spacing:1.4px;padding:4px 10px;border-radius:20px;background:#8b5cf630;color:#c4b5fd;text-transform:uppercase;border:1px solid #8b5cf650}
.btn-up{display:block;text-align:center;margin-top:11px;padding:11px;border-radius:11px;background:var(--grad);color:#fff;font-weight:800;font-size:12.5px;text-decoration:none;letter-spacing:.3px;box-shadow:0 6px 20px -4px #8b5cf688;transition:.18s}
.btn-up:hover{transform:translateY(-1px);box-shadow:0 10px 26px -4px #8b5cf6aa;filter:brightness(1.08)}

/* ---------- center workspace ---------- */
.work{overflow-y:auto;padding:30px 40px 60px;position:relative}
.work h1{font-size:27px;font-weight:900;letter-spacing:-.5px;display:flex;align-items:center;gap:12px}
.work h1 .hic{width:46px;height:46px;border-radius:14px;background:var(--grad);display:grid;place-items:center;box-shadow:0 8px 26px -4px #8b5cf688;flex:none}
.work h1 .hic .mdi{font-size:24px;color:#fff}
.work .sub{color:var(--mut);font-size:13.5px;margin-top:8px;max-width:560px;line-height:1.55}
.statline{display:flex;gap:8px;margin-top:14px;flex-wrap:wrap}
.statline .pill{font-size:10.5px;font-weight:700;letter-spacing:.4px;padding:5px 12px;border-radius:20px;background:var(--panel2);border:1px solid var(--line);color:var(--mut)}
.statline .pill.ok{color:#4ade80;border-color:#4ade8038}
.statline .pill.warn{color:var(--gd);border-color:#ffb02038}
.card{background:var(--panel);border:1px solid var(--line);border-radius:20px;padding:24px;margin-top:22px;backdrop-filter:blur(14px);position:relative;box-shadow:0 18px 50px -20px #000c}
.card:before{content:'';position:absolute;top:0;left:24px;right:24px;height:1px;background:linear-gradient(90deg,transparent,#ffffff26,transparent);pointer-events:none}
.field{margin-bottom:16px}
.field label{display:block;font-size:10.5px;font-weight:800;letter-spacing:1.2px;text-transform:uppercase;color:var(--mut);margin-bottom:8px}
.field textarea,.field input,.field select{width:100%;background:rgba(0,0,0,.34);border:1px solid var(--line);border-radius:12px;color:var(--txt);padding:13px 15px;font-size:14px;font-family:inherit;resize:vertical;transition:.18s}
.field textarea:focus,.field input:focus{border-color:var(--pu);outline:none;box-shadow:0 0 0 3px #8b5cf622,0 0 24px -6px #8b5cf655}
.field textarea::placeholder,.field input::placeholder{color:#5c5c7d}
.chips{display:flex;flex-wrap:wrap;gap:7px;margin-top:10px}
.chip{padding:6px 14px;border-radius:20px;background:#ffffff08;border:1px solid var(--line);color:var(--mut);font-size:12px;font-weight:700;cursor:pointer;transition:.16s}
.chip:hover{border-color:var(--pu);color:#fff;transform:translateY(-1px)}
.chip.on{background:var(--grad);color:#fff;border-color:transparent;box-shadow:0 4px 14px -2px #8b5cf688}
.row{display:flex;gap:14px;flex-wrap:wrap}
.row .field{flex:1;min-width:140px}
.toggle{display:flex;align-items:center;gap:11px;font-size:13px;font-weight:600;color:var(--mut);cursor:pointer;user-select:none}
.toggle .tk{width:42px;height:23px;border-radius:20px;background:#ffffff1a;position:relative;transition:.2s;flex:none;border:1px solid var(--line)}
.toggle .tk:after{content:'';position:absolute;top:2px;left:2px;width:17px;height:17px;border-radius:50%;background:#777;transition:.2s}
.toggle.on{color:#fff}
.toggle.on .tk{background:var(--grad);border-color:transparent}
.toggle.on .tk:after{left:21px;background:#fff}
.big-btn{display:inline-flex;align-items:center;gap:9px;padding:15px 34px;border:0;border-radius:14px;background:var(--grad);color:#fff;font-weight:900;font-size:15px;cursor:pointer;transition:.18s;box-shadow:0 10px 30px -6px #8b5cf6aa;position:relative;overflow:hidden;letter-spacing:.2px}
.big-btn:after{content:'';position:absolute;top:0;left:-80%;width:50%;height:100%;background:linear-gradient(100deg,transparent,#ffffff40,transparent);transform:skewX(-20deg);transition:left .5s}
.big-btn:hover{transform:translateY(-2px);box-shadow:0 14px 38px -6px #8b5cf6cc;filter:brightness(1.07)}
.big-btn:hover:after{left:130%}
.big-btn:disabled{opacity:.45;cursor:not-allowed;transform:none;box-shadow:none;filter:none}
.tool-off{opacity:.5;pointer-events:none}
.note{font-size:12px;color:var(--mut);margin-top:14px;line-height:1.65}
.note code{background:#ffffff12;padding:1px 7px;border-radius:5px;color:#8fd0ff}
.notewarn{display:flex;align-items:flex-start;gap:10px;margin-top:16px;padding:12px 14px;border-radius:12px;background:#ffb02010;border:1px solid #ffb02030;color:#ffd68a;font-size:12.5px;line-height:1.55}
.notewarn .mdi{font-size:17px;flex:none;margin-top:1px}
.notewarn a{color:var(--cy);font-weight:700}
.engs{display:flex;gap:6px;flex-wrap:wrap;margin-top:14px;align-items:center}
.engs .lbl{font-size:10px;letter-spacing:1.2px;color:var(--mut);font-weight:800;text-transform:uppercase;margin-right:4px}
.engs .chip{cursor:default;font-size:10px;padding:4px 11px;background:#ffffff06}
.engs .chip .mdi{color:#4ade80;margin-right:3px}

/* ---------- right results ---------- */
.results{background:rgba(9,9,20,.72);backdrop-filter:blur(18px);border-left:1px solid var(--line);display:flex;flex-direction:column;overflow:hidden}
.results h2{font-size:13px;font-weight:800;letter-spacing:1.4px;text-transform:uppercase;padding:20px 20px 14px;display:flex;align-items:center;gap:9px;color:#cfcfe8}
.results h2 .mdi{color:var(--pu);font-size:18px}
.results h2 .cnt{margin-left:auto;font-size:10.5px;color:var(--mut);font-weight:700;background:var(--panel2);padding:3px 10px;border-radius:14px;border:1px solid var(--line)}
.res-list{overflow-y:auto;padding:2px 16px 18px;flex:1}
.res-card{background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:13px;margin-bottom:11px;animation:fadein .35s;transition:.18s;position:relative;overflow:hidden}
.res-card:hover{border-color:#8b5cf645;transform:translateY(-1px);box-shadow:0 10px 26px -10px #000d}
@keyframes fadein{from{opacity:0;transform:translateY(8px)}to{opacity:1}}
.res-top{display:flex;align-items:center;gap:11px}
.res-ic{width:40px;height:40px;border-radius:11px;background:var(--grad-soft);border:1px solid #8b5cf633;display:grid;place-items:center;flex:none}
.res-ic .mdi{font-size:20px;color:var(--cy)}
.res-card.is-img .res-ic{overflow:hidden;padding:0;border:0}
.res-card.is-img .res-ic img{width:100%;height:100%;object-fit:cover}
.res-name{font-size:13px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.res-meta{font-size:10.5px;color:var(--mut);margin-top:2px;text-transform:capitalize}
.res-st{margin-left:auto;font-size:9px;font-weight:800;letter-spacing:.8px;padding:4px 10px;border-radius:20px;text-transform:uppercase;flex:none}
.st-done{background:#0e3a24;color:#52e39a;border:1px solid #52e39a33}
.st-pending,.st-processing{background:#3a2f0e;color:#ffd166;border:1px solid #ffd16633}
.st-failed{background:#3a0e0e;color:#ff7b7b;border:1px solid #ff7b7b33}
.eq{display:none;align-items:flex-end;gap:3px;height:16px;margin-left:auto}
.st-processing .eq{display:flex}
.res-card .eq{margin-left:0}
.eq span{width:3px;border-radius:2px;background:linear-gradient(180deg,var(--cy),var(--pu));animation:eq 0.9s ease-in-out infinite}
.eq span:nth-child(2){animation-delay:.18s}.eq span:nth-child(3){animation-delay:.36s}.eq span:nth-child(4){animation-delay:.54s}
@keyframes eq{0%,100%{height:5px}50%{height:16px}}
.prog{display:none;height:3px;border-radius:3px;background:#ffffff10;margin-top:10px;overflow:hidden}
.res-card.is-busy .prog{display:block}
.prog i{display:block;height:100%;width:38%;border-radius:3px;background:var(--grad);animation:slide 1.4s ease-in-out infinite}
@keyframes slide{0%{margin-left:-38%}100%{margin-left:100%}}
.res-card audio{width:100%;margin-top:11px;height:34px}
.res-card video{width:100%;margin-top:11px;border-radius:10px;max-height:210px;background:#000}
.res-card img.art{width:100%;margin-top:11px;border-radius:11px}
.res-acts{display:flex;gap:7px;margin-top:11px}
.res-acts a{flex:1;text-align:center;font-size:11px;font-weight:700;padding:8px 6px;border-radius:9px;background:#ffffff0c;color:var(--cy);text-decoration:none;transition:.16s;border:1px solid transparent}
.res-acts a:hover{background:#00b7ff18;border-color:#00b7ff44}
.res-err{font-size:11px;color:#ff9b9b;margin-top:9px;line-height:1.5}
.res-empty{text-align:center;color:var(--mut);font-size:12.5px;padding:52px 14px;line-height:1.75}
.res-empty .mdi{font-size:46px;display:block;margin-bottom:12px;background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent;opacity:.8}

/* login gate */
#gate{display:none;height:100vh;place-items:center;text-align:center;position:relative;z-index:1}
#gate .card{max-width:400px;padding:40px 36px;margin:20px}
#gate .card h1{justify-content:center}
#gate img{width:76px;height:76px;border-radius:20px;margin-bottom:18px;box-shadow:0 10px 34px #00b7ff55}
#gate p{line-height:1.7}

@media (max-width:1100px){.studio{grid-template-columns:220px 1fr}.results{display:none}}
@media (max-width:760px){
  .studio{grid-template-columns:1fr}
  .rail{display:none}
  .work{padding:20px 18px 60px}
  .m-tools{display:flex!important}
}
.m-tools{display:none;gap:8px;overflow-x:auto;padding-bottom:6px;margin-bottom:16px;scrollbar-width:none}
.m-tools::-webkit-scrollbar{display:none}
.m-tools .nav-it{flex:none;margin:0}

/* ---------- light theme ---------- */
body.light{
  --bg:#eef0f7; --bg2:#f7f8fc; --panel:rgba(255,255,255,.72); --panel2:#ffffff;
  --line:rgba(20,20,50,.10); --line2:rgba(109,72,220,.4);
  --txt:#17172b; --mut:#5c5c7d;
}
body.light .ambient:before{background:radial-gradient(circle,#00b7ff26,transparent 65%)}
body.light .ambient:after{background:radial-gradient(circle,#8b5cf622,transparent 65%)}
body.light .ambient .orb3{background:radial-gradient(circle,#fa2d4818,transparent 65%)}
body.light .rail,body.light .results{background:rgba(255,255,255,.65);backdrop-filter:blur(18px)}
body.light .card{box-shadow:0 14px 40px -18px #2b2b6b33;background:var(--panel)}
body.light .field textarea,body.light .field input,body.light .field select{background:#fff;color:var(--txt)}
body.light .field textarea::placeholder,body.light .field input::placeholder{color:#9a9ab8}
body.light .nav-it{color:#4a4a68}
body.light .nav-it:hover{color:#17172b;background:#8b5cf60d}
body.light .nav-it .ic{background:#fff;border-color:rgba(20,20,50,.10)}
body.light .nav-it.on{color:#17172b;background:linear-gradient(120deg,#00b7ff1f,#8b5cf61f)}
body.light .chip{background:#fff}
body.light .chip.on{color:#fff}
body.light .credits{background:#fff;box-shadow:0 10px 26px -14px #2b2b6b2e}
body.light .res-card{background:#fff;box-shadow:0 8px 22px -12px #2b2b6b22}
body.light .res-card audio{filter:invert(.92) hue-rotate(180deg)}
body.light .toggle .tk{background:#d8d9ea}
body.light .engs .chip{background:#fff}
body.light .res-acts a{background:#f0f1fa}
body.light .note code{background:#8b5cf618;color:#6d48dc}
body.light .res-st.st-done{background:#e3f9ee}
body.light .res-st.st-pending,body.light .res-st.st-processing{background:#fff5dd}
body.light .res-st.st-failed{background:#ffe9e9}
body.light .brand{border-color:var(--line)}
body.light .statline .pill{background:#fff}

/* theme toggle */
.theme-tog{display:flex;align-items:center;gap:10px;margin-top:12px;padding:9px 12px;border-radius:11px;background:var(--panel);border:1px solid var(--line);cursor:pointer;color:var(--mut);font-size:12px;font-weight:700;transition:.16s;user-select:none}
.theme-tog:hover{color:var(--txt);border-color:var(--line2)}
.theme-tog .mdi{font-size:17px}

/* ---------- dashboard ---------- */
.dash-hero{display:flex;align-items:center;gap:16px;flex-wrap:wrap}
.dash-hero .hello{flex:1;min-width:220px}
.dash-hero .hello h1{font-size:26px}
.dash-hero .hello .sub{margin-top:5px}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-top:22px}
.stat{background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:16px 18px;position:relative;overflow:hidden;backdrop-filter:blur(12px)}
.stat:before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:var(--grad);opacity:.75}
.stat .ic2{position:absolute;right:12px;top:12px;font-size:26px;opacity:.18}
.stat .lbl{font-size:10px;font-weight:800;letter-spacing:1.1px;text-transform:uppercase;color:var(--mut)}
.stat .val{font-size:26px;font-weight:900;margin-top:6px;letter-spacing:-.5px}
.stat .val.grad{background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent}
.stat .mini{font-size:11px;color:var(--mut);margin-top:4px}
.dash-cols{display:grid;grid-template-columns:1.25fr 1fr;gap:16px;margin-top:18px}
@media (max-width:1300px){.dash-cols{grid-template-columns:1fr}}
.dcard{background:var(--panel);border:1px solid var(--line);border-radius:18px;padding:20px;backdrop-filter:blur(12px)}
.dcard h3{font-size:12px;font-weight:800;letter-spacing:1.2px;text-transform:uppercase;color:var(--mut);display:flex;align-items:center;gap:8px;margin-bottom:16px}
.dcard h3 .mdi{font-size:16px;color:var(--pu)}
.bars{display:flex;align-items:flex-end;gap:8px;height:110px;padding-top:6px}
.bars .bcol{flex:1;display:flex;flex-direction:column;align-items:center;gap:7px;height:100%;justify-content:flex-end}
.bars .b{width:100%;max-width:34px;border-radius:7px 7px 3px 3px;background:var(--grad);min-height:4px;position:relative;transition:height .5s ease;box-shadow:0 0 14px #8b5cf633}
.bars .b:hover{filter:brightness(1.25)}
.bars .b .tt{position:absolute;top:-22px;left:50%;transform:translateX(-50%);font-size:10px;font-weight:800;color:var(--mut);opacity:0;transition:.15s}
.bars .b:hover .tt{opacity:1}
.bars .bl{font-size:9.5px;color:var(--mut);font-weight:700;letter-spacing:.5px}
.tbar{display:flex;align-items:center;gap:10px;margin-bottom:11px}
.tbar .t-ic{width:30px;height:30px;border-radius:8px;background:var(--grad-soft);border:1px solid #8b5cf62e;display:grid;place-items:center;flex:none}
.tbar .t-ic .mdi{font-size:15px;color:var(--cy)}
.tbar .t-name{font-size:12px;font-weight:700;width:96px;flex:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.tbar .t-track{flex:1;height:7px;border-radius:5px;background:#ffffff12;overflow:hidden}
body.light .tbar .t-track{background:#00000010}
.tbar .t-fill{height:100%;border-radius:5px;background:var(--grad);min-width:3px;transition:width .6s ease}
.tbar .t-n{font-size:11px;font-weight:800;color:var(--mut);width:26px;text-align:right;flex:none}
.recent-it{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--line);font-size:12px}
.recent-it:last-child{border:0}
.recent-it .r-ic{width:28px;height:28px;border-radius:8px;background:var(--grad-soft);display:grid;place-items:center;flex:none}
.recent-it .r-ic .mdi{font-size:14px;color:var(--cy)}
.recent-it .r-t{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600}
.recent-it .r-w{color:var(--mut);font-size:10.5px;flex:none}
.recent-it .dot2{width:8px;height:8px;border-radius:50%;flex:none}
.d-done{background:#52e39a}.d-failed{background:#ff7b7b}.d-pending,.d-processing{background:#ffd166}
</style>
</head>
<body>

<div class="ambient"><div class="orb3"></div></div>

<div id="gate"><div class="card">
  <img src="/files/logo/26/09/22/6ab284f5cd5bd/hitune_icon_blue_6ab2810b469b5.png" alt="Hitune">
  <h1><span class="mdi mdi-auto-fix" style="background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent"></span>AI Studio</h1>
  <p class="sub" style="margin:14px 0 22px">Hitune Music account se login karo — AI se song banao, mastering karo, karaoke nikalo.</p>
  <a class="btn-up" id="login-link" href="#">Log in to continue</a>
</div></div>

<div class="studio" id="studio" style="display:none">

  <!-- left rail -->
  <aside class="rail">
    <a class="brand" href="/">
      <span class="lg-wrap"><img src="/files/logo/26/09/22/6ab284f5cd5bd/hitune_icon_blue_6ab2810b469b5.png" alt="Hitune"></span>
      <span class="txt"><b>Hitune</b><span>AI STUDIO</span></span>
    </a>

    <div class="nav-label">Studio</div>
    <div class="nav-it t-dash" data-tool="dashboard"><span class="ic"><span class="mdi mdi-view-dashboard"></span></span>Dashboard</div>

    <div class="nav-label">Create</div>
    <div class="nav-it t-song_gen" data-tool="song_gen"><span class="ic"><span class="mdi mdi-music-box-multiple"></span></span>Create Song <span class="lock mdi mdi-crown" id="lock-song_gen" style="display:none"></span></div>
    <div class="nav-it t-cover_art" data-tool="cover_art"><span class="ic"><span class="mdi mdi-image-auto-adjust"></span></span>Cover Art <span class="lock mdi mdi-crown" id="lock-cover_art" style="display:none"></span></div>
    <div class="nav-it t-clip_video" data-tool="clip_video"><span class="ic"><span class="mdi mdi-movie-open-outline"></span></span>Clip / Reel <span class="lock mdi mdi-crown" id="lock-clip_video" style="display:none"></span></div>

    <div class="nav-label">Tools</div>
    <div class="nav-it t-karaoke" data-tool="karaoke"><span class="ic"><span class="mdi mdi-microphone-variant"></span></span>Karaoke <span class="lock mdi mdi-crown" id="lock-karaoke" style="display:none"></span></div>
    <div class="nav-it t-master" data-tool="master"><span class="ic"><span class="mdi mdi-tune"></span></span>Mastering <span class="lock mdi mdi-crown" id="lock-master" style="display:none"></span></div>
    <div class="nav-it t-lyrics" data-tool="lyrics"><span class="ic"><span class="mdi mdi-text-box-outline"></span></span>Lyrics Sync <span class="lock mdi mdi-crown" id="lock-lyrics" style="display:none"></span></div>

    <div class="nav-label">Links</div>
    <a class="nav-it" href="/"><span class="ic"><span class="mdi mdi-home"></span></span>Hitune Music</a>
    <a class="nav-it" href="https://distribution.hitune.in/" target="_blank"><span class="ic"><span class="mdi mdi-rocket-launch"></span></span>Distribution</a>

    <div class="rail-foot">
      <div class="credits">
        <div style="display:flex;justify-content:space-between;align-items:baseline">
          <span style="color:var(--mut);font-size:10px;font-weight:800;letter-spacing:1.2px">CREDITS TODAY</span>
          <b id="cred-left">—</b>
        </div>
        <div class="bar"><i id="cred-bar" style="width:0%"></i></div>
        <span class="planbadge" id="plan-badge">free</span>
        <a class="btn-up" href="/subscription_plans">Upgrade for more</a>
      </div>
      <div class="theme-tog" id="theme-tog"><span class="mdi mdi-white-balance-sunny" id="theme-ic"></span><span id="theme-txt">Light mode</span></div>
    </div>
  </aside>

  <!-- workspace -->
  <main class="work">
    <div class="m-tools" id="m-tools"></div>
    <div id="work"></div>
  </main>

  <!-- results -->
  <aside class="results">
    <h2><span class="mdi mdi-sparkles"></span>Generation Results <span class="cnt" id="res-cnt"></span></h2>
    <div class="res-list" id="res-list">
      <div class="res-empty"><span class="mdi mdi-music-note-eighth"></span>Generated music aur<br>tools ke results yahan dikhenge.</div>
    </div>
  </aside>

</div>

<script src="/dist-auth.js"></script>
<script>
var apiHeaders = {
  'Content-Type': 'application/x-www-form-urlencoded',
  'x-bof-request-code': 'BusyOwlFrameWorkVersion201',
  'x-bof-platform': 'web',
  'x-bof-version': '2098'
};
distAuth.headers(apiHeaders);
var TOOL_STATE = { tools:{}, plan:{}, jobs:[], stats:null };
var ACTIVE = 'dashboard';
var POLL = null;

var GENRES = ['Hip Hop','Pop','R&B','EDM','Lo-Fi','Rock','Bollywood','Sad','Romantic','Punjabi','Cinematic','Trap'];

var TOOLS = {
  song_gen:  { icon:'mdi-music-box-multiple', title:'Create Music',  desc:'Prompt likho — AI poora song banayega (vocals ya instrumental).' },
  cover_art: { icon:'mdi-image-auto-adjust', title:'AI Cover Art',  desc:'Text prompt se HD album cover generate karo.' },
  clip_video:{ icon:'mdi-movie-open-outline',title:'Clip / Reel Maker', desc:'Kisi bhi gaane ka vertical reel-ready video clip.' },
  karaoke:   { icon:'mdi-microphone-variant',title:'Karaoke Studio', desc:'Vocals remove karo — instrumental stem milta hai (Demucs).' },
  master:    { icon:'mdi-tune',               title:'AI Mastering',  desc:'Studio-quality loudness & EQ polish (Matchering).' },
  lyrics:    { icon:'mdi-text-box-outline',   title:'Smart Lyrics Sync', desc:'Gaane se auto time-synced LRC lyrics (Whisper).' },
};

function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g, c => '&#'+c.charCodeAt(0)+';'); }
function post(body, cb){
  fetch('/api/ai_studio', { method:'POST', headers:apiHeaders, body:new URLSearchParams(body) })
    .then(r => r.json()).then(cb).catch(() => cb({ success:false }));
}

/* ---------- workspace render ---------- */
function toolOn(t){ var i = TOOL_STATE.tools[t]; return i && i.enabled !== false; }
function toolAllowed(t){ var i = TOOL_STATE.tools[t]; return !i || i.allowed !== false; }

function renderWork(){
  if (ACTIVE === 'dashboard') return renderDashboard();
  var w = document.getElementById('work');
  var t = TOOLS[ACTIVE];
  var info = TOOL_STATE.tools[ACTIVE] || {};
  var on = toolOn(ACTIVE), ok = toolAllowed(ACTIVE);
  var eng = (info.engines||[]).map(e => '<span class="chip"><span class="mdi mdi-check-circle"></span>'+esc(e)+'</span>').join('');

  var html = '<h1><span class="hic"><span class="mdi '+t.icon+'"></span></span>'+t.title+'</h1><p class="sub">'+t.desc+'</p>';

  html += '<div class="statline">'+
    '<span class="pill '+(on?'ok':'warn')+'">'+(on?'● Live':'● Off')+'</span>'+
    '<span class="pill '+(ok?'':'warn')+'">'+(ok?'Plan: included':'Plan: locked')+'</span>'+
    (info.quota!=null?'<span class="pill">'+info.quota+' / day</span>':'')+
    '</div>';

  html += '<div class="card'+(on&&ok?'':' tool-off')+'">';

  if (ACTIVE === 'song_gen'){
    html += '<div class="field"><label>Describe your song</label>'+
      '<textarea id="f-prompt" rows="4" maxlength="600" placeholder="e.g. emotional punjabi love song, female vocals, slow tempo, flute"></textarea>'+
      '<div class="chips">'+GENRES.map(g=>'<span class="chip" data-g="'+g+'">'+g+'</span>').join('')+'</div></div>'+
      '<div class="row">'+
        '<div class="field"><label>Track title</label><input id="f-title" placeholder="My AI Song" maxlength="80"></div>'+
        '<div class="field"><label>Length (sec, 5–190)</label><input id="f-seconds" type="number" value="45" min="5" max="190"></div>'+
      '</div>'+
      '<div class="row" style="align-items:center">'+
        '<div class="toggle" id="f-inst"><span class="tk"></span>Instrumental only (no vocals)</div>'+
        '<div style="flex:1"></div>'+
        '<button class="big-btn" id="f-go"><span class="mdi mdi-creation"></span>Create Track</button>'+
      '</div>'+
      '<p class="note">Song generation ek external AI provider se hota hai — admin panel me provider/API key configured hai to ye live hai. '+
      'Har generation 1 credit use karti hai. Plan upgrade se zyada credits milte hain.</p>';
  }
  else if (ACTIVE === 'cover_art'){
    html += '<div class="field"><label>Cover art prompt</label>'+
      '<textarea id="f-prompt" rows="3" maxlength="400" placeholder="e.g. neon-lit city skyline, retro synthwave album cover, 3D"></textarea></div>'+
      '<button class="big-btn" id="f-go"><span class="mdi mdi-creation"></span>Generate Cover</button>';
  }
  else if (ACTIVE === 'clip_video'){
    html += '<div class="field"><label>Track ID / hash</label><input id="f-track" placeholder="Track ID ya md5 hash"></div>'+
      '<div class="row">'+
        '<div class="field"><label>Start (sec)</label><input id="f-start" type="number" value="0" min="0"></div>'+
        '<div class="field"><label>Duration (5–90s)</label><input id="f-dur" type="number" value="30" min="5" max="90"></div>'+
      '</div>'+
      '<button class="big-btn" id="f-go"><span class="mdi mdi-creation"></span>Render Clip</button>'+
      '<p class="note">Clip banne ke baad app me "Publish as Reel on IyolMe" se seedha reel ban jayegi.</p>';
  }
  else { // karaoke / master / lyrics
    html += '<div class="field"><label>Track ID / hash</label><input id="f-track" placeholder="Track ID ya md5 hash"></div>';
    if (ACTIVE === 'master')
      html += '<div class="field"><label>Reference track ID (optional)</label><input id="f-ref" placeholder="Jaisa sound chahiye us gaane ka ID"></div>';
    var labels = { karaoke:'Make Karaoke', master:'Master This Track', lyrics:'Sync Lyrics' };
    html += '<button class="big-btn" id="f-go"><span class="mdi mdi-creation"></span>'+labels[ACTIVE]+'</button>';
  }
  html += '</div>';

  if (!on)  html += '<div class="notewarn"><span class="mdi mdi-alert-outline"></span><span>Ye tool abhi admin panel se off hai ya provider key missing hai.</span></div>';
  if (!ok)  html += '<div class="notewarn"><span class="mdi mdi-crown"></span><span>Ye tool tumhare plan me nahi hai — <a href="/subscription_plans">upgrade karo</a>.</span></div>';
  html += '<div class="engs"><span class="lbl">Engines</span>'+ (eng||'<span class="chip">—</span>') +'</div>';
  w.innerHTML = html;

  if (ACTIVE === 'song_gen'){
    w.querySelectorAll('.chip[data-g]').forEach(function(c){
      c.onclick = function(){ c.classList.toggle('on'); };
    });
    var tg = document.getElementById('f-inst');
    if (tg) tg.onclick = function(){ tg.classList.toggle('on'); };
  }
  var go = document.getElementById('f-go');
  if (go) go.onclick = submit;
}

/* ---------- dashboard ---------- */
function renderDashboard(){
  var w = document.getElementById('work');
  var s = TOOL_STATE.stats;
  var plan = (TOOL_STATE.plan.key||'free');
  if (!s){
    w.innerHTML = '<h1><span class="hic"><span class="mdi mdi-view-dashboard"></span></span>Dashboard</h1>'+
      '<p class="sub">Tumhara AI studio overview — credits, generations, activity.</p>'+
      '<div class="card"><p class="note" style="margin:0">Loading stats…</p></div>';
    return;
  }
  var rate = s.total ? Math.round(s.done/s.total*100) : 0;

  /* 7-day chart */
  var days = [];
  for (var i=6;i>=0;i--){
    var d = new Date(); d.setDate(d.getDate()-i);
    var k = d.toISOString().slice(0,10);
    days.push({ k:k, n:(s.days&&s.days[k])||0, l:d.toLocaleDateString(undefined,{weekday:'short'}) });
  }
  var mx = Math.max.apply(null, days.map(d=>d.n).concat([1]));
  var chart = '<div class="bars">'+days.map(function(d){
    return '<div class="bcol"><div class="b" style="height:'+Math.max(4,d.n/mx*100)+'%"><span class="tt">'+d.n+' jobs</span></div><span class="bl">'+d.l+'</span></div>';
  }).join('')+'</div>';

  /* per-tool bars */
  var typeMax = Math.max.apply(null, Object.keys(s.by_type||{}).map(k=>s.by_type[k]).concat([1]));
  var tnames = { song_gen:'Create Song', cover_art:'Cover Art', clip_video:'Clip/Reel', karaoke:'Karaoke', master:'Mastering', lyrics:'Lyrics Sync' };
  var tbars = Object.keys(s.by_type||{}).sort((a,b)=>s.by_type[b]-s.by_type[a]).map(function(k){
    var n = s.by_type[k];
    return '<div class="tbar"><span class="t-ic"><span class="mdi '+(ICONS[k]||'mdi-file')+'"></span></span>'+
      '<span class="t-name">'+esc(tnames[k]||k)+'</span>'+
      '<span class="t-track"><span class="t-fill" style="width:'+Math.round(n/typeMax*100)+'%"></span></span>'+
      '<span class="t-n">'+n+'</span></div>';
  }).join('') || '<p class="note" style="margin:0">Abhi tak koi generation nahi — pehla song banao!</p>';

  /* recent jobs */
  var recent = (TOOL_STATE.jobs||[]).slice(0,6).map(function(j){
    return '<div class="recent-it"><span class="r-ic"><span class="mdi '+(ICONS[j.type]||'mdi-file')+'"></span></span>'+
      '<span class="r-t">'+esc(jobTitle(j))+'</span>'+
      '<span class="dot2 d-'+esc(j.status)+'"></span>'+
      '<span class="r-w">'+esc((j.time_add||'').slice(5,16).replace('T',' '))+'</span></div>';
  }).join('') || '<p class="note" style="margin:0">No activity yet.</p>';

  w.innerHTML =
    '<div class="dash-hero"><div class="hello">'+
      '<h1><span class="hic"><span class="mdi mdi-view-dashboard"></span></span>Dashboard</h1>'+
      '<p class="sub">Tumhara AI studio overview — credits, generations aur activity ek nazar me.</p>'+
    '</div><span class="planbadge" style="font-size:11px;padding:7px 14px;border-radius:20px;background:#8b5cf630;color:#c4b5fd;border:1px solid #8b5cf650;font-weight:800;letter-spacing:1.2px;text-transform:uppercase">'+esc(plan)+' plan</span></div>'+

    '<div class="stats-grid">'+
      '<div class="stat"><span class="mdi mdi-lightning-bolt ic2"></span><div class="lbl">Credits left</div><div class="val grad">'+s.quota_left+'<span style="font-size:14px;color:var(--mut);font-weight:700"> / '+s.quota_limit+'</span></div><div class="mini">aaj ke liye</div></div>'+
      '<div class="stat"><span class="mdi mdi-music-note-eighth ic2"></span><div class="lbl">Total jobs</div><div class="val">'+s.total+'</div><div class="mini">'+s.today+' aaj</div></div>'+
      '<div class="stat"><span class="mdi mdi-check-decagram ic2"></span><div class="lbl">Completed</div><div class="val" style="color:#52e39a">'+s.done+'</div><div class="mini">'+rate+'% success rate</div></div>'+
      '<div class="stat"><span class="mdi mdi-timer-sand ic2"></span><div class="lbl">In queue</div><div class="val" style="color:#ffd166">'+s.running+'</div><div class="mini">'+s.failed+' failed</div></div>'+
    '</div>'+

    '<div class="dash-cols">'+
      '<div class="dcard"><h3><span class="mdi mdi-chart-bar"></span>Last 7 days activity</h3>'+chart+'</div>'+
      '<div class="dcard"><h3><span class="mdi mdi-tune-vertical"></span>Generations by tool</h3>'+tbars+'</div>'+
    '</div>'+

    '<div class="dcard" style="margin-top:16px"><h3><span class="mdi mdi-history"></span>Recent generations</h3>'+recent+'</div>';
}

function refreshStats(){
  post({ action:'stats' }, function(res){
    if (res.success && res.stats){
      TOOL_STATE.stats = res.stats;
      if (res.plan) TOOL_STATE.plan.key = res.plan;
      if (ACTIVE === 'dashboard') renderDashboard();
    }
  });
}

/* ---------- theme ---------- */
function applyTheme(mode){
  document.body.classList.toggle('light', mode==='light');
  var ic = document.getElementById('theme-ic'), tx = document.getElementById('theme-txt');
  if (ic) ic.className = 'mdi ' + (mode==='light' ? 'mdi-weather-night' : 'mdi-white-balance-sunny');
  if (tx) tx.textContent = (mode==='light' ? 'Dark mode' : 'Light mode');
}

/* ---------- submit ---------- */
function submit(){
  var body = { action:'submit', type:ACTIVE };
  var el = function(id){ return document.getElementById(id); };
  if (ACTIVE === 'song_gen'){
    var chips = Array.from(document.querySelectorAll('.chip.on')).map(c=>c.dataset.g);
    body.prompt = (el('f-prompt').value || '').trim();
    if (!body.prompt){ alert('Pehle song ka description likho'); return; }
    if (chips.length) body.tags = chips.join(', ');
    body.title = (el('f-title').value || '').trim();
    body.seconds = el('f-seconds').value || 45;
    body.instrumental = el('f-inst').classList.contains('on') ? 1 : 0;
  } else if (ACTIVE === 'cover_art'){
    body.prompt = (el('f-prompt').value || '').trim();
    if (!body.prompt){ alert('Prompt likho'); return; }
  } else {
    var v = (el('f-track').value || '').trim();
    if (!v){ alert('Track ID/hash do'); return; }
    if (/^\d+$/.test(v)) body.track_id = v; else body.track_hash = v;
    if (ACTIVE === 'master' && el('f-ref') && el('f-ref').value.trim()) body.reference_track_id = el('f-ref').value.trim();
    if (ACTIVE === 'clip_video'){ body.start = el('f-start').value||0; body.duration = el('f-dur').value||30; }
  }
  var go = document.getElementById('f-go');
  if (go){ go.disabled = true; setTimeout(function(){ go.disabled=false; }, 2500); }
  post(body, function(res){
    if (res.success && res.job){ refreshJobs(); refreshQuota(); refreshStats(); }
    else {
      var code = (res.data && res.data.code) || res.message || 'failed';
      if (code === 'quota_exceeded') alert('Aaj ka free quota khatam — kal try karo ya plan upgrade karo.');
      else if (code === 'plan_locked') alert('Ye feature tumhare plan me nahi hai — upgrade karo.');
      else if (code === 'tool_disabled') alert('Tool abhi off hai (provider key missing ya admin disabled).');
      else alert('Error: ' + code);
    }
  });
}

/* ---------- results ---------- */
var ICONS = { song_gen:'mdi-music-box-multiple', karaoke:'mdi-microphone-variant', master:'mdi-tune',
  lyrics:'mdi-text-box-outline', cover_art:'mdi-image-auto-adjust', clip_video:'mdi-movie-open-outline' };

function jobTitle(j){
  if (j.params && j.params.title) return j.params.title;
  if (j.params && j.params.prompt) return j.params.prompt.slice(0,60);
  return (TOOLS[j.type]||{}).title || j.type;
}

function renderJobs(){
  var list = document.getElementById('res-list');
  var jobs = TOOL_STATE.jobs;
  document.getElementById('res-cnt').textContent = jobs.length ? jobs.length+' jobs' : '';
  if (!jobs.length){
    list.innerHTML = '<div class="res-empty"><span class="mdi mdi-music-note-eighth"></span>Generated music aur<br>tools ke results yahan dikhenge.</div>'; return;
  }
  list.innerHTML = jobs.map(function(j){
    var img = (j.type==='cover_art' && j.result_url);
    var busy = (j.status==='pending' || j.status==='processing');
    var media = '';
    if (j.status==='done' && j.result_url){
      if (j.type==='cover_art') media = '<img class="art" src="'+esc(j.result_url)+'">';
      else if (j.type==='clip_video') media = '<video src="'+esc(j.result_url)+'" controls playsinline></video>';
      else if (/\.(mp3|wav|m4a|ogg)(\?|$)/i.test(j.result_url)) media = '<audio src="'+esc(j.result_url)+'" controls preload="none"></audio>';
      else if (/\.(lrc|srt|txt)(\?|$)/i.test(j.result_url)) media = '<a class="res-acts" href="'+esc(j.result_url)+'" target="_blank" style="display:block;text-align:center;padding:9px;border-radius:9px;background:#ffffff0c;color:var(--cy);font-size:11px;font-weight:700;margin-top:11px;text-decoration:none">Open lyrics file</a>';
    }
    var acts = '';
    if (j.status==='done' && j.result_url)
      acts = '<div class="res-acts"><a href="'+esc(j.result_url)+'" download>Download</a>'+
        '<a href="'+esc(j.result_url)+'" target="_blank">Open</a></div>';
    return '<div class="res-card'+(img?' is-img':'')+(busy?' is-busy':'')+'"><div class="res-top">'+
      '<div class="res-ic">'+(img?'<img src="'+esc(j.result_url)+'">':'<span class="mdi '+(ICONS[j.type]||'mdi-file')+'"></span>')+'</div>'+
      '<div style="min-width:0;flex:1"><div class="res-name">'+esc(jobTitle(j))+'</div>'+
      '<div class="res-meta">'+esc(j.type)+(j.engine?' • '+esc(j.engine):'')+'</div></div>'+
      (busy?'<div class="eq"><span></span><span></span><span></span><span></span></div>':'')+
      '<span class="res-st st-'+esc(j.status)+'">'+esc(j.status)+'</span></div>'+
      '<div class="prog"><i></i></div>'+
      media + acts +
      (j.error?'<div class="res-err">'+esc(j.error).slice(0,140)+'</div>':'')+
      '</div>';
  }).join('');
  clearTimeout(POLL);
  if (jobs.some(j => j.status==='pending' || j.status==='processing')) POLL = setTimeout(refreshJobs, 6000);
}

function refreshJobs(){
  post({ action:'list', limit:25 }, function(res){
    if (res.success && res.jobs){
      var hadBusy = TOOL_STATE.jobs.some(j => j.status==='pending' || j.status==='processing');
      var nowBusy = res.jobs.some(j => j.status==='pending' || j.status==='processing');
      TOOL_STATE.jobs = res.jobs; renderJobs();
      if (hadBusy && !nowBusy){ refreshQuota(); refreshStats(); }
    }
  });
}
function refreshQuota(){
  post({ action:'quota' }, function(res){
    if (!res.success || !res.quota) return;
    document.getElementById('cred-left').textContent = res.quota.left;
    document.getElementById('cred-bar').style.width = (res.quota.limit? Math.min(100,res.quota.left/res.quota.limit*100):0)+'%';
    document.getElementById('plan-badge').textContent = res.quota.plan || 'free';
  });
}
function refreshTools(){
  post({ action:'tools' }, function(res){
    if (res.success && res.tools){
      TOOL_STATE.tools = res.tools;
      Object.keys(res.tools).forEach(function(t){
        var l = document.getElementById('lock-'+t);
        if (l) l.style.display = (res.tools[t].allowed===false) ? 'inline' : 'none';
        var l2 = document.getElementById('mlock-'+t);
        if (l2) l2.style.display = (res.tools[t].allowed===false) ? 'inline' : 'none';
      });
      if (res.plan){ TOOL_STATE.plan = res.plan; document.getElementById('plan-badge').textContent = res.plan.key || 'free'; }
      renderWork();
    }
  });
}

document.addEventListener('DOMContentLoaded', function(){
  var s = distAuth.sess();
  if (!s.id || !s.key){
    document.getElementById('gate').style.display = 'grid';
    document.getElementById('login-link').href = distAuth.loginUrl();
    return;
  }
  /* theme */
  var savedTheme = localStorage.getItem('htx_studio_theme') || 'dark';
  applyTheme(savedTheme);
  document.getElementById('theme-tog').onclick = function(){
    var m = document.body.classList.contains('light') ? 'dark' : 'light';
    localStorage.setItem('htx_studio_theme', m);
    applyTheme(m);
  };

  /* mobile tool scroller */
  var mt = document.getElementById('m-tools');
  mt.innerHTML = '<div class="nav-it" data-tool="dashboard"><span class="ic"><span class="mdi mdi-view-dashboard"></span></span>Dashboard</div>' +
    Object.keys(TOOLS).map(function(k){
      return '<div class="nav-it t-'+k+(k===ACTIVE?' on':'')+'" data-tool="'+k+'"><span class="ic"><span class="mdi '+TOOLS[k].icon+'"></span></span>'+TOOLS[k].title+' <span class="lock mdi mdi-crown" id="mlock-'+k+'" style="display:none"></span></div>';
    }).join('');
  document.getElementById('studio').style.display = 'grid';
  document.querySelectorAll('.nav-it[data-tool]').forEach(function(n){
    if (n.dataset.tool === ACTIVE) n.classList.add('on');
    n.onclick = function(){
      document.querySelectorAll('.nav-it[data-tool]').forEach(x=>x.classList.remove('on'));
      document.querySelectorAll('.nav-it[data-tool="'+n.dataset.tool+'"]').forEach(x=>x.classList.add('on'));
      ACTIVE = n.dataset.tool;
      renderWork();
    };
  });
  refreshTools(); refreshQuota(); refreshJobs(); refreshStats();
});
</script>
</body>
</html>
