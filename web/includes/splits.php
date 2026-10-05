<?php
/**
 * Split-royalty helpers (strategy doc §1 "Split Royalty").
 *
 * `track_splits` rows give a collaborator `pct`% of a track's royalties.
 * The owner's share is the remainder: (100 - SUM(active split pct))%.
 * Royalties are still recorded gross per-track in `track_royalties`;
 * splits are applied at read time so edits propagate to new imports.
 */

// All splits for a release, resolved against users when the email matches an account.
function splits_for_release($conn, $release_id) {
    $out = [];
    $stmt = $conn->prepare("
        SELECT s.*, rt.song_title, u.name AS user_name
        FROM track_splits s
        JOIN release_tracks rt ON rt.id = s.track_id
        LEFT JOIN users u ON u.id = s.user_id
        WHERE s.release_id = ? AND s.status = 'active'
        ORDER BY rt.track_number, s.id
    ");
    $stmt->bind_param("i", $release_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $out[] = $row;
    return $out;
}

// Net royalty for a user = own releases' remainder share + incoming collaborator shares.
function user_net_royalties($conn, $user_id) {
    // my own releases, minus what I split away
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(tr.royalty_amount * (100 - COALESCE(sp.given,0)) / 100), 0) AS net
        FROM track_royalties tr
        JOIN release_tracks rt ON tr.track_id = rt.id
        JOIN releases r ON rt.release_id = r.id
        LEFT JOIN (
            SELECT track_id, SUM(pct) AS given FROM track_splits WHERE status='active' GROUP BY track_id
        ) sp ON sp.track_id = tr.track_id
        WHERE r.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $own = (float) ($stmt->get_result()->fetch_assoc()['net'] ?? 0);
    $stmt->close();

    // shares credited to me as a collaborator on other people's tracks
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(tr.royalty_amount * s.pct / 100), 0) AS inc
        FROM track_splits s
        JOIN track_royalties tr ON tr.track_id = s.track_id
        WHERE s.status = 'active' AND s.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $incoming = (float) ($stmt->get_result()->fetch_assoc()['inc'] ?? 0);
    $stmt->close();

    return $own + $incoming;
}

// Add/replace an active split for a track. Returns error string or null.
function split_add($conn, $track_id, $release_id, $email, $pct) {
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return 'Invalid collaborator email.';
    if ($pct <= 0 || $pct > 100) return 'Split must be between 0 and 100.';

    // total split on a track cannot exceed 100%
    $stmt = $conn->prepare("SELECT COALESCE(SUM(pct),0) AS total FROM track_splits WHERE track_id = ? AND status='active' AND email <> ?");
    $stmt->bind_param("is", $track_id, $email);
    $stmt->execute();
    $existing = (float) $stmt->get_result()->fetch_assoc()['total'];
    if ($existing + $pct > 100) return 'Splits on this track already total ' . $existing . '% — ' . (100 - $existing) . '% left.';
    $stmt->close();

    // resolve collaborator account when the email belongs to a portal user
    $user_id = null;
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($u = $stmt->get_result()->fetch_assoc()) $user_id = (int) $u['id'];
    $stmt->close();

    $stmt = $conn->prepare("
        INSERT INTO track_splits (track_id, release_id, email, user_id, pct)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE pct = VALUES(pct), user_id = VALUES(user_id), status = 'active'
    ");
    $stmt->bind_param("iisid", $track_id, $release_id, $email, $user_id, $pct);
    $stmt->execute();
    $stmt->close();
    return null;
}
