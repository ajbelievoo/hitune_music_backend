<?php
/**
 * HiTune CSRF Protection Helpers
 * Requirement 15.9 — CSRF protection on all POST forms
 */

/**
 * Generate (or retrieve) a CSRF token stored in the session.
 */
function generateCsrfToken(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Validate a submitted CSRF token against the session token.
 * Returns false (and sends HTTP 403) if the token is invalid.
 */
function validateCsrfToken(string $token): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (empty($sessionToken) || !hash_equals($sessionToken, $token)) {
        http_response_code(403);
        // Output a minimal error page and stop execution
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>403 Forbidden</title></head>'
           . '<body style="font-family:sans-serif;background:#0a0a0f;color:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh;">'
           . '<div style="text-align:center"><h1>403 Forbidden</h1><p>Invalid or missing CSRF token. Please go back and try again.</p>'
           . '<a href="javascript:history.back()" style="color:#00b7ff;">Go Back</a></div></body></html>';
        exit;
    }

    return true;
}

/**
 * Return an HTML hidden input containing the current CSRF token.
 */
function csrfField(): string
{
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}
