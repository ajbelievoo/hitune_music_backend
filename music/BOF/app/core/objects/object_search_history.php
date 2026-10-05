<?php

if (!defined("bof_root")) die;

class object_search_history extends bof_type_object
{

    public function bof()
    {
        return array(
            "name" => "search_history",
            "label" => "User Search History",
            "icon" => "key",
            "db_table_name" => "_d_search_history",
        );
    }
    public function columns()
    {
        return array(
            "user_id" => array(
                "label" => "User",
                "bofAdmin" => array(
                    "list" => array(
                        "type" => "simple",

                    ),
                    "filters" => array(
                        "col_user" => array(
                            "title" => "User(s)",
                            "input" => array(
                                "name" => "col_user",
                                "type" => "bof_input",
                            ),
                            "bofInput" => array(
                                "object",
                                array(
                                    "type" => "user",
                                    "multi" => true,
                                    "args" => array(
                                        "filter" => "col_user",
                                    )
                                )
                            )
                        ),
                    ),
                ),
                "bofInput" => array(
                    "object",
                    array(
                        "type" => "user",
                        "multi" => false
                    )
                ),
                "relations" => array(
                    "user" => array(
                        "exec" => array(
                            "type" => "direct",
                            "parent_object" => "user",
                            "child_object" => "search_history",
                            "child_object_selector_column" => "user_id",
                            "delete_child_too" => true,
                            "limit" => 1
                        ),
                    ),
                ),
                "selectors" => array(
                    "col_user" => ["user_id", "by_column"],
                    "user_id" => ["user_id", "="]
                )
            ),
            "user_ip" => array(
                "label" => "User IP",
                "validator" => "ip",
            ),
            "query" => array(
                "label" => "Query",
                "bofAdmin" => array(
                    "list" => array(
                        "type" => "simple"
                    ),
                ),
                "validator" => "string"
            ),
            "token" => array(
                "label" => "Tokens",
                "bofAdmin" => array(
                    "list" => array(
                        "type" => "simple"
                    ),
                ),
                "validator" => "string"
            ),
            "object_type" => array(
                "label" => "Object type",
                "validator" => "string",
            ),
            "target_object_type" => array(
                "label" => "Target - Object type",
                "validator" => array(
                    "string",
                    array(
                        "empty()"
                    )
                )
            ),
            "target_object_id" => array(
                "validator" => array(
                    "int",
                    array(
                        "empty()"
                    )
                )
            ),
            "time_exe" => array(
                "label" => "Execution time",
                "validator" => array(
                    "float",
                    array(
                        "empty()"
                    )
                )
            ),
            "time_redirect" => array(
                "label" => "User react time",
                "validator" => array(
                    "timestamp",
                    array(
                        "empty()"
                    )
                )
            )
        );
    }
    public function bof_columns()
    {
        return array(
            "hash",
            "ID",
            "time_add"
        );
    }
    public function selectors()
    {
        return array(
            "user_id" => ["user_id", "="],
            "display" => function ($val) {
                return [ "target_object_type", "NOT", null, true ];
            },
            "users" => function ($val) {
                if ($val) {

                    $userID = false;
                    if ($userID)
                        $userWhere = ["user_id", "=", $userID];
                    else
                        $userWhere = array(
                            "oper" => "AND",
                            "cond" => array(
                                ["user_ip", "=", bof()->request->get_userIP()["string"]],
                                ["time_add", ">", "SUBDATE( now(), INTERVAL 2 DAY )", true]
                            )
                        );

                    return $userWhere;
                    
                }
            }
        );
    }
    public function relations()
    {
        return array();
    }
    public function bof_admin()
    {
        return array(
            "config" => array(
                "search" => false,
                "create" => false,
                "edit" => false,
                "delete" => false,
                "pagination" => true,
                "edit_page_url" => "search_history",
                "list_page_url" => "search_histories",
                "multi" => array(
                    "select" => false,
                    "delete" => false,
                    "edit"   => false
                )
            ),
        );
    }

    public function select($whereArgs = [], $selectArgs = [])
    {

        $search = null;
        $listing = null;
        $deleting = null;
        $editing = null;
        $_eq = [];
        extract($selectArgs);

        $display = false;
        extract( $whereArgs );

        if ( $display )
        $selectArgs["group_by"] = "GROUP BY target_object_type,target_object_id";

        $selectArgs["_eq"] = $_eq;

        return bof()->object->_select($this, $whereArgs, $selectArgs);
    }

    public function clean($item, $args)
    {

        $get_object_item = true;
        $library_page = false;
        extract($args);

        if ($get_object_item && !empty($item["target_object_type"] && !empty($item["target_object_id"]))) {

            if (bof()->object->core_files->validate_key("object", $item["target_object_type"])) {
                $property_object = bof()->object->__get($item["target_object_type"]);
                $property_item = $property_object->select(
                    array(
                        "ID" => $item["target_object_id"]
                    ),
                    array(
                        "as_widget" => true,
                        "_eq" => array(
                            "cover" => []
                        )
                    )
                );
            }

            if (!empty($property_item)) {
                $property_item["ot"] = $item["target_object_type"];
                if (!empty($property_item["raw"])) {
                    $property_item["buttons"] = bof()->bofClient->__parse_item_buttons(
                        $item["target_object_type"],
                        $property_object,
                        $property_item["raw"],
                        $property_object->bof_client()["buttons"]
                    );
                }
            } else {
                return false;
            }

            $item["property"] = $property_item;
        }

        return $item;
    }
    public function clean_as_widget($item, $args)
    {

        $library_page = false;
        extract($args);

        if (empty($item["property"]))
            return;

        return array(
            "title"    => $item["property"]["title"],
            "sub_data" => bof()->object->language->turn($item["target_object_type"], [], ["uc_first" => true, "lang" => "users"]),
            "sub_link" => null,
            "cover"    => !empty($item["property"]["cover"]) ? $item["property"]["cover"] : null,
            "raw"      => $item["property"]["raw"],
            "ot"       => $item["target_object_type"],
            "on"       => bof()->object->language->turn($item["target_object_type"], [], ["uc_first" => true, "lang" => "users"]),
            "buttons"  => $item["property"]["buttons"],
            "hash"     => $item["property"]["raw"]["hash"]
        );
    }
}
