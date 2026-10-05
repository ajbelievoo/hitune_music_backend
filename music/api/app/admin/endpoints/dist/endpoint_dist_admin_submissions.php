<?php

/**
 * Music Distribution - Admin Submissions Management
 * Admin can view, review, and update submission status
 */

if ( !defined("bof_root") ) die;

function endpoint_dist_admin_submissions( $loader, $excuter, $args ){

    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        return dist_admin_getSubmissions($loader);
    } else {
        return dist_admin_updateSubmission($loader);
    }
}

function dist_admin_getSubmissions($loader) {
    $db = $loader->db;
        
    $status = !empty($_GET['status']) ? $db->real_escape_string($_GET['status']) : null;
    $type = !empty($_GET['type']) ? $db->real_escape_string($_GET['type']) : null;
    $search = !empty($_GET['search']) ? $db->real_escape_string($_GET['search']) : null;
    $page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;

    $sql = "SELECT s.*, u.username, u.email, u.avatar, p.name as plan_name
        FROM `_dist_submissions` s
        LEFT JOIN `_u_list` u ON s.user_id = u.id
        LEFT JOIN `_dist_plans` p ON s.plan_id = p.id
        WHERE 1=1";
    
    $count_sql = "SELECT COUNT(*) as total FROM `_dist_submissions` s WHERE 1=1";
    
    if ($status) {
        $sql .= " AND s.status = '{$status}'";
        $count_sql .= " AND s.status = '{$status}'";
    }
    
    if ($type) {
        $sql .= " AND s.type = '{$type}'";
        $count_sql .= " AND s.type = '{$type}'";
    }
    
    if ($search) {
        $sql .= " AND (s.title LIKE '%{$search}%' OR s.artist_name LIKE '%{$search}%' OR u.username LIKE '%{$search}%')";
        $count_sql .= " AND (s.title LIKE '%{$search}%' OR s.artist_name LIKE '%{$search}%' OR u.username LIKE '%{$search}%')";
    }
    
    $sql .= " ORDER BY s.time_add DESC LIMIT {$limit} OFFSET {$offset}";

    $result = $db->query($sql);
    $submissions = [];
    
    while ($row = $result->fetch_assoc()) {
        $submissions[] = [
            'id' => (int)$row['id'],
            'user' => [
                'id' => (int)$row['user_id'],
                'username' => $row['username'],
                'email' => $row['email'],
                'avatar' => $row['avatar']
            ],
            'type' => $row['type'],
            'title' => $row['title'],
            'artist_name' => $row['artist_name'],
            'album_name' => $row['album_name'],
            'genre' => $row['genre'],
            'release_date' => $row['release_date'],
            'isrc' => $row['isrc'],
            'upc' => $row['upc'],
            'label_name' => $row['label_name'],
            'cover_art' => $row['cover_art_path'],
            'audio_file' => $row['audio_file_path'],
            'platforms' => json_decode($row['platforms'], true),
            'countries' => json_decode($row['countries'], true),
            'status' => $row['status'],
            'tunecore_url' => $row['tunecore_url'],
            'tunecore_status' => $row['tunecore_status'],
            'admin_notes' => $row['admin_notes'],
            'plan' => $row['plan_name'],
            'submitted_at' => $row['time_add'],
            'updated_at' => $row['time_update']
        ];
    }

    // Get total count
    $count_result = $db->query($count_sql);
    $total = $count_result->fetch_assoc()['total'];
    
    // Get status counts
    $status_counts = [];
    $status_query = $db->query("SELECT status, COUNT(*) as count FROM `_dist_submissions` GROUP BY status");
    while ($row = $status_query->fetch_assoc()) {
        $status_counts[$row['status']] = (int)$row['count'];
    }

    $loader->api->set_message("ok", [
        'submissions' => $submissions,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'total_items' => $total,
            'per_page' => $limit
        ],
        'status_counts' => $status_counts,
        'filters' => [
            'status' => $status,
            'type' => $type,
            'search' => $search
        ]
    ]);
}

function dist_admin_updateSubmission($loader) {
    $db = $loader->db;
    $admin = $loader->user->check();
    $admin_id = $admin->ID ?? 0;
    $input = dist_admin_getInput();

    $submission_id = !empty($input['submission_id']) ? (int)$input['submission_id'] : 0;
    if (!$submission_id) {
        return $loader->api->set_error("Submission ID required");
    }

    // Get current status
    $current = $db->query("SELECT * FROM `_dist_submissions` WHERE id = {$submission_id}");
    if ($current->num_rows === 0) {
        return $loader->api->set_error("Submission not found");
    }
    
    $submission = $current->fetch_assoc();
    $old_status = $submission['status'];

    $updates = [];
    $new_status = null;
    
    // Update status
    if (!empty($input['status']) && in_array($input['status'], 
        ['submitted', 'in_review', 'in_progress', 'approved', 'rejected', 'launched', 'taken_down'])) {
        $new_status = $db->real_escape_string($input['status']);
        $updates[] = "status = '{$new_status}'";
    }

    // Update admin notes
    if (isset($input['admin_notes'])) {
        $notes = $db->real_escape_string($input['admin_notes']);
        $updates[] = "admin_notes = '{$notes}'";
    }

    // Update TuneCore URL
    if (isset($input['tunecore_url'])) {
        $url = $db->real_escape_string($input['tunecore_url']);
        $updates[] = "tunecore_url = '{$url}'";
    }

    // Update TuneCore status
    if (isset($input['tunecore_status'])) {
        $tstatus = $db->real_escape_string($input['tunecore_status']);
        $updates[] = "tunecore_status = '{$tstatus}'";
    }

    // Update launch date
    if (isset($input['launch_date'])) {
        $ldate = $db->real_escape_string($input['launch_date']);
        $updates[] = "launch_date = '{$ldate}'";
    }

    // Update ISRC
    if (isset($input['isrc'])) {
        $isrc = $db->real_escape_string($input['isrc']);
        $updates[] = "isrc = '{$isrc}'";
    }

    // Update UPC
    if (isset($input['upc'])) {
        $upc = $db->real_escape_string($input['upc']);
        $updates[] = "upc = '{$upc}'";
    }

    if (empty($updates)) {
        return $loader->api->set_error("No updates provided");
    }

    $sql = "UPDATE `_dist_submissions` SET " . implode(', ', $updates) . ", time_update = NOW() WHERE id = {$submission_id}";
    
    if ($db->query($sql)) {
        // Log the activity
        $new_status = $new_status ?? $old_status;
        if ($new_status !== $old_status) {
            dist_admin_logActivity($loader, $submission['user_id'], $submission_id, 'status_change', $old_status, $new_status, 
                !empty($input['admin_notes']) ? $input['admin_notes'] : "Status changed to {$new_status}");
        }

        $loader->api->set_message("ok", [
            'message' => 'Submission updated successfully',
            'submission_id' => $submission_id,
            'new_status' => $new_status
        ]);
    } else {
        return $loader->api->set_error("Failed to update: " . $db->error);
    }
}

function dist_admin_logActivity($loader, $user_id, $submission_id, $action, $old_status, $new_status, $details) {
    $db = $loader->db;
    $admin = $loader->user->check();
    $admin_id = $admin->ID ?? 0;
    $details = $db->real_escape_string($details);
    $sql = "INSERT INTO `_dist_logs` (user_id, submission_id, action, old_status, new_status, details, performed_by)
        VALUES ({$user_id}, {$submission_id}, '{$action}', " . ($old_status ? "'{$old_status}'" : "NULL") . ", 
        " . ($new_status ? "'{$new_status}'" : "NULL") . ", '{$details}', {$admin_id})";
    $db->query($sql);
}

function dist_admin_getInput() {
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
