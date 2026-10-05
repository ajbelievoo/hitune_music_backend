<?php
/**
 * Google OAuth Configuration
 * 
 * Instructions:
 * 1. Go to https://console.cloud.google.com/
 * 2. Create a new project or select existing
 * 3. Go to APIs & Services > Credentials
 * 4. Click "Create Credentials" > "OAuth client ID"
 * 5. Configure OAuth consent screen (External or Internal)
 * 6. Set Application type: Web application
 * 7. Add Authorized redirect URIs: https://yourdomain.com/index.php?q=google_callback
 * 8. Copy Client ID and Client Secret below
 */

// Google OAuth Credentials - REPLACE THESE VALUES
$GOOGLE_CLIENT_ID = '';
$GOOGLE_CLIENT_SECRET = '';

// Get the redirect URI dynamically
$GOOGLE_REDIRECT_URI = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'yourdomain.com') . '/index.php?q=google_callback';

/**
 * Check if Google OAuth is properly configured
 * 
 * @return bool
 */
function isGoogleOAuthConfigured() {
    global $GOOGLE_CLIENT_ID, $GOOGLE_CLIENT_SECRET;
    return !empty($GOOGLE_CLIENT_ID) && !empty($GOOGLE_CLIENT_SECRET);
}

/**
 * Get Google OAuth authorization URL
 * 
 * @return string
 */
function getGoogleAuthUrl() {
    global $GOOGLE_CLIENT_ID, $GOOGLE_REDIRECT_URI;
    
    $params = [
        'client_id' => $GOOGLE_CLIENT_ID,
        'redirect_uri' => $GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'email profile',
        'access_type' => 'online',
        'prompt' => 'select_account'
    ];
    
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}
?>
