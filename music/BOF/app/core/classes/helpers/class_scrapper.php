<?php

if ( !defined( "bof_root" ) ) die;

class scrapper extends bof_type_class{

    public function setup_admin( $args ){

        $title = null;
        $id = null;
        extract( $args );

        if ( !$title || !$id )
        fall( "Invalid args scrapper.setup_admin" );

        bof()->listen( "highlights", "display_pre", function ($method_args, $method_result, $loader, $output, $args) {

            $highlights = $loader->highlights->getData();
            $highlights["scrappers_sec"]["items"]["scrapper_links"]["args"]["childs"][] = array(
                "title" => "{$args["title"]} Scrapper",
                "icon" => "pest_control",
                "link" => "{$args["id"]}_scrapper"
            );
            bof()->highlights->setData($highlights);

        }, $args );

        bof()->listen( "client_config", "get_pages_after", function ($method_args, &$method_result, $loader, $output, $args ) {

            if (is_array($method_result)) {
                $method_result["{$args["id"]}_scrapper"] = array(
                    "title" => "{$args["title"]} Scrapper Setting",
                    "url" => "^{$args["id"]}_scrapper$",
                    "link" => "{$args["id"]}_scrapper",
                    "theme_file" => "parts/content_setting",
                    "becli" => array(
                        (object) array(
                            "endpoint" => "bofAdmin/setting/{$args["id"]}_scrapper/",
                            "key" => "setting"
                        )
                    ),
                    "__sb_family" => "extensions",
                );
            }

        }, $args );

        bof()->listen( "bofAdmin", "setting_pre", function ($method_args, $method_result, $loader, $output, $args) {

            $setting = array(
                "groups" => array(
                    "setting" => array(
                        "title" => "Setting",
                        "icon" => "cloud_sync",
                        "inputs" => array(
                            "{$args["id"]}_s" => array(
                                "title" => "Active",
                                "tip" => ( !empty( $args["detail"] ) ? $args["detail"] : "If activated, this scrapper will replace the existing official API (if any)" ) . "<br><br><b style='color:rgb(var(--c_red))'>Disclaimer: Scraping may violate laws or website terms; you assume all legal risks</b>",
                                "col_name" => "{$args["id"]}_s",
                                "input" => array(
                                    "name" => "{$args["id"]}_s",
                                    "type" => "checkbox",
                                ),
                                "validator" => array(
                                    "boolean",
                                    array(
                                        "empty()",
                                        "int" => true
                                    )
                                )
                            ),
                            "{$args["id"]}_sp" => array(
                                "title" => "Proxy",
                                "col_name" => "{$args["id"]}_sp",
                                "input" => array(
                                    "name" => "{$args["id"]}_sp",
                                    "type" => "text",
                                ),
                                "tip" => "If defined, the scraper will route requests through this proxy to hide your server's IP<br>Format: `type://user:pass@ip:port`<br>Example: `socks5://john:abc123@192.168.1.5:8080`",
                                "validator" => array(
                                    "string",
                                    array(
                                        "empty()"
                                    )
                                )
                            ),
                        )
                    ),
                ),
                "action_btn_title" => "Save"
            );

            if ( !empty( $args["functions"] ) ){
                $setting["functions"] = $args["functions"];
            }

            bof()->bofAdmin->_add_setting("{$args["id"]}_scrapper", $setting);

        }, $args );
    }

}

?>