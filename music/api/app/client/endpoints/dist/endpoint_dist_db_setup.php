<?php

/**
 * Music Distribution - Database Setup
 * Creates necessary tables for the distribution system
 */

if ( !defined("bof_root") ) die;

function endpoint_dist_db_setup( $loader, $excuter, $args ){

    // Only allow admin to run this
    $admin_user = $loader->user->check();
    $admin_role_ids = !empty($admin_user->extra["role_ids"]) ? array_map("intval", (array)$admin_user->extra["role_ids"]) : array();
    if ( empty($admin_user->ID) || !in_array(4, $admin_role_ids, true) ) {
        return $loader->api->set_error( "access_denied" );
    }

    $db = $loader->db;
        $tables_created = [];
        $errors = [];

        // 1. Distribution Plans Table
        $sql = "CREATE TABLE IF NOT EXISTS `_dist_plans` (
            `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `slug` VARCHAR(50) NOT NULL UNIQUE,
            `price_yearly` DECIMAL(10,2) NOT NULL,
            `currency` VARCHAR(3) DEFAULT 'INR',
            `features` JSON NULL,
            `is_active` TINYINT(1) DEFAULT 1,
            `sort_order` INT(3) DEFAULT 0,
            `time_add` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `time_update` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_slug` (`slug`),
            INDEX `idx_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        try {
            $db->query($sql);
            $tables_created[] = "_dist_plans";
        } catch (Exception $e) {
            $errors[] = "_dist_plans: " . $e->getMessage();
        }

        // 2. User Subscriptions Table
        $sql = "CREATE TABLE IF NOT EXISTS `_dist_subscriptions` (
            `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT(11) UNSIGNED NOT NULL,
            `plan_id` INT(11) UNSIGNED NOT NULL,
            `status` ENUM('active','expired','cancelled','pending') DEFAULT 'pending',
            `payment_status` ENUM('paid','unpaid','refunded') DEFAULT 'unpaid',
            `amount_paid` DECIMAL(10,2) NULL,
            `start_date` DATE NULL,
            `end_date` DATE NULL,
            `transaction_id` VARCHAR(255) NULL,
            `time_add` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `time_update` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_user` (`user_id`),
            INDEX `idx_status` (`status`),
            INDEX `idx_dates` (`start_date`, `end_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        try {
            $db->query($sql);
            $tables_created[] = "_dist_subscriptions";
        } catch (Exception $e) {
            $errors[] = "_dist_subscriptions: " . $e->getMessage();
        }

        // 3. Music Submissions Table
        $sql = "CREATE TABLE IF NOT EXISTS `_dist_submissions` (
            `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT(11) UNSIGNED NOT NULL,
            `subscription_id` INT(11) UNSIGNED NOT NULL,
            `type` ENUM('single','album','ep') DEFAULT 'single',
            `title` VARCHAR(255) NOT NULL,
            `artist_name` VARCHAR(255) NOT NULL,
            `album_name` VARCHAR(255) NULL,
            `genre` VARCHAR(100) NULL,
            `release_date` DATE NULL,
            `description` TEXT NULL,
            `language` VARCHAR(50) NULL,
            `isrc` VARCHAR(20) NULL,
            `upc` VARCHAR(20) NULL,
            `label_name` VARCHAR(255) NULL,
            `recording_location` VARCHAR(255) NULL,
            `cover_art_path` VARCHAR(500) NULL,
            `audio_file_path` VARCHAR(500) NULL,
            `platforms` JSON NULL,
            `countries` JSON NULL,
            `status` ENUM('submitted','in_review','in_progress','approved','rejected','launched','taken_down') DEFAULT 'submitted',
            `admin_notes` TEXT NULL,
            `tunecore_url` VARCHAR(500) NULL,
            `tunecore_status` VARCHAR(100) NULL,
            `launch_date` DATE NULL,
            `time_add` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `time_update` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_user` (`user_id`),
            INDEX `idx_status` (`status`),
            INDEX `idx_type` (`type`),
            INDEX `idx_release_date` (`release_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        try {
            $db->query($sql);
            $tables_created[] = "_dist_submissions";
        } catch (Exception $e) {
            $errors[] = "_dist_submissions: " . $e->getMessage();
        }

        // 4. Track Details Table (for albums with multiple tracks)
        $sql = "CREATE TABLE IF NOT EXISTS `_dist_tracks` (
            `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `submission_id` INT(11) UNSIGNED NOT NULL,
            `track_number` INT(3) DEFAULT 1,
            `title` VARCHAR(255) NOT NULL,
            `artist_name` VARCHAR(255) NOT NULL,
            `duration` INT(5) NULL,
            `isrc` VARCHAR(20) NULL,
            `audio_file_path` VARCHAR(500) NULL,
            `time_add` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_submission` (`submission_id`),
            INDEX `idx_track_num` (`track_number`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        try {
            $db->query($sql);
            $tables_created[] = "_dist_tracks";
        } catch (Exception $e) {
            $errors[] = "_dist_tracks: " . $e->getMessage();
        }

        // 5. Royalty Reports Table
        $sql = "CREATE TABLE IF NOT EXISTS `_dist_royalties` (
            `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT(11) UNSIGNED NOT NULL,
            `submission_id` INT(11) UNSIGNED NOT NULL,
            `report_month` VARCHAR(20) NOT NULL,
            `platform` VARCHAR(100) NOT NULL,
            `streams` INT(11) UNSIGNED DEFAULT 0,
            `downloads` INT(11) UNSIGNED DEFAULT 0,
            `revenue` DECIMAL(12,2) DEFAULT 0.00,
            `currency` VARCHAR(3) DEFAULT 'USD',
            `report_file` VARCHAR(500) NULL,
            `is_paid` TINYINT(1) DEFAULT 0,
            `payment_date` DATE NULL,
            `payment_method` VARCHAR(100) NULL,
            `payment_reference` VARCHAR(255) NULL,
            `admin_notes` TEXT NULL,
            `time_add` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `time_update` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_user` (`user_id`),
            INDEX `idx_submission` (`submission_id`),
            INDEX `idx_month` (`report_month`),
            INDEX `idx_paid` (`is_paid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        try {
            $db->query($sql);
            $tables_created[] = "_dist_royalties";
        } catch (Exception $e) {
            $errors[] = "_dist_royalties: " . $e->getMessage();
        }

        // 6. Activity Logs Table
        $sql = "CREATE TABLE IF NOT EXISTS `_dist_logs` (
            `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT(11) UNSIGNED NULL,
            `submission_id` INT(11) UNSIGNED NULL,
            `action` VARCHAR(100) NOT NULL,
            `old_status` VARCHAR(50) NULL,
            `new_status` VARCHAR(50) NULL,
            `details` TEXT NULL,
            `performed_by` INT(11) UNSIGNED NULL,
            `time_add` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_user` (`user_id`),
            INDEX `idx_submission` (`submission_id`),
            INDEX `idx_action` (`action`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        try {
            $db->query($sql);
            $tables_created[] = "_dist_logs";
        } catch (Exception $e) {
            $errors[] = "_dist_logs: " . $e->getMessage();
        }

        // 7. Payout Requests Table
        $sql = "CREATE TABLE IF NOT EXISTS `_dist_payouts` (
            `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT(11) UNSIGNED NOT NULL,
            `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `currency` VARCHAR(3) DEFAULT 'INR',
            `method` VARCHAR(50) NULL,
            `details` TEXT NULL,
            `status` ENUM('requested','processing','paid','rejected') DEFAULT 'requested',
            `payment_reference` VARCHAR(255) NULL,
            `admin_notes` TEXT NULL,
            `time_add` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `time_update` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_user` (`user_id`),
            INDEX `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        try {
            $db->query($sql);
            $tables_created[] = "_dist_payouts";
        } catch (Exception $e) {
            $errors[] = "_dist_payouts: " . $e->getMessage();
        }

        // Insert Default Plans
        dist_insertDefaultPlans($db);

    $loader->api->set_message( "ok", [
        "tables_created" => $tables_created,
        "errors" => $errors,
        "message" => count($errors) === 0 ? "All tables created successfully" : "Some tables had errors"
    ]);
}

function dist_insertDefaultPlans($db) {
        $plans = [
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'price_yearly' => 4499.00,
                'features' => json_encode([
                    'schedule_release' => true,
                    'unlimited_releases' => true,
                    'spotify_verified' => true,
                    'apple_verified' => true,
                    'revenue_splits' => true,
                    'social_platforms' => true,
                    'social_fee' => 20,
                    'response_time' => '24h',
                    'store_automator' => true,
                    'trend_reports' => true,
                    'cover_art_creator' => true,
                    'own_isrc' => true,
                    'exclusive_partnerships' => true,
                    'promotional_opportunities' => true,
                    'pro_panels' => true,
                    'custom_label' => true,
                    'own_upc' => true,
                    'country_restrictions' => true,
                    'youtube_content_id' => true,
                    'youtube_oac' => true,
                    'advance_available' => true,
                    'additional_artist_cost' => 999
                ])
            ],
            [
                'name' => 'Breakout Artist',
                'slug' => 'breakout',
                'price_yearly' => 2799.00,
                'features' => json_encode([
                    'schedule_release' => true,
                    'unlimited_releases' => true,
                    'spotify_verified' => true,
                    'apple_verified' => true,
                    'revenue_splits' => true,
                    'social_platforms' => true,
                    'social_fee' => 20,
                    'response_time' => '48h',
                    'store_automator' => false,
                    'trend_reports' => true,
                    'cover_art_creator' => true,
                    'own_isrc' => true,
                    'exclusive_partnerships' => true,
                    'promotional_opportunities' => true,
                    'pro_panels' => false,
                    'custom_label' => true,
                    'own_upc' => true,
                    'country_restrictions' => false,
                    'youtube_content_id' => true,
                    'youtube_oac' => false,
                    'advance_available' => false,
                    'additional_artist_cost' => 999
                ])
            ],
            [
                'name' => 'Rising Artist',
                'slug' => 'rising',
                'price_yearly' => 1599.00,
                'features' => json_encode([
                    'schedule_release' => true,
                    'unlimited_releases' => true,
                    'spotify_verified' => true,
                    'apple_verified' => false,
                    'revenue_splits' => false,
                    'social_platforms' => true,
                    'social_fee' => 20,
                    'response_time' => '72h',
                    'store_automator' => false,
                    'trend_reports' => false,
                    'cover_art_creator' => true,
                    'own_isrc' => false,
                    'exclusive_partnerships' => false,
                    'promotional_opportunities' => false,
                    'pro_panels' => false,
                    'custom_label' => false,
                    'own_upc' => false,
                    'country_restrictions' => false,
                    'youtube_content_id' => false,
                    'youtube_oac' => false,
                    'advance_available' => false,
                    'additional_artist_cost' => 999
                ])
            ]
        ];

        foreach ($plans as $plan) {
            $check = $db->query("SELECT id FROM `_dist_plans` WHERE slug = '{$plan['slug']}'");
            if ($check->num_rows === 0) {
                $db->query("INSERT INTO `_dist_plans` (name, slug, price_yearly, features, sort_order) 
                    VALUES ('{$plan['name']}', '{$plan['slug']}', {$plan['price_yearly']}, '{$plan['features']}', 
                    CASE '{$plan['slug']}' WHEN 'professional' THEN 1 WHEN 'breakout' THEN 2 ELSE 3 END)");
            }
        }
    }

?>
