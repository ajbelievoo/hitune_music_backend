"use strict";window.bof_embed={waitForJQuery:function(){if(window.jQuery){console.log("jquery ready. Wait for assets")
window.bof_embed.waitForAssets()}else setTimeout(function(){window.bof_embed.waitForJQuery()},100)},waitForAssets:function(){console.log("waitForAssets")
if(window.bof&&window.m2&&window.ui){if(window.m2.app_ready!==!0){window.m2.app_ready()}
if(window.m2.all_parts_ready_cached){console.log("assets ready. Initialize embed")
window.bof_embed.ini()}else{setTimeout(function(){window.bof_embed.waitForAssets()},100)}}else setTimeout(function(){window.bof_embed.waitForAssets()},100)},ini:function(){window.bof_embed.data=_iniData;if(!window.bof_embed.data){window.bof_embed.error_handler("no_ini_data");return}
console.log("focus",_ot,_hash,_iniData);window.m2.user.listen();setTimeout(function(){window.m2_queue.set(_iniData)
window.m2_queue.active.setFocus()},1000);setTimeout(function(){$(document).find("a").attr("target","__blank")},2000)},error_handler:function($reason){console.log("FAILURE");console.log($reason)}};$(document).ready(function(){window.bof_embed.waitForJQuery()})