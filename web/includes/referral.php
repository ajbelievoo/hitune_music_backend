<?php
// Referral bonus program (strategy doc §8): users get a personal referral
// link; when a referred artist publishes their first release, the referrer
// earns `referral_bonus` (settings table, INR credit on users.referral_credit).

if (!function_exists('ref_code_for')) {
    function ref_code_for($conn, $user_id) {
        $user_id = (int) $user_id;
        $r = $conn->query("SELECT referral_code FROM users WHERE id = {$user_id} LIMIT 1");
        $code = ($r && $r->num_rows) ? $r->fetch_assoc()['referral_code'] : null;
        if ($code) return $code;
        for ($i = 0; $i < 10; $i++) {
            $code = 'HT' . strtoupper(substr(bin2hex(random_bytes(6)), 0, 8));
            $c = $conn->prepare("UPDATE users SET referral_code = ? WHERE id = ? AND (referral_code IS NULL OR referral_code = '')");
            $c->bind_param("si", $code, $user_id);
            $c->execute();
            if ($c->affected_rows > 0) return $code;
        }
        return null;
    }
}

if (!function_exists('ref_user_by_code')) {
    function ref_user_by_code($conn, $code) {
        $code = trim((string) $code);
        if ($code === '') return null;
        $stmt = $conn->prepare("SELECT id FROM users WHERE referral_code = ? LIMIT 1");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? (int) $row['id'] : null;
    }
}

// Record the signup event (no money yet) + return referrer id or null.
if (!function_exists('ref_record_signup')) {
    function ref_record_signup($conn, $referrer_id, $referee_id) {
        $referrer_id = (int) $referrer_id; $referee_id = (int) $referee_id;
        if (!$referrer_id || !$referee_id || $referrer_id === $referee_id) return null;
        $stmt = $conn->prepare("INSERT IGNORE INTO referral_events (referrer_id, referee_id, event) VALUES (?, ?, 'signup')");
        $stmt->bind_param("ii", $referrer_id, $referee_id);
        $stmt->execute();
        $conn->query("UPDATE users SET referred_by = {$referrer_id} WHERE id = {$referee_id} AND (referred_by IS NULL OR referred_by = 0)");
        return $referrer_id;
    }
}

// Credit the referrer once the referee's FIRST release goes live on HiTune.
// Called from ecosystem_sync publish success.
if (!function_exists('ref_credit_first_release')) {
    function ref_credit_first_release($conn, $referee_id) {
        $referee_id = (int) $referee_id;
        $r = $conn->query("SELECT referred_by FROM users WHERE id = {$referee_id} LIMIT 1");
        $referrer = ($r && $r->num_rows) ? (int) $r->fetch_assoc()['referred_by'] : 0;
        if (!$referrer) return false;

        $amount = 0.0;
        $s = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'referral_bonus' LIMIT 1");
        if ($s && $s->num_rows) $amount = (float) $s->fetch_assoc()['setting_value'];
        if ($amount <= 0) $amount = 100.00; // default ₹100

        $stmt = $conn->prepare("INSERT IGNORE INTO referral_events (referrer_id, referee_id, event, credit, meta)
            VALUES (?, ?, 'first_release', ?, 'release live on HiTune')");
        $stmt->bind_param("iid", $referrer, $referee_id, $amount);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $conn->query("UPDATE users SET referral_credit = referral_credit + {$amount} WHERE id = {$referrer}");
            return true;
        }
        return false;
    }
}
