"use strict";class m2c_soundcloud{constructor(exeArgs,displayData,sourceData,eventHandler,callBack,ID){this.ID=ID
this.exeArgs=exeArgs
this.sourceData=sourceData
this.eventHandler=eventHandler?eventHandler:function(ID,eventName){window.m2c_soundcloud_cache.cli.log("UncatchedEvent: "+eventName,4)};this.callBack=callBack?callBack:$.Deferred();window.m2c_soundcloud_cache.id=this.ID;this.exe={pre:()=>{window.m2c_soundcloud_cache.cli.log("Pre.Start",4)
var assetPromise=$.Deferred();window.bof._loadExtension({name:"soundclud_widget_api",path:"api.js",base:"https://w.soundcloud.com/player/",dir:"",skipNameCheck:!0,cache:!1,version:!1}).done(function(){assetPromise.resolve();window.m2c_soundcloud_cache.cli.log("Pre.Done",4)}).fail(function(){assetPromise.reject("Loading Soundclud Widget Api failed")});return assetPromise},run:()=>{window.m2c_soundcloud_cache.cli.log("Run.Start",4)
var runPromise=$.Deferred();if(window.m2c_soundcloud_cache.iframe){window.m2c_soundcloud_cache.iframe.load("https://api.soundcloud.com/tracks/"+(this.sourceData.soundcloud_id),{autoplay:!1,show_comments:!0,show_user:!0,visual:!0,callback:function(){runPromise.resolve()}})}else{$(document).find("#players #soundcloud").html("<iframe\
                        id='soundcloud_iframe'\
                        allow=autoplay\
                        scrolling='no'\
                        frameborder='no'\
                        src='https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/"+(this.sourceData.soundcloud_id)+"&amp;auto_play=true&amp;show_comments=false&amp;show_user=true&amp;visual=true'\
                        ></iframe>");var sc_player=SC.Widget("soundcloud_iframe");sc_player.bind(SC.Widget.Events.READY,function(){window.m2c_soundcloud_cache.cli.log("Event READY");window.m2c_soundcloud_cache.cli.event("loaded");window.m2c_soundcloud_cache.cli.event("canplay");sc_player.bind(SC.Widget.Events.LOAD_PROGRESS,function(){window.m2c_soundcloud_cache.cli.event("loading");window.m2c_soundcloud_cache.cli.log("Loading")});sc_player.bind(SC.Widget.Events.PLAY_PROGRESS,function(){});sc_player.bind(SC.Widget.Events.PLAY,function(){window.m2c_soundcloud_cache.cli.event("playing");window.m2c_soundcloud_cache.cli.log("Playing")});sc_player.bind(SC.Widget.Events.PAUSE,function(){window.m2c_soundcloud_cache.cli.event("paused");window.m2c_soundcloud_cache.cli.log("Paused")});sc_player.bind(SC.Widget.Events.FINISH,function(){window.m2c_soundcloud_cache.cli.event("ended");window.m2c_soundcloud_cache.cli.log("Ended")});sc_player.bind(SC.Widget.Events.SEEK,function(){window.m2c_soundcloud_cache.iframe.isPaused(function(_p){if(!_p){window.m2c_soundcloud_cache.cli.event("seeked");window.m2c_soundcloud_cache.cli.log("seeked")}})});sc_player.bind(SC.Widget.Events.ERROR,function(){window.m2c_soundcloud_cache.cli.event("error");window.m2c_soundcloud_cache.cli.log("error")});sc_player.pause()
window.m2c_soundcloud_cache.iframe=sc_player;runPromise.resolve()});window.m2c_soundcloud_cache.widget=sc_player}
return runPromise},halt:()=>{window.m2c_soundcloud_cache.cli.log("Halt",1)
try{window.m2c_soundcloud_cache.iframe.pause()}catch($err){}
try{$(document).find(".soundcloud_iframe_wrapper").remove()}catch($err){}}}
this.log=(text,level)=>{window.m2.log(this.ID+":m2c_soundcloud",text,level)}
this.event=(eventName,event)=>{this.log("Event: "+eventName,4)
if(this.ID!=window.m2c_soundcloud_cache.id){this.log("Event: "+eventName+" -> Failed. Different ID",4)
return}
this.eventHandler(this.ID,eventName)}
this.informer=(hook)=>{if(ID!=window.m2c_soundcloud_cache.id)
return;if(!window.m2c_soundcloud_cache.cli)
return;if(!window.m2c_soundcloud_cache.iframe)
return;if(hook=="seek"){var seekPromise=$.Deferred();window.m2c_soundcloud_cache.iframe.getPosition(function(abs){seekPromise.resolve(Math.round(abs/1000))})
return seekPromise}else if(hook=="duration"){var durationPromise=$.Deferred();window.m2c_soundcloud_cache.iframe.getDuration(function(abs_duration){if(abs_duration)durationPromise.resolve(Math.round(abs_duration/1000));})
return durationPromise}else if(hook=="volume"){var volumePromise=$.Deferred();window.m2c_soundcloud_cache.iframe.getVolume(function(vol){if(vol)volumePromise.resolve(vol);})
return volumePromise}else if(hook=="buffered"){return 0}}
this.controller=(action,data)=>{if(ID!=window.m2c_soundcloud_cache.id)
return;if(!window.m2c_soundcloud_cache.cli)
return;if(!window.m2c_soundcloud_cache.iframe)
return;window.m2c_soundcloud_cache.cli.log("Controller -> "+action);if(action=="play"){window.m2c_soundcloud_cache.iframe.play()}else if(action=="pause"||action=="stop"){window.m2c_soundcloud_cache.iframe.pause()}else if(action=="set_volume"||action=="volume"){window.m2c_soundcloud_cache.iframe.setVolume(data)}else if(action=="seek"){window.m2c_soundcloud_cache.iframe.getDuration(function(abs_duration){window.m2c_soundcloud_cache.iframe.seekTo(abs_duration*(data/100))})}else if(action=="full_screen"){return!1}}
this.cleanup=()=>{window.m2c_soundcloud_cache.cli.log("Cleanup")
this.exe.halt();window.m2c_soundcloud_cache.cli=null;window.m2c_soundcloud_cache.id=null}
window.m2c_soundcloud_cache.cli=this;this.exe.pre().done(()=>{window.m2c_soundcloud_cache.cli.log("Process.pre -> Done");this.exe.run().done(()=>{window.m2c_soundcloud_cache.cli.log("Process.run -> Done");this.callBack.resolve()}).fail((err)=>{window.m2c_soundcloud_cache.cli.log("Process.run -> Failed");this.callBack.reject({message:"Running cli failed",skip:!0})})}).fail((err)=>{window.m2c_soundcloud_cache.cli.log("Process.pre -> Failed");this.callBack.reject({message:"Preparing cli failed",skip:!0})})}}
window.m2c_soundcloud_cache={cli:null,iframe:null,id:null}