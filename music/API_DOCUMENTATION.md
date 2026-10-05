# Music Platform API Documentation

## Platform Overview
**Platform Name:** music.hitune.in  
**API Base URL:** https://music.hitune.in/api/  
**Version:** 2074  
**Environment:** Production/Development  

## Configuration Details

### Database Configuration
- **Host:** localhost
- **Database:** musicpro
- **Username:** musicpro
- **Timezone:** Africa/Abidjan

### Platform Features
- Client private mode: Disabled
- Auto images: Enabled
- Session IP lock: Disabled
- Session platform lock: Disabled
- API diagnostics: Advanced
- Production mode: Disabled (Development)
- Session live: Enabled

## API Endpoints Structure

### 1. Client Endpoints (`/api/app/client/endpoints/`)

#### Core Services
- **Client Configuration** (`endpoint_client_config.php`)
  - Purpose: Get client configuration settings
  - Method: POST
  - Response: Client configuration data

- **Client Translations** (`endpoint_client_translations.php`)
  - Purpose: Get translation strings for client interface
  - Method: POST
  - Response: Translation data

#### Search & Discovery
- **Search** (`endpoint_search.php`)
  - Purpose: Search for music content
  - Parameters: 
    - `query` (string): Search query
    - `page` (int): Page number (min: 2)
    - `ot` (string): Object type filter
  - Response: Search results with widgets and history

- **Search Suggestions** (`endpoint_searchSuggs.php`)
  - Purpose: Get search suggestions
  - Method: POST
  - Response: Suggested search terms

- **Search Submit** (`endpoint_searchSubmit.php`)
  - Purpose: Submit search query
  - Method: POST
  - Response: Search submission confirmation

#### Music Streaming & Playback
- **External Music** (`endpoint_external_music.php`)
  - Purpose: Handle external music sources
  - Method: POST
  - Response: External music data

- **Music Record** (`muse/endpoint_muse_record.php`)
  - Purpose: Record music plays and statistics
  - Parameters:
    - `object_type` (string): Type of music object
    - `object_hash` (md5): Unique object identifier
  - Features:
    - Tracks unique plays per user
    - Updates play counts and unique play counts
    - User-generated content action logging

#### User Management
- **User Authentication** (`user/endpoint_user_auth.php`)
  - Purpose: User authentication
  - Method: POST
  - Response: Authentication status

- **User Login Social** (`user/endpoint_login_social_ini.php`, `user/endpoint_login_social_get.php`)
  - Purpose: Social media login integration
  - Method: POST
  - Response: Social login data

- **User Logout** (`user/endpoint_user_logout.php`)
  - Purpose: User logout functionality
  - Method: POST
  - Response: Logout confirmation

- **User Edit** (`user/endpoint_user_edit.php`)
  - Purpose: Edit user profile
  - Method: POST
  - Response: Edit confirmation

- **User Verify** (`user/endpoint_user_verify.php`)
  - Purpose: User verification
  - Method: POST
  - Response: Verification status

#### User Content Management
- **User Library** (`user/endpoint_user_library.php`)
  - Purpose: Access user music library
  - Method: POST
  - Response: User's saved music content

- **User Upload** 
  - **Config** (`user/endpoint_user_upload_config.php`): Upload configuration
  - **Submit** (`user/endpoint_user_upload_submit.php`): Submit uploads
  - **Verify Sources** (`user/endpoint_user_upload_verify_sources.php`): Verify upload sources
  - **Verify Group** (`user/endpoint_user_upload_verify_group.php`): Verify upload groups

#### Payment & Subscriptions
- **Payment Check** (`endpoint_payment_check.php`)
  - Purpose: Check payment status
  - Method: POST
  - Response: Payment verification

- **User Payment** 
  - **Get Link** (`user/endpoint_user_pay_get_link.php`): Get payment links
  - **Initialize** (`user/endpoint_user_pay_ini.php`): Initialize payments

- **User Subscriptions** (`user/endpoint_user_subs.php`)
  - Purpose: Handle user subscriptions
  - Method: POST
  - Response: Subscription data

- **User Withdrawal** 
  - **Initialize** (`user/endpoint_user_withdrawal_ini.php`): Start withdrawal
  - **Process** (`user/endpoint_user_withdrawal.php`): Handle withdrawal

#### Content Sharing & Social
- **Share** (`endpoint_share.php`)
  - Purpose: Share content
  - Method: POST
  - Response: Share confirmation

- **View Bio** (`endpoint_view_bio.php`)
  - Purpose: View artist/user biographies
  - Method: POST
  - Response: Bio information

#### Ads & Monetization
- **Get Ads** (`endpoint_get_ads.php`)
  - Purpose: Retrieve advertisements
  - Method: POST
  - Response: Ad content

- **Click Ads** (`endpoint_click_ads.php`)
  - Purpose: Track ad clicks
  - Method: POST
  - Response: Click tracking confirmation

#### Localization
- **Change Currency** (`endpoint_change_currency.php`)
  - Purpose: Change display currency
  - Method: POST
  - Response: Currency change confirmation

- **Change Language** (`endpoint_change_language.php`)
  - Purpose: Change interface language
  - Method: POST
  - Response: Language change confirmation

#### Email Management
- **Email Unsubscribe** (`endpoint_email_unsubscribe.php`)
  - Purpose: Unsubscribe from emails
  - Method: POST
  - Response: Unsubscribe confirmation

#### User Notifications
- **Push Register** (`user/endpoint_user_push_register.php`)
  - Purpose: Register for push notifications
  - Method: POST
  - Response: Registration confirmation

### 2. Muse Music Engine (`/api/app/client/endpoints/muse/`)

#### Music Discovery
- **Muse Infinite** (`endpoint_muse_infinite.php`)
  - Purpose: Infinite music discovery
  - Method: POST
  - Response: Infinite music feed

- **Muse Stream Heads** (`endpoint_muse_stream_heads.php`)
  - Purpose: Get streaming headers
  - Method: POST
  - Response: Stream metadata

#### Content Fetching
- **Fetch Lyrics** (`endpoint_muse_fetch_lyrics.php`)
  - Purpose: Fetch song lyrics
  - Method: POST
  - Response: Lyrics data

- **Request Source** (`endpoint_muse_request_source.php`)
  - Purpose: Request music sources
  - Method: POST
  - Response: Source information

- **Request Download** (`endpoint_muse_request_download.php`)
  - Purpose: Request download links
  - Method: POST
  - Response: Download information

#### External Platform Integration
- **YouTube Integration**
  - **Request ID** (`endpoint_muse_request_youtube_id.php`): Get YouTube IDs
  - **Download** (`endpoint_muse_download_youtube.php`): Download from YouTube

- **SoundCloud Integration**
  - **Request ID** (`endpoint_muse_request_soundcloud_id.php`): Get SoundCloud IDs

#### Content Management
- **Muse Report** (`endpoint_muse_report.php`)
  - Purpose: Report content issues
  - Method: POST
  - Response: Report confirmation

- **Mude Solve Raaz** (`endpoint_muse_solve_raaz.php`)
  - Purpose: Solve music puzzles/mysteries
  - Method: POST
  - Response: Solution data

- **Unlock Solution** (`endpoint_muse_unlock_solution.php`)
  - Purpose: Unlock content solutions
  - Method: POST
  - Response: Unlocked content

- **Check Focus Status** (`endpoint_muse_check_focus_status.php`)
  - Purpose: Check focus mode status
  - Method: POST
  - Response: Focus status

- **Muse Embed** (`endpoint_muse_embed.php`)
  - Purpose: Generate embed codes
  - Method: POST
  - Response: Embed code

- **Preview No FF** (`endpoint_muse_preview_no_ff.php`)
  - Purpose: Preview without fast-forward
  - Method: POST
  - Response: Preview data

### 3. Admin Endpoints (`/api/app/admin/endpoints/`)

#### Authentication & Access
- **Admin Login** (`endpoint_login.php`)
  - Purpose: Admin authentication
  - Method: POST
  - Response: Admin login status

#### System Management
- **Check Version** (`endpoint_check_version.php`)
  - Purpose: Check system version
  - Method: POST
  - Response: Version information

- **Highlights** (`endpoint_highlights.php`)
  - Purpose: Get system highlights
  - Method: POST
  - Response: Highlighted features/data

- **Notifications** (`endpoint_notifications.php`)
  - Purpose: System notifications
  - Method: POST
  - Response: Notification data

- **Stats** (`endpoint_stats.php`)
  - Purpose: System statistics
  - Method: POST
  - Response: Statistical data

- **Dashboard Highlights** (`endpoint_dashboard_highlights.php`)
  - Purpose: Dashboard overview data
  - Method: POST
  - Response: Dashboard metrics

#### Configuration Management
- **Client Config** (`endpoint_client_config.php`)
  - Purpose: Manage client configurations
  - Method: POST
  - Response: Configuration data

- **Files Storage Setting** (`endpoint_files_storage_setting.php`)
  - Purpose: Configure file storage
  - Method: POST
  - Response: Storage configuration

### 4. Plugin System

#### Extra Payment Gateways (`/plugins/bof_extra_gateways/`)
Supported payment processors:
- **Razorpay** (`class_pgt_razorpay.php`)
- **Paystack** (`class_pgt_paystack.php`)
- **Flutterwave** (`class_pgt_flutterwave.php`)
- **CinetPay** (`class_pgt_cinetpay.php`)
- **KKiaPay** (`class_pgt_kkiapay.php`)
- **Chapa** (`class_pgt_chapa.php`)
- **FedaPay** (`class_pgt_fedapay.php`)
- **YooMoney** (`class_pgt_yoomoney.php`)
- **MPC** (`class_pgt_mpc.php`)
- **PesaPal** (`class_pgt_pesapal.php`)

#### Sitemap Generator (`/plugins/bof_tool_sitemap_generator/`)
- **Execute** (`endpoint_sitemap_generator_execute.php`): Generate sitemaps
- **Cancel** (`endpoint_sitemap_generator_cancel.php`): Cancel generation

### 5. Theme System (`/themes/`)
- **Shady Theme** (`/themes/shady/`)
  - Client functions: `bof_functions_client.php`
  - Admin functions: `bof_functions_admin.php`
  - Shared functions: `bof_functions_shared.php`

## Security Features

### Authentication & Authorization
- Session-based authentication
- IP locking (configurable)
- Platform fingerprint locking
- Session lifetime management
- Cross-platform session control

### Data Protection
- MD5 hashing for object identifiers
- User input validation and sanitization
- API endpoint protection
- License verification system

## API Response Format

### Success Response
```json
{
  "status": "ok",
  "message": "Operation successful",
  "data": { /* response data */ }
}
```

### Error Response
```json
{
  "status": "error",
  "message": "Error description",
  "error_code": "ERROR_CODE"
}
```

## Usage Guidelines

### Rate Limiting
- Implement appropriate rate limiting for API calls
- Monitor usage patterns to prevent abuse
- Use session management for user tracking

### Error Handling
- Always check response status
- Handle network errors gracefully
- Implement retry mechanisms for failed requests

### Best Practices
1. Always validate user input on client side
2. Use HTTPS for all API communications
3. Implement proper authentication flows
4. Cache responses where appropriate
5. Handle pagination for large datasets
6. Monitor API performance and usage

## Integration Examples

### Basic Search Request
```javascript
fetch('https://music.hitune.in/api/app/client/endpoints/endpoint_search.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    query: 'artist name',
    page: 2,
    ot: 'music'
  })
})
.then(response => response.json())
.then(data => console.log(data));
```

### Music Playback Tracking
```javascript
fetch('https://music.hitune.in/api/app/client/endpoints/muse/endpoint_muse_record.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    object_type: 'track',
    object_hash: 'md5_hash_of_track'
  })
});
```

## Support & Maintenance

### System Requirements
- PHP 7.4+
- MySQL 5.7+
- Web server (Apache/Nginx)
- SSL certificate for HTTPS

### Monitoring
- API diagnostics enabled for advanced monitoring
- Version checking system for updates
- Error logging and reporting
- Performance metrics collection

### Updates
- Current version: 2074
- Regular updates through version checking system
- Plugin system for extending functionality
- Theme system for customization

---

**Note:** This documentation covers the complete API structure of the music.hitune.in platform. All endpoints are organized by functionality and include detailed parameter specifications, response formats, and usage examples. The platform supports comprehensive music streaming, user management, payment processing, and administrative functions.