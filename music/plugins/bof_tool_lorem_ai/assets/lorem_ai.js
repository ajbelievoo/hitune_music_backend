"use strict";

window.bof_lorem_ai = {

  o: null,

  displaying: function(){
    return window.app._extension( "daterangepicker", true );
  },
  set: function(){
    window.bof_lorem_ai.o = window.ui.page.curr().args.becli[0].endpoint + "";
    window.ui.page.curr().args.becli[0].endpoint = "bofAdmin/setting/lorem_ai_"+window.ui.page.curr().args.urlData.url.match[0]+"/";
  },
  unset: function(){
    window.ui.page.curr().args.becli[0].endpoint = window.bof_lorem_ai.o;
  }

}
