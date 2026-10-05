<?php
/**
 * Assetlinks Endpoint
 * Serves the assetlinks.json for Android App Links
 */

if (!defined("root") || !defined("bof_root")) die;

function endpoint_assetlinks( $loader, $excuter, $args ){
    
    $assetlinks = [
        [
            "relation" => ["delegate_permission/common.handle_all_urls"],
            "target" => [
                "namespace" => "android_app",
                "package_name" => "com.hitune.app",
                "sha256_cert_fingerprints" => [
                    "EA:28:0C:0B:D5:8A:60:08:AE:99:11:A3:97:F1:9D:87:38:2E:01:E2:E0:E2:DE:1D:18:73:4C:64:FB:67:C1:61"
                ]
            ]
        ]
    ];

    header('Content-Type: application/json; charset=utf-8');
    http_response_code(200);
    echo json_encode($assetlinks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}
?>