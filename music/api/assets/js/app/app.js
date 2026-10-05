"use strict";

window.app = {

  config: null,
  translations: null,
  cache: {
    color: "light",
    location: null,
    defaultClickAction: 'direct',
    scrollDisTop: 0
  },
  events: {

    bof_ready: function () {

      window.app.events.setGlobalListeners();

      $.when(
        window.app.getConfig(),
        window.bof._loadExtension({
          name: "lang",
          path: "app/lang.js",
        })
      ).done(function (
        configResult
      ) {

        var loadPlatformJss = window.app._loadPlatformJavas();

        // App JSs
        var loadAppJSs = window.app._loadAppJavas();

        // Theme CSS Files
        var loadThemeStylesStart = window._g._mt();
        var loadThemeStylesArray = [];
        var loadThemeStyles = $.Deferred();
        if ( window.bof._safeGet("app.config.theme.assets.css") ) {
          for (var i = 0; i < window.app.config.theme.assets.css.length; i++) {
            var themeStyle = window.app.config.theme.assets.css[i];
            if (!themeStyle) continue;
            loadThemeStylesArray.push(
              window.bof._loadCSS(themeStyle)
            );
          }
        } else {
          loadThemeStyles.resolve(0);
        }
        if (loadThemeStylesArray) {
          $.when.apply($, loadThemeStylesArray).done(function () {
            loadThemeStyles.resolve(window._g._pt(loadThemeStylesStart));
          });
        }

        // Theme JS Files
        var loadThemeJavasStart = window._g._mt();
        var loadThemeJavasArray = [];
        var loadThemeJavas = $.Deferred();
        if (window.bof._safeGet("app.config.theme.assets.js")) {
          for (var i = 0; i < window.app.config.theme.assets.js.length; i++) {
            var themeJava = window.app.config.theme.assets.js[i];
            loadThemeJavasArray.push(
              window.bof._loadExtension(themeJava)
            );
          }
        } else {
          loadThemeJavas.resolve(0);
        }
        if (loadThemeJavasArray) {
          $.when.apply($, loadThemeJavasArray).done(function () {
            loadThemeJavas.resolve(window._g._pt(loadThemeJavasStart));
          });
        }

        // Theme CSS Variables
        if (window.bof._safeGet("app.config.theme.css_variables")) {
          for (var i = 0; i < Object.keys(window.app.config.theme.css_variables).length; i++)
            var css_variable_name = Object.keys(window.app.config.theme.css_variables)[i];
          var css_variable_value = window.app.config.theme.css_variables[css_variable_name];
          document.documentElement.style.setProperty('--' + css_variable_name, css_variable_value);
        }

        // Translations
        var loadTranslations = window.lang.get();

        // ThemeParts
        var loadThemeParts = $.Deferred();
        loadTranslations.done(function () {
          window.app._loadParts().done(function (result) {
            loadThemeParts.resolve(result)
          }).fail(function (err) {
            loadThemeParts.reject(err);
          });
        });

        $.when(loadAppJSs, loadThemeParts, loadThemeStyles, loadThemeJavas, loadTranslations, loadPlatformJss).done(function (loadPartTime, loadStyleTime, loadJavaTime) {

          window.bof.log("Loading Theme: " + window.app.config.theme.name + " HTML done in " + loadPartTime);
          window.bof.log("Loading Theme: " + window.app.config.theme.name + " CSS done in " + loadStyleTime);
          window.bof.log("Loading Theme: " + window.app.config.theme.name + " JS done in " + loadJavaTime);

          if (!window.app.config.user.logged && window.user.logged)
            window.user.loggedOut(false);

          var initialPageAction;

          if (!window.user.online()) {
            initialPageAction = $.Deferred();
            initialPageAction.resolve();
            window.ui.body.addClass("offline_start", true);
            window.bof_offline.mode.on();
          }
          else if (
            !window.user.logged() && 
            ( window.app.config.setting.private || window.apk ) && 
            ( $_bof_config ? ( $_bof_config.requested_page ? $_bof_config.requested_page != "user_auth" : true ) : false ) 
          ) {
            initialPageAction = window.ui.page.load("user_auth");
          }
          else if ($_bof_config.requested_url) {

            if (window.location)
              window.app.cache.location = JSON.stringify(window.location);

            if ($_bof_config.requested_page == "404")
              initialPageAction = window.ui.page.load("404")
            else if ($_bof_config.requested_page == "403")
              initialPageAction = window.ui.page.load("403")
            else
              initialPageAction = window.ui.link.navigate($_bof_config.requested_url);

          }
          else {
            initialPageAction = window.ui.link.navigate("/");
          }

          initialPageAction.always(function () {

            var loadMenus = window.app._loadMenus();

            $.when.apply($, loadMenus).done(function () {

              window.app.events.setSidebarSta(true);
              window.ui.link.listen();
              window.ui.history.listen();
              window.bof_input.listen();
              window.bof_dropdown.listen();
              window.ui.body.removeSplashClasses();
              if ( window.m2 ){
                window.m2.app_ready()
                window.m2.user.listen();
              }
              if ( window.bof_offline )
              window.bof_offline.listen();
              window.chapar.listen();
              window.app.events.setGlobalListeners2();
              window.app.actions.search.listen();
              $(document).trigger("app_ready");

              document.querySelector(':root').style.setProperty('--window_height', $(window).height() + "px");
              $(window).on("resize", function (e) {
                document.querySelector(':root').style.setProperty('--window_height', $(window).height() + "px");
              });

              var color = null;

              if (window.cache.get("color"))
                color = window.cache.get("color");

              if (color) {
                window.ui.body.addClass(color, true);
                window.app.cache.color = color;
              }

              if (window.bof._safeGet("app.config.setting.additional_body_classes")) {
                for (var z = 0; z < window.app.config.setting.additional_body_classes.length; z++) {
                  window.ui.body.addClass(window.app.config.setting.additional_body_classes[z], true);
                }
              }

            });

          });

        });

      });

    },
    lock: {
      first_on: function ($name) { },
      on: function ($name) { },
      off: function ($name) { },
      all_off: function ($name) { }
    },

    page_unloading: function ($args) {
      window.config.events.page_unloading();
      $(document).trigger("page_unloading");
    },
    page_rendering: function ($args) {

      var becli = window.ui.page.get_data("becli");
      if (becli) {
        for (var i = 0; i < Object.keys(becli).length; i++) {
          var _k = Object.keys(becli)[i];
          var _v = becli[_k];
          if (_v["messages"] ? _v["messages"][0] == "404" : false) {
            window.ui.lock.off($args.name);
            var promise = $.Deferred();
            promise.reject();
            setTimeout(function () {
              window.ui.page.load("404");
            }, 100);
            return promise;
          }
        }
      }

      window.config.events.page_rendering();

    },
    page_ready: function ($args) {
      window.config.events.page_ready();
      $(document).trigger("page_ready");
    },
    historyPopState: function () {

      window.bof.log("History Pop");

      if (window.m2_queue.ui.isOpen()) {
        window.bof.log("History Pop -> MuseClose");
        window.m2_queue.ui.destroy()
        return "HALT";
      }

      if ( $("body").hasClass("active_modal") ){
        window.bof.log("History Pop -> modalClose");
        window.bof_modal.close();
        return "HALT";
      }

      if (window.pageBuilder.widget.item.open) {
        window.bof.log("History Pop -> bofButtonsClose");
        window.pageBuilder.widget.item.closeMenu();
        return "HALT";
      }

      if ( $("body").hasClass("sidebar_fl_show") ){
        window.bof.log("History Pop -> fSidebarClose");
        window.app.ui.fSidebar.close()
        return "HALT";
      }

      if ( $("body").hasClass("dropdown_active") ){
        window.bof.log("History Pop -> dropDownClose");
        window.bof_dropdown.close()
        return "HALT";
      }

      if ($("body").hasClass("mobile") && $("body").hasClass("open_menu")) {
        window.bof.log("History Pop -> Legacy:: MenuCloser");
        $("body").removeClass("open_menu");
        $(document).find(".sidebar .menu_parent .link_wrapper.parent.active").removeClass("active");
        return "HALT";
      }

    },

    setGlobalListeners: function () {

      $(document).on("contextmenu", function (e) {
        e.preventDefault();
      });
      $(document).on("click", "form .btn.submit", function (e) {

        var button = $(this);
        var form = $(this).parents("form");
        var validated = null;

        if (button.hasClass("loading")) return;

        form.find(".message_holder").removeClass("display");
        form.find(".message_holder .message").html(" ");

        if (form.hasClass("validate")) validated = form.valid();
        else validated = true;

        if (validated) {
          button.removeClass("failure").removeClass("success").addClass("loading");
          form.removeClass("failure").removeClass("success").addClass("loading").submit();
        }

      });
      $(document).on("click", ".header .i .icon.pre", function () {
        window.history.back();
      });
      $(document).on("click", "body .header .i.openSidebar", function () {
        if ( window.app.config.mobile || window.config.platform != "web" ){
          window.app.ui.fSidebar.toggle()
        } else {
          $("body").toggleClass("overlay_sidebar");
        }
      });
      $(document).on("click", ".widget.type_slider.liquid .arrows .arrow", function (e) {

        var $_dir = "ltr";
        var direction = $(this).attr("class").includes("next") ? "right" : "left";;
        var widget = $(this).parents(".widget");
        var slider = widget.find(".slider_wrapper")[0];
        var total_width = slider.scrollWidth;
        var visible_width = slider.offsetWidth;
        var item_width = $(slider).find(".item").outerWidth(true);
        var scroll_position = slider.scrollLeft;
        var visible_items = Math.floor(visible_width / item_width) > 1 ? Math.floor(visible_width / item_width) : 1;
        var to_right = ($_dir == "ltr" ? +1 : -1) * (direction == "right" ? +1 : -1) * (visible_items * item_width);
        var new_scroll_position = scroll_position + to_right;
        var max_scroll_position = total_width - visible_width;
        var final_scroll_position = 9999;
        var possible_scroll_poisitions = [];

        for (var x = 0; x <= max_scroll_position; x = x + item_width) {
          possible_scroll_poisitions.push(x);
        } possible_scroll_poisitions.push(max_scroll_position);
        possible_scroll_poisitions.forEach(function (x) {
          var __c = Math.abs(new_scroll_position - x);
          var __b = Math.abs(final_scroll_position - new_scroll_position);
          if (__b > __c) final_scroll_position = x;
        });

        if (visible_width + scroll_position == total_width && to_right > 0) final_scroll_position = 0;
        if (scroll_position == 0 && to_right < 0) final_scroll_position = max_scroll_position;

        $(slider).stop().animate({ scrollLeft: final_scroll_position + "px" }, 800);

      });
      $(document).on("click", ".lightSwitch", function (e) {

        var colors = [
          "dark",
          "grey",
          "light"
        ];

        var cur_color = window.app.cache.color;
        var new_color = colors[colors.indexOf(cur_color) + 1 > colors.length - 1 ? 0 : colors.indexOf(cur_color) + 1];

        window.app.cache.color = new_color;

        window.ui.body.removeClass("dark", true);
        window.ui.body.removeClass("grey", true);
        window.ui.body.removeClass("light", true);
        window.ui.body.addClass(new_color, true);
        window.cache.set("color", new_color);

      });
      $(document).on("click", ".currencySwitch", function (e) {

        window.bof_dropdown.close();

        var html = "<div class='options'>";
        for (var i = 0; i < Object.keys(window.app.config.currencies).length; i++) {

          var currency_code = Object.keys(window.app.config.currencies)[i];
          var currency_name = window.app.config.currencies[currency_code];
          var currency_selected = window.app.config.user.currency == currency_code ? " selected" : "";
          html += "<div class='option" + currency_selected + "' data-value='" + currency_code + "'>" + currency_name + "</div>";

        }
        html += "</div>";

        window.bof_modal.create({
          title: window.lang.return("change_currency", { ucfirst: true }),
          content: html,
          class: "options options_currencies"
        });

      });
      $(document).on("click", ".languageSwitch", function (e) {

        window.bof_dropdown.close();

        var html = "<div class='options'>";
        for (var i = 0; i < Object.keys(window.app.config.languages).length; i++) {

          var language_code = Object.keys(window.app.config.languages)[i];
          var language_name = window.app.config.languages[language_code];
          var language_selected = window.app.config.user.language == language_code ? " selected" : "";
          html += "<div class='option" + language_selected + "' data-value='" + language_code + "'>" + language_name + "</div>";

        }
        html += "</div>";

        window.bof_modal.create({
          title: window.lang.return("change_language", { ucfirst: true }),
          content: html,
          class: "options options_languages"
        });

      });
      $(document).on("click", ".modal.options_languages .options .option", function (e) {

        if ($(this).hasClass("selected"))
          return;

        if ( window.bof_offline_sw ){ window.bof_offline_sw.deleteBofClient(); }

        if (!window.user.logged()) {
          window.cache.set("language", $(this).data("value"));
          window.location.reload();
        }

        window.becli.exe({
          endpoint: "change_language",
          post: {
            code: $(this).data("value")
          },
          callBack: function (sta, data) {
            if (sta) {
              window.location.reload();
            }
          }
        })

      });
      $(document).on("click", ".modal.options_currencies .options .option", function (e) {

        if ($(this).hasClass("selected"))
          return;

        window.becli.exe({
          endpoint: "change_currency",
          post: {
            code: $(this).data("value")
          },
          callBack: function (sta, data) {
            if (sta) {
              window.location.reload();
            }
          }
        })

      });
      $(document).on("click", ".bof_alert", function (e) {

        var ID = $(this).data("alert-id");
        $(document).find(".bof_alert.ID_" + ID).remove();
        delete window.app.becli.__cache.alert[ID];
        window.app.becli._alert_sort();

      });
      $(document).on("click", ".bofForm ._submit", function (e) {

        var formDom = $(this).parents(".bofForm");
        var ID = formDom.attr("ID");
        var formData = null;

        var allBeclis = window.ui.page.data.becli;
        for (var i = 0; i < Object.keys(allBeclis).length; i++) {
          var _becliKey = Object.keys(allBeclis)[i];
          var _becliData = allBeclis[_becliKey];
          if (_becliData.bofForm || _becliData.bofForms) {

            var _becliForms = _becliData.bofForms ? _becliData.bofForms : [_becliData.bofForm];
            if (_becliForms) {
              for (var z = 0; z < _becliForms.length; z++) {
                var _becliForm = _becliForms[z];
                if (_becliForm.ID === ID && _becliForm.becli) {
                  formData = _becliForm;
                }
              }
            }

          }
        }

        if (formData) {

          if (formDom.hasClass("_loading"))
            return;

          window.app.ui.bofForm.loading(ID);

          var becliData = formData.becli;

          window.becli.exe({
            endpoint: becliData.endpoint,
            post: formDom.find("form").serialize(),
            callBack: function (sta, data) {
              if (sta)
                window.app.ui.bofForm.done(ID, {}, data);
              else
                window.app.ui.bofForm.failed(ID, {}, data);
            }
          });

        }

      });
      $(document).on("click", ".logout_handler", window.app.actions.user.logout)
      $("#main").scroll(function (e) {

        var pos_top = $("#main").scrollTop();

        if ( window.app.config.mobile ){

          var pre_pos_top = window.ui.history.get().scrollTop
          var dis = Math.abs( pos_top - pre_pos_top );

          var direction = pos_top > pre_pos_top ? "down" : "up"
          if ( direction == "down" && dis ){
            if ( $("#main").scrollTop() > 0 )
            window.app.cache.scrollDisTop = window.app.cache.scrollDisTop + dis;
            if ( window.app.cache.scrollDisTop > 50 )
              window.app.cache.scrollDisTop = 50
          } else if ( direction == "up" && dis ){
            window.app.cache.scrollDisTop = window.app.cache.scrollDisTop - dis;
            if ( window.app.cache.scrollDisTop < 0 )
              window.app.cache.scrollDisTop = 0
          }

          $(document).find(".header").css("--dis_top",(window.app.cache.scrollDisTop*2))

          if (pos_top > 120)
            $(document).find(".header").removeClass("resting").addClass("floating")
          else if ( pos_top < 30 ) {
            $(document).find(".header").addClass("resting").removeClass("floating")
          }
  
          if (pos_top > 400)
            $(document).find(".header").addClass("big_floating")
          else
            $(document).find(".header").removeClass("big_floating")

        }
        else {

          if (pos_top > 60)
            $(document).find(".header").removeClass("resting").addClass("floating")
          else
            $(document).find(".header").addClass("resting").removeClass("floating")
  
          if (pos_top > 400)
            $(document).find(".header").addClass("big_floating")
          else
            $(document).find(".header").removeClass("big_floating")

        }

        window.ui.history.record("scrollTop", pos_top);

        

      });
      $(document).on("ui_page_load_failure", function (e, args) {
        //if (!args.reason.endsWith("HALT"))
          // window.app.becli.alert(false, window.lang.return("pageload_failed", { ucfirst: true }));
      })
      $(document).on("click", ".modal.user_list .content ._user", function (e) {

        if ($(e.target).hasClass("btn"))
          return;

        window.bof_modal.close();
        window.ui.link.navigate($(this).data("url"))

      });
      $(document).on("click", ".modal.purchase .unlock_options .ugp .upi .btn", function (e) {

        if ($(this).hasClass("loading"))
          return;

        var btn = $(this);

        btn.addClass("loading")

        var item = btn.parents(".upi");
        var ot = item.data("ot");
        var hash = item.data("hash");

        if (ot == "user_subs_plan") {
          window.bof_modal.close();
          window.ui.link.navigate("subscription_plans?plan=" + hash);
          return;
        }

        window.app.actions.purchase(ot, hash, (sta, data) => {

          btn.removeClass("loading");

          var resultModalButtons = [];

          if (sta) {
            resultModalButtons.push(["btn-primary", window.lang.return("purchases", { ucfirst: true }), "window.ui.link.navigate( \"user_library?tab=purchased\" ); window.bof_modal.close(); window.bof_modal.close();"]);
          } else {
            resultModalButtons.push(["btn-primary", window.lang.return("add_funds", { ucfirst: true }), "window.ui.link.navigate( \"user_pay\" ); window.bof_modal.close(); window.bof_modal.close();"]);
          }

          window.bof_modal.create({
            class: "purchase_modal",
            title: data.messages[0],
            content: data.more,
            layer: 2,
            buttons: resultModalButtons
          });

        });

      });
      $(document).on("change", ".modal.share .embed_wrapper .options_wrapper .option ._i", function (e) {

        var _ot = $(document).find(".modal_wrapper .modal .content .embed_wrapper").data("ot");
        var _hash = $(document).find(".modal_wrapper .modal .content .embed_wrapper").data("hash");
        var _m = $(document).find(".modal.share .embed_wrapper .options_wrapper .option ._i[name=_media] option:selected").val();
        var _d = $(document).find(".modal.share .embed_wrapper .options_wrapper .option ._i[name=_theme] option:selected").val() == "dark" ? "?_dark&" : "?";
        var _c = $(document).find(".modal.share .embed_wrapper .options_wrapper .option ._i[name=_hex]").val();
        if (_c.substr(0, 1) == "#") _c = _c.substr(1);

        var _ns = "<iframe frameborder='0' height='136px' width='100%' src='" + ($_bof_config.endpoint_address + "muse_embed/" + _ot + "/" + _hash + "/" + _d + "_m_color=" + _c + "&_type=" + _m) + "'></iframe>"

        $(document).find(".modal.share .embed_wrapper #iframe_code").val(_ns);
        $(document).find(".modal_wrapper .modal .content .embed_wrapper #iframe_holder").html(_ns);

      });
      $(document).on("click", ".modal.share .sharers .etc", function (e) {
        window.app.actions.share.etc();
      });
      $(document).on("mouseover", ".bof_tooltip", function (e) {
        if ($(this).hasClass("tooltiped")) return;
        $(this).addClass("tooltiped");
        $(this).append("<div class='bof_tooltip_wrapper'>" + $(this).data("tip") + "</div>");
        window.bof_dropdown._exe($(this), $(this).find(".bof_tooltip_wrapper"));
        $(document).find(".bof_tooltip_wrapper").addClass("active")
      })
      $(document).on("mouseleave", ".bof_tooltip", function (e) {
        if (!$(this).hasClass("tooltiped")) return;
        $(this).removeClass("tooltiped");
        $(document).find(".bof_tooltip_wrapper").remove()
      })

      if ( window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone ) {
        window.ui.body.addClass("is_pwa", true);
      } else {
        window.ui.body.addClass("isnt_pwa", true);
      }

    },
    setGlobalListeners2: function () {

      if (!window.app.config.mobile) {
        $(document).on("contextmenu", ".item", function (e) {
          window.app.ui.clickReact(e, $(this), "desktop", "rightclick");
        });
        $(document).on("click", ".item", function (e) {
          window.app.ui.clickReact(e, $(this), "desktop", "click");
        });
      }
      else {

        $(document).on("click", ".sidebar .menu_parent .link_wrapper.parent a.parent", function (e) {

          if (!$(this).hasClass("download_menu_wrapper"))
            $(document).find(".bof_dropdown.file_list.active").removeClass("active")

          if (!$(this).hasClass("chapar_menu_wrapper"))
            $(document).find(".bof_dropdown.chapar_msgs.active").removeClass("active")

        });

        $(document).on("click", ".sidebar .menu_parent .link_wrapper.parent a.child", function (e) {
          $("body").removeClass("open_menu");
          $(document).find(".sidebar .menu_parent .link_wrapper.parent.active").removeClass("active");
        });

        function isBackdropFilterSupported() {
          const testElement = document.createElement("div");
          testElement.style.backdropFilter = "blur(1px)";
          return testElement.style.backdropFilter !== "";
        }

        if (isBackdropFilterSupported()) {
          window.ui.body.addClass("backdrop_support");
        } else {
          window.ui.body.addClass("backdrop_no_support");
        }

        $(document).on("dropdown_start",function(e,r){
          if ( r == "user_dropdown" || r == "chapar_drop" ){
            if ( !window.user.logged() ){
              window.bof_dropdown.close();
              window.user.loggedOut(true);
            }
          }
        })

        window.app.ui.fSidebar.listen();

      }

      window.pageBuilder.widget.item.listen();

    },
    setSidebarSta: function ($ini) {

      $ini = $ini === true;

      if (window.app.config.mobile)
        return;

      if ($ini) {
        $(window).on("resize", function () {
          window.app.events.setSidebarSta();
        })
      }

      if (window.innerWidth < 1050)
        window.ui.body.addClass("no_sidebar", true);

      else {

        var dCs = [];
        var curPage = window.ui.page.curr();
        if ( window.bof._safeGet( "data.becli.single.page.classes", curPage ) ) 
          dCs = window.ui.page.curr().data.becli.single.page.classes.split(" ");

        if ( window.bof._safeGet( "o_args.body_class", curPage ) ) 
          dCs = dCs.concat(window.ui.page.curr().o_args.body_class)

        if (!dCs.includes("no_sidebar"))
          window.ui.body.removeClass("no_sidebar", true);

      }

    }

  },

  becli: {
    __cache: { alert: {} },
    exe: function ($type, $typeArgs, $args) {

      var ID = window._g.uniqid();
      return window.becli.exe(
        $.extend(
          $args,
          {
            callBefore: window.app.becli._before,
            callBefore_param: {
              ID: ID,
              type: $type,
              typeArgs: $typeArgs
            },
            callBack: window.app.becli._after,
            callBack_param: {
              ID: ID,
              type: $type,
              typeArgs: $typeArgs
            }
          }
        )
      );

    },
    alert: function ($sta, $text) {

      var ID = window._g.uniqid();
      window.app.becli._before({
        args: {
          callBefore_param: {
            type: "alert",
            ID: ID,
          }
        }
      });

      window.app.becli._after($sta, {
        messages: [$text]
      }, {
        callBack_param: {
          type: "alert",
          ID: ID
        },
        args: {
          reload_after: false
        }
      });

    },
    _before: function ($args) {

      var display_args = $args.args.callBefore_param;
      if (display_args.type == "button") {
        if (display_args.typeArgs.dom.hasClass("bof_processing")) return "BOF_HALT"
        display_args.typeArgs.dom.removeClass("bof_done").removeClass("bof_fail").addClass("bof_processing").attr("disabled", "disabled")
        $(document).find(".groups_wrapper .group.failed_inputs").removeClass("failed_inputs");
      }
      else if (display_args.type == "alert") {
        $(document).find("body").append("<div class='bof_alert sta_loading unshown ID_" + display_args.ID + "' data-alert-id='" + display_args.ID + "'>\
          <span class='mdi mdi-"+ (display_args.mdi ? display_args.mdi : "airplane-takeoff") + " _icon'></span>\
          <span class='text'>"+ window.lang.return("processing_dots", { ucfirst: true }) + "</span>\
        </div>");
        window.app.becli.__cache.alert[display_args.ID] = "loading";
        window.app.becli._alert_sort();
      }

      $(document).find(".bof_input.failed").removeClass("failed");
      $(document).find(".setting_wrapper.failed").removeClass("failed");
      $(document).find(".setting_wrapper .error").remove();

    },
    _after: function (sta, data, $args) {
      var display_args = $args.callBack_param;

      if (display_args.type == "button") {
        display_args.typeArgs.dom.removeClass("bof_processing").addClass(sta ? "bof_done" : "bof_fail").attr("disabled", false).text(data.messages[0]);

        if (!sta ? data.bad_inputs : false) {

          for (var i = 0; i < data.bad_inputs.length; i++) {
            $(document).find(".bof_input[name=" + data.bad_inputs[i] + "]").addClass("failed");
            $(document).find(".setting_wrapper#item_" + data.bad_inputs[i]).addClass("failed");
            var bi_cs = $(document).find(".setting_wrapper#item_" + data.bad_inputs[i]).attr("class").split(" ");
            for (var i = 0; i < bi_cs.length; i++) {
              var bi_c = bi_cs[i];
              if (bi_c.startsWith("group_")) {
                $(document).find(".groups_wrapper .group[data-code=" + bi_c.substr(6) + "]").addClass("failed_inputs");
              }
            }
            if (data.inputs ? data.inputs.report.fail[data.bad_inputs[i]] : false) {
              $(document).find(".setting_wrapper#item_" + data.bad_inputs[i]).append("<div class='error'>" + data.inputs.report.fail[data.bad_inputs[i]] + "</div>")
            }
          }
        }
        else if (sta) {

          if (data.redirect)
            window.ui.link.navigate(data.redirect);

          else if ($args.args.reload_after !== false)
            window.ui.page.reload();

        }

      }
      else if (display_args.type == "alert") {

        var requestID = $args.callBack_param.ID;

        $(document).find(".bof_alert.ID_" + requestID).removeClass("sta_loading").addClass(sta ? "sta_done" : "sta_failed").find(".text").text(data["messages"][0])
        $(document).find(".bof_alert.ID_" + requestID).find("._icon").removeClass("mdi-airplane-takeoff").addClass(sta ? "mdi-robot-happy-outline" : "mdi-robot-dead-outline")
        window.app.becli.__cache.alert[requestID] = sta ? "done" : "failed";
        window.app.becli._alert_sort();

        if (sta && $args.args.reload_after === true) {
          window.ui.page.reload();
        }

        setTimeout(function () {
          $(document).find(".bof_alert.ID_" + requestID).remove();
          delete window.app.becli.__cache.alert[requestID];
          window.app.becli._alert_sort();
        }, 10000);

        if (data["messages"].length > 1) {

          for (var i = 1; i < data["messages"].length; i++) {
            var extraMessage = data["messages"][i];
            var extraMessageID = window._g.uniqid();
            $(document).find("body").append("<div class='bof_alert sta_" + (sta ? "done" : "failed") + " ID_" + extraMessageID + "' data-alert-id='" + extraMessageID + "'>\
              <span class='mdi "+ (sta ? "mdi-robot-happy-outline" : "mdi-robot-dead-outline") + " _icon'></span>\
              <span class='text'>"+ extraMessage + "</span>\
            </div>");
            window.app.becli.__cache.alert[extraMessageID] = sta ? "done" : "failed";
            window.app.becli._alert_sort();
            setTimeout(function () {
              $(document).find(".bof_alert.ID_" + extraMessageID).remove();
              delete window.app.becli.__cache.alert[extraMessageID];
              window.app.becli._alert_sort();
            }, 10000);
          }

        }

      }

      if ($args["args"]["c_callback"]) {
        $args["args"]["c_callback"](sta, data, $args);
      }

    },
    _alert_sort: function () {

      var pos = "bottom";
      var alerts = window.app.becli.__cache.alert;
      var alerts_offset = 0;

      if ($("body").hasClass("mobile")) {

        pos = "top";

      }
      else {

        if ($("body").hasClass("muse_active") && !$("body").hasClass("muse_hide"))
          alerts_offset = $(document).find("#player").outerHeight();

      }

      for (var i = Object.keys(alerts).length; i >= 0; i = i - 1) {
        var alert_id = Object.keys(alerts)[i - 1];
        var alert_dom = $(document).find(".bof_alert.ID_" + alert_id);
        alert_dom.css(pos, alerts_offset + 20 + "px");
        alerts_offset += alert_dom.outerHeight() + 10;
      }

    },
    _alert_reset: function () {
      window.app.becli.__cache.alert = {};
      $(document).find(".bof_alert").remove();
    }
  },
  ui: {
    scrollTo: function (element) {
      $("#main").animate({ scrollTop: element[0].offsetTop + "px" });
    },
    playHead: {
      active: false,
      set: function (data) {
        
        $(document).find(".header .playHead .title").text(data.head_play_title)
        $(document).find(".header").addClass("hasPlayHead");
        window.app.ui.playHead.active = true;

      },
      unset: function () {

        if (!window.app.ui.playHead.active)
          return;

        $(document).find(".header").removeClass("hasPlayHead");
        window.app.ui.playHead.active = false;

      }
    },
    actions: {
      hideSliderArrows: function () {

        $(".widget.type_slider.liquid").each(function (index, item) {
          var slider = $(item).find(".slider_wrapper")[0];
          if (slider.scrollWidth && slider.offsetWidth ? slider.scrollWidth - slider.offsetWidth < 50 : true)
            $(item).find(".arrows").remove();
        });

      },
    },
    bofForm: {
      loading: function ($ID, $args) {
        $(document).find("#" + $ID).removeClass("_failed").removeClass("_done").addClass("_loading");
        $(document).find("#" + $ID).find("._submit").html("<span class='mdi mdi-refresh'></span>");
      },
      done: function ($ID, $args, $result) {

        $(document).find("#" + $ID).removeClass("_loading").removeClass("_failed").addClass("_done");
        var _message = $result["messages"][0];

        if (_message.length < 30)
          $(document).find("#" + $ID).find("._submit").html($result["messages"][0]);

        else {
          $(document).find("#" + $ID).find("._submit").html(window.lang.return("successful", { ucfirst: true }));
          $(document).find("#" + $ID).find("._error").addClass("ok").html($result["messages"][0]);
        }

      },
      failed: function ($ID, $args, $result) {
        $(document).find("#" + $ID).removeClass("_loading").removeClass("_done").addClass("_failed");
        $(document).find("#" + $ID).find("._submit").html(window.lang.return("retry", { uc: true }));
        $(document).find("#" + $ID).find("._error").html("<span class='mdi mdi-alert-circle-outline'></span>" + $result["messages"][0]);
      },
    },
    modal: {
      user_list: function ($userList, $args) {

        var _content = "";

        for (var i = 0; i < $userList.length; i++) {
          var _user = $userList[i];
          _content += "<div class='_user hash_" + _user.hash + " " + (_user.class ? _user.class : "") + "' data-hash='" + _user.hash + "' data-url='" + _user.url + "' " + (_user.attr ? _user.attr : "") + ">";
          _content += "<div class='avatar'>" + _user.bof_file_avatar + "</div>";
          _content += "<div class='name'>" + _user.name_styled + "</div>";
          _content += "<div class='username'>" + _user.username + "</div>";
          if (_user.buttons) _content += "<div class='buttons'>" + _user.buttons + "</div>";
          _content += "</div>";
        }

        window.bof_modal.create({
          title: $args.title,
          tip: $args.tip,
          content: _content,
          class: "user_list",
          buttons: []
        });

      }
    },
    clickReact: function (e, target, deviceType, eventType) {

      var Actions = window.app.config.setting.touch;

      var Action = Actions[eventType];
      var Item = target;

      if ( deviceType == "mobile" ? target.parents(".item.hammered").length : false ){
        Item = target.parents(".item.hammered")
      }

      var ID = Item.attr("id").substr(4);
      var Data = window.pageBuilder.widget.item.get(ID);

      if ($(e.target).parents(".button_wrapper").length || $(e.target).hasClass("button_wrapper")) {
        var button = $(e.target).parents(".button_wrapper").length ? $(e.target).parents(".button_wrapper") : $(e.target);
        if (button.hasClass("more")) {
          window.pageBuilder.widget.item.openMenu(ID, e.clientX, e.clientY);
        }
        else if (button.hasClass("play")) {
          if ( Data.ot && Data.hash ){
            window.m2.user.request( "focus", Data.ot, Data.hash, Data.sources, Data);
          }
        }
        return;
      }

      if (Item.hasClass("no_action"))
        Action = "none";

      else if (Item.hasClass("action_menu"))
        Action = "menu";

      else if (Action == "play" && !Data.buttons.items.play)
        Action = "visit";

      else if (Data.buttons.play ? Data.buttons.play.AsAction : false)
        Action = "play"

      else if (target.parents(".widget").hasClass("playAsAction")) {
        if (eventType == "click" || eventType == "tap")
          Action = "play";
        else if (eventType == "rightclick" || eventType == "hold")
          Action = "menu"
        else
          Action = "visit"
      }

      else if (!Data.buttons.items.link)
        Action = "none"

      else if (ID == "main")
        Action = "menu"

      if (Action == "menu")
        window.pageBuilder.widget.item.openMenu(ID, e.clientX, e.clientY);
      else if (Action == "visit")
        window.ui.link.navigate(Data.url);
      else if (Action == "play"){
        if ( Data.ot && Data.hash )
        window.m2.user.request( "focus", Data.ot, Data.hash, Data.sources, Data);
      }

      $(document).trigger("clickReact", {
        Data: Data,
        Action: Action
      })

    },
    fSidebar: {
      state: "closed",
      listen: function(){
        $("body").on("click",function(){
          setTimeout(function(){
            window.app.ui.fSidebar.detectNSet();
          },25)
        })
        $(document).on("page_ready",function(){
          setTimeout(function(){
            window.app.ui.fSidebar.detectNSet(true);
            $(document).find(".header").css("--dis_top","0")
            window.app.cache.scrollDisTop = 0
          },30)
        })
        $(document).on("app_ready",function(){
          setTimeout(function(){
            window.app.ui.fSidebar.detectNSet(true);
          },30)
        })
      },
      toggle: function(){
        if ( window.app.ui.fSidebar.state == "closed" ){
          window.app.ui.fSidebar.open()
        } else {
          window.app.ui.fSidebar.close()
        }
      },
      open: function(){

        if ( window.app.ui.fSidebar.state == "opened" )
          return;

        window.ui.body.addClass("sidebar_fl_show", true);
        window.app.ui.fSidebar.state = "opened"

        $("body").off( "click", window.app.ui.fSidebar.listenOnClick );
        $("body").on( "click", window.app.ui.fSidebar.listenOnClick );
        window.bof_dropdown.close()

      },
      close: function(){

        if ( window.app.ui.fSidebar.state == "closed" )
          return;

        window.ui.body.removeClass("sidebar_fl_show", true);
        window.app.ui.fSidebar.state = "closed";

        $("body").off( "click", window.app.ui.fSidebar.listenOnClick );

      },
      listenOnClick: function(e){

        if ( $(e.target).hasClass("sidebar_fl") )
          return;

        if ( $(e.target).parents(".sidebar_fl").length )
          return;

        if ( $(e.target).hasClass("mm_menu") || $(e.target).parents(".mm_menu").length )
          return;

        if ( $(e.target).hasClass("pre_wrapper") || $(e.target).parents(".pre_wrapper").length )
          return;

        if ( window.app.ui.fSidebar.state == "closed" )
          return;

        window.app.ui.fSidebar.close()

      },
      detectNSet: function(cur){

        var detect = window.app.ui.fSidebar.detect(cur);

        if ( detect === null || detect === undefined ){
          return;
        }

        $(document).find("body.mobile .sidebar .menu_parent .link_wrapper.parent a.parent.active").removeClass("active")

        if ( typeof( detect ) == "string" ){
          $(document).find("body.mobile .sidebar .menu_parent .link_wrapper.parent a.parent#mn_"+detect).addClass("active")
        }
        else {
          $($(document).find("body.mobile .sidebar .menu_parent .link_wrapper.parent a.parent")[detect]).addClass("active")
        }

      },
      detect: function(cur){

        var is_menu = $("body").hasClass("sidebar_fl_show")
        var is_library = $(document).find("#user_dropdown.active").length
        var is_download = $(document).find("#download_drop.active").length
        var is_chapar = $(document).find("#chapar_drop.active").length
        var cur_url = window.ui.link.navigating_to.url.full

        if ( cur === true )
        cur_url = window.ui.page.curr().args.urlData.url.full;

        var one = null

        if ( is_menu )
          one = "menu"
        else if ( is_library )
          one = "library"
        else if ( is_download )
          one = "downloads"
        else if ( is_chapar )
          one = "notifications"
        else {

          var navIs = $(document).find(".sidebar .menu_wrapper").hasClass("num5") ? 5 : 4;

          for ( var navI=0; navI<navIs; navI++ ){

            var navI_dom = $(document).find("body.mobile .sidebar .menu_parent .link_wrapper.parent a.parent")[ navI ]
            var href = navI_dom.getAttribute("href");
            var navI_id = navI_dom.getAttribute("id").substr( 3 )

            if ( !href )
              continue;

            if ( href == cur_url ){
              one = navI
              break
            }

          }

        }

        return one;

      }
    },
  },
  actions: {
    user: {
      like: function (liking, objectType, objectHash) {

        if (liking === null || liking === undefined)
          liking = $(document).find("._like.bof_" + objectType + "_" + objectHash).hasClass("liked") ? false : true;

        var post = {
          object_type: objectType,
          object: objectHash
        };

        var playing = window.m2_focus.sourceData
        if (playing ?
          (playing.data ?
            playing.data.ot == objectType &&
            playing.data.hash == objectHash &&
            playing.source.type[0] == "youtube" &&
            playing.source.type[1].ID
            : false)
          : false) {
          post.yt_id = playing.source.type[1].ID;
          post.yt_key = window.cache.get("sess_google_key");
        }

        window.app.becli.exe("alert", {}, {
          endpoint: liking ? "like" : "unlike",
          post: post,
          ID: "userLikeOBject",
          c_callback: function (sta, data, args) {
            if ( window.bof_offline_sw ){ window.bof_offline_sw.deleteBofClient(); }
            if (sta) {

              $(document).find("._like.bof_" + objectType + "_" + objectHash).removeClass(liking ? "unliked" : "liked").addClass(liking ? "liked" : "unliked").attr("data-action", liking ? "unlike" : "like").find("._t").text(liking ? window.lang.return("unlike", { ucfirst: true }) : window.lang.return("like", { ucfirst: true }));
              
              try {
                if ( objectType ==  window.m2_queue.active.get().data.ot && objectHash == window.m2_queue.active.get().data.hash ){
                  if ( !liking ){
                    $(document).find("#player .buttons_wrapper .button.muse_like_handler").removeClass("liked").addClass("unliked").find(".mdi").addClass("mdi-heart-outline").removeClass("mdi-heart")
                  } else {
                    $(document).find("#player .buttons_wrapper .button.muse_like_handler").addClass("liked").removeClass("unliked").find(".mdi").removeClass("mdi-heart-outline").addClass("mdi-heart")
                  }            
                }
              } catch( err ){}

            }
          }
        });

      },
      subscribe: function (subscribing, objectType, objectHash) {

        if (subscribing === null || subscribing === undefined)
          subscribing = $(document).find("._subscribe.bof_" + objectType + "_" + objectHash).hasClass("subscribed") ? false : true;

        var post = {
          object_type: objectType,
          object: objectHash
        };

        window.app.becli.exe("alert", {}, {
          endpoint: subscribing ? "subscribe" : "unsubscribe",
          post: post,
          c_callback: function (sta, data, args) {
            if (sta) {
              if ( window.bof_offline_sw ){ window.bof_offline_sw.deleteBofClient(); }
              $(document).find("._subscribe.bof_" + objectType + "_" + objectHash).removeClass(subscribing ? "unsubscribed" : "subscribed").addClass(subscribing ? "subscribed" : "unsubscribed").attr("data-action", subscribing ? "unsubscribe" : "subscribe").find("._t")
                .text(
                  objectType == "user" ?
                    (subscribing ? window.lang.return("unfollow", { ucfirst: true }) : window.lang.return("follow", { ucfirst: true })) :
                    (subscribing ? window.lang.return("unsubscribe", { ucfirst: true }) : window.lang.return("subscribe", { ucfirst: true }))
                );
            }
          }
        });

      },
      playlist: {
        delete_confirm: function (playlist_id, data) {

          window.bof_modal.create({
            title: window.lang.return("confirm", { ucfirst: true }),
            tip: window.lang.return("confirm_remove_playlist", { ucfirst: true }),
            buttons: [
              ["btn-primary the_loading_button", window.lang.return("confirm", { ucfirst: true }), "window.app.actions.user.playlist.delete(\"" + playlist_id + "\")"]
            ]
          });

        },
        delete: function (playlist_id) {

          window.bof_modal.set_loading("button");
          window.becli.exe({
            endpoint: "playlist_remove",
            post: {
              id: playlist_id
            },
            callBack: function (sta, data, args) {
              if (sta) {
                window.bof_modal.close();
                window.app.becli.alert(true, window.lang.return("removed", { ucfirst: true }));
                window.app.getConfig(true);
              }
              else {
                window.bof_modal.finish_loading("button");
                window.bof_modal.set_error(data.messages[0]);
              }
            }
          });

        },
        edit_start: function (playlist_id, playlist_ini_data) {

          window.bof_modal.set_loading("initial");
          window.becli.exe({
            endpoint: "playlist_edit_ini",
            post: {
              id: playlist_id
            },
            callBack: function (sta, data, args) {
              if (!sta) {
                window.bof_modal.close();
                window.app.becli.alert(true, window.lang.return("not_found", { ucfirst: true }));
              }
              else {

                var pl = data.playlist;
                window.bof_modal.close();
                window.bof_modal.create({
                  title: window.lang.return("edit_playlist", { ucfirst: true }),
                  tip: pl.name,
                  inputs: {
                    name: {
                      label: window.lang.return("name", { ucfirst: true }),
                      tip: window.lang.return("choose_name", { ucfirst: true }),
                      input: {
                        type: "text",
                        name: "name",
                        value: pl.name
                      }
                    },
                    private: {
                      label: window.lang.return("privacy", { ucfirst: true }),
                      tip: window.lang.return("playlist_privacy", { ucfirst: true }),
                      input: {
                        type: "select_i",
                        name: "private",
                        options: [
                          ["0", window.lang.return("public", { ucfirst: true })],
                          ["1", window.lang.return("private", { ucfirst: true })],
                        ],
                        value: pl.private ? 1 : 0
                      }
                    },
                    cover_id: data.cover_input.data,
                    collabs: data.collabs_input.data,
                  },
                  buttons: [
                    ["btn-primary the_loading_button", window.lang.return("confirm", { ucfirst: true }), "window.app.actions.user.playlist.edit(\"" + playlist_id + "\")"]
                  ]
                });

              }
            }
          });

        },
        edit: function (playlist_id) {

          var modalData = window.bof_modal.get(true);

          if (!modalData.name) {
            window.bof_modal.set_error(window.lang.return("enter_a_name", { ucfirst: true }));
            return;
          }

          modalData.id = playlist_id;
          modalData.private = modalData.private === "1" ? 1 : 0;

          window.bof_modal.set_loading("button");

          window.becli.exe({
            endpoint: "playlist_edit",
            post: modalData,
            callBack: function (sta, data, args) {

              if (sta) {
                window.bof_modal.close();
                window.app.becli.alert(true, window.lang.return("edited", { ucfirst: true }));
                window.app.getConfig(true);
              }
              else {
                window.bof_modal.finish_loading("button");
                window.bof_modal.set_error(data.messages[0]);
              }

            }
          });

        },
        keep: function (playlist_id) {

          window.becli.exe({
            endpoint: "playlist_keep",
            post: {
              id: playlist_id
            },
            callBack: function (sta, data, args) {
              if (sta)
                window.app.becli.alert(true, window.lang.return("added", { ucfirst: true }));
              else
                window.bof_modal.set_error(data.messages[0]);
            }
          });

        },
        lose: function (playlist_id) {

          window.becli.exe({
            endpoint: "playlist_lose",
            post: {
              id: playlist_id
            },
            callBack: function (sta, data, args) {
              if (sta)
                window.app.becli.alert(true, window.lang.return("removed", { ucfirst: true }));
              else
                window.bof_modal.set_error(data.messages[0]);
            }
          });

        },
        shorten: function (playlist_id, item_ot, item_id, i) {

          window.becli.exe({
            endpoint: "playlist_shorten",
            post: {
              id: playlist_id,
              ot: item_ot,
              item: item_id,
              i: i
            },
            callBack: function (sta, data, args) {
              if (sta)
                window.app.becli.alert(true, window.lang.return("removed", { ucfirst: true }));
              else
                window.bof_modal.set_error(data.messages[0]);
            }
          });

        },
      },
      item_single_edit: {
        delete_confirm: function (object_type, object_hash, Data) {

          window.bof_modal.create({
            title: window.lang.return("confirm", { ucfirst: true }),
            tip: window.lang.return("confirm_remove_item", { ucfirst: true }),
            buttons: [
              ["btn-primary the_loading_button", window.lang.return("confirm", { ucfirst: true }), "window.app.actions.user.item_single_edit.delete(\"" + object_type + "\",\"" + object_hash + "\")"]
            ]
          });

        },
        delete: function (object_type, object_hash) {

          window.bof_modal.set_loading("button");
          window.becli.exe({
            endpoint: "user_edit_single_item_rem",
            post: {
              ot: object_type,
              oh: object_hash,
            },
            callBack: function (sta, data, args) {
              if (sta) {
                if ( window.bof_offline_sw ){ window.bof_offline_sw.deleteBofClient(); }
                window.bof_modal.close();
                window.app.becli.alert(true, window.lang.return("removed", { ucfirst: true }));
                window.app.getConfig(true);
              }
              else {
                window.bof_modal.finish_loading("button");
                window.bof_modal.set_error(data.messages[0]);
              }
            }
          });

        },
        edit_start: function (object_type, object_hash, Data) {

          window.bof_modal.set_loading("initial");
          window.bof_input.load_daterangepicker();

          window.becli.exe({
            endpoint: "user_edit_single_item_ini",
            post: {
              object_type: object_type,
              object_hash: object_hash
            },
            callBack: function (sta, data, args) {
              if (!sta) {
                window.bof_modal.close();
                window.app.becli.alert(true, window.lang.return("not_found", { ucfirst: true }));
              }
              else {

                window.bof_modal.close();
                window.bof_modal.create({
                  title: window.lang.return("edit", { ucfirst: true }),
                  tip: data.inputs.title.input.value,
                  class: "edit_single",
                  inputs: data.inputs,
                  groups: data.groups,
                  buttons: [
                    ["btn-primary the_loading_button", "Edit", "window.app.actions.user.item_single_edit.edit( \"" + object_type + "\", \"" + object_hash + "\" )"]
                  ]
                });

                $(document).find(".modal_wrapper .modal .groups .group.basic").click();

                setTimeout(function () {
                  $(document).find(".modal_wrapper .modal .groups .group_basic").click();
                  window.bof_input.hook_daterangepicker();
                }, 400);

              }
            }
          });

        },
        edit: function (object_type, object_hash) {

          var modalData = window.bof_modal.get(true);
          modalData.object_type = object_type;
          modalData.object_hash = object_hash;

          window.bof_modal.set_loading("button");

          window.becli.exe({
            endpoint: "user_edit_single_item",
            post: modalData,
            callBack: function (sta, data, args) {

              if (sta) {
                window.bof_modal.close();
                window.app.becli.alert(true, "Edited");
              }
              else {
                window.bof_modal.finish_loading("button");
                window.bof_modal.set_error(data.messages[0]);
              }

            }
          });

        },
      },
      logout: function () {

        window.becli.exe({
          endpoint: "user_logout",
          callBack: function () {
            if ( window.bof_offline_sw ){ window.bof_offline_sw.deleteBofClient(); }
            window.user.loggedOut(true);
            window.app.getConfig(true).done(function () {
              window.app._loadParts().done(function () {
                window.app._loadMenus();
              })
            });
          }
        });

      },
      unsubscribe: function( $id ){

        window.bof_modal.set_loading("button");
        window.becli.exe({
          endpoint: "cancel_subs_plan",
          post: {
            id: $id
          },
          callBack: function( sta, data ){
            if ( window.bof_offline_sw ){ window.bof_offline_sw.deleteBofClient(); }
            window.ui.page.reload();
            window.bof_modal.close();
          }
        })

      }
    },
    get_unlock_solution: function (object_type, object_hook, source_type, source_hook, source_id) {

      window.bof_modal.set_loading("initial");
      window.becli.exe({
        endpoint: "muse_unlock_solution",
        post: {
          object_type: object_type,
          object_hook: object_hook,
          source_type: source_type,
          source_hook: source_hook,
          source_id: source_id
        },
        callBack: function (sta, data, args) {
          if (!sta) {
            window.bof_modal.close();
          }
          else {

            window.bof_modal.close();

            var $html = "<div class='unlock_options'>";
            if (data.items) {
              $html += "<div class='direct ugp'>";
              for (var i = 0; i < data.items.length; i++) {
                var item = data.items[i];
                $html += "<div class='upi bof_" + (item.ot + "_" + item.hash) + (item.cover ? " has_cover" : " ") + " direct_i' data-ot='" + item.ot + "' data-hash='" + item.hash + "'>";
                $html += "<div class='cover'>" + item.cover + "</div>";
                $html += "<div class='name'>" + item.name + "<span class='type'>" + item.on + "</span></div>";
                $html += "<div class='price'>" + item.price + "</div>";
                $html += "<div class='btn btn-primary'>" + window.lang.return("buy", { ucfirst: true }) + "<div class='loader'></div></div>";
                $html += "</div>";
              }
              $html += "</div>";
            }
            if (data.subs_plans) {
              $html += "<div class='subs_plans ugp'>";
              $html += "<div class='upg_title'>" + window.lang.return("get_access_by_plans", { ucfirst: true }) + "</div>";
              for (var i = 0; i < data.subs_plans.length; i++) {
                var item = data.subs_plans[i];
                $html += "<div class='upi bof_" + (item.ot + "_" + item.hash) + (item.cover ? " has_cover" : " ") + " subs_plans_i' data-ot='" + item.ot + "' data-hash='" + item.hash + "'>";
                $html += "<div class='cover'>" + item.cover + "</div>";
                $html += "<div class='name'>" + item.name + "</div>";
                $html += "<div class='price'>" + item.price + "</div>";
                $html += "<div class='btn btn-primary'>" + window.lang.return("buy", { ucfirst: true }) + "<div class='loader'></div></div>";
                $html += "</div>";
              }
              $html += "</div>";
            }
            $html += "</div>";

            window.bof_modal.create({
              title: data.title,
              content: $html,
              class: "purchase",
              buttons: [
              ]
            });
          }
        }
      });

    },
    view_bio: function (object_type, object_hash) {

      window.bof_modal.set_loading("initial");
      window.becli.exe({
        endpoint: "view_bio",
        post: {
          object_type: object_type,
          object_hook: object_hash,
        },
        callBack: function (sta, data, args) {
          if (!sta) {
            window.bof_modal.close();
            window.app.becli.alert(false, data.messages[0]);
          }
          else {

            var attrs = data.data.attrs;
            var html = "";

            html += "<div class='bio_wrapper" + (attrs ? " hasAttr" : "") + "'>";
            if (attrs) {
              html += "<div class='attrs'>";
              if (data.data.cover)
                html += "<div class='avatar' style='background-image:url(\"" + data.data.cover + "\")'></div>";
              for (var i = 0; i < attrs.length; i++) {
                if (attrs[i][1])
                  html += "<div class='attr'><b>" + attrs[i][0] + "</b> " + attrs[i][1] + "</div>";
              }
              html += "</div>";
            }
            html += "<div class='content_wrapper def_scroll'><h4>" + data.data.name + "</h4>" + data.data.content + "</div>";
            html += "</div>";

            window.bof_modal.close();
            window.bof_modal.create({
              content: html,
              class: "biography",
            });

          }
        }
      });

    },
    purchase: function (object_type, object_hash, callBack) {

      var promise = window.becli.exe({
        endpoint: "purchase",
        post: {
          object_type: object_type,
          object_hash: object_hash
        },
        callBack: callBack
      })["promise"]
      
      promise.done(function(){
        if ( window.bof_offline_sw ){ window.bof_offline_sw.deleteBofClient(); }
      });

      return promise;

    },
    search: {
      inied: false,
      timer: false,
      history: false,
      suggs: false,
      listen: function(){
        $(document).on("change", "#search_query", function () {

          var query = $(document).find("#search_query").val();
          // window.ui.history.record("search_query", query);
  
        });
        $(document).on("input", "#search_query", function () {
          window.app.actions.search.exe();
        });

      },
      exe: function ($addToHistory) {
        if (this.timer)
          clearTimeout(this.timer);

        if ( $addToHistory !== false )
          $addToHistory = true;

        var query = $(document).find("#search_query").val();
        $(document).find(".widget.searchSuggs").slideUp(200);
        $(document).find(".widget.search_result_widget").remove();

        if (query) {

          $(document).find(".widget.type_search_form").addClass("loading");
          $(document).find("#main .widget:not(.type_search_form)").addClass("hidden").slideUp(150);

          this.timer = setTimeout(function () {
            var promises = [];
            window.becli.exe({
              ID: "search",
              endpoint: "search",
              post: {
                query: query
              },
              callBack: function (sta, data) {
                if (sta) {

                  for (var i = 0; i < Object.keys(data.widgets).length; i++) {
                    var ii = Object.keys(data.widgets)[i];
                    var renderWidgetPromise = window.app.actions.search.renderSearchResultWidget(data.widgets[ii]);
                    promises.push(renderWidgetPromise);
                  }

                  $.when.apply($, promises).done(function () {
                    $(document).find(".widget.type_search_form").removeClass("loading");
                    $(document).find("#main .widget.hidden:not(.searchSuggs)").removeClass("hidden").slideDown(150);
                    setTimeout(function () {
                      window.app.ui.scrollTo($(document).find(".widget.type_search_form"));
                      window.app.ui.actions.hideSliderArrows();
                    }, 200);
                  });

                  window.app.actions.search.history = data.history;
                  if ( $addToHistory )
                  window.ui.history.add( window.ui.page.curr().o_args.urlData.url.path + '?query=' + query );

                }
              }
            })
          }, 50);

        }
      },
      renderSearchResultWidget: function ($widget) {

        var promise = $.Deferred();
        window.ui.page.data.becli.single.widgets.push($widget);
    
        window.ui.theme.part("theme/parts/widget", {
          base: $_bof_config.assets_address,
          target: false
        }).done(function (HTML) {
          window.render.mix(HTML, $widget)
            .done(function (Data) {
              $(document).find(".widget.type_search_form").after(Data);
              promise.resolve();
            })
        });
    
        return promise;
    
      },
      ini: function () {

        if (this.inied) return;
        this.inied = true;

        $(document).on("clickReact", window.app.actions.search.crf);
        $(document).on("click", ".search_result_widget .item a", function () {
          var i = window.pageBuilder.widget.item.get($(this).parents(".item").attr("ID").substr(4));
          window.app.actions.search.submit(i.ot, i.hash)
        });

        var hasQuery = false;
        if ( window.location ? window.location.href : false ){
          var parseWindowHref = new URL( window.location.href );
          if ( parseWindowHref.searchParams.get("query") ){
            $(document).find("#search_query").val(  parseWindowHref.searchParams.get("query") )
            window.app.actions.search.exe(false);
            hasQuery = true;
          }
        }

        if ( !hasQuery ){
          var g = this.getSuggs();
          g.then(function(){
            window.app.actions.search.showSuggs();
          });
        }
        

      },
      unhook: function () {

        if (!this.inied) return;
        this.inied = false;

        setTimeout(function () {
          $(document).off("click", ".search_result_widget .item a");
          $(document).off("clickReact", window.app.actions.search.crf);
        }, 500)

      },
      submit: function (ot, h) {
        if ( !this.history )
          return;
        window.becli.exe({
          endpoint: "searchSubmit",
          liquid: true,
          post: {
            history: this.history,
            ot: ot,
            hash: h
          },
          callBack: function (sta, data) {
          }
        })
      },
      getSuggs: async function () {
        var p = $.Deferred();
        window.becli.exe({
          endpoint: "searchSuggs",
          post: {
            type: "ini"
          },
          callBack: function (sta, data) {
            if ( sta ){
              p.resolve();
              window.app.actions.search.suggs = data.suggestions;
            }
            else{
              p.reject();
            }
          }
        });
        return p;
      },
      showSuggs: function(){

        window.app.actions.search.renderSearchResultWidget(window.app.actions.search.suggs.history).done(function(){
          window.app.ui.actions.hideSliderArrows();
        });

        window.app.actions.search.renderSearchResultWidget(window.app.actions.search.suggs.popular).done(function(){
          window.app.ui.actions.hideSliderArrows();
        });

      },
      crf: function (e, d) {if (d.Action == "menu") return;window.app.actions.search.submit(d.Data.ot, d.Data.hash)}
    },
    share: {
      cache: null,
      ini: function (ini_data) {

        window.bof_modal.set_loading("initial");
        window.becli.exe({
          endpoint: "share",
          post: {
            object_type: ini_data.ot,
            object_hash: ini_data.hash
          },
          callBack: function (sta, data, args) {
            if (!sta) {
              window.bof_modal.close();
              window.app.becli.alert(false, window.lang.return("failed", { ucfirst: true }));
            }
            else {

              window.app.actions.share.cache = data.item;

              var groups = [];
              if (data.embedable)
                groups = [
                  ["share", "share"],
                  ["embed", "embed"]
                ];

              var _html = "<div class='a_content share_wrapper group_share " + (data.item.image ? "has_img" : "no_img") + "'>\
                "+ (data.item.image ? "<div class='cover_wrapper'><img src='" + data.item.image + "'></div>" : "") + "\
                <div class='title_wrapper'>"+ data.item.title + "</div>\
                <div class='url_wrapper'><input type='text' value='"+ data.item.url + "' readonly></div>\
                <div class='sharers'>\
                  <a class='tw' target='_blank' href='https://twitter.com/intent/tweet?text="+ encodeURIComponent(data.item.title) + "&url=" + encodeURIComponent(data.item.url) + "'><span class='mdi mdi-twitter'></span></a>\
                  <a class='fb' target='_blank' href='https://www.facebook.com/sharer.php?u="+ encodeURIComponent(data.item.url) + "'><span class='mdi mdi-facebook'></span></a>\
                  <a class='pi' target='_blank' href='https://pinterest.com/pin/create/button/?url="+ encodeURIComponent(data.item.url) + "&media=" + encodeURIComponent(data.item.image) + "&description=" + encodeURIComponent(data.item.title) + "'><span class='mdi mdi-pinterest'></span></a>\
                  <a class='rd' target='_blank' href='https://www.reddit.com/submit?title="+ encodeURIComponent(data.item.title) + "&url=" + encodeURIComponent(data.item.url) + "'><span class='mdi mdi-reddit'></span></a>\
                  <a class='etc' ><span class='mdi mdi-share-variant'></span></a>\
                </div>\
              </div>";

              if (data.embedable) {

                var dark_mode_string = window.app.cache.color === "light" ? "?" : "?_dark&";
                var theme_color = getComputedStyle(document.documentElement).getPropertyValue('--theme_color').trim().replaceAll(", ", ",").trim();
                var theme_color_hex = window._g.rgbToHex(theme_color);

                var iFrame_string = "<iframe frameborder='0' height='136px' width='100%' src='" + ($_bof_config.endpoint_address + "muse_embed/" + ini_data.ot + "/" + ini_data.hash + "/" + dark_mode_string + "_m_color=" + theme_color_hex) + "'></iframe>";

                var embedable_string = "";
                for (var _I = 0; _I < data.embedable.length; _I++) {
                  embedable_string += "<option value='" + data.embedable[_I] + "'>" + window.lang.return(data.embedable[_I], { ucfirst: true }) + "</option>";
                }


                _html += "<div class='a_content embed_wrapper group_embed hideByGroup' data-ot='" + ini_data.ot + "' data-hash='" + ini_data.hash + "'>\
                  <b>Code: </b>\
                  <input type='text' value='' id='iframe_code' readonly>\
                  <b>Options: </b>\
                  <div class='options_wrapper'>\
                    <div class='option'>\
                      <b>Media: </b>\
                      <select name='_media' class='_i'>"+ embedable_string + "</select>\
                    </div>\
                    <div class='option'>\
                      <b>Theme: </b>\
                      <select name='_theme' class='_i'><option>dark</option><option>light</option></select>\
                    </div>\
                    <div class='option'>\
                      <b>Color: </b>\
                      <input name='_hex' class='_i' type='text' value='"+ theme_color_hex + "'>\
                    </div>\
                  </div>\
                  <b>Preview: </b>\
                  <div id='iframe_holder'>"+ iFrame_string + "</div>\
                </div>";

              }

              window.bof_modal.close();
              window.bof_modal.create({
                class: "share",
                title: window.lang.return("share", { ucfirst: true }),
                tip: data.tip,
                content: _html,
                groups: groups,
              });
              setTimeout(function () {
                $(document).find("#iframe_code").val(iFrame_string);
              }, 100);

            }
          }
        });

      },
      etc: function () {
        if (navigator.share) {
          navigator.share({
            title: window.app.config.brand.name,
            text: window.app.actions.share.cache.title,
            url: window.app.actions.share.cache.url,
          })
            .catch((error) => window.app.becli.alert(false, error));
        } else {
          window.app.becli.alert(false, window.lang.return("failed", { ucfirst: true }))
        }
      }
    },
  },
  ads: {
    cache: {
      gaus: []
    },
    hook: function () {

      if (!window.app.config.setting.has_thingie)
        return;

      var ads = $(document).find("bof_thingie");
      if (!ads.length) return;

      var promise = $.Deferred();
      var promiseToLoadAll = [];

      for (var i = 0; i < ads.length; i++) {
        promiseToLoadAll.push(
          window.app.ads.loadItem($(ads[i]))
        );
      }

      $.when.apply($, promiseToLoadAll).done(function () {
        promise.resolve();
      });

      return promise;

    },
    loadItem: function ($dom) {

      window.becli.exe({
        endpoint: "get_the_thingie",
        post: {
          placement: $dom.html()
        },
        callBack: function (sta, data) {
          if (sta) {

            if (data.html ? data.html.type == "gau" : false) {

              var GD = data.html.data;

              var promiseToLoadJS = $.Deferred();
              if (!window.app.ads.cache.gaus.includes(GD.client)) {

                window.bof._loadExtension({
                  name: "gau_" + GD.client,
                  path: "adsbygoogle.js?client=" + GD.client,
                  base: "https://pagead2.googlesyndication.com/pagead/js/",
                  dir: "",
                  skipNameCheck: true,
                  version: false,
                }).done(function () {
                  promiseToLoadJS.resolve();
                  window.app.ads.cache.gaus.push(GD.client);
                }).fail(function () {
                  promiseToLoadJS.reject("Loading GoogleAdUnit.js failed");
                });

              } else {
                promiseToLoadJS.resolve();
              }

              promiseToLoadJS.done(function () {
                $dom.parents(".widget.type_ads").addClass("display");
                $dom.html('<center><ins class="adsbygoogle" style="display:block" data-ad-client="' + GD.client + '" data-ad-slot="' + GD.ad + '" data-ad-format="auto" data-full-width-responsive="true"></ins></center>');
                setTimeout(function () {
                  window.adsbygoogle.push({})
                }, 200);
              });

            } else {
              $dom.parents(".widget.type_ads").addClass("display")
              $dom.html(data.html)
            }

          }
        }
      })

    }
  },

  _loadMenus: function () {

    // Theme Menus
    var loadMenus = [];
    if (window.app.config.theme.menus) {
      var themeMenus = window.app.config.theme.menus;
      for (var i = 0; i < Object.keys(themeMenus).length; i++) {
        var themeMenuKey = Object.keys(themeMenus)[i];
        var themeMenu = themeMenus[themeMenuKey];
        var renderMenu = window.app._loadMenu(themeMenuKey, themeMenu);
        loadMenus.push(renderMenu);
      }
    }

    if (window.app.config.mobile) {
      // $(document).find(".header .chapar_menu_wrapper").remove();
      // $(document).find(".header .chapar_msgs").remove();
      $(document).find("#chapar_drop").prependTo("#main")
      $(document).find("#user_dropdown").prependTo("#main")
    } else {
      // $(document).find(".sidebar .chapar_menu_wrapper").remove();
      // $(document).find(".sidebar .chapar_msgs").remove();
    }

    $(document).trigger("_loadMenus");

    return loadMenus;

  },
  _loadMenu: function (key, data) {
    var renderMenu = window.app.renderMenu(key).done(function (menuContent) {
      $("body").find("._bof_" + key).html(menuContent);
    });
    return renderMenu;
  },
  renderMenu: function ($name) {

    var path = "theme/parts/menu";
    if ( $name == "sidebar" ){
      if ( window.config.platform!=="web" )
        path = "theme/parts/menuNative"
      else if ( window.app.config.mobile )
        path = "theme/parts/menuMobile"
    }
    var promise = $.Deferred();
    var Data = window.app.config.theme.menus[$name];
    window.ui.theme.part(path, {
      base: $_bof_config.assets_address,
      target: false
    }).done(function (Html) {
      window.render.mix(Html, Data).done(function (Output) {
        promise.resolve(Output);
      });
    });
    return promise;

  },
  _loadParts: function () {

    // ThemeParts
    $(document).find(".bof_part").remove();
    var loadThemePartsStart = window._g._mt();
    var loadThemePartsArray = [];
    var loadThemeParts = $.Deferred();
    if (window.app.config.theme.parts) {
      for (var i = 0; i < window.app.config.theme.parts.length; i++) {
        loadThemePartsArray.push(window.app._loadPart(window.app.config.theme.parts[i]));
      }
    } else {
      loadThemeParts.resolve(0);
    }
    if (loadThemePartsArray) {
      $.when.apply($, loadThemePartsArray).done(function () {
        loadThemeParts.resolve(window._g._pt(loadThemePartsStart));
      });
    }

    return loadThemeParts;

  },
  _loadPart: function (themePart) {

    var promise = $.Deferred();;
    var themeArgs = themePart[1];
    var loadThemePart = window.ui.theme.part(themePart[0], $.extend({}, themePart[1], { target: false }));

    loadThemePart.done(function (loadedThemePart) {
      window.render.mix(loadedThemePart).done(function (loadedThemePartRendered) {
        if (!themeArgs["target"]) {
          $("body").prepend("<div class='bof_part'>" + loadedThemePartRendered + "</div>");
        } else {
          $(document).find(themeArgs["target"]).prepend("<div class='bof_part'>" + loadedThemePartRendered + "</div>")
        }
        setTimeout(function () {
          $(document).find("#footer ._bof_footer .menu_wrapper .link_wrapper.mobile_only").remove();
          $(document).find("#footer ._bof_footer .menu_wrapper .bof_dropdown").remove();
        }, 300);
        promise.resolve();
      })
    });

    return promise;

  },
  _loadAppJavas: function () {

    return window.config.javas();

  },
  _loadPlatformJavas: function () {

    var promise = $.Deferred();

    if (window.app.config.mobile) {

      window.ui.body.addClass("mobile", true);

      window.bof._loadExtension({
        skipNameCheck: true,
        version: false,
        name: "hammer_min_js",
        path: "third/hammer.min.js",
        base: "bof_assets",
      }).done(function () {
        promise.resolve();
      }).fail(function () {
        promise.reject("Loading hammer.min.js failed");
      });

    }
    else {
      window.ui.body.addClass("desktop", true);
      promise.resolve();
    }

    return promise;

  },

  pages: {
    external_music: {
      events: {
        rendering: function () {

          window.ui.lock.off("external_music");

          var beCli = window.ui.page.get_data("becli");
          if (beCli ? (beCli.single ? beCli.single.url : false) : false) {
            window.ui.link.navigate(beCli.single.url);
          }
          else {
            window.ui.link.navigate("404");
          }

          var promise = $.Deferred();
          promise.reject("HALT");
          return promise;

        }
      }
    },
    user_auth: {
      events: {
        displaying: function ( $a ) {

          var validatePromise = $.Deferred();
          var validateLanguage = $.Deferred();
          var apkPromise = $.Deferred();

          if ( window.apk ){
            window.apk.pages.user_auth.displaying( apkPromise, $a )
          } else {
            apkPromise.resolve();
          }

          window.bof._loadExtension({
            name: "jquery.validate.min.js",
            path: "jquery.validate.min.js",
            base: "https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/",
            dir: "",
            skipNameCheck: true,
            version: false
          }).done(function () {

            validatePromise.resolve();

            if (window.lang.code == "en" || window.lang.code === null || window.lang.code === undefined) {
              validateLanguage.resolve();
            }
            else {
              var langcodeName = window.lang.code;
              if (langcodeName == "pt")
                langcodeName = "pt_PT"
              window.bof._loadExtension({
                name: "jquery.validate.messages_" + langcodeName + ".js",
                path: "messages_" + langcodeName + ".js",
                base: "https://cdn.jsdelivr.net/npm/jquery-validation@1.19.3/dist/localization/",
                dir: "",
                skipNameCheck: true,
                version: false
              }).done(function () {
                validateLanguage.resolve();
              }).fail(function () {
                validateLanguage.resolve();
                window.bof.log("Loading jquery.validate.language:" + window.lang.code + " failed");
              });
            }

          }).fail(function () {
            validatePromise.reject("Loading jquery.validate failed");
          });

          return $.when(validatePromise, validateLanguage, apkPromise).fail(function (err) {
            window.bof.log(err);
          });

        },
        ready: function () {

          window.addEventListener('message', function(event) {
            if (event.data.type === 'social_login' && event.data.data) {
              if (window.app.pages.user_auth.social_loginner_promise) {
                window.app.pages.user_auth.social_loginner_promise.resolve(event.data.data);
              }
            }
          });

          if ( window.apk )
          window.apk.pages.user_auth.ready()

          $(".auth_form form").validate();
          $(document).on("click", ".sl_buttons .sl", function (e) {

            var target = $(this).attr("data-id");

            if ( window.apk ? window.config.platform == "ios" : false ){
              window.app.pages.user_auth.social_loginner = cordova.InAppBrowser.open(
                $_bof_config.endpoint_address + "login_social_ini?alert=true&target=" + target,
                "_system",
                "hidden=yes"
              )
            } else {
              window.app.pages.user_auth.social_loginner = window.open(
                $_bof_config.endpoint_address + "login_social_ini?alert=true&target=" + target,
                "social_loginner",
                "left=100,top=100,width=500,height=600"
              )
            }

            window.app.pages.user_auth.social_loginner_promise = $.Deferred();
            window.app.pages.user_auth.social_loginner_promise
            .done(function (data) {
              window.app.pages.user_auth.functions.checker(true, JSON.parse(data));
              window.app.pages.user_auth.social_loginner.close()
            })
            .fail(function (error) {
              console.log(error);
              window.app.pages.user_auth.functions.checker(false, { messages: [error] });
              window.app.pages.user_auth.social_loginner.close()
            })

          });

          var ua = navigator.userAgent.toLowerCase();
          if (ua.indexOf('safari') != -1) {
            if (!(ua.indexOf('chrome') > -1)) {
              $(document).find("body.auth_page .auth_form_wrapper .logo_wrapper img").css("height","auto")
              $(document).find("body.auth_page .auth_form_wrapper .logo_wrapper img").css("height", $(document).find("body.auth_page .auth_form_wrapper .logo_wrapper img").height() + "px" )
            }
          }

          if ( ( window.app.config.setting.social_login ) && $(document).find("body.auth_page .auth_form_wrapper .auth_form .sl_wrapper").length) {
            window.becli.exe({
              endpoint: "login_social_get",
              callBack: function (sta, data) {
                if ( data.google_off ){
                  window.bof._loadCSS({
                    name: "roboto_font",
                    path: "family=Roboto:wght@400;500&display=swap",
                    base: "https://fonts.googleapis.com/css2?",
                    dir: "",
                    skipNameCheck: true,
                    version: false
                  })
                  window.ui.body.addClass( "google_off" );
                }
                if (!sta) {
                  $(document).find("body.auth_page .auth_form_wrapper .auth_form .sl_wrapper").remove();
                } else {
                  $(document).find("body.auth_page .auth_form_wrapper .auth_form .sl_wrapper").removeClass("loading");
                  var _HTML = "<div class=\"sl_buttons\">";
                  for (var i = 0; i < data.sls.length; i++) {
                    var _sl = data.sls[i];
                    if ( _sl.id == "google" && data.google_off ){
                      _HTML += "<div class=\"sl google\" data-id=\"" + _sl.id + "\">\
                      <span class=\"google_org\"><img src=\"https://developers.google.com/static/identity/images/g-logo.png\"></span> <span class=\"_t\">Sign in with Google</span>\
                      </div><br>";
                    } else if ( _sl.id == "twitter" ) {
                      _HTML += "<div class=\"sl\" data-id=\"" + _sl.id + "\">\
                      <span class=\"icon-x\"></span>\
                    </div>";
                    } else {
                      _HTML += "<div class=\"sl\" data-id=\"" + _sl.id + "\">\
                      <span class=\"mdi mdi-"+ _sl.icon + "\"></span>\
                    </div>";
                    }
                    
                  }
                  _HTML += "</div>";
                  _HTML += "<div class=\"seperator_text\">or use your account</div>";
                  $(document).find("body.auth_page .auth_form_wrapper .auth_form .sl_wrapper").html(_HTML);
                  $(document).trigger("user_auth_social_login_loaded")
                }
              }
            })
          } 
          else {
            $(document).find("body.auth_page .auth_form_wrapper .auth_form .sl_wrapper").remove();
          }

          if ($(document).find("body.auth_page .auth_form_wrapper .auth_form form").data("action") == "verification") {
            $(document).find("body.auth_page .auth_form_wrapper .auth_form .btn_wrapper .btn-primary").click();
          }

          $.validator.addMethod("check_username", function (value, element) {
            return /^[a-zA-Z0-9_.]+$/.test(value)
          }, window.lang.return("invalid_username", { ucfirst: true }));

          $.validator.addMethod("check_password", function (value, element) {
            return $(document).find("#password").val() == value
          }, window.lang.return("pws_dont_match", { ucfirst: true }));

        },
        unloading: function () {

          if ( window.apk )
          window.apk.pages.user_auth.unloading()

          $(document).off("click", ".sl_buttons .sl");

        },
      },
      social_loginner: null,
      social_loginner_promise: null,
      functions: {
        before: function () { },
        checker: function (sta, data) {

          var action = $(document).find(".auth_form form").data("action");

          if ( window.bof_offline_sw ){ 
            window.bof_offline_sw.deleteBofClient(); 
          }

          if (sta) {

            if (data.sess_id || data.sess_key) {

              cache.set("sess_id", data.sess_id);
              cache.set("sess_key", data.sess_key);
              if (data.google_key) cache.set("sess_google_key", data.google_key)

              window.app.getConfig(true).done(function () {
                window.app._loadParts().done(function () {
                  window.app._loadMenus();
                  var _dback = new URLSearchParams( window.location.search ).get( "dback" );
                  if ( _dback && /^https:\/\/[a-z0-9.-]*hitune\.in(\/|$)/.test( _dback ) ){
                    window.location.href = _dback;
                  } else if ( window.user.getLoginDestionation() ){
                    window.ui.link.navigate( window.user.getLoginDestionation() );
                  } else {
                    window.ui.link.navigate("user_area");
                  }
                })
              });
              window.app._loadParts();

              return;

            }

            $(document).find(".auth_form form").removeClass("loading").addClass("failure");
            $(document).find(".auth_form form .btn.submit").remove();
            $(document).find(".auth_form .message_holder").addClass("display").addClass("successful");
            $(document).find(".auth_form .message_holder .message").text(data.messages[0]);
            $(document).find(".auth_form .message_holder .icon").attr("class", "icon mdi mdi-alert-circle-outline");

            if ( window.apk ? data.recover_by_email === true : false ){
              window.ui.link.navigate("userAuth?do=recover_confirm&message=yes&email=" + $(document).find("input[name=email]").val())
            }

            return;

          }

          $(document).find(".auth_form form").removeClass("loading").addClass("failure");
          $(document).find(".auth_form form .btn.submit").removeClass("loading");
          $(document).find(".auth_form form .btn.submit").addClass("failure");
          $(document).find(".auth_form form .btn.submit .message").text(window.lang.return("retry"));
          $(document).find(".auth_form .message_holder").addClass("display");
          $(document).find(".auth_form .message_holder .message").text(data.messages[0]);
          $(document).find(".auth_form .message_holder .icon").attr("class", "icon mdi mdi-alert-circle-outline");

          if ( window.apk ? data.verify_email === true : false ){
            window.ui.link.navigate("userAuth?do=verification&message=yes&email=" + $(document).find("input[name=email]").val())
          }

        }
      }
    },
    user_area: {
      events: {
        rendering: function () {

          window.ui.lock.off("user_area");

          var userData = window.app.config.user.data;
          if (userData.username) {
            window.ui.link.navigate("user/" + userData.username);
          }
          else {
            window.user.loggedOut(true);
          }

          var promise = $.Deferred();
          promise.reject("HALT");
          return promise;

        }
      }
    },
    user_library: {
      events: {
        ready: function () {
          var tab = window.ui.page.data.becli.single.tab;
          $(document).find(".tabs ._" + tab).addClass("active")[0].scrollIntoView({ block: "end", inline: "nearest" });
        },

      }
    },
    user_edit: {
      events: {
        ready: function () {
          var tab = window.ui.page.data.becli.single.tab;
          $(document).find(".tabs ._" + tab).addClass("active")[0].scrollIntoView({ block: "end", inline: "nearest" });
          $(document).on("click", "#session_list .del_sess", function (e) {
            var sid = $(this).data("sess-id");
            window.app.becli.exe("alert", {}, {
              endpoint: "user_edit?tab=sessions&action=submit",
              post: {
                id: sid
              },
              c_callback: function (sta, data, args) {
                window.ui.page.reload();
              }
            });
          });
          $(document).on("click", "body #main .content ._cancel_sub", function(e){
            var _id = $(this).data("sub-id");
            window.bof_modal.create({
              title: window.lang.return("confirm", { ucfirst: true }),
              content: window.lang.return("sub_cancel"),
              buttons: [
                ["btn-primary the_loading_button", window.lang.return("confirm", { ucfirst: true }), "window.app.actions.user.unsubscribe(\""+_id+"\")"]
              ],
              cancelHook: "back"
            });
          });
        },
        unloading: function () {
          $(document).off("click", "#session_list .del_sess");
          $(document).off("click", "body #main .content ._cancel_sub");
        }
      }
    },
    user_pay: {
      endpoint: "user_pay_get_link",
      gateways_data: null,
      currency_data: null,
      purchase_data: null,
      functions: {
        calc: function () {

          var gateway_name = window.app.pages.user_pay.selected_gateway;
          var gateway_data = window.app.pages.user_pay.gateway_data[gateway_name];
          var input_value = parseFloat($(document).find(".add_fund_wrapper .pgts_wrapper .is_wrapper .bof_input").val());
          var fee_mul = gateway_data.fee ? (1 + (gateway_data.fee / 100)) : 1;
          var fee = input_value ? Number((input_value * (fee_mul - 1)).toFixed(3)) : "?";
          var final_value = input_value ? Number((input_value * fee_mul).toFixed(2)) : "?";

          $(document).find(".add_fund_wrapper .pgts_wrapper .is_wrapper .details span b#amount_fee i").text(fee);
          $(document).find(".add_fund_wrapper .pgts_wrapper .is_wrapper .details span b#amount_full i").text(final_value);
          $(document).find(".add_fund_wrapper .pgts_wrapper .btns_wrapper .btn span").text(final_value + " " + window.app.pages.user_pay.currency_data.symbol)

        }
      },
      events: {
        ready: function (amount) {

          if (window.ui.page.curr().data.becli.single.gateways) {
            window.app.pages.user_pay.gateway_data = window.ui.page.curr().data.becli.single.gateways;
            window.app.pages.user_pay.currency_data = window.ui.page.curr().data.becli.single.currency;
            window.app.pages.user_pay.purchase_data = null;

            var gtids = Object.keys( window.ui.page.curr().data.becli.single.gateways );
            var fgtid = gtids[0];
            var fgt = window.ui.page.curr().data.becli.single.gateways[ fgtid ]
            $(document).find(".add_fund_wrapper .pgts_wrapper .is_wrapper .bof_input").val( fgt.placeholder )
          }

          $(document).on("click", ".add_fund_wrapper .pgts_wrapper .pgts .pgt", function (e) {

            if ($(this).hasClass("selected")) return;

            $(document).find(".add_fund_wrapper .pgts_wrapper .pgts .pgt.selected").removeClass("selected");
            $(this).addClass("selected");

            var name = $(this).data("name");
            var data = window.app.pages.user_pay.gateway_data[name];

            $(document).find(".add_fund_wrapper .pgts_wrapper .is_wrapper .bof_input").val( data.placeholder )

            window.app.pages.user_pay.selected_gateway = name;
            window.app.pages.user_pay.functions.calc();

          });
          $(document).on("change", ".add_fund_wrapper .pgts_wrapper .is_wrapper .bof_input", function () {
            window.app.pages.user_pay.functions.calc();
          });
          $(document).on("input", ".add_fund_wrapper .pgts_wrapper .is_wrapper .bof_input", function () {
            window.app.pages.user_pay.functions.calc();
          });
          $(document).on("click", ".add_fund_wrapper .pgts_wrapper .btns_wrapper .btn", function (e) {

            window.app.becli.exe("button", {
              dom: $(this),
            }, {
              endpoint: window.app.pages.user_pay.endpoint,
              post: {
                gateway: window.app.pages.user_pay.selected_gateway,
                amount: $(document).find(".add_fund_wrapper .pgts_wrapper .is_wrapper .bof_input").val(),
                purchase_data: window.app.pages.user_pay.purchase_data ? JSON.stringify(window.app.pages.user_pay.purchase_data) : null
              },
              c_callback: function (sta, data) {
                if (sta) {
                  if (data.type == "html") {
                    $(document).find(".add_fund_wrapper .pgts_wrapper").html("<div class='pay_html'>" + data.content + "</div>")
                  }
                  else if (data.type == "link") {
                    window.location.href = data.link;
                  }
                  else if (data.type == "script") {

                    var f = data.data.funcName ? data.data.funcName : "bof_gateway_" + window.app.pages.user_pay.selected_gateway

                    window.bof._loadExtension({
                      name: f,
                      path: data.link,
                      base: "",
                      dir: "",
                      version: false
                    }).done(function () {
                      window[ f ].setup(data) 
                    }).fail(function () {
                    });

                  }
                }
              },
              reload_after: false
            })

          });

          $(document).find(".add_fund_wrapper .pgts_wrapper .pgts .pgt:first-child").click();
          if (amount ? typeof (amount) == "number" : false) {
            $(document).find(".add_fund_wrapper .pgts_wrapper .is_wrapper .bof_input[name=amount]").val(amount);
            window.app.pages.user_pay.functions.calc();
          }

        },
        unloading: function () {
          $(document).off("click", ".add_fund_wrapper .pgts_wrapper .pgts .pgt");
          $(document).off("change", ".add_fund_wrapper .pgts_wrapper .is_wrapper .bof_input");
          $(document).off("click", ".add_fund_wrapper .pgts_wrapper .btns_wrapper .btn");
          $(document).off("input", ".add_fund_wrapper .pgts_wrapper .is_wrapper .bof_input");
        }
      }
    },
    user_verify: {
      events: {
        ready: function () {
          var tab = window.ui.page.data.becli.single.tab;
          $(document).find(".tabs ._" + tab).addClass("active");
        }
      }
    },
    user_subs: {
      cache: {},
      pay: function () {
        window.bof_modal.set_loading("initial");
        window.becli.exe({
          endpoint: "user_pay_ini",
          callBack: function (sta, data) {

            if (!sta) return;

            window.app.pages.user_pay.gateway_data = data.gateways;
            window.app.pages.user_pay.currency_data = data.currency;
            window.app.pages.user_pay.purchase_data = {
              type: "user_subs_plan",
              hook: window.app.pages.user_subs.cache.hash,
              period: window.app.pages.user_subs.cache.period
            };

            window.ui.theme.part("pages/user_pay", { target: false, dir: "theme", base: $_bof_config.assets_address }).done(function (html) {
              window.render.mix(html, { single: data }).done(function (renderred) {

                window.bof_modal.close();
                window.bof_modal.create({
                  class: "plan_pay_inline",
                  title: "Pay",
                  content: renderred,
                });

                window.app.pages.user_pay.events.ready(parseFloat(window.app.pages.user_subs.cache.price.replace(/[^\d.-]/g, '')));

              });
            });

            $(document).on("modal_destroyed", function () {
              window.app.pages.user_pay.events.unloading();
            });

          }
        })
      },
      events: {
        displaying: function () {
          // window.ui.body.addClass( "muse_hide", true );
          // window.ui.body.addClass( "no_footer" );
          // window.ui.body.addClass( "no_sidebar", true );
        },
        ready: function () {
          $(document).on("click", "body #main .content #plans .plans_wrapper.simpler .plan_wrapper", function (e) {

            var hash = $(this).data("hash");
            window.app.pages.user_subs.cache["hash"] = hash;

            $(document).find("body #main .content #plans .plans_wrapper.simpler .plan_wrapper").removeClass("active");
            $(this).addClass("active");

            $(document).find("body #main .content #plans .plans_wrapper.main_one .plan_wrapper.active").removeClass("active");
            $(document).find("body #main .content #plans .plans_wrapper.main_one .plan_wrapper.id_" + hash).addClass("active");
            $(document).find("body #main .content #plans .plans_wrapper.main_one .plan_wrapper.active .periods .period:first-child").click();

          });
          $(document).on("click", "body #main .content #plans .plans_wrapper.main_one .plan_wrapper .periods .period", function (e) {

            $(document).find("body #main .content #plans .plans_wrapper.main_one .plan_wrapper .periods .period.active").removeClass("active");
            $(this).addClass("active");

            var range = $(this).data("range");
            window.app.pages.user_subs.cache["period"] = range;
            var rangeTitle = window.lang.return(range, { ucfirst: true });

            var hash = $(this).parents(".plan_wrapper").data("hash");

            var plan = window.ui.page.curr().data.becli.single.plans[hash];
            var prices = plan.prices;
            var price = prices.final_parsed[range];
            $(document).find("body #main .content #plans .plans_wrapper .plan_wrapper.id_" + hash + " div.price ._n").text(price);
            $(document).find("body #main .content #plans .plans_wrapper.simpler .plan_wrapper.id_" + hash + " div.price .pname").text(rangeTitle);
            $(document).find("body #main .content .continue_wrapper .selected_plan .val").text(window.ui.page.curr().data.becli.single.plans[hash].name);
            $(document).find("body #main .content .continue_wrapper .selected_plan_period .val").text(rangeTitle);
            $(document).find("body #main .content .continue_wrapper .selected_plan_price .val").text(price);
            window.app.pages.user_subs.cache["price"] = price;
            window.app.pages.user_subs.cache["hash"] = hash;

            if (plan.discount) {
              var o_price = prices.original_parsed[range];
              $(document).find("body #main .content #plans .plans_wrapper .plan_wrapper.id_" + hash + " div.price .old").text(o_price);
              $(document).find("body #main .content .continue_wrapper .selected_plan_price .val").html("<span class='old'>" + o_price + "</span>" + price);
            }

          });
          $(document).on("click", "body #main .content .continue_wrapper .button_wrapper .btn", function (e) {

            var plan = window.app.pages.user_subs.cache;
            window.bof_modal.set_loading("initial");
            window.becli.exe({
              endpoint: "purchase_subs_plan",
              post: {
                hash: plan.hash,
                period: plan.period
              },
              callBack: function (sta, data) {

                var resultModalButtons = [];

                if (sta&&data? (data.hook == "subscribe_link") : false ){
                  window.location.href = data.link;
                  return;
                }
                else if (sta) {
                  resultModalButtons.push(["btn-primary", window.lang.return("purchases", { ucfirst: true }), "window.ui.link.navigate( \"user_edit?tab=transactions\" ); window.bof_modal.close(); window.bof_modal.close();"]);
                } 
                else if (data ? data.hook == "insufficient_fund" : false) {
                  resultModalButtons.push(["btn-primary", window.lang.return("pay", { ucfirst: true }), "window.app.pages.user_subs.pay(); window.bof_modal.close(); window.bof_modal.close();"]);
                }

                window.bof_modal.close();
                if (data.messages[0] != "403") {
                  window.bof_modal.create({
                    class: "purchase_modal",
                    title: data.messages[0],
                    content: data.more,
                    buttons: resultModalButtons
                  });
                }

              }
            })

          });
          $(document).find("body #main .content #plans .plans_wrapper.simpler .plan_wrapper:first-child").click();
          // window.ui.body.addClass( "no_footer" );
          // window.ui.body.addClass( "no_sidebar", true );
        },
        unloading: function () {
          $(document).off("click", "body #main .content #plans .plans_wrapper.simpler .plan_wrapper");
          $(document).off("click", "body #main .content #plans .plans_wrapper.main_one .plan_wrapper .periods .period");
          $(document).off("click", "body #main .content .continue_wrapper .button_wrapper .btn");
          // window.ui.body.removeClass( "muse_hide", true );
          // if ( window.app.config.setting.additional_body_classes ? !window.app.config.setting.additional_body_classes.includes( "no_sidebar" ) : true )
          // window.ui.body.removeClass( "no_sidebar", true );
        }
      }
    },
    upload: {
      events: {
        ready: function () {
          window.bof_upload.ready();
        },
        unloading: function () {
          window.bof_upload.unloading();
        },
      },
    },
    object_browse: {
      inied: false,
      events: {
        displaying: function () {

          var promise = $.Deferred();
          var rangePromise = $.Deferred();
          var rangePromise2 = $.Deferred();

          if (!window.app.pages.object_browse.inied) {

            window.app.pages.object_browse.inied = true;

            window.bof._loadExtension({
              name: "nouislider_js",
              path: "nouislider.min.js",
              base: "https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.6.0/",
              dir: "",
              skipNameCheck: true,
              version: false
            }).done(function () {
              rangePromise.resolve();
            }).fail(function () {
              rangePromise.reject("Loading nouislider.js failed");
            });

            window.bof._loadCSS({
              name: "nouislider_css",
              path: "nouislider.min.css",
              base: "https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.6.0/",
              dir: "",
              skipNameCheck: true,
              version: false
            }).done(function () {
              rangePromise2.resolve();
            }).fail(function () {
              rangePromise2.reject("Loading nouislider.css failed");
            });

          }
          else {

            rangePromise.resolve();
            rangePromise2.resolve();

          }

          $.when(rangePromise, rangePromise2).done(function () {
            promise.resolve();
          }).fail(function (err) {
            promise.reject(err);
          })

          $(document).on("click", "body.page_object_browse #main .bb_filters .bb_buttons .btn._apply", function (e) {

            var filters_raw = $(".bb_filters :input").serializeArray();
            var filters = {};
            for (var i = 0; i < filters_raw.length; i++) {
              var filter = filters_raw[i];
              if (filter.value !== "" && filter.value !== "__all__") {
                filters[filter.name] = filter.value;
              }
            }

            var url = window.ui.page.args.urlData.url.path;
            if (Object.keys(filters).length) url += "?" + $.param(filters);

            window.ui.link.navigate(url);

          });

          return promise;

        },
        unloading: function (args) {

          $(document).off("click", "body.page_object_browse #main .bb_filters .bb_buttons .btn._apply");

          if (window.ui.page.curr().name != args.name) {
            $(document).find("body.page_object_browse.muse_active #main .bb_filters").remove();
            $(document).find("body.page_object_browse.muse_active #main .bb_content").remove();
            window.ui.body.removeClass("page_object_browse");
          }

        },
        ready: function () {

          var filters = window.ui.page.curr().data.becli.single.filters;
          for (var i = 0; i < Object.keys(filters).length; i++) {
            var filter_name = Object.keys(filters)[i];
            var filter = filters[filter_name];
            if (filter.input ? filter.input.type == "range_two" : false) {

              var slider = document.getElementById('range_' + filter_name);

              if (slider) {

                var _smi = filter.input.min;
                var _sma = filter.input.max;

                if (filter.input.value) {
                  var _vs = filter.input.value.split("-");
                  if (_vs.length == 2) {
                    _smi = _vs[0];
                    _sma = _vs[1];
                  }
                }

                noUiSlider.create(slider, {
                  start: [_smi, _sma],
                  connect: true,
                  range: {
                    'min': filter.input.min,
                    'max': filter.input.max
                  },
                  tooltips: {
                    to: function (numericValue) {
                      return numericValue.toFixed(0);
                    }
                  },
                  pips: {
                    mode: 'count',
                    values: 5
                  }
                });

                if (slider.noUiSlider) {
                  slider.noUiSlider.on("update", function (values, handle, unencoded) {

                    var name = this.target.getAttribute("ID").substr("range_".length);
                    var filter = window.ui.page.curr().data.becli.single.filters[name];
                    var filter_0 = filter.input.min;
                    var filter_1 = filter.input.max;
                    if (unencoded[0] != filter_0 || unencoded[1] != filter_1) {
                      $(document).find("input[name=" + name + "]").val(unencoded[0].toFixed(0) + "-" + unencoded[1].toFixed(0));
                    }
                    else {
                      $(document).find("input[name='" + name + "']").val("")
                    }

                  });
                }

              }

            }
          }

          if (Object.keys(window.ui.page.args.urlData.url.query).length)
            $("#main")[0].scrollTop = $(".bb_content")[0].offsetTop;

        }
      }
    },
  },

  _extension_new: function ($name, _extension_links, $loadAfter) {

    var promiseToLoadExtension = $.Deferred();
    if (_extension_links === false)
      return;

    if (Array.isArray(_extension_links)) {

      var _array_promises = [];
      for (var i = 0; i < _extension_links.length; i++) {
        var _extension_link = _extension_links[i];
        var _extension_link_exe = _extension_link.type == "js" ? window.bof._loadExtension(_extension_link) : window.bof._loadCSS(_extension_link);
        _array_promises.push(_extension_link_exe);
      }
      $.when.apply($, _array_promises).done(function () {
        promiseToLoadExtension.resolve();
      }).fail(function () {
        promiseToLoadExtension.reject();
        window.bof.log("Extension new: " + _extension_link_exe + " failed");
      })

    }
    else {

      var _exe = _extension_links.type != "css" ? window.bof._loadExtension(_extension_links) : window.bof._loadCSS(_extension_links);

      _exe.done(function () {
        promiseToLoadExtension.resolve();
      }).fail(function () {
        promiseToLoadExtension.reject();
        window.bof.log("Extension new: " + _extension_link_exe + " failed");
      })

    }
    return promiseToLoadExtension;

  },
  getConfig: function ($reload) {

    var promise = $.Deferred();

    if (window.app.config && !$reload) {
      promise.resolve(window.app.config);
    } else {
      window.becli.exe({
        endpoint: "client_config",
        liquid: true,
        callBack: function (sta, data) {

          if (sta) {

            if (data["pages"]) {
              for (var i = 0; i < Object.keys(data["pages"]).length; i++) {

                var pageName = Object.keys(data["pages"])[i];
                var pageArgs = data["pages"][pageName];

                pageArgs.events = pageArgs.events ? pageArgs.events : {};

                var oldPageArgs = {};

                if (Object.keys(window.app.pages).includes(pageName))
                  oldPageArgs = window.app.pages[pageName];

                window.app.pages[pageName] = $.extend(pageArgs, oldPageArgs);

              }
            }

            window.app.config = data;
            promise.resolve(data);
            $(document).trigger("config_ready")

          }
          else {
            promise.reject();
          }


        }
      })
    }

    return promise;

  },

};
