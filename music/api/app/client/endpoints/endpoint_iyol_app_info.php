<?php

if ( !defined( "bof_root" ) ) die;

function endpoint_iyol_app_info( $loader, $excuter, $args ){

	$appData = null;
	try {
		require_once( root . "/app/controllers/playstore_controller.php" );
		$controller = new PlayStoreController();
		$appData = $controller->getIyolAppData();
		if ( empty($appData["cached"]) || $appData["cached"] === false ) {
			$appData = $controller->getDefaultData();
		}
	} catch ( \Throwable $e ) {
		$appData = array(
			"app_name" => "iYol App",
			"package_name" => "com.vidmite.app",
			"icon_url" => "https://iyolme.com/brand/logo-icon.png",
			"rating" => null,
			"downloads" => null,
			"reviews_count" => null,
			"error" => "playstore_unavailable",
		);
	}

	$loader->api->set_message( "ok", array( "app" => $appData ) );

}

?>
