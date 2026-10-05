<?php
/**
 * Hitune SSO Sync Helper
 *
 * Keeps the distribution website account (web.users) in sync with the
 * Hitune Music account (musicpro._u_list) so one email + password works
 * on both music.hitune.in and the distribution site.
 *
 * All functions fail silently — a music-side hiccup must never break
 * the local login/signup flow.
 */

/**
 * Fetch a Hitune Music user row by email.
 * @return array|null
 */
function sso_music_find(mysqli $conn, string $email): ?array
{
    try {
        $stmt = $conn->prepare(
            "SELECT ID, username, name, email, password, time_verify
             FROM musicpro._u_list WHERE email = ? LIMIT 1"
        );
        if (!$stmt) return null;
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    } catch (Throwable $e) {
        error_log('sso_music_find: ' . $e->getMessage());
        return null;
    }
}

/**
 * Create a Hitune Music account mirroring a distribution user.
 * $passwordHash must already be a password_hash() value.
 * $verified = true marks the music account verified immediately.
 * @return bool
 */
function sso_music_create(mysqli $conn, string $name, string $email, string $passwordHash, bool $verified): bool
{
    try {
        if (sso_music_find($conn, $email)) return true; // already exists

        // Unique username derived from email local part
        $base = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', strstr($email, '@', true) ?: 'user'));
        if ($base === '') $base = 'user';
        $base = substr($base, 0, 40);
        $username = $base;
        for ($i = 0; $i < 20; $i++) {
            $chk = $conn->prepare("SELECT ID FROM musicpro._u_list WHERE username = ? LIMIT 1");
            $chk->bind_param("s", $username);
            $chk->execute();
            $taken = $chk->get_result()->num_rows > 0;
            $chk->close();
            if (!$taken) break;
            $username = $base . random_int(100, 9999);
        }

        $hash = md5(uniqid((string) mt_rand(), true));
        $timeVerify = $verified ? date('Y-m-d H:i:s') : null;

        $stmt = $conn->prepare(
            "INSERT INTO musicpro._u_list
                (hash, username, name, password, email, role_ids, external_addresses, time_verify, time_add)
             VALUES (?, ?, ?, ?, ?, '2', '[]', ?, NOW())"
        );
        if (!$stmt) return false;
        $stmt->bind_param("ssssss", $hash, $username, $name, $passwordHash, $email, $timeVerify);
        $ok = $stmt->execute();
        if (!$ok) error_log('sso_music_create: ' . $stmt->error);
        $stmt->close();
        return $ok;
    } catch (Throwable $e) {
        error_log('sso_music_create: ' . $e->getMessage());
        return false;
    }
}

/**
 * Mark the mirrored music account as verified (called after web email verification).
 */
function sso_music_mark_verified(mysqli $conn, string $email): void
{
    try {
        $stmt = $conn->prepare(
            "UPDATE musicpro._u_list SET time_verify = NOW()
             WHERE email = ? AND time_verify IS NULL"
        );
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Throwable $e) {
        error_log('sso_music_mark_verified: ' . $e->getMessage());
    }
}

/**
 * Sync a new password to the music account (called on web password reset).
 */
function sso_music_update_password(mysqli $conn, string $email, string $passwordHash): void
{
    try {
        $stmt = $conn->prepare(
            "UPDATE musicpro._u_list SET password = ? WHERE email = ?"
        );
        if ($stmt) {
            $stmt->bind_param("ss", $passwordHash, $email);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Throwable $e) {
        error_log('sso_music_update_password: ' . $e->getMessage());
    }
}

/**
 * Auto-provision a local web.users row from a verified music login.
 * Returns the new local user array or null.
 */
function sso_provision_local(mysqli $conn, array $musicUser): ?array
{
    try {
        $name = $musicUser['name'] ?: $musicUser['username'];
        $verified = !empty($musicUser['time_verify']) ? 1 : 0;

        $stmt = $conn->prepare(
            "INSERT INTO users (name, email, password, auth_type, email_verified, created_at)
             VALUES (?, ?, ?, 'email', ?, NOW())"
        );
        if (!$stmt) return null;
        $stmt->bind_param("sssi", $name, $musicUser['email'], $musicUser['password'], $verified);
        if (!$stmt->execute()) {
            $stmt->close();
            return null;
        }
        $id = $conn->insert_id;
        $stmt->close();

        return ['id' => $id, 'email' => $musicUser['email'], 'name' => $name, 'email_verified' => $verified];
    } catch (Throwable $e) {
        error_log('sso_provision_local: ' . $e->getMessage());
        return null;
    }
}
