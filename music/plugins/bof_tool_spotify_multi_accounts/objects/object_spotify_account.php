<?php

if (!defined("bof_root")) die;

class object_m_spotify_account extends bof_type_object
{

    public function bof()
    {
        return array(
            "name" => "m_spotify_account",
            "label" => "Spotify Account",
            "icon" => "account_box",
            "db_table_name" => "_c_m_spotify_accounts",
        );
    }
    public function columns()
    {
        return array(


            "client_id" => array(
                "label" => "Client ID",
                "tip" => "Client ID is the unique identifier of your application. <a href=\"https://developer.spotify.com/documentation/general/guides/app-settings/\">more info</a>",
                "col_name" => "client_id",
                "input" => array(
                    "name" => "client_id",
                    "type" => "text",
                    "placeholder" => "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
                ),
                "validator" => array(
                    "string",
                    array(
                        "strict" => true,
                        "empty()",
                    )
                ),
                "bofAdmin" => array(
                    "object" => array(
                        "required" => true,
                    ),
                    "list" => array(
                        "type" => "simple"
                    )
                )
            ),

            "client_secret" => array(
                "label" => "Client Secret",
                "tip" => "Client Secret is the key that you pass in secure calls to the Spotify Accounts and Web API services. <a href=\"https://developer.spotify.com/documentation/general/guides/app-settings/\">more info</a>",
                "col_name" => "client_secret",
                "input" => array(
                    "name" => "client_secret",
                    "type" => "text",
                    "placeholder" => "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
                ),
                "validator" => array(
                    "string",
                    array(
                        "strict" => true,
                        "empty()",
                    )
                ),
                "bofAdmin" => array(
                    "object" => array(
                        "required" => true,
                    ),
                    "list" => array(
                        "type" => "simple"
                    )
                )
            ),

            "token" => array(
                "validator" => array(
                    "string",
                    array(
                        "empty()"
                    )
                )
            ),

            "proxy" => array(
                "label" => "Proxy",
                "tip" => "Proxy in type://username:password@address:port format. Example: socks5://rawmean:busyowl@6.64.69.66:2080<br><br><b>Creating multiple Spotify accounts without proxy is counter productive as Spotify limits requests based on IP address as well</b>",
                "input" => array(
                    "name" => "proxy",
                    "type" => "text",
                    "placeholder" => "type://username:password@address:port"
                ),
                "validator" => array(
                    "string",
                    array(
                        "empty()"
                    )
                ),
                "bofAdmin" => array(
                    "object" => array(
                    ),
                    "list" => array(
                        "type" => "tag"
                    )
                )
            ),

            "sta_ok_reqs" => array(
                "label" => "OK Requests",
                "validator" => array(
                    "int",
                    array(
                        "empty()"
                    )
                ),
                "bofAdmin" => array(
                    "list" => array(
                        "type" => "simple"
                    )
                )
            ),

            "sta_failed_reqs" => array(
                "label" => "Failed Requests",
                "validator" => array(
                    "int",
                    array(
                        "empty()"
                    )
                ),
                "bofAdmin" => array(
                    "list" => array(
                        "type" => "simple"
                    )
                )
            ),

            "sta_limited_reqs" => array(
                "label" => "Timed out Requests",
                "validator" => array(
                    "int",
                    array(
                        "empty()"
                    )
                ),
                "bofAdmin" => array(
                    "list" => array(
                        "type" => "simple"
                    )
                )
            ),

            "time_used" => array(
                "validator" => array(
                    "timestamp",
                    array(
                        "empty()"
                    )
                )
            ),

            "time_limited" => array(
                "validator" => array(
                    "timestamp",
                    array(
                        "empty()"
                    )
                )
            ),

            "active" => array(
                "label" => "Active",
                "validator" => array(
                  "boolean",
                  array(
                    "empty()",
                    "int" => true
                  ),
                ),
                "input" => array(
                  "type" => "checkbox",
                ),
                "selectors" => array(
                  "active" => [ "active", "=" ],
                ),
                "bofAdmin" => array(
                  "sortable" => true,
                  "filters" => array(
                    "active" => array(
                      "title" => "Status",
                      "input" => array(
                        "name" => "active",
                        "type" => "select_i",
                        "options" => array(
                          [ 0, "in-active" ],
                          [ 1, "active" ],
                          [ "__all__", "all" ]
                        ),
                        "value" => "__all__"
                      ),
                      "validator" => array(
                        "in_array",
                        array(
                          "values" => [ "__all__", "0", "1" ]
                        )
                      )
                    ),
                  ),
                  "list" => array(
                    "type" => "boolean",
                    "args" => array(
                      "payloads" => [ "activate", "deactivate" ]
                    )
                  ),
                  "object" => array(
                  )
                ),
              ),

        );
    }
    public function bof_columns()
    {
        return array(
            "ID",
            "time_add",
        );
    }
    public function selectors()
    {
        return array(
            "usable" => function( $val ){
                if ( !$val ) return;
                return array(
                    "oper" => "AND",
                    "cond" => array(
                        [ "active", "=", "1" ],
                        array(
                            "oper" => "OR",
                            "cond" => array(
                                [ "time_limited", null, null, true ],
                                [ "time_limited", "<", "SUBDATE( now(), INTERVAL 2 HOUR )", true ]
                            )
                        )
                    )
                );
            }
        );
    }
    public function bof_admin()
    {
        return array(
            "config" => array(
                "search" => false,
                "create" => true,
                "edit" => true,
                "delete" => true,
                "pagination" => true,
                "edit_page_url" => "music_spotify_account",
                "list_page_url" => "music_spotify_accounts",
                "multi" => array(
                    "select" => true,
                    "delete" => true,
                    "edit"   => false
                )
            ),
            "buttons" => array(
                "activate" => array(
                    "id" => "activate",
                    "label" => "Activate",
                    "payload" => array(
                        "post" => array(
                            "__action" => "activate"
                        )
                    )
                ),
                "deactivate" => array(
                    "id" => "deactivate",
                    "label" => "De-Activate",
                    "payload" => array(
                        "post" => array(
                            "__action" => "deactivate"
                        )
                    )
                ),
            ),
            "buttons_renderer" => function ($item, $buttons) {

                if ($item["active"])
                    unset($buttons["activate"]);
                else
                    unset($buttons["deactivate"]);

                return $buttons;
            },
            "actions" => array(
                "activate" => function ($ids) {
                    bof()->object->m_spotify_account->update(
                        array(
                            "ID_in" => $ids
                        ),
                        array(
                            "active" => 1
                        )
                    );
                    return [true, "activated"];
                },             
                "deactivate" => function ($ids) {
                    bof()->object->m_spotify_account->update(
                        array(
                            "ID_in" => $ids
                        ),
                        array(
                            "active" => 0
                        )
                    );
                    return [true, "deactivated"];
                }
            ),
        );
    }

}
