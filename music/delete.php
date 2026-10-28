<?php
// Public account & data deletion page — required by Google Play "Data safety".
// Logged-in users can delete their account inline (password confirm -> user_delete endpoint).
if (session_status() === PHP_SESSION_NONE) session_start();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delete Account &amp; Data — Hitune Music</title>
<link rel="icon" href="/logo.png">
<style>
:root{--pad:14px}
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',system-ui,-apple-system,sans-serif}
body{background:#06060e;color:#e9e9f5;min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:40px 18px;position:relative;overflow-x:hidden}
.orb{position:fixed;border-radius:50%;filter:blur(110px);opacity:.30;pointer-events:none;z-index:0}
.orb1{width:520px;height:520px;background:#00b7ff;top:-180px;left:-140px}
.orb2{width:560px;height:560px;background:#8b5cf6;bottom:-200px;right:-160px}
.orb3{width:340px;height:340px;background:#fa2d48;top:38%;left:56%;opacity:.16}
.brand{display:flex;align-items:center;gap:14px;z-index:1;margin-bottom:26px}
.brand img{width:58px;height:58px;border-radius:16px;filter:drop-shadow(0 10px 30px rgba(0,183,255,.45))}
.brand .t{font-size:24px;font-weight:800;letter-spacing:-.5px}
.brand .t span{background:linear-gradient(120deg,#00b7ff,#8b5cf6);-webkit-background-clip:text;background-clip:text;color:transparent}
.card{width:100%;max-width:640px;background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.09);border-radius:22px;padding:34px 32px;box-shadow:0 30px 80px -30px rgba(0,0,0,.8),inset 0 1px 0 rgba(255,255,255,.06);backdrop-filter:blur(14px);z-index:1;margin-bottom:20px}
h1{font-size:30px;font-weight:800;letter-spacing:-.8px;margin-bottom:6px}
.sub{color:#8a8aa8;font-size:14px;line-height:1.6;margin-bottom:24px}
.sub b{color:#c9c9e8}
h2{font-size:15px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;color:#8fd0ff;margin:26px 0 12px;display:flex;align-items:center;gap:8px}
h2:before{content:"";width:22px;height:2px;background:linear-gradient(90deg,#00b7ff,#8b5cf6);border-radius:2px}
ol{margin-left:20px;color:#b9b9d4;font-size:14px;line-height:1.9}
ol li::marker{color:#8b5cf6;font-weight:700}
ul{margin-left:20px;color:#b9b9d4;font-size:14px;line-height:1.9}
ul li::marker{color:#00b7ff}
.note{margin-top:14px;font-size:13px;color:#6f6f92;line-height:1.6;background:rgba(139,92,246,.07);border:1px solid rgba(139,92,246,.18);border-radius:12px;padding:12px 14px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:none;border-radius:12px;padding:13px 28px;font-size:14px;font-weight:800;letter-spacing:.3px;cursor:pointer;text-decoration:none;transition:.18s;color:#fff}
.btn-primary{background:linear-gradient(135deg,#00b7ff,#8b5cf6 60%,#fa2d48 140%);box-shadow:0 10px 28px -8px rgba(139,92,246,.65)}
.btn-primary:hover{transform:translateY(-2px);filter:brightness(1.1)}
.btn-danger{background:linear-gradient(135deg,#ff4d6d,#c81e3f);box-shadow:0 10px 28px -8px rgba(250,45,72,.6)}
.btn-danger:hover{transform:translateY(-2px);filter:brightness(1.08)}
.btn-ghost{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);color:#d5d5ec}
.btn-ghost:hover{background:rgba(255,255,255,.11)}
.delbox{border:1px solid rgba(250,45,72,.28);background:rgba(250,45,72,.05);border-radius:16px;padding:20px;margin-top:10px}
.delbox input{width:100%;background:rgba(0,0,0,.35);border:1px solid rgba(255,255,255,.10);border-radius:12px;height:48px;padding:12px 16px;color:#f4f4fb;font-size:15px;margin:12px 0}
.delbox input:focus{outline:none;border-color:rgba(250,45,72,.55);box-shadow:0 0 0 3px rgba(250,45,72,.15)}
.msg{display:none;font-size:13px;padding:11px 14px;border-radius:10px;margin-top:12px}
.msg.err{background:rgba(250,45,72,.12);border:1px solid rgba(250,45,72,.35);color:#ff9fb0}
.msg.ok{background:rgba(0,200,120,.1);border:1px solid rgba(0,200,120,.3);color:#7ee8b8}
.checkrow{display:flex;align-items:flex-start;gap:10px;margin:14px 0;font-size:13px;color:#b9b9d4;line-height:1.5;cursor:pointer}
.checkrow input{margin-top:3px;accent-color:#fa2d48;width:16px;height:16px;cursor:pointer}
.footer{color:#55557a;font-size:12px;margin-top:8px;z-index:1;text-align:center;line-height:1.8}
.footer a{color:#8fd0ff;text-decoration:none}
.gate{text-align:center;padding:10px 0 4px}
.gate p{color:#b9b9d4;font-size:14px;margin-bottom:18px;line-height:1.6}
.hidden{display:none!important}
</style>
</head>
<body>
<div class="orb orb1"></div><div class="orb orb2"></div><div class="orb orb3"></div>

<div class="brand">
  <img src="/logo.png" alt="Hitune Music">
  <div class="t">Hitune <span>Music</span></div>
</div>

<div class="card">
  <h1>Delete Account &amp; Data</h1>
  <p class="sub"><b>Hitune Music</b> (by Hitune) — this page lets you delete your account and associated data, or request deletion of specific data. It applies to your account on <b>music.hitune.in</b> and the <b>Hitune Music</b> Android app.</p>

  <h2>Delete your account</h2>
  <div id="gateOut" class="gate hidden">
    <p>Log in to your Hitune Music account to delete it instantly from this page.</p>
    <a class="btn btn-primary" id="loginBtn" href="/userAuth?dback=/delete">Log in to continue</a>
    <p style="margin-top:16px;font-size:12.5px;color:#6f6f92">Or request deletion by email: <a href="mailto:support@hitune.in" style="color:#8fd0ff">support@hitune.in</a> — include the email address registered to your account. Requests are completed within 7 days.</p>
  </div>

  <div id="gateIn" class="hidden">
    <p style="color:#b9b9d4;font-size:14px;line-height:1.6;margin-bottom:6px">You are logged in as <b id="accMail" style="color:#fff"></b>. This action is <b style="color:#ff9fb0">permanent</b> — your profile, playlists, library, uploads and settings will be deleted.</p>
    <div class="delbox">
      <label class="checkrow"><input type="checkbox" id="ack"><span>I understand that deleting my account is permanent and removes my profile, playlists, liked songs, listening history, wallet balance and uploaded content.</span></label>
      <input type="password" id="pw" placeholder="Confirm your account password" autocomplete="current-password">
      <button class="btn btn-danger" id="delBtn" style="width:100%">Permanently delete my account</button>
      <div class="msg" id="msg"></div>
    </div>
  </div>

  <h2>Delete specific data (keep your account)</h2>
  <ul>
    <li><b>Playlists &amp; library items</b> — delete them anytime inside the app/website (Playlist → ⋮ → Delete).</li>
    <li><b>Listening history &amp; recommendations data</b> — Settings → Clear history.</li>
    <li><b>Downloaded / offline files</b> — removed by deleting downloads or uninstalling the app.</li>
    <li><b>Comments, clips &amp; uploads</b> — delete each item from its ⋮ menu.</li>
    <li><b>Anything else</b> — email <a href="mailto:support@hitune.in" style="color:#8fd0ff">support@hitune.in</a> with what you want removed; we process it within 7 days.</li>
  </ul>

  <h2>What we delete &amp; what we keep</h2>
  <ul>
    <li><b>Deleted:</b> profile, login credentials, playlists, likes, follows, listening history, saved items, uploaded tracks &amp; clips you own, comments and personalization data.</li>
    <li><b>Retained:</b> payment/payout transaction records and invoices are kept for up to <b>12 months</b> as required for tax/legal compliance; anonymized analytics are kept without personal identifiers. If you are a registered artist/label, content distributed to third-party platforms must be taken down via Hitune Distribution before deletion.</li>
  </ul>

  <div class="note">Account deletion is immediate and irreversible. A short backup retention of up to 30 days may apply before residual copies are purged.</div>
</div>

<div class="footer">
  <a href="/">Hitune Music</a> &nbsp;·&nbsp; <a href="mailto:support@hitune.in">support@hitune.in</a>
</div>

<script src="/dist-auth.js"></script>
<script>
var API = "/api/";
function bofHeaders(sess){
  var h = { "x-bof-request-code":"BusyOwlFrameWorkVersion201", "x-bof-platform":"web", "x-bof-version":"2074" };
  if (sess && sess.id){ h["x-bof-sess-id"]=sess.id; h["x-bof-sess-key"]=sess.key; }
  return h;
}
var sess = (window.distAuth && distAuth.sess) ? distAuth.sess() : null;
if (sess && sess.id){
  document.getElementById("gateIn").classList.remove("hidden");
  document.getElementById("accMail").textContent = sess.email || sess.name || "your account";
} else {
  document.getElementById("gateOut").classList.remove("hidden");
}

document.getElementById("delBtn").addEventListener("click", function(){
  var msg = document.getElementById("msg");
  msg.className = "msg"; msg.style.display = "none";
  if (!document.getElementById("ack").checked){ msg.className="msg err"; msg.style.display="block"; msg.textContent="Please tick the confirmation box first."; return; }
  var pw = document.getElementById("pw").value;
  if (!pw){ msg.className="msg err"; msg.style.display="block"; msg.textContent="Enter your password to confirm."; return; }
  var btn = this; btn.disabled = true; btn.textContent = "Deleting…";

  var body = new URLSearchParams({ password: pw }).toString();
  fetch(API + "user_delete", { method:"POST", headers: Object.assign({"Content-Type":"application/x-www-form-urlencoded"}, bofHeaders(sess)), body: body })
  .then(r=>r.json()).then(function(d){
    if (d && (d.success || d.message === "deleted")){
      msg.className="msg ok"; msg.style.display="block"; msg.textContent="Your account has been permanently deleted. Redirecting…";
      try{ localStorage.removeItem("dm_sess_id"); localStorage.removeItem("dm_sess_key"); }catch(e){}
      document.cookie="hitune_sess_id=;path=/;domain=.hitune.in;max-age=0";
      document.cookie="hitune_sess_key=;path=/;domain=.hitune.in;max-age=0";
      setTimeout(function(){ location.href="/"; }, 2500);
    } else {
      btn.disabled=false; btn.textContent="Permanently delete my account";
      msg.className="msg err"; msg.style.display="block";
      msg.textContent = (d && (d.message || (d.messages||[])[0])) === "wrong_old_password" ? "Wrong password — try again." : ("Deletion failed: " + ((d && (d.message||(d.messages||[])[0])) || "unknown error"));
    }
  }).catch(function(){
    btn.disabled=false; btn.textContent="Permanently delete my account";
    msg.className="msg err"; msg.style.display="block"; msg.textContent="Network error — please try again.";
  });
});
</script>
</body>
</html>
