<?php

if (!defined("bof_root")) die;

function endpoint_user_edit($loader, $excuter, $args)
{

  $userData = $loader->object->user->select(
    array(
      "ID" => $loader->user->get()->ID
    ),
    array(
      "cache_load_rt" => false,
      "_eq" => array(
        "avatar" => [],
        "bg_img" => []
      )
    )
  );

  $tabs = array(
    "profile" => array(
      "ID" => "profile",
      "becli" => array(
        "endpoint" => "user_edit?tab=profile&action=submit"
      ),
      "inputs" => array(
        "name" => array(
          "required" => true,
          "value" => $userData["name"],
          "hook" => "name",
          "input" => array(
            "type" => "text"
          ),
          "validator" => array(
            "string",
            array(
              "strip_emoji" => false,
            ),
          ),
        ),
        "avatar_id" => array(
          "value" => $userData["avatar_id"],
          "hook" => "avatar",
          "bofInput" => array(
            "file",
            array(
              "type" => "image",
              "object_type" => "user_avatar",
              "object_name" => "user",
              "object_id" => $userData["ID"],
              "translate" => true
            )
          ),
        ),
        "bg_img_id" => array(
          "value" => $userData["bg_img_id"],
          "hook" => "cover",
          "tip_hook" => "user_cover_tip",
          "bofInput" => array(
            "file",
            array(
              "type" => "image",
              "object_type" => "user_bg",
              "object_name" => "user",
              "object_id" => $userData["ID"],
              "translate" => true
            )
          ),
        ),
      ),
    ),
    "security" => array(
      "ID" => "security",
      "becli" => array(
        "endpoint" => "user_edit?tab=security&action=submit"
      ),
      "inputs" => array(
        "email" => array(
          "value" => $userData["email"],
          "hook" => "email",
          "input" => array(
            "type" => "text",
            "disabled" => true
          ),
          "validator" => "email",
        ),
        "old_password" => array(
          "required" => true,
          "hook" => "old_password",
          "input" => array(
            "type" => "password"
          ),
          "validator" => "password",
        ),
        "new_password" => array(
          "hook" => "new_password",
          "input" => array(
            "type" => "password"
          ),
          "validator" => "password",
        ),
        "new_password_verify" => array(
          "hook" => "password_repeat",
          "input" => array(
            "type" => "password"
          ),
          "validator" => "password",
        ),
        "social_login" => array(
          "hook" => "social_login_enabled",
          "tip_hook" => "social_login_enabledt",
          "input" => array(
            "type" => "checkbox"
          ),
          "validator" => "boolean",
        ),
      )
    ),
    "transactions" => array(
      "ID" => "transactions",
      "content" => function ($loader) {

        $transactions = $loader->object->transaction->select(
          array(
            "user_id" => $loader->user->check()->ID
          ),
          array(
            "limit" => 100,
            "order_by" => "ID",
            "order" => "DESC",
            "get_item" => true
          )
        );

        $transactions_html = [];
        if ($transactions) {
          foreach ($transactions as $transaction) {

            $_transactionLabel = bof()->object->language->turn($transaction["type"], [], ["uc_first" => true, "lang" => "users"]);
            $transactions_html[] = "<div class='transaction type_{$transaction["type"]}'><div class='_tw ss'>
              <div class='_ds'>
                <div class='_type'>{$_transactionLabel}</div>
                <div class='_amount'><b>" . ($transaction["amount"] > 0 ? "+" . $transaction["amount"] : $transaction["amount"]) . "</b> {$transaction["currency"]}</div>
              </div>
              <div class='_item'>
                " . (!empty($transaction["object_item"]["cover"]["image_thumb"]) ? "<div class='_cw' style='background-image:url(\"{$transaction["object_item"]["cover"]["image_thumb"]}\")'></div>" : "") . "
                <div class='_ci'>
                  <b>" . (!empty($transaction["object_item"]["title"]) ? $transaction["object_item"]["title"] : "?") . "</b>
                  {$transaction["object_label"]}
                </div>
              </div>
            </div></div>";
          }
        } else {
          $transactions_html[] = "<div class='empty'>" . $loader->object->language->turn("no_transactions", [], ["uc_first" => true, "lang" => "users"]) . "</div>";
        }

        $user_funds = $loader->object->user->select(["ID" => $loader->user->check()->ID])["fund"];

        return array(
          "html" => "<div class='transactions'>" .
            "<div class='transaction type_fund'>" .
            "<div class='_tw'>" .
            "<div class='_t'>" . $loader->object->language->turn("your_funds", [], ["uc_first" => true, "lang" => "users"]) . ":</div>" .
            "<div class='_p'>" . ($user_funds ? $loader->object->currency->parse_price($user_funds)["string"] : "0") . "</div>" .
            "</div>" .
            "<a class='_af' href='user_pay'><span>" . $loader->object->language->turn("add_funds", [], ["uc_first" => true, "lang" => "users"]) . "</span></a>" .
            "</div>" .
            implode("", $transactions_html) .
            "</div>",
        );
      }
    ),
    "notifications" => array(
      "ID" => "notifications",
      "becli" => array(
        "endpoint" => "user_edit?tab=notifications&action=submit"
      ),
      "inputs" => array(),
    ),
    "links" => array(
      "ID" => "links",
      "becli" => array(
        "endpoint" => "user_edit?tab=links&action=submit"
      ),
      "inputs" => array(),
    ),
    "sessions" => array(
      "ID" => "sessions",
      "becli" => array(
        "endpoint" => "user_edit?tab=sessions&action=submit"
      ),
      "content" => function ($loader) {

        $sessions = $loader->object->session->select(
          array(
            "user_id" => $loader->user->check()->ID
          ),
          array(
            "limit" => 100,
            "order_by" => "ID",
            "order" => "DESC",
          )
        );

        $sessions_html = [];
        if ($sessions) {

          require_once(realpath(bof_root . "/app/core/third/WhichBrowser_Parser-PHP_v2.1.1/autoload.php"));
          $_t_you = bof()->object->language->turn("you", [], ["uc_first" => true, "lang" => "users"]);

          foreach ($sessions as $session) {

            $_cs = "";
            if ($session["session_id"] == $loader->session->getID())
              $_cs = "this";

            $agent_data = json_decode(strtolower(json_encode(new WhichBrowser\Parser($session["data_decoded"]["user_agent"]))), 1);
            $sessions_html[] = "<tr class='session {$_cs}'>
            <td class='country'>" . ($session["ip_country"] == "_U" ? "-" : "<img src='https://flagsapi.com/{$session["ip_country"]}/flat/16.png'>") . "</td>
            <td class='ip'><span>{$session["ip"]}</span>" . (!empty($_cs) ? "<b>{$_t_you}</b>" : "") . "</td>
            <td class='platform'>{$session["platform_type"]}</td>
            <td class='platform'>{$agent_data["os"]["name"]}</td>
            <td class='platform'>{$agent_data["browser"]["name"]}</td>
            <td class='time_online'>" . bof()->general->passed_time_from_time_hr($session["time_online"], ["translate" => true]) . "</td>
            <td class='btns'><span class='mdi mdi-close-thick del_sess' data-sess-id='{$session["session_id"]}'></span></td>
            </tr>";
          }
        }

        $_t_ip = bof()->object->language->turn("ip", [], ["uc_first" => true, "lang" => "users"]);
        $_t_platform = bof()->object->language->turn("platform", [], ["uc_first" => true, "lang" => "users"]);
        $_t_os = bof()->object->language->turn("operating_system", [], ["uc_first" => true, "lang" => "users"]);
        $_t_browser = bof()->object->language->turn("browser", [], ["uc_first" => true, "lang" => "users"]);
        $_t_last_seen = bof()->object->language->turn("last_seen", [], ["uc_first" => true, "lang" => "users"]);
        $_t_manage = bof()->object->language->turn("manage", [], ["uc_first" => true, "lang" => "users"]);

        return array(
          "html" => "<table id='session_list'>
          <thead><td>-</td><td>{$_t_ip}</td><td>{$_t_platform}</td><td>{$_t_os}</td><td>{$_t_browser}</td><td>{$_t_last_seen}</td><td>{$_t_manage}</td></thead>
          <tbody>" . implode(PHP_EOL, $sessions_html) . "</tbody>
          </table>",
        );
      }
    ),
    "delete" => array(
      "ID" => "delete",
      "becli" => array(
        "endpoint" => "user_edit?tab=delete&action=submit"
      ),
      "inputs" => array(
        "password" => array(
          "required" => true,
          "hook" => "password",
          "input" => array(
            "type" => "password"
          ),
          "validator" => "password",
        ),
        "delete_account" => array(
          "required" => true,
          "hook" => "delete_account",
          "tip_hook" => "delete_account_t",
          "input" => array(
            "type" => "checkbox"
          ),
          "validator" => "boolean",
        ),
      )
    ),
    "unsub" => array(
      "ID" => "unsub",
      "becli" => array(
        "endpoint" => "user_edit?tab=unsub&action=submit"
      ),
      "content" => function(){

        $html = "<div class='_nada'><span class='mdi mdi-emoticon-sad-outline'></span><span class='_title'>" . (
        bof()->object->language->turn("nothing_found", [], ["uc_first" => true])
        ) . "</span><span class='_det'>" . (
        bof()->object->language->turn("no_s_subs")
        ) . "</span></div>";

        $plans = bof()->object->user_subs->select(
          array(
            "user_id" => bof()->user->get()->ID,
            [ "gateway_time_recur", "NOT", null, true ]
          ),
          array(
            "single" => false,
            "limit" => false,
            "cleaner" => function( $item ){
              $sub_plan = bof()->object->user_subs_plan->sid( $item["subs_plan_id"] );
              $item = "<div class='_sub'>
                <div class='_h'>
                  <div class='_pn'>{$sub_plan["name"]}</div>
                  <div class='_pp'>{$sub_plan["_prices"]["final_parsed"][ $item["subs_plan_time_range"] ]}</div>
                </div>
                <div class='_tpay _ps'>".bof()->object->language->turn("sub_t_pays",[],["uc_first"=>true]).": <b>".number_format($item["payment_count"])."</b></div>
                <div class='_npay _ps'>".bof()->object->language->turn("sub_n_pay",[],["uc_first"=>true]).": <b>". bof()->general->time_in_future_hr( strtotime($item["gateway_time_recur"]) ) ."</b></div>
                <div class='_ppay _ps'>".bof()->object->language->turn("sub_p_pay",[],["uc_first"=>true]).": <b>". bof()->general->time_in_future_hr( strtotime($item["payment_time"]) ) ."</b></div>
                <div class='_btns'><a class='btn btn-failed _cancel_sub' data-sub-id='{$item["gateway_sub_id"]}'>".bof()->object->language->turn("cancel")."</a></div>
              </div>";
              return $item;
            }
          )
        );

        if ( $plans ){
          $html = "<div class='_subs'>".implode("",$plans)."</div>";
        }

        return array(
          "html" => $html,
          "subs" => $plans
        );

      }
    ),
  );

  $enabledTabsByAdmin = bof()->object->db_setting->get("user_sps", "all");
  if ($enabledTabsByAdmin != "all") {
    foreach (array_keys($tabs) as $_tN) {
      if (!in_array($_tN, explode(",", $enabledTabsByAdmin), true))
        unset($tabs[$_tN]);
    }
  }

  if ( !empty( $tabs["unsub"] ) && !bof()->object->db_setting->get("gateway_stripe_subs") ){
    unset( $tabs["unsub"] );
  }

  bof()->call("_custom", "user_edit_tabs", $userData, $tabs);

  $tab = $loader->nest->user_input("get", "tab", "in_array", ["values" => array_keys($tabs)], array_keys($tabs)[0]);
  $submit = $loader->nest->user_input("get", "action", "equal", ["value" => "submit"]);

  if ($tab == "notifications") {

    $userNotifications = bof()->object->user_setting->get_notification($userData["ID"]);
    $definedNotifications = bof()->object->notification->select([], ["empty_select" => true, "single" => false, "limit" => false]);
    $activeNotifications = [];

    if ($definedNotifications) {
      foreach ($definedNotifications as $definedNotification) {

        if (empty($definedNotification["setting_decoded"]["methods"]["all"]) || $definedNotification["hook"] == "welcome")
          continue;

        $tabs["notifications"]["inputs"][$definedNotification["hook"]] = array(
          "label" => bof()->object->language->turn("noti_{$definedNotification["hook"]}", [], ["uc_first" => true, "lang" => "users"]),
          "tip" => bof()->object->language->turn("noti_{$definedNotification["hook"]}_tip", [], ["uc_first" => true, "lang" => "users"]),
          "input" => array(
            "type" => "checkbox",
            "name" => $definedNotification["hook"],
            "value" => $userNotifications[$definedNotification["hook"]]
          ),
          "validator" => array(
            "boolean",
            array(
              "empty()",
              "int" => true
            ),
          ),
        );

        $activeNotifications[] = $definedNotification;
      }

      $tabs["notifications"]["inputs"]["__email__"] = array(
        "label" => bof()->object->language->turn("noti_email", [], ["uc_first" => true, "lang" => "users"]),
        "tip" => bof()->object->language->turn("noti_email_tip", [], ["uc_first" => true, "lang" => "users"]),
        "input" => array(
          "type" => "checkbox",
          "name" => "__email__",
          "value" => bof()->object->user_setting->get($userData["ID"], "notify_email", bof()->object->db_setting->get("ma_sub_default") )
        ),
        "validator" => array(
          "boolean",
          array(
            "empty()",
            "int" => true
          ),
        ),
      );
    }
  } elseif ($tab == "links") {

    $socialPlatforms = bof()->seo->get_social_links_map();
    foreach ($socialPlatforms as $_ssK => $_sD) {
      $tabs[$tab]["inputs"][$_ssK] = array(
        "label" => $_sD["name"],
        "input" => array(
          "type" => "text",
          "name" => $_ssK,
          "value" => !empty($userData["external_addresses_decoded"][$_ssK]) ? $userData["external_addresses_decoded"][$_ssK] : null
        ),
        "validator" => $_sD["validator"]
      );
    }
  } elseif ($tab == "security") {
    $tabs["security"]["inputs"]["social_login"]["input"]["value"] = bof()->object->user_setting->get($userData["ID"], "social_login", 1);
  }

  if ($submit) {

    if ($tab == "sessions") {
      $id = bof()->nest->user_input("post", "id", "md5");
      if ($id) {
        bof()->object->session->delete(array(
          "session_id" => $id,
          "user_id" => bof()->user->check()->ID
        ));
        bof()->chapar->notify_admin("user_session_delete", array(
          "session_id" => $id
        ));
      }
      $loader->api->set_message("ok");
      return;
    }

    try {
      $validate = $loader->bofForm->validate($tabs[$tab]);
    } catch (Exception $err) {
      $loader->api->set_error($err->getMessage(), ["output_args" => ["turn" => false]]);
      return;
    }

    if ($tab === "profile") {

      $set = array();
      if ($validate["name"]["exists"]) $set["name"] = $validate["name"]["value"];
      if ($validate["avatar_id"]["exists"]) $set["avatar_id"] = $validate["avatar_id"]["value"];
      if ($validate["bg_img_id"]["exists"]) $set["bg_img_id"] = $validate["bg_img_id"]["value"];

      if ($set) {
        $loader->object->user->update(array(
          "ID" => $loader->user->get()->ID
        ), $set);
        bof()->user->save_session();
      }

      bof()->chapar->notify_admin("user_edit_ok", array(
      ));

      $userData = $loader->object->user->select(
        array(
          "ID" => $loader->user->get()->ID
        ),
        array(
          "cache_load_rt" => false,
          "_eq" => array(
            "avatar" => [],
            "bg_img" => []
          )
        )
      );

    } elseif ($tab === "security") {

      if ($validate["new_password"]["value"]) {

        $verify_old_password = $loader->object->user->verify_password($validate["old_password"]["value"], $userData["password"]);
        if (!$verify_old_password) {
          $loader->api->set_error("wrong_old_password");
          bof()->chapar->notify_admin("user_secure_failed", array(
            "error" => "Wrong old password"
          ));
          return;
        }

        if ($validate["new_password"]["value"] !== $validate["new_password_verify"]["value"]) {
          $loader->api->set_error("passwords_dont_match");
          bof()->chapar->notify_admin("user_secure_failed", array(
            "error" => "New passwords dont match"
          ));
          return;
        }

        $hash_new_password = $loader->object->user->hash_password($validate["new_password"]["value"]);
        $loader->object->user->update(
          array(
            "ID" => $userData["ID"]
          ),
          array(
            "password" => $hash_new_password
          )
        );
        bof()->chapar->notify_admin("user_secure_ok", array(
        ));

      }

      bof()->object->user_setting->set($userData["ID"], "social_login", !empty($validate["social_login"]["value"]) ? 1 : 0);

    } elseif ($tab == "notifications") {

      $userNotifications = [];
      if ($definedNotifications) {
        foreach ($definedNotifications as $definedNotification) {
          $userNotifications[$definedNotification["hook"]] = !empty($validate[$definedNotification["hook"]]["value"]);
        }
      }

      $loader->object->user_setting->set($userData["ID"], "notify_email", $validate["__email__"]["value"] ? 1 : 0);
      $loader->object->user_setting->set($userData["ID"], "notifications", json_encode($userNotifications), "json");
      bof()->chapar->notify_admin("user_notification_ok", array(
      ));

    } elseif ($tab == "links") {

      $socialLinks = [];
      foreach ($socialPlatforms as $_ssK => $_sD) {
        if (!empty($validate[$_ssK]["value"]))
          $socialLinks[$_ssK] = $validate[$_ssK]["value"];
      }
      $loader->object->user->update(
        array(
          "ID" => $userData["ID"]
        ),
        array(
          "external_addresses" => json_encode($socialLinks)
        )
      );

      bof()->chapar->notify_admin("user_social_ok", array(
      ));

    } elseif ($tab == "delete") {

      $verify_old_password = $loader->object->user->verify_password($validate["password"]["value"], $userData["password"]);
      if (!$verify_old_password) {
        $loader->api->set_error("wrong_old_password");
        return;
      }

      bof()->object->user->delete(["ID" => $userData["ID"]]);

      bof()->chapar->notify_admin("user_delete_ok", array());

    }


    $userData = $loader->object->user->select(
      array(
        "ID" => $loader->user->get()->ID
      ),
      array(
        "cache_load_rt" => false,
        "_eq" => array(
          "avatar" => [],
          "bg_img" => []
        )
      )
    );

    $loader->api->set_message("saved", array(
      "user" => array(
        "ID" => $userData["ID"],
        "username" => $userData["username"],
        "name" => $userData["name_styled"],
        "email" => $userData["email"],
        "avatar" => !empty($userData["bof_file_avatar"]) ? $userData["bof_file_avatar"] : null,
        "avatar_url" => !empty($userData["bof_file_avatar"]["image_thumb"]) ? $userData["bof_file_avatar"]["image_thumb"] : null,
        "cover" => !empty($userData["bof_file_bg_img"]) ? $userData["bof_file_bg_img"] : null,
        "cover_url" => !empty($userData["bof_file_bg_img"]["image_thumb"]) ? $userData["bof_file_bg_img"]["image_thumb"] : null
      )
    ));
  } else {

    if (!empty($tabs[$tab]["inputs"])) {

      try {
        $inputs_parsed = $loader->bofForm->parse($tabs[$tab]);
      } catch (Exception $err) {
        $loader->api->set_error("failed: " . $err->getMessage(), ["output_args" => ["turn" => false]]);
        return;
      }

      $loader->api->set_message("ok", array(
        "tab" => $tab,
        "bofForm" => $inputs_parsed,
        "user" => array(
          "ID" => $userData["ID"],
          "username" => $userData["username"],
          "name" => $userData["name_styled"],
          "email" => $userData["email"],
          "avatar" => !empty($userData["bof_file_avatar"]) ? $userData["bof_file_avatar"] : null,
          "avatar_url" => !empty($userData["bof_file_avatar"]["image_thumb"]) ? $userData["bof_file_avatar"]["image_thumb"] : null,
          "cover" => !empty($userData["bof_file_bg_img"]) ? $userData["bof_file_bg_img"] : null,
          "cover_url" => !empty($userData["bof_file_bg_img"]["image_thumb"]) ? $userData["bof_file_bg_img"]["image_thumb"] : null
        ),
        "seo" => array(
          "title" => bof()->object->language->turn("setting", [], ["lang" => "users", "uc_first" => true])
        ),
        "tabs" => array_keys($tabs)
      ));
    } elseif (!empty($tabs[$tab]["content"])) {

      $loader->api->set_message("ok", array(
        "tab" => $tab,
        "content" => $tabs[$tab]["content"]($loader),
        "seo" => array(
          "title" => bof()->object->language->turn("setting", [], ["lang" => "users", "uc_first" => true])
        ),
        "tabs" => array_keys($tabs)
      ));
    }
  }
}
