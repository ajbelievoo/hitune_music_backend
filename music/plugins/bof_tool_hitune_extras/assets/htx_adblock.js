/* HiTune AdBlock Blocker — bait element + bait script detection */
(function(){

  var done = false;
  var mode = (window.HTX_ADBLOCK_MODE || "overlay");
  var url  = (window.HTX_ADBLOCK_URL || "");

  function blocked(){
    if ( done ) return; done = true;
    if ( mode === "redirect" && url ){ location.href = url; return; }
    var el = document.createElement( "div" );
    el.style.cssText = "position:fixed;inset:0;z-index:999999;background:rgba(10,10,15,.97);color:#fff;display:flex;align-items:center;justify-content:center;text-align:center;font-family:system-ui,sans-serif;padding:24px";
    var btn = mode === "overlay" ? "<button onclick='this.parentNode.parentNode.parentNode.remove()' style='margin-top:18px;padding:10px 24px;border:0;border-radius:10px;background:#7c3aed;color:#fff;font-size:15px;cursor:pointer'>Continue anyway</button>" : "";
    el.innerHTML = "<div style='max-width:420px'><h2 style='margin:0 0 10px;font-size:22px'>Ad blocker detected</h2><p style='opacity:.75;margin:0;line-height:1.5'>Please disable your ad blocker and reload to keep HiTune Music free for everyone.</p>" + btn + "</div>";
    ( document.body || document.documentElement ).appendChild( el );
  }

  function check(){
    try {
      var bait = document.createElement( "div" );
      bait.className = "adsbox ad-banner adsbygoogle pub_300x250 text-ads";
      bait.style.cssText = "position:absolute;left:-9999px;top:-9999px;width:1px;height:1px;pointer-events:none";
      document.body.appendChild( bait );
      setTimeout( function(){
        var cs = getComputedStyle( bait );
        var hidden = bait.offsetHeight === 0 || cs.display === "none" || cs.visibility === "hidden";
        bait.remove();
        if ( hidden || !window.__htx_ads_ok ) blocked();
      }, 350 );
    } catch( e ){ if ( !window.__htx_ads_ok ) blocked(); }
  }

  var s = document.createElement( "script" );
  s.src = "/ads.js?_=" + Date.now();
  s.onerror = blocked;

  if ( document.readyState === "loading" )
  document.addEventListener( "DOMContentLoaded", function(){ document.head.appendChild( s ); check(); } );
  else { document.head.appendChild( s ); check(); }

})();
