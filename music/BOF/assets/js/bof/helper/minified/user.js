"use strict";window.user={logged:function(){var hasSessionID=cache.get("sess_id");if(hasSessionID)
return hasSessionID;return!1},loggedOut:function(loadLogin){if(cache.get("sess_id"))
cache.remove("sess_id");if(cache.get("sess_key"))
cache.remove("sess_key");if(cache.get("muse_que_i"))
cache.remove("muse_que_i");if(cache.get("muse_que"))
cache.remove("muse_que");if(loadLogin){try{var loginPage=window.app&&window.app.pages&&window.app.pages.login?"login":"user_auth";window.ui.page.load(loginPage).done(function(){window.ui.lock.offAll();window.ui.body.removeSplashClasses()})}catch(err){console.log(err)}}},online:function(){return navigator.onLine},_loginDesctionation:null,setLoginDestination:function($url){if($url.substr(0,$_bof_config.web_address.length)==$_bof_config.web_address)
this._loginDesctionation=$url.substr($_bof_config.web_address.length);},getLoginDestionation:function(){return this._loginDesctionation}}
