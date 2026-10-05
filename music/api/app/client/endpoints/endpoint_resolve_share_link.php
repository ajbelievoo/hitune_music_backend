<?php

if ( !defined( "bof_root" ) ) die;

/**
 * Share Link Resolution Endpoint
 * Resolves a share token to the actual object data
 */
function endpoint_resolve_share_link( $loader, $excuter, $args ){

    $shareToken = $loader->nest->user_input( "get", "token", "string" );

    if ( !$shareToken ){
        $loader->api->set_message( "error", array(
            "message" => "Missing required parameter: token"
        ) );
        return;
    }

    // Validate token format (32 character hex)
    if ( !preg_match( '/^[a-f0-9]{32}$/', $shareToken ) ){
        $loader->api->set_message( "error", array(
            "message" => "Invalid share token format"
        ) );
        return;
    }

    // Lookup share link in database
    $db = $loader->db;
    $table = $db->prefix . "share_links";

    // Check if table exists
    $checkTable = $db->query( "SHOW TABLES LIKE '{$table}'" );
    if ( $checkTable->num_rows == 0 ){
        $loader->api->set_message( "error", array(
            "message" => "Share link not found"
        ) );
        return;
    }

    // Get share link data
    $stmt = $db->prepare( "
        SELECT * FROM {$table}
        WHERE share_token = ?
        AND (expires_at IS NULL OR expires_at > NOW())
    " );
    $stmt->bind_param( "s", $shareToken );
    $stmt->execute();
    $result = $stmt->get_result();
    $shareData = $result->fetch_assoc();
    $stmt->close();

    if ( !$shareData ){
        $loader->api->set_message( "error", array(
            "message" => "Share link not found or expired"
        ) );
        return;
    }

    // Update click count
    $updateStmt = $db->prepare( "UPDATE {$table} SET click_count = click_count + 1 WHERE share_token = ?" );
    $updateStmt->bind_param( "s", $shareToken );
    $updateStmt->execute();
    $updateStmt->close();

    $objectType = $shareData["object_type"];
    $objectHash = $shareData["object_hash"];
    $objectId = $shareData["object_id"];

    // Build object name
    $objectName = "m_" . $objectType;
    if ( $objectType == "playlist" ) $objectName = "playlist";

    // Get object details
    $objectData = null;
    try {
        $theObject = $loader->object->__get( $objectName );
        if ( $theObject ){
            $selectArgs = array();
            if ( $objectHash ){
                $selectArgs["hash"] = $objectHash;
            } else if ( $objectId ){
                $selectArgs["ID"] = $objectId;
            }

            if ( !empty( $selectArgs ) ){
                $objectItem = $theObject->select(
                    $selectArgs,
                    array(
                        "indexed" => true,
                        "match_page" => true,
                        "muse_source" => true,
                        "_eq" => array(
                            "sources" => array(),
                        ),
                        "cache_load_rt" => false
                    )
                );

                if ( $objectItem ){
                    $objectData = bof()->seo->fetch( array(
                        "object" => $objectName,
                        "item" => $objectItem,
                        "lang" => null,
                    ), true );

                    // Add sources for tracks
                    if ( $objectType == "track" && !empty( $objectItem["sources"] ) ){
                        $sources = array();
                        foreach( $objectItem["sources"] as $source_G ){
                            $sources_by_type = $loader->source->get( "stream", $source_G["ot"], $source_G["raw"], $source_G["sources"], "stream" );
                            if ( !empty( $sources_by_type["all"]["sources"] ) ){
                                foreach( $sources_by_type["all"]["sources"] as $_s_group ){
                                    if ( !empty( $_s_group["sources"] ) ){
                                        foreach( $_s_group["sources"] as $_s_group_source ){
                                            if ( !$_s_group_source["locked"] ){
                                                $sources[] = $_s_group_source["hook"];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        $objectData["sources"] = array_unique( $sources );
                    }
                }
            }
        }
    } catch ( Exception $e ) {
        // Object not found
    }

    if ( !$objectData ){
        $loader->api->set_message( "error", array(
            "message" => "Shared content no longer available"
        ) );
        return;
    }

    $loader->api->set_message( "ok", array(
        "share_token" => $shareToken,
        "object_type" => $objectType,
        "object_hash" => $objectHash,
        "object_id" => $objectId,
        "object_data" => $objectData,
        "created_at" => $shareData["created_at"]
    ) );

    // Ensure JSON output
    header('Content-Type: application/json');
    echo json_encode(array(
        "success" => true,
        "data" => array(
            "share_token" => $shareToken,
            "object_type" => $objectType,
            "object_hash" => $objectHash,
            "object_id" => $objectId,
            "object_data" => $objectData,
            "created_at" => $shareData["created_at"]
        )
    ));

}

?>
