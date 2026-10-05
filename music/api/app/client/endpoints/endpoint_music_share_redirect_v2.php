<?php
/**
 * Share Link Redirect Endpoint V2
 * Handles /music/{type}/{hash}/ URLs and redirects to proper object pages
 */

if (!defined("root") || !defined("bof_root")) die;

function endpoint_music_share_redirect_v2( $loader, $excuter, $args ){
    
    $url = (string) bof()->request->get_requested_url();
    file_put_contents( dirname(__FILE__) . "/redirect_debug.log", date("Y-m-d H:i:s") . " - URL: " . $url . "\n", FILE_APPEND );
    
    $matches = [];
    if ( preg_match( "/^m?u?sic\/(track|album|artist|playlist|podcast|episode|book)\/([^\/]{1,100})\/?$/", $url, $matches ) ){
        $objectType = $matches[1];
        $objectSlug = $matches[2];
    } elseif ( preg_match( "/^(track|album|artist|playlist|podcast|episode|book)\/([^\/]{1,100})\/?$/", $url, $matches ) ){
        $objectType = $matches[1];
        $objectSlug = $matches[2];
    } else {
        header("HTTP/1.1 404 Not Found");
        return;
    }

    // Map friendly names to BOF object types
    $typeMap = [
        'track' => 'm_track',
        'album' => 'm_album',
        'artist' => 'm_artist',
        'playlist' => 'ugc_playlist',
        'podcast' => 'p_show',
        'episode' => 'p_episode',
        'book' => 'a_book'
    ];

    $bofType = isset($typeMap[$objectType]) ? $typeMap[$objectType] : 'm_' . $objectType;

    // Get the object
    $object = null;
    if ( bof()->object->core_files->validate_key( "object", $bofType ) ){
        try {
            $object = bof()->object->__get($bofType);
        } catch (Throwable $e) {}
    }

    if (!$object) {
        $objectType = htmlspecialchars($objectType);
        goto show_410;
    }

    $item = null;
    // Try to find by hash (32 char hex)
    if (strlen($objectSlug) === 32 && ctype_xdigit($objectSlug)) {
        try {
            $item = $object->select(
                array( array( "hash", "=", $objectSlug ) ),
                array( "cache_load_rt" => false )
            );
        } catch (Throwable $e) {}
    }

    // If not found by hash, try by seo_url
    if (!$item && preg_match('/^[a-z0-9-_.]+$/i', $objectSlug)) {
        try {
            $item = $object->select(
                array( array( "seo_url", "=", $objectSlug ) ),
                array( "cache_load_rt" => false )
            );
        } catch (Throwable $e) {}
    }

    // FALLBACK: If not found by exact SEO URL, try LIKE for tracks/albums/artists
    if (!$item && preg_match('/^[a-z0-9-_.]+$/i', $objectSlug) && in_array($bofType, ['m_track', 'm_album', 'm_artist'])) {
        try {
            $item = $object->select(
                array( array( "seo_url", "LIKE", "$objectSlug%" ) ),
                array( "cache_load_rt" => false, "limit" => 1 )
            );
        } catch (Throwable $e) {}
    }

    // If still not found, try by code (only for music objects that usually have it)
    if (!$item && in_array($bofType, ['m_track', 'm_album', 'm_artist'])) {
        try {
            $item = $object->select(
                array( array( "code", "=", $objectSlug ) ),
                array( "cache_load_rt" => false )
            );
        } catch (Throwable $e) {}
    }

    // If item found, redirect to its proper URL
    if ($item) {
        
        // Clean item to ensure URL is populated
        try {
            $item = $object->clean($item, []);
        } catch (Throwable $e) {}
        
        if (!empty($item['url'])) {
            $targetUrl = $item['url'];
            
            // Ensure $targetUrl is absolute or starts with /
            if ( strpos( $targetUrl, 'http' ) !== 0 && strpos( $targetUrl, '/' ) !== 0 ){
                $targetUrl = web_address . $targetUrl;
            }
            
            // Add query parameters if present in the current request, excluding internal ones
            if ( !empty( $_GET ) ){
                $cleanGet = $_GET;
                unset($cleanGet['_st'], $cleanGet['_lc'], $cleanGet['q']);
                if ( !empty($cleanGet) ) {
                    $targetUrl .= (strpos($targetUrl, '?') !== false ? '&' : '?') . http_build_query($cleanGet);
                }
            }
            
            // Redirect to actual item page
            header("Location: " . $targetUrl, true, 302);
            exit;
        }
    }

    // If not found, show 404/410 page
    show_410:
    http_response_code(410);
    header("Content-Type: text/html; charset=utf-8");
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Content Not Found - HiTune</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background-color: #f8f9fa; color: #333; }
            .container { text-align: center; padding: 2rem; background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 400px; width: 90%; }
            h1 { font-size: 2rem; margin-bottom: 1rem; color: #e74c3c; }
            p { margin-bottom: 2rem; line-height: 1.5; }
            .btn { display: inline-block; background-color: #007bff; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; font-weight: bold; transition: background-color 0.2s; }
            .btn:hover { background-color: #0056b3; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Oops!</h1>
            <p>The <?php echo htmlspecialchars($objectType); ?> you're looking for could not be found. It might have been moved or deleted.</p>
            <a href="/" class="btn">Go to Homepage</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>