"use strict";class m2c_audio{constructor(exeArgs,displayData,sourceData,eventHandler,callBack,ID){this.ID=ID
this.exeArgs=exeArgs
this.sourceData=sourceData
this.eventHandler=eventHandler?eventHandler:function(ID,eventName){this.log("UncatchedEvent: "+eventName,4)};this.callBack=callBack?callBack:$.Deferred();window.m2c_audio_cache.id=this.ID;this.exe={pre:()=>{this.log("Pre.Start",4)
var promise=$.Deferred();window.bof._loadExtension({name:"amplitudejs",path:"amplitude.js",base:"https://cdn.jsdelivr.net/npm/amplitudejs@5.3.2/dist/",dir:"",skipNameCheck:!0,version:!1}).done(function(){promise.resolve()}).fail(function(){promise.reject({hard:!0,message:"Loading amplitudejs failed"})});return promise},run:()=>{this.log("Run.Start",4)
var runPromise=$.Deferred();if(window.m2c_audio_cache.amplitude){var newSong=window.m2c_audio_cache.amplitude.addSong({name:displayData.title,artist:displayData.sub_title,url:sourceData.address,cover_art_url:displayData.cover});window.m2c_audio_cache.amplitude.skipTo(0,newSong);runPromise.resolve()}else{window.m2c_audio_cache.amplitude=Amplitude;window.m2c_audio_cache.amplitude.init({preload:"auto",continue_next:!1,delay:-1,songs:[{name:displayData.title,artist:displayData.sub_title,url:sourceData.address,cover_art_url:displayData.cover}],callbacks:{initialized:function(){runPromise.resolve()},stop:function(){if(window.m2c_audio_cache.cli)
window.m2c_audio_cache.cli.event("stopped");},loadstart:function(){if(window.m2c_audio_cache.cli)
window.m2c_audio_cache.cli.event("loading");},loadeddata:function(){if(window.m2c_audio_cache.cli)
window.m2c_audio_cache.cli.event("loaded");},pause:function(){if(window.m2c_audio_cache.cli)
window.m2c_audio_cache.cli.event("paused");},play:function(){if(window.m2c_audio_cache.cli)
window.m2c_audio_cache.cli.event("played");},playing:function(){if(window.m2c_audio_cache.cli)
window.m2c_audio_cache.cli.event("playing");},seeked:function(){if(window.m2c_audio_cache.cli)
window.m2c_audio_cache.cli.event("seeked");},canplay:function(){if(window.m2c_audio_cache.cli)
window.m2c_audio_cache.cli.event("canplay");},ended:function(){if(window.m2c_audio_cache.cli)
window.m2c_audio_cache.cli.event("ended");},error:function(err){if(window.m2c_audio_cache.cli)
window.m2c_audio_cache.cli.event("error",err);console.log(err)},abort:function(err){console.log(err)}}})}
runPromise.done(function(){window.m2c_audio_cache.amplitude.pause()});return runPromise},halt:()=>{this.log("Halt",1)
try{window.m2c_audio_cache.amplitude.pause();window.m2c_audio_cache.amplitude.stop();window.m2c_audio_cache.amplitude.removeSong(0)}catch(err){}}}
this.log=(text,level)=>{window.m2.log(this.ID+":m2c_audio",text,level)}
this.event=(eventName,event)=>{this.log("Event: "+eventName,4)
if(this.ID!=window.m2c_audio_cache.id){this.log("Event: "+eventName+" -> Failed. Different ID",4)
return}
if(eventName=="loaded"){if(window.m2c_audio_cache.amplitude.getSongDuration()===Infinity){window.m2_focus.ui.add_player_class("infinite_source")
window.m2_focus.cli.icy_reader()}}
this.eventHandler(this.ID,eventName)}
this.informer=(hook)=>{if(ID!=window.m2c_audio_cache.id)
return;if(!window.m2c_audio_cache.cli)
return;if(!window.m2c_audio_cache.amplitude)
return;if(hook=="seek"){return window.m2c_audio_cache.amplitude.getSongPlayedSeconds()}else if(hook=="duration"){return window.m2c_audio_cache.amplitude.getSongDuration()}else if(hook=="volume"){return window.m2c_audio_cache.amplitude.getVolume()}else if(hook=="buffered"){return window.m2c_audio_cache.amplitude.getBuffered()}}
this.controller=(action,data)=>{if(ID!=window.m2c_audio_cache.id)
return;if(!window.m2c_audio_cache.cli)
return;if(!window.m2c_audio_cache.amplitude)
return;this.log("Controller -> "+action);if(action=="play"){window.m2c_audio_cache.amplitude.play()}else if(action=="pause"||action=="stop"){window.m2c_audio_cache.amplitude.pause()}else if(action=="stop"){window.m2c_audio_cache.amplitude.stop();window.m2c_audio_cache.amplitude.removeSong(0)}else if(action=="set_volume"||action=="volume"){window.m2c_audio_cache.amplitude.setVolume(parseFloat(data))}else if(action=="set_speed"||action=="speed"){window.m2c_audio_cache.amplitude.setPlaybackSpeed(parseFloat(data))}else if(action=="seek"){window.m2c_audio_cache.amplitude.setSongPlayedPercentage(parseFloat(data))}else if(action=="full_screen"){return}}
this.cleanup=()=>{this.log("Cleanup")
this.exe.halt();window.m2c_audio_cache.cli=null;window.m2c_audio_cache.id=null}
this.exe.pre().done(()=>{this.log("Process.pre -> Done");this.exe.run().done(()=>{this.log("Process.run -> Done");this.callBack.resolve()}).fail((err)=>{this.log("Process.run -> Failed");this.callBack.reject({message:"Running cli failed",skip:!0})})}).fail((err)=>{this.log("Process.pre -> Failed");this.callBack.reject({message:"Preparing cli failed",skip:!0})});window.m2c_audio_cache.cli=this}}
window.m2c_audio_cache={cli:null,amplitude:null,id:null}