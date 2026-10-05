<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_other_apps_ui( $loader, $excuter, $args ){

  $apps = bof()->object->other_app->select(
    array(),
    array(
      "limit" => 50,
      "order_by" => "ID",
      "order" => "DESC",
      "clean" => false
    )
  );

  $html = '<div class="playstore_container">';
  $html .= '<h1 class="playstore_title">Other Apps</h1>';
  $html .= '<div class="playstore_grid">';

  if ( $apps ){
    foreach( $apps as $app ){
      
      $icon = "https://play-lh.googleusercontent.com/6Ug99NnI77oN7vD0j0fJj5e9r6h2N9-m-r-6-i-r-m-r-6-i-r-m-r-6-i-r-m-r-6-i-r"; // Fallback
      if ( !empty( $app["icon_id"] ) ){
        $file = bof()->object->file->select( [ "ID" => $app["icon_id"] ] );
        if ( $file ) $icon = $file["image_thumb"];
      }

      $html .= '<a href="'.$app["url"].'" target="_blank" class="playstore_card">';
      $html .= '<div class="app_icon_wrapper"><img src="'.$icon.'" alt="'.$app["name"].'" class="app_icon"></div>';
      $html .= '<div class="app_details">';
      $html .= '<div class="app_name">'.$app["name"].'</div>';
      $html .= '<div class="app_meta">';
      $html .= '<span class="app_rating">'.$app["rating"].' <span class="mdi mdi-star"></span></span>';
      $html .= '<span class="app_downloads">'.$app["downloads"].' downloads</span>';
      $html .= '</div>';
      $html .= '</div>';
      $html .= '</a>';
    }
  } else {
    $html .= '<p>No apps found.</p>';
  }

  $html .= '</div></div>';

  // Inject CSS
  $css = '<style>
    .playstore_container { max-width: 1200px; margin: 40px auto; padding: 0 20px; font-family: "Google Sans", Roboto, Arial, sans-serif; }
    .playstore_title { font-size: 24px; color: #fff; margin-bottom: 30px; font-weight: 500; }
    .playstore_grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
    .playstore_card { display: flex; align-items: center; background: rgba(255,255,255,0.05); padding: 15px; border-radius: 12px; text-decoration: none; transition: background 0.2s; border: 1px solid rgba(255,255,255,0.05); }
    .playstore_card:hover { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.15); }
    .app_icon_wrapper { width: 64px; height: 64px; margin-right: 15px; flex-shrink: 0; }
    .app_icon { width: 100%; height: 100%; border-radius: 12px; object-fit: cover; }
    .app_details { overflow: hidden; }
    .app_name { font-size: 16px; color: #fff; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 4px; }
    .app_meta { font-size: 13px; color: rgba(255,255,255,0.6); display: flex; align-items: center; gap: 10px; }
    .app_rating { display: flex; align-items: center; gap: 2px; }
    .app_rating .mdi { font-size: 14px; color: #ffb400; }
    @media (max-width: 600px) {
      .playstore_grid { grid-template-columns: 1fr; }
    }
  </style>';

  echo $css . $html;

}
