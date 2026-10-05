<?php

/**
 * Music Distribution - User Dashboard
 * Returns user's submissions, subscription status, and royalty reports
 */

if ( !defined("bof_root") ) die;

function endpoint_dist_dashboard( $loader, $excuter, $args ){

    $user = $loader->user->check();
    if (!$user || empty($user->ID)) {
        return $loader->api->set_error("access_denied");
    }
    $user_id = $user->ID;
    $db = $loader->db;

        // Get user subscription
        $subscription = null;
        $sub_query = $db->query("SELECT s.*, p.name as plan_name, p.slug as plan_slug, p.features 
            FROM `_dist_subscriptions` s 
            JOIN `_dist_plans` p ON s.plan_id = p.id 
            WHERE s.user_id = {$user_id} 
            ORDER BY s.id DESC LIMIT 1");
        
        if ($sub_query && ($sub_result = $sub_query->fetch_assoc())) {
            $features = !empty($sub_result['features']) ? (array)json_decode($sub_result['features'], true) : array();
            $releases_limit = ( empty($features['unlimited_releases']) && !empty($features['max_releases']) )
                ? (int)$features['max_releases'] : null;
            $subscription = [
                'id' => (int)$sub_result['id'],
                'plan' => $sub_result['plan_name'],
                'plan_slug' => $sub_result['plan_slug'],
                'status' => $sub_result['status'],
                'payment_status' => $sub_result['payment_status'],
                'start_date' => $sub_result['start_date'],
                'end_date' => $sub_result['end_date'],
                'is_active' => ($sub_result['status'] === 'active' && $sub_result['end_date'] >= date('Y-m-d')),
                'days_remaining' => $sub_result['end_date'] ? 
                    max(0, floor((strtotime($sub_result['end_date']) - time()) / 86400)) : 0,
                'releases_used' => function_exists("dist_release_count")
                    ? dist_release_count( $db, $user_id, (int)$sub_result['id'] ) : 0,
                'releases_limit' => $releases_limit,
                'can_renew' => (bool)($sub_result['end_date'] &&
                    (strtotime($sub_result['end_date']) - time()) < 30 * 86400)
            ];
        }

        // Get submissions
        $submissions = [];
        $status_filter = !empty($_GET['status']) ? $db->real_escape_string($_GET['status']) : null;
        
        $sql = "SELECT * FROM `_dist_submissions` WHERE user_id = {$user_id}";
        if ($status_filter) {
            $sql .= " AND status = '{$status_filter}'";
        }
        $sql .= " ORDER BY time_add DESC";
        
        $result = $db->query($sql);
        while ($result && ($row = $result->fetch_assoc())) {
            $submissions[] = [
                'id' => (int)$row['id'],
                'type' => $row['type'],
                'title' => $row['title'],
                'artist_name' => $row['artist_name'],
                'album_name' => $row['album_name'],
                'genre' => $row['genre'],
                'cover_art' => $row['cover_art_path'],
                'status' => $row['status'],
                'status_label' => dist_getStatusLabel($row['status']),
                'dashboard_status' => function_exists("dist_dashboard_status") ? dist_dashboard_status($row) : dist_getStatusLabel($row['status']),
                'catalog_published' => !empty($row['catalog_published']),
                'ai_pct' => (int)($row['ai_pct'] ?? 0),
                'ai_badge' => !empty($row['ai_pct']) ? 'AI Original' : null,
                'tunecore_url' => $row['tunecore_url'],
                'tunecore_status' => $row['tunecore_status'],
                'launch_date' => $row['launch_date'],
                'admin_notes' => $row['admin_notes'],
                'submitted_at' => $row['time_add'],
                'updated_at' => $row['time_update']
            ];
        }

        // Get stats
        $stats = [
            'total_submissions' => count($submissions),
            'published' => count(array_filter($submissions, fn($s) => $s['status'] === 'launched')),
            'approved' => count(array_filter($submissions, fn($s) => in_array($s['status'], ['approved', 'launched']))),
            'in_review' => count(array_filter($submissions, fn($s) => in_array($s['status'], ['submitted', 'in_review', 'in_progress']))),
            'rejected' => count(array_filter($submissions, fn($s) => $s['status'] === 'rejected'))
        ];

        // Get royalty summary
        $royalties = [
            'total_earned' => 0,
            'total_streams' => 0,
            'reports' => []
        ];
        
        $royalty_query = $db->query("SELECT 
            SUM(revenue) as total_earned,
            SUM(streams) as total_streams,
            COUNT(DISTINCT report_month) as report_count
            FROM `_dist_royalties` 
            WHERE user_id = {$user_id}");
        
        if ($royalty_query && ($royalty_result = $royalty_query->fetch_assoc())) {
            $royalties['total_earned'] = (float)$royalty_result['total_earned'];
            $royalties['total_streams'] = (int)$royalty_result['total_streams'];
        }

        // Get recent royalty reports
        $reports_query = $db->query("SELECT report_month, SUM(revenue) as revenue, SUM(streams) as streams
            FROM `_dist_royalties` 
            WHERE user_id = {$user_id}
            GROUP BY report_month
            ORDER BY report_month DESC
            LIMIT 12");
        
        while ($reports_query && ($report = $reports_query->fetch_assoc())) {
            $royalties['reports'][] = [
                'month' => $report['report_month'],
                'revenue' => (float)$report['revenue'],
                'streams' => (int)$report['streams']
            ];
        }

        // Payout balance (shared helper from loader.php)
        $payout_balance = function_exists("dist_payout_balance") ? dist_payout_balance( $db, $user_id ) : null;

    $loader->api->set_message( "ok", [
        'subscription' => $subscription,
        'submissions' => $submissions,
        'stats' => $stats,
        'royalties' => $royalties,
        'payout_balance' => $payout_balance,
        'status_options' => [
            ['value' => 'submitted', 'label' => 'Submitted'],
            ['value' => 'in_review', 'label' => 'In Review'],
            ['value' => 'in_progress', 'label' => 'In Progress'],
            ['value' => 'approved', 'label' => 'Approved'],
            ['value' => 'launched', 'label' => 'Launched'],
            ['value' => 'rejected', 'label' => 'Rejected']
        ]
    ]);
}

?>
