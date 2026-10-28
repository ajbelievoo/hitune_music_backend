<?php
/**
 * ISRC / UPC auto-assignment helpers.
 * Gated by settings.auto_assign_codes = '1'.
 *
 * ISRC format: CCXXXYYNNNNN (12 chars) — country(2) + registrant(3) + year(2) + designation(5)
 * UPC format : 12-digit UPC-A with valid check digit.
 */

function getCodeAssignSetting(string $key, string $default = ''): string {
    global $conn;
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ($row && $row['setting_value'] !== '') ? $row['setting_value'] : $default;
}

/** Generate a unique ISRC for this DB. */
function generateIsrc(): ?string {
    global $conn;
    $prefix = strtoupper(preg_replace('/[^A-Z0-9]/', '', getCodeAssignSetting('isrc_prefix', 'INHIT')));
    if (strlen($prefix) !== 5) $prefix = 'INHIT';
    $year = date('y');
    for ($i = 0; $i < 25; $i++) {
        $code = $prefix . $year . str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        $stmt = $conn->prepare("SELECT 1 FROM release_tracks WHERE isrc_code = ? LIMIT 1");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if (!$exists) return $code;
    }
    return null;
}

/** Generate a unique 12-digit UPC-A with valid check digit. */
function generateUpc(): ?string {
    global $conn;
    for ($i = 0; $i < 25; $i++) {
        $digits = '7' . str_pad((string) random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
        $sum = 0;
        for ($j = 0; $j < 11; $j++) {
            $sum += ((int) $digits[$j]) * (($j % 2 === 0) ? 3 : 1);
        }
        $check = (10 - ($sum % 10)) % 10;
        $code = $digits . $check;
        $stmt = $conn->prepare("SELECT 1 FROM releases WHERE upc_code = ? LIMIT 1");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if (!$exists) return $code;
    }
    return null;
}

/**
 * Assign missing UPC (release) + ISRCs (tracks) for a release.
 * No-op unless settings.auto_assign_codes = '1'.
 * Returns ['upc' => ?, 'isrc_assigned' => n]
 */
function assignReleaseCodes(int $releaseId): array {
    global $conn;
    $result = ['upc' => null, 'isrc_assigned' => 0];

    if (getCodeAssignSetting('auto_assign_codes', '0') !== '1') {
        return $result;
    }

    // UPC on the release
    $stmt = $conn->prepare("SELECT upc_code FROM releases WHERE id = ?");
    $stmt->bind_param("i", $releaseId);
    $stmt->execute();
    $rel = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($rel && empty($rel['upc_code'])) {
        $upc = generateUpc();
        if ($upc) {
            $u = $conn->prepare("UPDATE releases SET upc_code = ? WHERE id = ?");
            $u->bind_param("si", $upc, $releaseId);
            $u->execute();
            $u->close();
            $result['upc'] = $upc;
        }
    }

    // ISRC per track
    $t = $conn->prepare("SELECT id FROM release_tracks WHERE release_id = ? AND (isrc_code IS NULL OR isrc_code = '')");
    $t->bind_param("i", $releaseId);
    $t->execute();
    $rows = $t->get_result()->fetch_all(MYSQLI_ASSOC);
    $t->close();

    foreach ($rows as $row) {
        $isrc = generateIsrc();
        if (!$isrc) break;
        $u = $conn->prepare("UPDATE release_tracks SET isrc_code = ? WHERE id = ?");
        $tid = (int) $row['id'];
        $u->bind_param("si", $isrc, $tid);
        $u->execute();
        $u->close();
        $result['isrc_assigned']++;
    }

    return $result;
}
