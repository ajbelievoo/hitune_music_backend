<?php

/**
 * Music Distribution - Admin Royalty Management
 * Admin can add and manage royalty reports for users
 */

if ( !defined("bof_root") ) die;

function endpoint_dist_admin_royalties( $loader, $excuter, $args ){

    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            return dist_admin_getRoyalties($loader);
        case 'POST':
            return dist_admin_addRoyalties($loader);
        case 'PUT':
            return dist_admin_markPaid($loader);
        default:
            return $loader->api->set_error("Method not allowed");
    }
}

function dist_admin_getRoyalties($loader) {
    $db = $loader->db;
    
    $user_id = !empty($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    $submission_id = !empty($_GET['submission_id']) ? (int)$_GET['submission_id'] : null;
    $month = !empty($_GET['month']) ? $db->real_escape_string($_GET['month']) : null;
    $page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;

    $sql = "SELECT r.*, u.username, u.email, s.title as song_title, s.artist_name
        FROM `_dist_royalties` r
        LEFT JOIN `_u_list` u ON r.user_id = u.id
        LEFT JOIN `_dist_submissions` s ON r.submission_id = s.id
        WHERE 1=1";
    
    if ($user_id) {
        $sql .= " AND r.user_id = {$user_id}";
    }
    
    if ($submission_id) {
        $sql .= " AND r.submission_id = {$submission_id}";
    }
    
    if ($month) {
        $sql .= " AND r.report_month = '{$month}'";
    }
    
    $sql .= " ORDER BY r.report_month DESC, r.id DESC LIMIT {$limit} OFFSET {$offset}";

    $result = $db->query($sql);
    $royalties = [];
    
    while ($row = $result->fetch_assoc()) {
        $royalties[] = [
            'id' => (int)$row['id'],
            'user' => [
                'id' => (int)$row['user_id'],
                'username' => $row['username'],
                'email' => $row['email']
            ],
            'submission' => [
                'id' => (int)$row['submission_id'],
                'title' => $row['song_title'],
                'artist' => $row['artist_name']
            ],
            'report_month' => $row['report_month'],
            'platform' => $row['platform'],
            'streams' => (int)$row['streams'],
            'downloads' => (int)$row['downloads'],
            'revenue' => (float)$row['revenue'],
            'currency' => $row['currency'],
            'report_file' => $row['report_file'],
            'is_paid' => (bool)$row['is_paid'],
            'payment_date' => $row['payment_date'],
            'payment_method' => $row['payment_method'],
            'payment_reference' => $row['payment_reference'],
            'admin_notes' => $row['admin_notes'],
            'time_add' => $row['time_add']
        ];
    }

    // Get summary
    $summary_sql = "SELECT 
        SUM(revenue) as total_revenue,
        SUM(streams) as total_streams,
        COUNT(DISTINCT user_id) as total_artists,
        SUM(CASE WHEN is_paid = 1 THEN revenue ELSE 0 END) as paid_amount,
        SUM(CASE WHEN is_paid = 0 THEN revenue ELSE 0 END) as pending_amount
        FROM `_dist_royalties`";
    
    $summary_result = $db->query($summary_sql);
    $summary = $summary_result->fetch_assoc();

    $loader->api->set_message("ok", [
        'royalties' => $royalties,
        'summary' => [
            'total_revenue' => (float)$summary['total_revenue'],
            'total_streams' => (int)$summary['total_streams'],
            'total_artists' => (int)$summary['total_artists'],
            'paid_amount' => (float)$summary['paid_amount'],
            'pending_amount' => (float)$summary['pending_amount']
        ],
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit
        ]
    ]);
}

function dist_admin_addRoyalties($loader) {
    $db = $loader->db;
    $input = dist_royalty_getInput();

    // Required fields
    if (empty($input['user_id']) || empty($input['submission_id']) || 
        empty($input['report_month']) || empty($input['platform'])) {
        return $loader->api->set_error("Required fields: user_id, submission_id, report_month, platform");
    }

    $user_id = (int)$input['user_id'];
    $submission_id = (int)$input['submission_id'];
    $report_month = $db->real_escape_string($input['report_month']);
    $platform = $db->real_escape_string($input['platform']);
    $streams = !empty($input['streams']) ? (int)$input['streams'] : 0;
    $downloads = !empty($input['downloads']) ? (int)$input['downloads'] : 0;
    $revenue = !empty($input['revenue']) ? (float)$input['revenue'] : 0.00;
    $currency = !empty($input['currency']) ? $db->real_escape_string($input['currency']) : 'USD';
    $admin_notes = !empty($input['admin_notes']) ? $db->real_escape_string($input['admin_notes']) : null;

    // Check if report already exists
    $check = $db->query("SELECT id FROM `_dist_royalties` 
        WHERE user_id = {$user_id} AND submission_id = {$submission_id} 
        AND report_month = '{$report_month}' AND platform = '{$platform}'");
    
    if ($check->num_rows > 0) {
        // Update existing
        $existing = $check->fetch_assoc();
        $sql = "UPDATE `_dist_royalties` SET 
            streams = {$streams},
            downloads = {$downloads},
            revenue = {$revenue},
            admin_notes = " . ($admin_notes ? "'{$admin_notes}'" : "NULL") . ",
            time_update = NOW()
            WHERE id = {$existing['id']}";
        
        if ($db->query($sql)) {
            $loader->api->set_message("ok", [
                'message' => 'Royalty report updated',
                'id' => (int)$existing['id']
            ]);
        }
    } else {
        // Insert new
        $sql = "INSERT INTO `_dist_royalties` 
            (user_id, submission_id, report_month, platform, streams, downloads, revenue, currency, admin_notes)
            VALUES ({$user_id}, {$submission_id}, '{$report_month}', '{$platform}', 
            {$streams}, {$downloads}, {$revenue}, '{$currency}', 
            " . ($admin_notes ? "'{$admin_notes}'" : "NULL") . ")";
        
        if ($db->query($sql)) {
            $loader->api->set_message("ok", [
                'message' => 'Royalty report added',
                'id' => $db->insert_id
            ]);
        }
    }

    return $loader->api->set_error("Failed to save royalty report: " . $db->error);
}

function dist_admin_markPaid($loader) {
    $db = $loader->db;
    $input = dist_royalty_getInput();

    if (empty($input['ids']) || !is_array($input['ids'])) {
        return $loader->api->set_error("IDs array required");
    }

    $payment_method = !empty($input['payment_method']) ? $db->real_escape_string($input['payment_method']) : 'Bank Transfer';
    $payment_reference = !empty($input['payment_reference']) ? $db->real_escape_string($input['payment_reference']) : null;

    $ids = array_map('intval', $input['ids']);
    $id_list = implode(',', $ids);

    $sql = "UPDATE `_dist_royalties` SET 
        is_paid = 1,
        payment_date = CURDATE(),
        payment_method = '{$payment_method}',
        payment_reference = " . ($payment_reference ? "'{$payment_reference}'" : "NULL") . ",
        time_update = NOW()
        WHERE id IN ({$id_list})";

    if ($db->query($sql)) {
        $loader->api->set_message("ok", [
            'message' => 'Payments marked as paid',
            'count' => $db->affected_rows
        ]);
    } else {
        return $loader->api->set_error("Failed to update payments: " . $db->error);
    }
}

function dist_royalty_getInput() {
    $input = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
        $json = file_get_contents('php://input');
        if ($json) {
            $input = json_decode($json, true);
        }
        if (empty($input)) {
            $input = $_POST;
        }
    }
    return $input;
}
?>
