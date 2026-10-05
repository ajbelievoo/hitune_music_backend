<?php

if ( !defined( "bof_root" ) ) die;

class editorjs extends bof_type_class {

  public function finalize( $caller, $new_data_id, $new_data, $old_data=null ){

    $files = array(
      "images" => [],
      "videos" => []
    );


    $old_data = $old_data ? ( is_array( $old_data ) ? $old_data : json_decode( $old_data, 1 ) ) : null;
    $new_data = is_array( $new_data ) ? $new_data : ( $new_data ? json_decode( $new_data, 1 ) : [] );

    if ( !empty( $new_data["blocks"] ) ){
      foreach( $new_data["blocks"] as $_bi => $_block ){

        $_block_data = !empty( $_block["data"] ) ? $_block["data"] : null;

        if ( $_block["type"] == "image" && $_block_data ){
          if ( !empty( $_block_data["file"]["id"] ) && !empty( $_block_data["file"]["key"] ) ){
            if ( bof()->object->file->validate_pass( $_block_data["file"]["id"], $_block_data["file"]["key"] ) ){

              $_finalize_upload = bof()->object->file->finalize_upload(
                "image",
                "editorjs_img",
                $caller->direct->bof()["name"] . $new_data_id,
                $_block_data["file"]["id"],
                false
              );

              if ( $_finalize_upload ){
                $_file_new_data = bof()->object->file->select(["ID"=>$_block_data["file"]["id"]],["cache_load_rt"=>false]);
                $new_data["blocks"][ $_bi ]["data"]["file"]["url"] = $_file_new_data["web_address"];
                $files["images"][] = $_block_data["file"]["id"];
              }

            }
          }
        }
        elseif ( $_block["type"] == "video" && $_block_data ){

          if ( !empty( $_block_data["file"]["id"] ) && !empty( $_block_data["file"]["key"] ) ){
            if ( bof()->object->file->validate_pass( $_block_data["file"]["id"], $_block_data["file"]["key"] ) ){

              $_finalize_upload = bof()->object->file->finalize_upload(
                "video",
                "editorjs_vid",
                $caller->direct->bof()["name"] . $new_data_id,
                $_block_data["file"]["id"],
                false,
                array(
                  "encrypt" => false,
                  "lower" => false,
                )
              );

              if ( $_finalize_upload ){
                $_file_new_data = bof()->object->file->select(["ID"=>$_block_data["file"]["id"]],["cache_load_rt"=>false]);
                $new_data["blocks"][ $_bi ]["data"]["file"]["url"] = $_file_new_data["web_address"];
                $files["videos"][] = $_block_data["file"]["id"];
              }

            }
          }
        }

      }
    }

    $new_data["files"] = $files;

    if ( !empty( $old_data["files"]["images"] ) ? array_diff( $old_data["files"]["images"], $files["images"] ) : false ){
      $removed_images = array_diff( $old_data["files"]["images"], $files["images"] );
      foreach( $removed_images as $removed_image ){
        bof()->object->file->unlink( $removed_image, $caller->direct->bof()["name"] . $new_data_id );
      }
    }
    if ( !empty( $old_data["files"]["videos"] ) ? array_diff( $old_data["files"]["videos"], $files["videos"] ) : false ){
      $removed_videos = array_diff( $old_data["files"]["videos"], $files["videos"] );
      foreach( $removed_videos as $removed_video ){
        bof()->object->file->unlink( $removed_video, $caller->direct->bof()["name"] . $new_data_id );
      }
    }

    return json_encode( $new_data );

  }
  public function remove( $caller, $old_data_id, $old_data ){

    $old_data = !empty( $old_data ) ? ( is_array( $old_data ) ? $old_data : json_decode( $old_data, 1 ) ) : false;

    if ( !empty( $old_data["blocks"] ) ){
      foreach( $old_data["blocks"] as $_block ){
        if ( $_block["type"] == "image" && !empty( $_block["data"]["file"]["id"] ) ){
          bof()->object->file->unlink( $_block["data"]["file"]["id"], $caller->direct->bof()["name"] . $old_data_id );
        }
        if ( $_block["type"] == "video" && !empty( $_block["data"]["file"]["id"] ) ){
          bof()->object->file->unlink( $_block["data"]["file"]["id"], $caller->direct->bof()["name"] . $old_data_id );
        }
      }
    }

  }
  public function htmlize( $dataObject ){

    $html = '';

    if ( empty( $dataObject->blocks ) )
    return $html;

    foreach ($dataObject->blocks as $block) {

      switch ($block->type) {

        case 'paragraph':
        $alignment = !empty( $block->data->alignment ) ? $block->data->alignment : false;
        $html .= '<p'.($alignment?' style="text-align:'.$alignment.'"':'').'>' . $block->data->text . '</p>';
        break;

        case 'header':
        $html .= '<h'. $block->data->level .'>' . $block->data->text . '</h'. $block->data->level .'>';
        break;

        case 'raw':
        $html .= $block->data->html;
        break;

        case 'list':
        $lsType = ($block->data->style == 'ordered') ? 'ol' : 'ul';
        $html .= '<' . $lsType . '>';
        foreach($block->data->items as $item) {
          $html .= '<li>' . $item . '</li>';
        }
        $html .= '</' . $lsType . '>';
        break;

        case 'code':
        $html .= '<pre><code class="language-'. (!empty($block->data->lang)?$block->data->lang:"") .'">'. $block->data->code .'</code></pre>';
        break;

        case 'delimiter':
        $html .= '<div class="delimiter"></div>';
        break;

        case 'quote':
        $html .= '<blockquote class="quote_wrapper"><div class="text">'.$block->data->text.'</div><div class="caption">'.$block->data->caption.'</div></blockquote>';
        break;

        case 'warning':
        $html .= '<div class="warning_wrapper"><div class="title">'.$block->data->title.'</div><div class="message">'.$block->data->message.'</div></div>';
        break;

        case 'image':
        $classes = [];
        $classes[] = $block->data->stretched ? "stretched" : "not_stretched";
        $classes[] = $block->data->withBackground ? "has_bg" : "no_bg";
        $classes[] = $block->data->withBorder ? "has_border" : "no_border";
        $classes[] = $block->data->caption ? "has_caption" : "no_caption";
        $html .= '<figure class="img_pnl ' . ( implode( " ", $classes ) ) . '" '.($block->data->withBackground?"style=\"background-image:url({$block->data->file->url})\"":"").'><img src="' . $block->data->file->url . '" />' . ( $block->data->caption ? "<figcaption>{$block->data->caption}</figcaption>" : "" ) . '</figure>';
        break;

        case 'video':
        $classes = [];
        $classes[] = $block->data->stretched ? "stretched" : "not_stretched";
        $html .= '<div class="video_wrapper ' . ( implode( " ", $classes ) ) . '"><video controls><source src="'.$block->data->file->url.'" type="video/mp4"></video></div>';
        break;

        case 'checklist':
        $html .= '<div class="_cl">';
        foreach($block->data->items as $item){
          $html .= '<div class="_cl_i '. ( $item->checked ? 'checked' : 'unchecked' ) .'"><span class="_icon"></span>'.$item->text.'</div>';
        }
        $html .= '</div>';
        break;

        case 'table':
        $acols = $block->data->content;
        if ( $block->data->withHeadings ){
          $cols = array_values( array_slice( $acols, 1 ) );
          $hcols = reset( $acols );
        } else {
          $cols = $acols;
        }
        $html .= '<table class="'.($block->data->withHeadings?"has_heading":"no_heading").'">';
        if ( $block->data->withHeadings ){
          $html .= "<thead><tr>";
          foreach( $hcols as $td )
          $html .= "<td>{$td}</td>";
          $html .= "</tr></thead>";
        }
        $html .= "<tbody>";
        foreach( $cols as $tr ){
          $html .= "<tr>";
          foreach( $tr as $td )
          $html .= "<td>{$td}</td>";
          $html .= "</tr>";
        }
        $html .= "</tbody>";
        $html .= '</table>';
        break;

        case 'AnyButton':
        $target = ( $block->data->link ? (
           substr( $block->data->link, 0, strlen(web_address) ) != web_address && substr( $block->data->link, 0, 4 ) == 'http'
           ) : false ) ? " target='_blank'" : "";
        $html .= "<a class='btn btn-primary' href='{$block->data->link}' {$target}>{$block->data->text}</a>";
        break;

        default:
        break;

      }

    }

    return "<div class='editorjs_html_wrapper'>{$html}</div>";

  }
  public function editorjsize( $string ){

    if ( !$string )
    return false;

    $string_line_by_line = bof()->general->explode_by_line( str_replace( "<br>", "\n", $string ) );
    $blocks = [];

    foreach( $string_line_by_line as $string_line ){
      if ( !empty( $string_line ) )
      $blocks[] = array(
        "type" => "paragraph",
        "data" => array(
          "text" => $string_line,
          "alignment" => "inherit"
        ),
      );
    }

    if ( empty( $blocks ) )
    return false;

    return array(
      "time" => time(),
      "blocks" => $blocks,
      "version" => "2.25.0",
      "files" => array(
        "images" => [],
        "videos" => []
      )
    );

  }

}

?>
