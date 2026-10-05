<?php
require_once( __DIR__ . "/api/app/config.php" );
require_once( bof_root . "/loader.php" );
require_once( root . "/app/client/loader.php" );

$objectSlug = "rohitak_rock-fazeeta";
$bofType = "m_track";
$object = bof()->object->__get($bofType);
$item = $object->select(
    array( array( "seo_url", "LIKE", "$objectSlug%" ) ),
    array( "cache_load_rt" => false, "limit" => 1 )
);
echo "Result for LIKE '$objectSlug%': " . ( $item ? "Found (ID: " . $item['ID'] . ", seo_url: " . $item['seo_url'] . ")" : "Not Found" ) . "\n";
?>