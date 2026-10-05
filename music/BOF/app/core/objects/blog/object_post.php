<?php

if ( !defined( "bof_root" ) ) die;

class object_b_post {

  // BusyOwlFramework handshake
  public function bof(){
    return array(
      "name" => "b_post",
      "label" => "Blog Post",
      "icon" => "article",
      "db_table_name" => "_c_b_posts",
      "db_rel_table_name" => "_c_b_posts_relations",
      "db_rel_table_col_name" => "post_id",
      "widgetable" => true,
      "browsable" => true,
    );
  }
  public function columns(){
    return array(
      "title" => array(

        "public" => true,
        "label" => "Title",
        "validator" => array(
          "string",
          array(
            "strip_emoji" => false
          ),
        ),
        "input" => array(
          "type" => "text"
        ),
        "bofAdmin" => array(
          "sortable" => true,
          "object" => array(
            "required" => true,
            "seo_slug_source" => true
          ),
          "list" => array(
            "type" => "simple",
            "class" => "title",
            "renderer" => function ( $displayItem, $item, $displayData ){
              if ( !empty( $item["bof_file_cover"] ) )
              $displayData["image_preview"] = $item["bof_file_cover"]["image_thumb"];
              return $displayData;
            },
          ),
        ),

      ),
      "content" => array(

        "public" => true,
        "label" => "Content",
        "validator" => "editor_js",
        "input" => array(
          "type" => "text_editor"
        ),
        "bofAdmin" => array(
          "object" => array(
            "required" => true,
          ),
        ),

      ),
      "user_id" => array(

        "label" => "Author",
        "validator" => array(
          "int",
          array(
            "empty()"
          )
        ),
        "bofInput" => array(
          "object",
          array(
            "type" => "user",
            "multi" => false,
          )
        ),
        "bofAdmin" => array(
          "object" => array(
            "required" => true,
            "multi" => true
          ),
          "list" => array(
            "type" => "simple",
            "class" => "username",
            "renderer" => function ( $displayItem, $item, $displayData ){
              $displayData["data"] = "@" . ( $item["bof_dir_user"] ? $item["bof_dir_user"]["username"] : "?" );
              return $displayData;
            },
          ),
          "filters" => array(
            "col_user" => array(
              "title" => "Author(s)",
              "bofInput" => array(
                "object",
                array(
                  "type" => "user"
                )
              )
            )
          ),
        ),
        "selectors" => array(
          "col_user" => [ "user_id", "by_column" ],
        ),
        "relations" => array(
          "user" => array(
            "exec" => array(
              "type" => "direct",
              "parent_object" => "user",
              "parent_object_stats_column" => "s_posts",
              "child_object" => "b_post",
              "child_object_selector_column" => "user_id",
              "limit" => 1,
              "delete_child_too" => true
            )
          )
        ),

      ),
      "s_categories" => array(
        "validator" => array(
          "int",
          array(
            "empty()",
            "min" => 0
          )
        ),
      ),
      "time_edit" => array(

        "label" => "Edit time",
        "validator" => array(
          "timestamp",
          array(
            "empty()"
          )
        ),
        "input" => array(
          "type" => "time"
        ),
        "bofAdmin" => array(
          "object" => array(
            "multi" => true
          )
        ),

      ),
      "active" => array(

        "label" => "Published",
        "input" => array(
          "type" => "checkbox"
        ),
        "validator" => array(
          "boolean",
          array(
            "empty()",
            "int" => true
          )
        ),
        "bofAdmin" => array(
          "object" => array(
            "multi" => true,
            "group" => "setting",
          ),
          "list" => array(
            "type" => "boolean",
            "args" => array(
              "payloads" => [ "publish", "unpublish" ]
            )
          ),
          "filters" => array(
            "published" => array(
              "title" => "Published",
              "input" => array(
                "type" => "select_i",
                "options" => array(
                  [ 0, "No" ],
                  [ 1, "Yes" ],
                  [ "__all__", "All" ],
                ),
                "value" => "__all__"
              ),
              "validator" => array(
                "in_array",
                array(
                  "values" => [ "1", "0", "__all__" ]
                )
              )
            ),
          ),
        ),

      ),
    );
  }
  public function relations(){
    return array(
      "categories" => array(
        "bofAdmin" => array(
          "filters" => array(
            "b_category_ids" => array(
              "title" => "Category(s)",
              "bofInput" => array(
                "object",
                array(
                  "type" => "b_category",
                  "multi" => true,
                )
              )
            ),
          ),
          "objects" => array(
            "b_post_categories" => array(
              "label" => "Category(s)",
              "column_name" => "category_ids",
              "bofInput" => array(
                "object",
                array(
                  "type" => "b_category",
                  "multi" => true,
                )
              ),
              "bofAdmin" => array(
                "object" => array(
                  "multi" => true
                )
              )
            ),
          ),
          "lists" => array(
            "categories" => array(
              "type" => "simple",
              "class" => "tags",
              "label" => "Categories",
              "renderer" => function ( $displayItem, $item, $displayData ){
                $displayData["data"] = "<div>";
                if ( !empty( $item["bof_rel_categories"] ) ){
                  foreach( $item["bof_rel_categories"] as $_cat ){
                    $displayData["data"] .= "<span>{$_cat["name"]}</span>";
                  }
                }
                $displayData["data"] .= "</div>";
                return $displayData;
              },
            )
          ),
        ),
        "bofClient" => array(
          "filters" => array(
            "b_category_ids" => "_bofAdmin"
          )
        ),
        "exec" => array(
          "type" => "hub",
          "hub_type" => "category",
          "parent_object" => "b_post",
          "parent_object_stats_column" => "s_categories",
          "child_object" => "b_category",
          "child_object_stats_column" => "s_posts"
        ),
      ),
      "tags" => array(
        "bofAdmin" => array(
          "filters" => array(
            "b_tag_ids" => array(
              "title" => "Tag(s)",
              "bofInput" => array(
                "object",
                array(
                  "type" => "b_tag",
                  "multi" => true,
                )
              )
            ),
          ),
          "objects" => array(
            "b_post_tags" => array(
              "label" => "Tag(s)",
              "column_name" => "tag_ids",
              "bofInput" => array(
                "object",
                array(
                  "type" => "b_tag",
                  "multi" => true,
                )
              ),
              "bofAdmin" => array(
                "object" => array(
                  "multi" => true
                )
              )
            ),
          ),
        ),
        "bofClient" => array(
          "filters" => array(
            "b_tag_ids" => "_bofAdmin"
          )
        ),
        "exec" => array(
          "type" => "hub",
          "hub_type" => "tag",
          "parent_object" => "b_post",
          "parent_object_stats_column" => "s_categories",
          "child_object" => "b_tag",
          "child_object_stats_column" => "s_posts"
        ),
      ),
    );
  }
  public function stats_columns(){
    return array(
      "views",
      "views_unique",
      "shares",
    );
  }
  public function selectors(){
    return array(
      "user_id" => [ "user_id", "=" ],
      "query" => [ "title", "LIKE%lower" ],
      "active" => function( $val ){
        if ( $val === 0 || $val === "0" || $val === false )
        return [ "active", "=", "0" ];
        elseif ( $val === 1 || $val === "1" || $val === true )
        return [ "active", "=", "1" ];
      },
      "b_category_ids" => [ "ID", "parent_with_relations", "rel_parent" => "b_post", "hub_type" => "category" ],
      "b_tag_ids" => [ "ID", "parent_with_relations", "rel_parent" => "b_post", "hub_type" => "tag" ],
      "rel_category" => [ "ID", "parent_with_relations", "rel_parent" => "b_post", "hub_type" => "category" ],
      "rel_tag" => [ "ID", "parent_with_relations", "rel_parent" => "b_post", "hub_type" => "tag" ],
      "published" => [ "active", "=" ]
    );
  }
  public function bof_columns(){
    return array(
      "ID",
      "hash",
      "time_add",
      "seo" => array(
        "o_title_format" => array(
          "title" => "title",
          "author_username" => "author's username"
        )
      ),
      // "price" => [],
      "cover"
    );
  }
  public function bof_admin(){
    return array(
      "config" => array(
        "search" => true,
        "create" => true,
        "edit" => true,
        "delete" => true,
        "pagination" => true,
        "edit_page_url" => "blog_post",
        "list_page_url" => "blog_posts",
        "multi" => array(
          "select" => true,
          "delete" => true,
          "edit"   => true
        )
      ),
      "buttons" => array(
        "activate" => array(
          "id" => "activate",
          "label" => "Publish",
          "payload" => array(
            "post" => array(
              "__action" => "publish"
            )
          )
        ),
        "deactivate" => array(
          "id" => "deactivate",
          "label" => "Un-Publish",
          "payload" => array(
            "post" => array(
              "__action" => "unpublish"
            )
          )
        ),
      ),
      "buttons_renderer" => function( $item, $buttons ){

        if ( $item["active"] )
        unset( $buttons["activate"] );

        if ( !$item["active"] )
        unset( $buttons["deactivate"] );

        return $buttons;

      },
      "filters" => array(),
      "list" => array(
        "title" => null,
        "categories" => null,
        "user_id" => null,
        "stats" => array(
          "type" => "simple",
          "class" => "details",
          "label" => "Stats",
          "renderer" => function( $displayItem, $item, $displayData ){
            $displayData["data"] = "<ul>";
            $displayData["data"] .= "<li><b>Views:</b> {$item["s_views"]}</li>";
            $displayData["data"] .= "<li><b>Unique Views:</b> {$item["s_views_unique"]}</li>";
            $displayData["data"] .= "</ul>";
            return $displayData;
          },
        ),
        "active" => null
      ),
      "object" => array(),
      "object_groups" => array(
        [ "setting", "Setting" ],
        // [ "price", "Price" ]
      ),
      "actions" => array(
        "publish" => function( $ids ){
          bof()->object->b_post->update(array(
            "ID_in" => $ids
          ),array(
            "active" => 1
          ));
          return [ true, "Published" ];
        },
        "unpublish" => function( $ids ){
          bof()->object->b_post->update(array(
            "ID_in" => $ids
          ),array(
            "active" => 0
          ));
          return [ true, "Un-Published" ];
        },
      ),

    );
  }
  public function bof_client(){
    return array(
      "single_url_prefix" => "article",
      "list_url" => "articles",
      "buttons" => array(
        "link" => true,
        "share" => true,
        "extra_after" => array(
          "visit_tags" => array(
            "dynamic" => true,
            "func" => function( $button, $item, $args ){

              $button = null;
              $tags = !empty( $item["bof_rel_tags"] ) ? $item["bof_rel_tags"] : bof()->object->b_tag->select(["b_post_tags"=>$item["ID"]],["limit"=>20]);
              if ( $tags ){
                $button = array(
                  "title" => "Open Tag",
                  "icon" => "open-in-app",
                  "childs" => array()
                );
                foreach( $tags as $tag ){
                  $button["childs"][] = array(
                    "icon" => "open-in-app",
                    "title" => $tag["name"],
                    "url" => $tag["url"]
                  );
                }
              }

              return $button;

            }
          ),
          "visit_categories" => array(
            "dynamic" => true,
            "func" => function( $button, $item, $args ){

              $button = null;
              $categories = !empty( $item["bof_rel_categories"] ) ? $item["bof_rel_categories"] : bof()->object->b_category->select(["b_post_categories"=>$item["ID"]],["limit"=>20]);
              if ( $categories ){
                $button = array(
                  "title" => "Open Category",
                  "icon" => "open-in-app",
                  "childs" => array()
                );
                foreach( $categories as $tag ){
                  $button["childs"][] = array(
                    "icon" => "open-in-app",
                    "title" => $tag["name"],
                    "url" => $tag["url"]
                  );
                }
              }

              return $button;

            }
          ),
        )
      )
    );
  }

  // BusyOwlFramework helpers
  public function insert( $setArray ){

    if ( empty( $setArray["hash"] ) )
    $setArray["hash"] = $this->_bof_this->get_free_hash();

    return bof()->object->_insert( $this, $setArray );

  }
  public function select( $whereArgs=[], $selectArgs=[] ){

    $search = false;
    $listing = false;
    $as_widget = false;
    $client_single = false;
    $indexed = false;
    $client_widget = false;
    $match_page = false;
    $search_terms = false;
    $_eq = [];
    extract( $selectArgs );
    $selectArgs[ "_eq" ] = $_eq;

    if ( $search || $listing || $as_widget || $client_single ){
      $_eq[ "cover" ] = [];
    }

    if ( $search_terms ){
      $_eq["categories"] = array(
        "limit" => 3,
        "clean" => false
      );
      $_eq["tags"] = array(
        "limit" => 3,
        "clean" => false
      );
    }

    if ( $client_single ){
      $_eq[ "categories" ] = array(
        "public" => true
      );
      $_eq[ "tags" ] = array(
        "public" => true
      );
    }
    if ( $match_page ){
      $_eq[ "user"] = ["clean"=>false];
    }

    if ( $as_widget ){
      $_eq[ "user" ] = [];
    }

    if ( $listing ){
      $_eq[ "categories" ] = [];
      $_eq[ "user" ] = [];
    }

    if ( $indexed || $client_single )
    $whereArgs["active"] = true;

    $selectArgs["_eq"] = $_eq;
    return bof()->object->_select( $this, $whereArgs, $selectArgs );

  }
  public function clean( $item, $args=[] ){

    $_eq = [];
    $search = false;
    $listing = false;
    $match_page = false;
    extract( $args );

    if ( $search ){
      $item = array(
        "ID" => $item["ID"],
        "title" => $item["title"],
        "image" => !empty( $item["bof_file_cover"]["image_thumb"] ) ? $item["bof_file_cover"]["image_thumb"] : false
      );
    }
    if ( $match_page ){
      $item = array(
        "title" => $item["title"],
        "author_username" => !empty( $item["bof_dir_user"]["username"] ) ? $item["bof_dir_user"]["username"] : null,
        "seo_url" => $item["seo_url"],
        "cover_id" => $item["cover_id"]
      );
    }

    return $item;

  }
  public function clean_as_widget( $item, $args=[] ){

    return array(
      "title" => $item["title"],
      "sub_data" => "",
      "cover" =>  !empty( $item["bof_file_cover"] ) ? $item["bof_file_cover"] : null,
      "ot" => "b_post",
      "raw" => $item
    );

  }
  public function clean_client_single( $item, $args=[] ){

    $widgets = $page = [];

    return array(
      "widgets" => $widgets,
      "data" => $item,
      "page" => $page
    );

  }
  public function clean_search_terms( $item ){

    $o = array(
      $item["title"] => 1,
    );

    foreach( [ "categories", "tags" ] as $_k ){
      if ( !empty( $item["bof_rel_{$_k}"] ) ){
        foreach( $item["bof_rel_{$_k}"] as $_i ){
          $o[ $_i["name"] ] = $_k == "tags" ? 0.2 : 0.14;
        }
      }
    }

    return $o;

  }

}

?>
