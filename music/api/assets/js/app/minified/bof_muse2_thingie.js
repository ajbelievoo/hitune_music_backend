"use strict";window.m2_thingie={log:function(text,level){window.m2.log("m2_thingie",text,level)},has:function(){var has=!1
if(window.m2.isEmbed()){has=!1}else if(window.m2_thingie.cache){has=!0}else if(window.m2_focus.setting.get("thingie_has")){has=!0
window.m2_thingie.check()}else{has=!1}
window.m2_thingie.log("has: "+(has?"yes":"no"))
return has},get:function(){return window.m2_thingie.cache[1]},getType:function(){return window.m2_thingie.cache[0]},check:function(){var promise=$.Deferred()
if(window.m2.isEmbed()){promise.reject()
return promise}
if(window.m2_thingie.cache){promise.resolve()
return promise}
if(window.m2_thingie.playing){promise.reject()
return promise}
var _mIni=null;var _mCheck=window.m2_focus.setting.get("check");var _mThingie=window.m2_focus.setting.get("thingie");var _mThingie_interval=window.m2.config.get("thingie_interval");if(window.m2_focus.setting.get("ini"))
_mIni=window.m2_focus.setting.get("ini");else{_mIni=window._g._mt();window.m2_focus.setting.set("ini",_mIni)}
var _m=_mIni;if(_mThingie)
_m=_mThingie;window.m2_thingie.log("thingie.ini: "+_mIni+" - "+((window._g._mt()-_mIni)/1000));window.m2_thingie.log("thingie.last: "+_mThingie+" - "+((window._g._mt()-_mThingie)/1000));window.m2_thingie.log("thingie.check: "+_mCheck+" - "+((window._g._mt()-_mCheck)/1000));window.m2_thingie.log("thingie.interval: "+_mThingie_interval);if(_mThingie_interval===-1?window._g._mt()-_mCheck<(1*60*1000):!1){window.m2_thingie.log("thingie.interval: disabled. return",3);promise.reject()
return promise}
if(window.m2.config.get("thingie_offset")?(window._g._mt()-_mIni<window.m2.config.get("thingie_offset")*60*1000):!1){window.m2_thingie.log("thingie.ini: fresh. return",3);promise.reject()
return promise}
if(_mThingie&&_mThingie_interval?window._g._mt()-_mThingie<_mThingie_interval*60*1000:!1){window.m2_thingie.log("thingie.last: fresh. return",3);promise.reject()
return promise}
window.becli.exe({liquid:!0,endpoint:"get_the_thingie",post:{placement:"bof_AUDI0"},callBack:function(sta,data){window.m2_focus.setting.set("check",window._g._mt());if(!sta){_mThingie_interval=-1;window.m2_thingie.log("thingie.get: none. return",3);window.m2_focus.setting.set("thingie_has",0);promise.reject()}else{window.m2_focus.setting.set("thingie_has",1);window.m2_thingie.cache=[data.thingie_type,data.thingie];window.m2_thingie.log("thingie.get: gotOne!",3);promise.resolve()}}});return promise},isPlaying:function(){return window.m2_thingie.playing},play:function(autoplay){var thingiePlayPromise=$.Deferred()
if(window.m2_thingie.playing){thingiePlayPromise.resolve()
window.m2_thingie.log("Already playing")
return thingiePlayPromise}
var ID=window._g.uniqid(10);var adData=window.m2_thingie.get()
var adType=window.m2_thingie.getType()
var cli_promise=$.Deferred()
if(window.m2.config.get("hotfix")?(window.m2.hotfix_inied?!window.m2.hotfix_inied.includes(adType):!0):!1){window.m2_thingie.log("Playing rejected. IOS hotfix. Users has not played "+adType+" source yet")
thingiePlayPromise.reject()
return thingiePlayPromise}
window.m2_focus.cancelPreSetObject();window.m2_focus.cancelPreSet();window.m2_focus.cancelPreCli();window.m2_focus.ui.clear_seeker()
window.m2_thingie.playing=autoplay;window.m2_thingie.ID=ID;var cli=new m2c({autoplay:!0,volume:100,muted:!1,speed:1},{title:"Ads"},[adType,adData],window.m2_thingie.eventHandler,cli_promise,ID)
cli_promise.done(function(){window.m2_thingie.log("Initiating cli -> Success",2,ID)
window.m2_thingie.seeker=window.m2_thingie.seek(!0)
if(!autoplay){window.m2_focus.setStatus("paused")
window.m2_focus.ui.hook()}
if(!window.m2.config.get("queue_disable_auto"))
window.m2_queue.ui.build()
thingiePlayPromise.resolve()
window.m2_thingie.log("CLI Running")}).fail(function(err){window.m2_thingie.log("Initiating cli -> Failure -> "+err.message,1,ID)
window.m2_thingie.errorHandler(err)
thingiePlayPromise.reject()})
$(document).find("#player").addClass("thingie_attached")
$(document).find("#player.thingie_attached .controls_wrapper").after("<div class='thingie_indicator'><div><a href='"+adData.url+"' target='_blank'>"+adData.title+"</a><a href='"+adData.url+"' target='_blank'>"+window.lang.return("sponsor",{ucfirst:!0})+"</a></div></div>")
$(document).find("#player.thingie_attached .data_wrapper a").attr("href",adData.url)
$(document).find("#player .data_wrapper .cover_holder div").css("background-image","url('"+adData.banner+"')")
window.m2_queue.ui.previewFocus()
window.m2_thingie.cli=cli
return thingiePlayPromise},seek:function($mother){if(window.m2_thingie.cli?window.m2_thingie.cli.informer:!1){var duration_p=window.m2_thingie.cli.informer("duration")
var seek_p=window.m2_thingie.cli.informer("seek")
duration_p.done(function(duration){seek_p.done(function(seek){if(duration&&seek){var remain=Math.round(duration-seek)
$(document).find(".queue #preview .text_wrapper #thingie_skip ._rt").text(remain)
var offset_ratio=Math.round(duration?seek/duration*100:0);$(document).find("#player .progress_bar .progress .progress_e").css("width",offset_ratio+"%")
var time_cur=window._g.duration_hr(seek);if(seek&&(seek>0||seek===0||seek==="0")&&$(document).find("#player .progress_bar .progress .time.cur").text()!=time_cur)
$(document).find("#player .progress_bar .progress .time.cur").text(time_cur);var time_tot=window._g.duration_hr(duration);if(duration&&(duration>0)&&$(document).find("#player .progress_bar .progress .time.tot").text()!=time_tot)
$(document).find("#player .progress_bar .progress .time.tot").text(time_tot)}
if(seek>window.m2.config.get("thingie_skipable_threshold")){$(document).find(".queue #preview .text_wrapper #thingie_skip .btn.cant_skip").removeClass("cant_skip")}})})}
setTimeout(function(){window.m2_thingie.seek()},500)},skip:function(){if(!window.m2.config.get("thingie_skipable")){window.m2_thingie.log("Skipping failed. Skipping disabled",1)
return}
var seek_p=window.m2_thingie.cli.informer("seek")
seek_p.done(function(seek){if(seek<window.m2.config.get("thingie_skipable_threshold")){window.m2_thingie.log("Skipping failed. Threshold not reached",1)
return}
window.m2_thingie.cli.controller("seek",99)})},eventHandler:function(ID,event){if(ID!=window.m2_thingie.ID)
return;if(event=="ended"){window.m2_thingie.cli.cleanup()
window.m2_focus.setting.set("thingie",window._g._mt())
window.m2_focus.setting.set("thingie_has",0)
window.m2_thingie.playing=!1
window.m2_thingie.cache=!1
clearTimeout(window.m2_thingie.seeker)
window.m2_thingie.seeker=null
window.m2_queue.ui.previewFocus()
setTimeout(function(){window.m2_queue.active.setFocus()},750)}
window.m2_thingie.log("eventHandler "+event,1)},errorHandler:function(err){window.m2_thingie.log("errorHandler "+err.message,1)}}