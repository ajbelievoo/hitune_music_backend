<?php

/**
 * Music Distribution - Get Plans
 * Returns available distribution plans with features
 */

if ( !defined("bof_root") ) die;

function endpoint_dist_plans( $loader, $excuter, $args ){

    $db = $loader->db;
        
        $query = "SELECT * FROM `_dist_plans` WHERE is_active = 1 ORDER BY sort_order ASC";
        $result = $db->query($query);

        $plans = [];
        while ($result && ($row = $result->fetch_assoc())) {
            $features = json_decode($row['features'], true);
            
            $plans[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'slug' => $row['slug'],
                'price' => [
                    'amount' => (float)$row['price_yearly'],
                    'currency' => $row['currency'],
                    'period' => 'year'
                ],
                'features' => $features,
                'comparison' => [
                    'schedule_release' => $features['schedule_release'] ?? false,
                    'unlimited_releases' => $features['unlimited_releases'] ?? false,
                    'spotify_verified' => $features['spotify_verified'] ?? false,
                    'apple_verified' => $features['apple_verified'] ?? false,
                    'revenue_splits' => $features['revenue_splits'] ?? false,
                    'social_platforms' => $features['social_platforms'] ?? false,
                    'response_time' => $features['response_time'] ?? '72h',
                    'store_automator' => $features['store_automator'] ?? false,
                    'trend_reports' => $features['trend_reports'] ?? false,
                    'cover_art_creator' => $features['cover_art_creator'] ?? false,
                    'own_isrc' => $features['own_isrc'] ?? false,
                    'exclusive_partnerships' => $features['exclusive_partnerships'] ?? false,
                    'promotional_opportunities' => $features['promotional_opportunities'] ?? false,
                    'pro_panels' => $features['pro_panels'] ?? false,
                    'custom_label' => $features['custom_label'] ?? false,
                    'own_upc' => $features['own_upc'] ?? false,
                    'country_restrictions' => $features['country_restrictions'] ?? false,
                    'youtube_content_id' => $features['youtube_content_id'] ?? false,
                    'youtube_oac' => $features['youtube_oac'] ?? false,
                    'advance_available' => $features['advance_available'] ?? false,
                    'additional_artist_cost' => $features['additional_artist_cost'] ?? 999
                ]
            ];
        }

    // Add user subscription status if logged in
    $user_subscription = null;
    $logged_in = false;
    $user = $loader->user->check();
    if ($user && !empty($user->ID)) {
        $logged_in = true;
        $user_id = $user->ID;
        $sub_query = "SELECT s.*, p.name as plan_name, p.slug as plan_slug 
            FROM `_dist_subscriptions` s 
            JOIN `_dist_plans` p ON s.plan_id = p.id 
            WHERE s.user_id = {$user_id} AND s.status = 'active' 
            AND s.end_date >= CURDATE()
            ORDER BY s.id DESC LIMIT 1";
        $sub_result = $db->query($sub_query);
        if ($sub_result && $sub_result->num_rows > 0) {
            $user_subscription = $sub_result->fetch_assoc();
        }
    }

    $loader->api->set_message( "ok", [
        'logged_in' => $logged_in,
        'plans' => $plans,
        'user_subscription' => $user_subscription,
        'platforms' => [
            'Spotify', 'Apple Music', 'Amazon Music', 'YouTube Music', 
            'Tidal', 'Deezer', 'Pandora', 'iTunes', 'Google Play',
            'TikTok', 'Facebook', 'Instagram', 'Snapchat'
        ]
    ]);

}
?>
