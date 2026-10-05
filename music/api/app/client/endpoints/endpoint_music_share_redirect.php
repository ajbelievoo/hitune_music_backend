<?php 
 /** 
  * Share Link Redirect Endpoint 
  * Handles /music/{type}/{hash}/ URLs and redirects to proper object pages 
  */ 
  
 if (!defined("root") || !defined("bof_root")) die; 
  
 // Get matched URL components from BOF's urlData 
 $urlData = bof()->request->urlData; 
 $matches = isset($urlData['url']['match']) ? $urlData['url']['match'] : []; 
  
 $objectType = isset($matches[1]) ? $matches[1] : ''; 
 $objectSlug = isset($matches[2]) ? $matches[2] : ''; 
  
 if (empty($objectType) || empty($objectSlug)) { 
     bof()->response->set_code(404); 
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
  
 // Check if object type exists 
 if (!bof()->object->__isset($bofType)) { 
     bof()->response->set_code(404); 
     return; 
 } 
  
 $object = bof()->object->__get($bofType); 
 $item = null; 
  
 // Try to find by hash (32 char hex - this is what Flutter app sends) 
 if (strlen($objectSlug) === 32 && ctype_xdigit($objectSlug)) { 
     $item = $object->select([ 
         "hash" => $objectSlug, 
         "active" => 1 
     ]); 
 } 
  
 // If not found by hash, try by seo_slug 
 if (!$item && preg_match('/^[a-z0-9-]+$/i', $objectSlug)) { 
     $item = $object->select([ 
         "seo_slug" => $objectSlug, 
         "active" => 1 
     ]); 
 } 
  
 // If still not found, try by code 
 if (!$item) { 
     $item = $object->select([ 
         "code" => $objectSlug, 
         "active" => 1 
     ]); 
 } 
  
 // If item found, redirect to its proper URL 
 if ($item && !empty($item['url'])) { 
     $targetUrl = $item['url']; 
      
     // Add query parameters if present 
     $queryString = isset($urlData['url']['query_s']) ? $urlData['url']['query_s'] : ''; 
     if ($queryString) { 
         $targetUrl .= (strpos($targetUrl, '?') !== false ? '&' : '?') . $queryString; 
     } 
      
     // Redirect to actual item page 
     header("Location: " . $targetUrl, true, 302); 
     exit; 
 } 
  
 // If not found, show 404 page 
 bof()->response->set_code(404); 
 ?> 
 <!DOCTYPE html> 
 <html> 
 <head> 
 <title>Track Not Found - HiTune</title> 
 <style> 
 body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; text-align: center; padding: 50px; background: #121212; color: #fff; } 
 h1 { font-size: 48px; margin-bottom: 20px; color: #1DB954; } 
 p { font-size: 18px; color: #b3b3b3; margin-bottom: 30px; } 
 a { color: #1DB954; text-decoration: none; font-weight: bold; } 
 a:hover { text-decoration: underline; } 
 .container { max-width: 600px; margin: 0 auto; } 
 </style> 
 </head> 
 <body> 
 <div class="container"> 
 <h1>404</h1> 
 <p>The track or album you're looking for could not be found.</p> 
 <p><a href="/">← Go to Homepage</a></p> 
 </div> 
 </body> 
 </html> 
