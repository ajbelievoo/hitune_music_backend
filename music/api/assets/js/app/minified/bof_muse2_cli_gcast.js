"use strict";class m2c_gcast{constructor(exeArgs,displayData,sourceData,eventHandler,callBack,ID){this.ID=ID
this.exeArgs=exeArgs
this.sourceData=sourceData
this.eventHandler=eventHandler?eventHandler:function(ID,eventName){this.log("UncatchedEvent: "+eventName,4)};this.callBack=callBack?callBack:$.Deferred();window.m2c_gcast_cache.id=this.ID;this.exe={run:()=>{this.log("Run.Start",4)
var runPromise=$.Deferred();var cjs=null;if(window.m2c_gcast_cache.client){cjs=window.m2c_gcast_cache.client}else{cjs=new Castjs({receiver:'CC1AD845',});cjs.on('available',()=>{if(!window.m2c_gcast_cache.cli)return;window.m2c_gcast_cache.cli.log("Event -> available")});cjs.on('connect',()=>{if(!window.m2c_gcast_cache.cli)return;window.m2c_gcast_cache.cli.log("Event -> connect")
window.m2c_gcast_cache.cli.event("loading")});cjs.on('disconnect',()=>{if(!window.m2c_gcast_cache.cli)return;window.m2c_gcast_cache.cli.log("Event -> disconnect")
window.m2_focus.gcast.disconnect();window.m2_queue.active.setFocus()
window.m2_queue.ui.previewFocus()});cjs.on('timeupdate',()=>{if(!window.m2c_gcast_cache.cli)return;window.m2c_gcast_cache.cli.log("Event -> timeupdate")
if(window.m2c_gcast_cache.client.duration>0&&window.m2c_gcast_cache.client.time>0&&window.m2c_gcast_cache.client.time>=Math.floor(window.m2c_gcast_cache.client.duration)-1)
window.m2c_gcast_cache.cli.event("ended");});cjs.on('statechange',()=>{if(!window.m2c_gcast_cache.cli)return;window.m2c_gcast_cache.cli.log("Event -> statechange: "+(window.m2c_gcast_cache.client?window.m2c_gcast_cache.client.state:"destroyed"))});cjs.on('playing',()=>{if(!window.m2c_gcast_cache.cli)return;window.m2c_gcast_cache.cli.log("Event -> playing")
window.m2c_gcast_cache.cli.event("playing");window.m2c_gcast_cache.promise.resolve()});cjs.on('pause',()=>{if(!window.m2c_gcast_cache.cli)return;window.m2c_gcast_cache.cli.log("Event -> pause")
window.m2c_gcast_cache.cli.event("paused")});cjs.on('end',()=>{if(!window.m2c_gcast_cache.cli)return;window.m2c_gcast_cache.cli.log("Event -> end")});cjs.on('buffering',()=>{if(!window.m2c_gcast_cache.cli)return;window.m2c_gcast_cache.cli.log("Event -> buffering")
window.m2c_gcast_cache.cli.event("loading")});cjs.on('error',(e,d)=>{if(!window.m2c_gcast_cache.cli)return;window.m2c_gcast_cache.cli.log("Event -> error: "+e)});window.m2c_gcast_cache.client=cjs}
var metadata={poster:displayData.cover_big,title:displayData.title,description:displayData.sub_title,}
if(!cjs.available){}
try{cjs.cast(sourceData.address,metadata)
window.m2c_gcast_cache.promise=runPromise}catch(Error){console.log("Casting FAILED -> "+Error)
runPromise.reject(Error)}
return runPromise},halt:()=>{this.log("Halt",1)
try{window.m2c_gcast_cache.client.seek(0);window.m2c_gcast_cache.client.pause()}catch(err){console.error(err)}}}
this.log=(text,level)=>{window.m2.log(this.ID+":m2c_gcast",text,level)}
this.event=(eventName,event)=>{this.log("Event: "+eventName,4)
if(this.ID!=window.m2c_gcast_cache.id){this.log("Event: "+eventName+" -> Failed. Different ID",4)
return}
this.eventHandler(this.ID,eventName)}
this.informer=(hook)=>{if(ID!=window.m2c_gcast_cache.id)
return;if(!window.m2c_gcast_cache.cli)
return;if(!window.m2c_gcast_cache.client)
return;if(hook=="seek"){return window.m2c_gcast_cache.client.time}else if(hook=="duration"){return window.m2c_gcast_cache.client.duration}else if(hook=="volume"){return window.m2c_gcast_cache.client.volumeLevel*100}else if(hook=="buffered"){return 0}}
this.controller=(action,data)=>{if(ID!=window.m2c_gcast_cache.id){this.log("ID Changed -> "+ID+" != "+window.m2c_gcast_cache.id);return}
if(!window.m2c_gcast_cache.cli)
return;if(!window.m2c_gcast_cache.client)
return;this.log("Controller -> "+action);if(action=="play"){window.m2c_gcast_cache.client.play()}else if(action=="pause"||action=="stop"){window.m2c_gcast_cache.client.pause()}else if(action=="seek"){window.m2c_gcast_cache.client.seek(data,!0)}else if(action=="set_volume"||action=="volume"){window.m2c_gcast_cache.client.volume(data?parseFloat(data/100):0)}else if(action=="full_screen"){return}else if(action=="set_speed"||action=="speed"){return}}
this.cleanup=()=>{this.log("Cleanup")
this.exe.halt();try{window.m2c_gcast_cache.client.pause()}catch(error){}
window.m2c_gcast_cache.cli=null;window.m2c_gcast_cache.id=null}
this.exe.run().done(()=>{this.log("Process.run -> Done");this.callBack.resolve()}).fail((err)=>{this.log("Process.run -> Failed");this.callBack.reject({message:err,skip:!0})});window.m2c_gcast_cache.cli=this}}
window.m2c_gcast_cache={cli:null,client:null,id:null};window.m2c_gcast=m2c_gcast