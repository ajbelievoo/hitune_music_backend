# Search API Documentation for Flutter App

## Endpoint: `endpoint_search.php`
The search endpoint allows searching for songs, albums, and artists.

### Request URL
`POST /api/endpoint_search.php`

### Request Body (POST parameters)
- `query` (string, required): The search term.
- `ot` (string, optional): Object type filter (e.g., `m_track`, `m_album`, `m_artist`).
- `page` (int, optional, min: 2): Page number for pagination.

### Response Structure
```json
{
  "success": true,
  "message": "ok",
  "data": {
    "widgets": [
      {
        "name": "tracks",
        "display": {
          "title": "Tracks"
        },
        "items": [
          {
            "hash": "md5_hash",
            "title": "Song Title",
            "sub_title": "Artist Name",
            "cover": "image_url",
            "buttons": {
              "play": {
                "object_type": "m_track",
                "object_hash": "md5_hash"
              }
            }
          }
        ]
      }
    ],
    "history": "search_history_hash"
  }
}
```

## Endpoint: `endpoint_muse_request_source.php`
Used to get the playable source for a song.

### Request URL
`POST /api/endpoint_muse_request_source.php`

### Request Body (POST parameters)
- `object_type` (string, required): Always `m_track` for songs.
- `object_hash` (string, required): The hash of the track to play.
- `solve` (string, optional): Set to `true` to force source resolution.

### Response Structure
```json
{
  "success": true,
  "message": "ok",
  "data": {
    "sources": [
      {
        "source": {
          "type": [
            "audio",
            {
              "address": "playable_stream_url",
              "mime": "audio/mpeg",
              "type": "stream"
            }
          ]
        },
        "data": {
          "ID": "track_hash",
          "cover": "album_cover_url"
        }
      }
    ]
  }
}
```

## Playback Tips for Flutter Team
1.  **Instant Play**: Use the `address` from the response directly for playback.
2.  **Fallback**: If `sources` is empty or returns `failed_pending`, wait a few seconds and retry once.
3.  **Album Covers**: The `cover` URL is provided in the `data` section of each source.
4.  **Search Suggestion**: For live search suggestions, use `endpoint_searchSuggs.php` with `query` parameter.
