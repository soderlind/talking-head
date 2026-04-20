# Configuration Constants

All settings can be configured via the **Talking Head > Settings** admin page. You can also override any setting using PHP constants in `wp-config.php` or environment variables of the same name.

**Priority:** constant > environment variable > database (admin UI) > default

When a setting is locked by a constant or environment variable, the admin field is disabled and shows "Locked by constant or environment variable."

> **Note:** As of v1.5.0, this plugin requires WordPress 7.0+ and uses the built-in WordPress AI Client for text-to-speech. Configure AI connectors at **Settings → Connectors** — no API keys needed in plugin settings.

## Voice

```php
// Default voice for new speaker profiles: 'alloy', 'echo', 'fable', 'onyx', 'nova', or 'shimmer'
define( 'TALKING_HEAD_DEFAULT_VOICE', 'alloy' );
```

## Audio Processing

```php
// Stitching mode: 'file' (concatenate on server) or 'virtual' (serve segments individually)
define( 'TALKING_HEAD_STITCHING_MODE', 'file' );

// Absolute path to the FFmpeg binary (optional — PHP fallback if not found)
define( 'TALKING_HEAD_FFMPEG_PATH', '/usr/bin/ffmpeg' );

// Output format: 'mp3' or 'aac'
define( 'TALKING_HEAD_OUTPUT_FORMAT', 'mp3' );

// Output bitrate: '128k', '192k', '256k', or '320k'
define( 'TALKING_HEAD_OUTPUT_BITRATE', '192k' );

// Milliseconds of silence between turns (0–5000)
define( 'TALKING_HEAD_SILENCE_GAP_MS', '500' );
```

## Limits

```php
// Maximum turns per episode (1–200)
define( 'TALKING_HEAD_MAX_SEGMENTS', '50' );

// Maximum characters per turn (100–4096)
define( 'TALKING_HEAD_MAX_SEGMENT_CHARS', '4096' );

// API requests per minute (1–60)
define( 'TALKING_HEAD_RATE_LIMIT', '10' );
```

## Reference Table

| Constant | Default | Allowed Values |
|----------|---------|----------------|
| `TALKING_HEAD_DEFAULT_VOICE` | `alloy` | `alloy`, `echo`, `fable`, `onyx`, `nova`, `shimmer` |
| `TALKING_HEAD_STITCHING_MODE` | `file` | `file`, `virtual` |
| `TALKING_HEAD_FFMPEG_PATH` | `/opt/homebrew/bin/ffmpeg` | Any executable path |
| `TALKING_HEAD_OUTPUT_FORMAT` | `mp3` | `mp3`, `aac` |
| `TALKING_HEAD_OUTPUT_BITRATE` | `192k` | `128k`, `192k`, `256k`, `320k` |
| `TALKING_HEAD_SILENCE_GAP_MS` | `500` | `0`–`5000` |
| `TALKING_HEAD_MAX_SEGMENTS` | `50` | `1`–`200` |
| `TALKING_HEAD_MAX_SEGMENT_CHARS` | `4096` | `100`–`4096` |
| `TALKING_HEAD_RATE_LIMIT` | `10` | `1`–`60` |
