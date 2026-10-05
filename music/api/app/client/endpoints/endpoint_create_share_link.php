<?php

if ( !defined( "bof_root" ) ) die;

/**
 * Share Link Creation Endpoint
 * Creates a unique share token for tracks, albums, artists, or playlists
 */
function endpoint_create_share_link( $loader, $excuter, $args ){

    $objectType = $loader->nest->user_input( "post", "object_type", "string" );
    $objectHash = $loader->nest->user_input( "post", "object_hash", "md5" );
    $objectId = $loader->nest->user_input( "post", "object_id", "id" );

    if ( !$objectType || (!$objectHash && !$objectId) ){
        $loader->api->set_message( "error", array(
            "message" => "Missing required parameters: object_type and (object_hash or object_id)"
        ) );
        return;
    }

    // Validate object type
    $validTypes = array( "track", "album", "artist", "playlist" );
    if ( !in_array( $objectType, $validTypes ) ){
        $loader->api->set_message( "error", array(
            "message" => "Invalid object_type. Must be one of: " . implode( ", ", $validTypes )
        ) );
        return;
    }

    // Generate unique share token
    $shareToken = hash( "sha256", uniqid( rand(), true ) . $objectType . $objectHash . $objectId . time() );
    $shareToken = substr( $shareToken, 0, 32 ); // Shorten to 32 chars

    // Get user ID if logged in (optional)
    $userId = null;
    try {
        $userId = bof()->user->get( "ID" );
    } catch ( Exception $e ) {
        // User not logged in, that's okay
    }

    // Store share link in database
    $db = $loader->db;
    $table = $db->prefix . "share_links";

    // Check if share_links table exists, create if not
    $checkTable = $db->query( "SHOW TABLES LIKE '{$table}'" );
    if ( $checkTable->num_rows == 0 ){
        $db->query( "
            CREATE TABLE IF NOT EXISTS {$table} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                share_token VARCHAR(32) UNIQUE NOT NULL,
                object_type VARCHAR(20) NOT NULL,
                object_hash VARCHAR(32) NULL,
                object_id INT NULL,
                created_by INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NULL,
                click_count INT DEFAULT 0,
                INDEX idx_token (share_token),
                INDEX idx_object (object_type, object_hash, object_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        " );
    }

    // Insert share link
    $stmt = $db->prepare( "
        INSERT INTO {$table} (share_token, object_type, object_hash, object_id, created_by)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        share_token = share_token,
        created_at = created_at
    " );

    $stmt->bind_param( "sssis", $shareToken, $objectType, $objectHash, $objectId, $userId );
    $result = $stmt->execute();
    $stmt->close();

    if ( !$result ){
        $loader->api->set_message( "error", array(
            "message" => "Failed to create share link"
        ) );
        return;
    }

    // Build the share URL
    $baseUrl = rtrim( bof()->seo->get_base_url(), "/" );
    $shareUrl = "{$baseUrl}/s/{$shareToken}";

    // Get object details for the response
    $objectDetails = null;
    if ( $objectHash ){
        try {
            $objectName = "m_" . $objectType; // e.g., m_track, m_album, etc.
            if ( $objectType == "playlist" ) $objectName = "playlist";

            $theObject = $loader->object->__get( $objectName );
            if ( $theObject ){
                $objectItem = $theObject->select(
                    array( "hash" => $objectHash ),
                    array( "indexed" => true, "match_page" => true )
                );
                if ( $objectItem ){
                    $objectDetails = bof()->seo->fetch( array(
                        "object" => $objectName,
                        "item" => $objectItem,
                        "lang" => null,
                    ), true );
                }
            }
        } catch ( Exception $e ) {
            // Object not found, continue without details
        }
    }

    $loader->api->set_message( "ok", array(
        "share_token" => $shareToken,
        "share_url" => $shareUrl,
        "object_type" => $objectType,
        "object_hash" => $objectHash,
        "object_details" => $objectDetails
    ) );

    // Ensure JSON output
    header('Content-Type: application/json');
    echo json_encode(array(
        "success" => true,
        "data" => array(
            "share_token" => $shareToken,
            "share_url" => $shareUrl,
            "object_type" => $objectType,
            "object_hash" => $objectHash,
            "object_details" => $objectDetails
        )
    ));

}

?>
