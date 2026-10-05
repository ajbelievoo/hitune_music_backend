# HiTune API Documentation for Flutter Team

## Endpoint Address
The primary endpoint address for all API requests is: `https://music.hitune.in/api/`

---

## 1. Search API
To perform a search, use the following POST request.

- **Endpoint**: `https://music.hitune.in/api/search`
- **Method**: `POST`
- **Parameters**:
    - `query` (string): The search query.
    - `ot` (string): Object Type to search for (optional). Possible values: `m_track`, `m_album`, `m_artist`.
    - `page` (int): Page number (optional, default: 1).

- **Response Structure**:
    ```json
    {
      "success": true,
      "messages": [],
      "widgets": [
        {
          "type": "table",
          "title": "Search Results",
          "items": [
            {
              "title": "Song Title",
              "sub_title": "Artist Name",
              "hash": "...",
              "object_type": "m_track",
              "cover": "..."
            }
          ]
        }
      ]
    }
    ```

---

## 2. Playback / Source API
To get the audio/video source for a specific track.

- **Endpoint**: `https://music.hitune.in/api/muse/request_source`
- **Method**: `POST`
- **Parameters**:
    - `object_type` (string): Usually `m_track`.
    - `object_hash` (string): The hash of the track.
    - `solve` (string): Set to `true` if you need the final resolved URL (optional).

- **Source Resolution (Raaz)**:
    If the response contains `raaz: true`, it means the URL needs to be decrypted or resolved via another call to `muse/solve_raaz`.

---

## 3. Upload API
To upload new tracks from the app.

- **Endpoint**: `https://music.hitune.in/api/upload`
- **Method**: `POST`
- **Headers**:
    - `x-becli-version`: `1.0.0`
    - `x-bof-app-package`: `com.hitune.app`
    - `x-bof-version`: `2074`
- **Parameters**:
    - `file` (file): The audio file to upload.
    - `type` (string): Default is `audio`.
    - `object_type` (string): Default is `m_track_source`.

---

## 4. Authentication / Session
For personalized actions (playlists, likes), include session data.

- **Headers**:
    - `x-bof-sess-id`: Your session ID.
    - `x-bof-sess-key`: Your session key.

---

---

## Important Configurations
- **Fulltext Search**: `inverted_indexing` (Enabled in `config_user.php`).
- **Playback Delay Fix**: Request blocking interval is reduced to 2 minutes to ensure instant playback for new songs.
- **Search Timeout**: Web/App search debounce is reduced to 50ms for instant results.
- **Cover Image**: The `cover` key in search and other object results is now a direct URL string (image thumbnail).

---

## 5. File Upload Endpoint
**Endpoint:** `POST https://music.hitune.in/api/upload`
**Description:** Uploads a file (audio, image, or video) to the server.

### Headers
- `X-Becli-Version`: Must be `1.0.0` or higher.
- `X-Bof-App-Package`: `com.hitune.app`
- `Content-Type`: `multipart/form-data`

### Body (Form-Data)
- `file`: The file to upload.
- `type`: Type of file (`audio`, `image`, or `video`).
- `object_type`: The object type the file belongs to (e.g., `m_track_source`).

### Response
```json
{
    "success": true,
    "message": "done",
    "data": {
        "type": "audio",
        "file_id": 123,
        "file_pass": "abcde12345",
        "file_preview": "https://music.hitune.in/files/unused/filename.mp3"
    }
}
```

---

## 2. User Upload Verification
**Endpoint:** `POST /api/app/client/user_upload_verify_sources`
**Description:** Verifies the files or YouTube links before final submission.

### Parameters (POST)
- `content_data`: `{"ID":"music"}` (JSON string)
- `source_data`: `{"ID":"audio"}` or `{"ID":"youtube"}` (JSON string)
- `given_data`: 
    - For Audio: `{"files":[{"type":"audio","success":true,"file_id":"123","file_pass":"abcde"}]}`
    - For YouTube: `{"inputs":{"youtube_id":"YOUTUBE_VIDEO_ID"}}`

### Response
Returns a `verified` array. Each item has a unique `ID` (e.g., `69c30806c7fb3`). This ID is used as the `source_id` in the next step.

---

## 3. User Upload Submission
**Endpoint:** `POST /api/app/client/user_upload_submit`
**Description:** Finalizes the upload and creates the track/album in the database.

### Parameters (POST)
- `content_data`: `{"ID":"music"}` (JSON string)
- `source_data`: `{"ID":"audio"}` or `{"ID":"youtube"}` (JSON string)
- `source_id`: The unique ID received from the `verify_sources` response (e.g., `69c30806c7fb3`).
- `verified_source`: The complete object of the item from the `verified` array in the `verify_sources` response (JSON string).
- **Item Fields (Prefixed with `source_id`):**
    - `{source_id}_title`: Track title (String)
    - `{source_id}_artist_name`: Artist name (String)
    - `{source_id}_album_id`: (Optional) Existing Album ID (Integer)
    - `{source_id}_release_date`: Release date (YYYY-MM-DD)
    - `{source_id}_cover`: Cover image file ID or URL (String)
    - `{source_id}_description`: (Optional) Track description (String)

### ✅ ANSWERS TO YOUR QUESTIONS:

**1. Kya field names sahi hain? (title, artist_name, album, cover, release_date, description)**
- ✅ **Field names are correct** but must be prefixed with `source_id`
- ✅ Use `{source_id}_album_id` instead of `{source_id}_album` for existing albums
- ✅ Example: `69c30806c7fb3_title`, `69c30806c7fb3_artist_name`, etc.

**2. Kya group_hash ke saath koi aur required parameter hai?**
- ❌ **NO group_hash parameter is required** - this was causing the "invalid_data" error
- ✅ The system uses `source_id` as prefix for all fields, not `group_hash`

**3. user_upload_verify_sources response mein kya fields aane chahiye jo submit mein bhejne hain?**
- ✅ **Required fields from verify_sources response:**
  - `content_data`: `{"ID":"music"}`
  - `source_data`: `{"ID":"audio"}` or `{"ID":"youtube"}`
  - `source_id`: The unique ID from verify_sources response
  - `verified_source`: Complete verified object from verify_sources response

### Example Payload (Corrected)
```json
{
  "content_data": "{\"ID\":\"music\"}",
  "source_data": "{\"ID\":\"audio\"}",
  "source_id": "69c30806c7fb3",
  "verified_source": "{\"data\":{\"type\":\"audio\",\"success\":true,\"file_id\":\"374\",\"file_pass\":\"51c589621c\"},\"tags\":{},\"ID\":\"69c30806c7fb3\"}",
  "69c30806c7fb3_title": "AUD-20260324-WA0007",
  "69c30806c7fb3_artist_name": "anmol",
  "69c30806c7fb3_release_date": "2026-03-25",
  "69c30806c7fb3_cover": "380",
  "69c30806c7fb3_description": "helli"
}
```

---

## 4. YouTube Integration & Automation
The system supports automated YouTube track fetching and streaming.

### YouTube ID Fetching
- **Endpoint:** `POST /api/app/client/muse/request_youtube_id`
- **Parameters:** `title`, `sub_title`, `object_type` (must be `m_track`), `object_hash`, `duration`.
- **Functionality:** Searches YouTube for the track and saves the ID to the database.

---

## 5. Error Handling & Troubleshooting

When an API request fails, the response will have `success: false`. The `messages` array will contain the error code or description.

### Common Error Codes
- `failed_pending`: The source is currently being resolved (e.g., fetching from YouTube). 
    - **Action**: Wait 2-3 seconds and retry the request.
- `cant_play`: No playable source could be found for this track.
- `access_denied`: The user does not have permission to play this track (e.g., requires subscription).
- `bad_inputs`: One or more required parameters are missing or invalid.
- `Request in progress`: Another request for the same resource is already being processed.

### Playback Flow (with Raaz)
1. Call `muse/request_source`.
2. If `raaz: true` is present in the source object:
    - You MUST call `muse/solve_raaz` with the same parameters to get the final stream URL.
    - If `request_source` returns a direct `audio` or `video` type with an `address`, you can play it directly.

### Instant Playback for New Songs
The backend has been optimized to reduce playback delays:
- Request blocking interval reduced to **2 minutes**.
- Search debounce reduced to **50ms**.
- Automatic YouTube ID fetching and stream extraction is enabled.

---

## 6. YouTube Import Process
1. **Upload Verification:** Use `{"source_data":"{\"ID\":\"youtube\"}"}` and `{"given_data":"{\"inputs\":{\"youtube_id\":\"VIDEO_ID\"}}"}`
2. **System automatically:** Fetches stream URL using piped extraction
3. **Requirements:** 
   - YouTube API key configured in settings
   - `youtube_piped` setting enabled in database
   - `youtube_automation` setting enabled

### YouTube Import Example (Complete Flow)

**Step 1: Verify YouTube Source**
```bash
curl -X POST https://music.hitune.in/api/app/client/user_upload_verify_sources \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "X-Bof-App-Package: com.hitune.app" \
  -H "X-Bof-Version: 2074" \
  -d "content_data={\"ID\":\"music\"}" \
  -d "source_data={\"ID\":\"youtube\"}" \
  -d "given_data={\"inputs\":{\"youtube_id\":\"dQw4w9WgXcQ\"}}" \
  -d "sess_id=YOUR_SESSION_ID" \
  -d "sess_key=YOUR_SESSION_KEY"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "verified": [{
      "ID": "69c30806c7fb3",
      "data": {
        "type": "youtube",
        "youtube_id": "dQw4w9WgXcQ",
        "title": "Rick Astley - Never Gonna Give You Up (Official Music Video)",
        "duration": "213",
        "cover": "https://i.ytimg.com/vi/dQw4w9WgXcQ/maxresdefault.jpg"
      }
    }]
  }
}
```

**Step 2: Submit YouTube Track**
```bash
curl -X POST https://music.hitune.in/api/app/client/user_upload_submit \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "X-Bof-App-Package: com.hitune.app" \
  -H "X-Bof-Version: 2074" \
  -d "content_data={\"ID\":\"music\"}" \
  -d "source_data={\"ID\":\"youtube\"}" \
  -d "source_id=69c30806c7fb3" \
  -d "verified_source={\"data\":{\"type\":\"youtube\",\"youtube_id\":\"dQw4w9WgXcQ\",\"title\":\"Rick Astley - Never Gonna Give You Up\",\"duration\":\"213\",\"cover\":\"https://i.ytimg.com/vi/dQw4w9WgXcQ/maxresdefault.jpg\"},\"ID\":\"69c30806c7fb3\"}" \
  -d "69c30806c7fb3_title=Never Gonna Give You Up" \
  -d "69c30806c7fb3_artist_name=Rick Astley" \
  -d "69c30806c7fb3_release_date=1987-07-27" \
  -d "69c30806c7fb3_cover=https://i.ytimg.com/vi/dQw4w9WgXcQ/maxresdefault.jpg" \
  -d "69c30806c7fb3_description=Official music video" \
  -d "sess_id=YOUR_SESSION_ID" \
  -d "sess_key=YOUR_SESSION_KEY"
```

---

## 5. Configuration & Troubleshooting

### Upload Settings
- **Max Upload Size:** 50MB
- **Allowed Formats:** mp3, m4a, flac, wav, mp4, avi, jpg, gif, png
- **Upload Directory:** `/www/wwwroot/music/files/`

### Required Headers for App Requests
- `X-Bof-App-Package: com.hitune.app`
- `X-Bof-Version: 2074`
- `X-Becli-Version: 1.0.0` (for upload endpoint)

### Common Issues & Solutions

**"invalid_data" Error:**
- Missing required fields: `content_data`, `source_data`, `source_id`, `verified_source`
- Using `group_hash` instead of `source_id` prefix
- Not including complete `verified_source` object from verify_sources response

**"403" Error:**
- Session authentication issues - ensure valid `sess_id` and `sess_key`
- Missing required headers for app requests
- User upload permissions not configured

**Upload Failures:**
- Check file size limits (50MB max)
- Verify allowed file formats
- Ensure proper headers are sent
- Check server upload directory permissions

### Handle Upload Errors & Solutions

**"Invalid upload" Error from handle_upload():**
- **Cause:** Missing required POST parameters `type` and `object_type`
- **Solution:** Ensure these parameters are included in upload requests:
  - `type`: Must be `audio`, `image`, or `video`
  - `object_type`: Must be valid BOF object type (e.g., `m_track_source`, `m_album_c`, `m_track_c`)

**Example Fix for App Upload:**
```bash
curl -X POST https://music.hitune.in/api/app/client/upload \
  -H "X-Becli-Version: 1.0.0" \
  -H "X-Bof-App-Package: com.hitune.app" \
  -F "type=audio" \
  -F "object_type=m_track_source" \
  -F "file=@/path/to/your/file.mp3"
```

**Required POST Parameters for handle_upload:**
- `type`: File type (`audio`, `image`, `video`)
- `object_type`: BOF object identifier (`m_track_source`, `m_album_c`, `m_track_c`)

### Database Configuration Required:
1. **User Upload Permissions:** Enable `user_upload_music` and `user_upload_music_types` in user roles
2. **YouTube Settings:** Configure `youtube_piped` and `youtube_automation` settings
3. **File Settings:** Set appropriate upload directory and max file size limits
4. **Storage Settings:** Configure `fs_chunk` and `fs_chunk_size` for large file uploads

## Complete Upload/Import Configuration Checklist

### 1. BOF Settings Configuration
```sql
-- Enable YouTube import settings
INSERT INTO `settings` (`name`, `value`) VALUES ('youtube_piped', '1');
INSERT INTO `settings` (`name`, `value`) VALUES ('youtube_automation', '1');
INSERT INTO `settings` (`name`, `value`) VALUES ('ut', '1');

-- File upload settings
INSERT INTO `settings` (`name`, `value`) VALUES ('fs_chunk', '1');
INSERT INTO `settings` (`name`, `value`) VALUES ('fs_chunk_size', '1048576'); -- 1MB chunks
```

### 2. User Role Configuration
Navigate to Admin Panel → Users → Roles and enable:
- **user_upload_music**: Allow users to upload music
- **user_upload_music_types**: Select allowed sources (audio, video, youtube, soundcloud)

### 3. File Permissions
```bash
# Set proper permissions for upload directories
chmod 755 /www/wwwroot/music/upload/
chmod 755 /www/wwwroot/music/files/
chown -R www-data:www-data /www/wwwroot/music/upload/
chown -R www-data:www-data /www/wwwroot/music/files/
```

### 4. Nginx/Apache Configuration
Ensure upload limits are sufficient:
```nginx
client_max_body_size 100M;
```

### 5. Database Table Structure
Ensure `_u_playlists` table exists for user playlist functionality:
```sql
CREATE TABLE IF NOT EXISTS `_u_playlists` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `status` varchar(50) DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Testing Upload/Import Functionality

### Test Direct Upload
```bash
# Test audio upload
curl -X POST https://music.hitune.in/api/app/client/upload \
  -H "X-Becli-Version: 1.0.0" \
  -H "X-Bof-App-Package: com.hitune.app" \
  -F "type=audio" \
  -F "object_type=m_track_source" \
  -F "file=@/path/to/test.mp3"

# Expected response: {"success":true,"messages":["ok"],"data":{"file_id":"123"}}
```

### Test YouTube Import
```bash
# Test YouTube import
curl -X POST https://music.hitune.in/api/app/client/muse/request_source \
  -H "X-Becli-Version: 1.0.0" \
  -H "Content-Type: application/json" \
  -d '{
    "object_type": "m_track_c",
    "object_hash": "youtube_video_id",
    "solve": "true"
  }'
```

### Test User Upload Submit
```bash
# Step 1: Verify source
curl -X POST https://music.hitune.in/api/app/client/user/upload_verify_sources \
  -H "X-Becli-Version: 1.0.0" \
  -H "Content-Type: application/json" \
  -d '{
    "content_type": "music",
    "source_type": "audio",
    "source_id": "69c30806c7fb3"
  }'

# Step 2: Submit upload (use response from verify_sources)
curl -X POST https://music.hitune.in/api/app/client/user/upload_submit \
  -H "X-Becli-Version: 1.0.0" \
  -H "Content-Type: application/json" \
  -d '{
    "content_data": "{\"ID\":\"music\"}",
    "source_data": "{\"ID\":\"audio\"}",
    "source_id": "69c30806c7fb3",
    "verified_source": "{\"data\":{\"type\":\"audio\",\"success\":true,\"file_id\":\"374\",\"file_pass\":\"51c589621c\"},\"tags\":{},\"ID\":\"69c30806c7fb3\"}",
    "69c30806c7fb3_title": "Test Track",
    "69c30806c7fb3_artist_name": "Test Artist",
    "69c30806c7fb3_release_date": "2026-03-25",
    "69c30806c7fb3_cover": "380",
    "69c30806c7fb3_description": "Test description"
  }'
```

## Common Issues & Solutions

### Issue: "Invalid app header - upload restricted"
**Solution:** Ensure proper headers are sent:
- `X-Becli-Version: 1.0.0` or higher
- `X-Bof-App-Package: com.hitune.app`
- `X-Bof-Version: 2074`

### Issue: "no_files_sent"
**Solution:** Ensure file is properly attached with correct field name (`file` or `$file`)

### Issue: "Invalid upload" from handle_upload
**Solution:** Add required POST parameters:
- `type`: audio, image, or video
- `object_type`: m_track_source, m_album_c, m_track_c

### Issue: "invalid_data" from user_upload_submit
**Solution:** Include all required fields:
- `content_data`: JSON with content type ID
- `source_data`: JSON with source type ID  
- `source_id`: Unique source identifier
- `verified_source`: JSON from verify_sources response
- Dynamic fields with proper prefix (e.g., `{source_id}_title`)

### Issue: YouTube import returns "failed_pending"
**Solution:** Enable YouTube settings and check youtube_piped configuration

### Issue: Large file uploads fail
**Solution:** Increase upload limits in PHP, Nginx/Apache, and BOF settings

## API Response Codes

- **200**: Success
- **400**: Bad Request (missing parameters, invalid data)
- **403**: Forbidden (invalid headers, insufficient permissions)
- **404**: Not Found (endpoint or resource not found)
- **500**: Internal Server Error (check logs)

## Debug Log Locations
- `/www/wwwroot/music/api/app/client/endpoints/upload_debug.log` - Direct upload issues
- `/www/wwwroot/music/api/app/client/endpoints/upload_verify_sources_debug.log` - Verify sources issues  
- `/www/wwwroot/music/api/app/client/endpoints/upload_submit_debug.log` - Submit upload issues