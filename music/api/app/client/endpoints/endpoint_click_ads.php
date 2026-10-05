<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_click_ads( $loader, $excuter, $args ){

  $adID = substr( $loader->request->get_requested_url(), strlen("redirect_to/"), -1 );

  if ( $adID ){

    $get_ads = $loader->object->ads->select(
      array(
        "ID" => $adID,
        "displayable" => true,
      ),
      array(
        "limit" => 1,
        "for_click" => true
      )
    );

    if ( !$get_ads )
    die("invalid ad or no URL");

    bof()->chapar->notify_admin("ad_clicked", array(
      "ad_url" => $get_ads,
      "ad_id" => $adID
    ));

    if ( ob_get_contents() ) ob_clean();
    header("Location: {$get_ads}");
    die;
    exit;
    return;

  }

}

?>
