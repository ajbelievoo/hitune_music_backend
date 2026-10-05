<?php

if (!defined("bof_root")) die;

class tool_spotify_multi_accounts
{

    private $account = null;

    public function setup()
    {

        bof()->object->core_files->add_object("m_spotify_account", bof_tool_spotify_multi_accounts . "/objects/object_spotify_account.php");

        if (bof()->getName() == "bof_admin")
            $this->setup_admin();

        $this->parasite();
    }

    public function setup_admin()
    {

        bof()->bofAdmin->_add_object("m_spotify_account", ["seo" => false]);
        bof()->listen("client_config", "get_pages_after", function ($method_args, &$method_result, $loader) {
            $method_result["music_spotify_accounts"] = array(
                "title" => "Music Spotify Accounts",
                "url" => "^music_spotify_accounts$",
                "link" => "music_spotify_accounts",
                "theme_file" => "parts/content_table",
                "becli" => array(
                    (object) array(
                        "endpoint" => "bofAdmin/list/m_spotify_account/?\$bof ? urlData^url^query_s\$",
                        "key" => "content",
                    )
                ),
                "__sb_family" => "content",
            );
            $method_result["music_spotify_account"] = array(
                "title" => "Music Spotify Account",
                "url" => "^music_spotify_account\/(.*?)$",
                "link" => "music_spotify_account",
                "link_par" => "music_spotify_accounts",
                "theme_file" => "parts/content_single",
                "becli" => array(
                    (object) array(
                        "endpoint" => "bofAdmin/object/m_spotify_account/?IDs=\$bof ? urlData^url^match^0\$&\$bof ? urlData^url^query_s\$",
                        "key" => "entity",
                    )
                ),
                "__sb_family" => "content",
            );
        });
        bof()->listen("highlights", "display_pre", function ($method_args, $method_result, $loader) {

            $highlights = bof()->highlights->getData();
            $highlights["content_links"]["items"]["music"]["args"]["childs"][] = array(
                "icon"  => "account_box",
                "title" => "Spotify Accounts",
                "link"  => "music_spotify_accounts"
            );
            $highlights["extensions_links"]["items"]["tools_links"]["args"]["childs"][] = array(
                "icon"  => "account_box",
                "title" => "Spotify Multi Accounts",
                "link"  => "music_spotify_accounts"
            );
            bof()->highlights->setData($highlights);
        });
    }

    public function set_account()
    {
        $this->account = bof()->object->m_spotify_account->select(
            array(
                "usable" => true
            ),
            array(
                "single" => true,
                "limit" => 1,
                "order" => "ASC",
                "order_by" => "time_used",
                "cache_load_rt" => false
            )
        );
        return $this->account;
    }
    public function get_account(){
        return $this->account;
    }
    public function set_token( $newToken ){
        $this->account["token"] = $newToken;
    }

    public function parasite()
    {

        bof()->listen("spotify", "set_configs_replace", function ($input, $output) {
            $account = bof()->tool_spotify_multi_accounts->set_account();
            return $account ? true : false;
        });

        bof()->listen("spotify", "get_keys_replace", function ($input, $output) {
            $account = bof()->tool_spotify_multi_accounts->get_account();
            if ( !$account ) return false;
            return array(
                "client_id" => $account["client_id"],
                "client_key" => $account["client_secret"],
                "token" => $account["token"],
                "proxy" => $account["proxy"]
            );
        });

        bof()->listen("spotify", "set_token_replace", function ($input, $output) {
            $account = bof()->tool_spotify_multi_accounts->get_account();
            if ( !$account ) return false;
            bof()->object->m_spotify_account->update(
                array(
                    "ID" => $account["ID"]
                ),
                array(
                    "token" => $input[0]
                )
            );
            bof()->tool_spotify_multi_accounts->set_token( $input[0] );
            return true;
        });

        bof()->listen("spotify", "check_result_replace", function ($input, $output) {
            $account = bof()->tool_spotify_multi_accounts->get_account();
            if ( !$account ) return false;
            $result = $input[0];
            if ( $result["http_code"] == 429 ){
                bof()->object->m_spotify_account->update(
                    array(
                        "ID" => $account["ID"]
                    ),
                    array(
                        "time_used" => bof()->general->mysql_timestamp(),
                        "time_limited" => bof()->general->mysql_timestamp(),
                        "sta_limited_reqs" => $account["sta_limited_reqs"] + 1
                    )
                );
            } elseif ( $result["http_code"] != 200 ){
                bof()->object->m_spotify_account->update(
                    array(
                        "ID" => $account["ID"]
                    ),
                    array(
                        "time_used" => bof()->general->mysql_timestamp(),
                        "sta_failed_reqs" => $account["sta_failed_reqs"] + 1
                    )
                );
            } else {
                bof()->object->m_spotify_account->update(
                    array(
                        "ID" => $account["ID"]
                    ),
                    array(
                        "time_used" => bof()->general->mysql_timestamp(),
                        "sta_ok_reqs" => $account["sta_ok_reqs"] + 1
                    )
                );
            }
            bof()->tool_spotify_multi_accounts->set_account();
        });
    }
}
