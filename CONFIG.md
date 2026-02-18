# Configuration Constants

All settings can be configured via the **Talking Head > Settings** admin page. You can also override any setting using PHP constants in `wp-config.php` or environment variables of the same name.

**Priority:** constant > environment variable > database (admin UI) > default

When a setting is locked by a constant or environment variable, the admin field is disabled and shows "Locked by constant or environment variable."

## Provider

```php
// Which TTS provider to use: 'openai' or 'azure_openai'
define( 'TALKING_HEAD_TTS_PROVIDER', 'openai' );

// OpenAI API key
define( 'TALKING_HEAD_OPENAI_API_KEY', 'sk-...' );

// TTS model: 'tts-1', 'tts-1-hd', or 'gpt-4o-mini-tts'
define( 'TALKING_HEAD_OPENAI_TTS_MODEL', 'tts-1' );

// Default voice for new speaker profiles: 'alloy', 'echo', 'fable', 'onyx', 'nova', or 'shimmer'
define( 'TALKING_HEAD_DEFAULT_VOICE', 'alloy' );
```

## Azure OpenAI

```php
// Azure OpenAI API key
define( 'TALKING_HEAD_AZURE_OPENAI_API_KEY', '...' );

// Azure OpenAI endpoint URL
define( 'TALKING_HEAD_AZURE_OPENAI_ENDPOINT', 'https://my-resource.openai.azure.com/' );

// Azure OpenAI deployment ID (name of your TTS deployment)
define( 'TALKING_HEAD_AZURE_OPENAI_DEPLOYMENT_ID', 'tts-hd' );

// Azure OpenAI API version
define( 'TALKING_HEAD_AZURE_OPENAI_API_VERSION', '2024-05-01-preview' );
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
| `TALKING_HEAD_TTS_PROVIDER` | `openai` | `openai`, `azure_openai` |
| `TALKING_HEAD_OPENAI_API_KEY` | *(empty)* | Any string |
| `TALKING_HEAD_OPENAI_TTS_MODEL` | `tts-1` | `tts-1`, `tts-1-hd`, `gpt-4o-mini-tts` |
| `TALKING_HEAD_DEFAULT_VOICE` | `alloy` | `alloy`, `echo`, `fable`, `onyx`, `nova`, `shimmer` |
| `TALKING_HEAD_AZURE_OPENAI_API_KEY` | *(empty)* | Any string |
| `TALKING_HEAD_AZURE_OPENAI_ENDPOINT` | *(empty)* | Valid URL |
| `TALKING_HEAD_AZURE_OPENAI_DEPLOYMENT_ID` | *(empty)* | Any string |
| `TALKING_HEAD_AZURE_OPENAI_API_VERSION` | `2024-05-01-preview` | Any string |
| `TALKING_HEAD_STITCHING_MODE` | `file` | `file`, `virtual` |
| `TALKING_HEAD_FFMPEG_PATH` | `/opt/homebrew/bin/ffmpeg` | Any executable path |
| `TALKING_HEAD_OUTPUT_FORMAT` | `mp3` | `mp3`, `aac` |
| `TALKING_HEAD_OUTPUT_BITRATE` | `192k` | `128k`, `192k`, `256k`, `320k` |
| `TALKING_HEAD_SILENCE_GAP_MS` | `500` | `0`–`5000` |
| `TALKING_HEAD_MAX_SEGMENTS` | `50` | `1`–`200` |
| `TALKING_HEAD_MAX_SEGMENT_CHARS` | `4096` | `100`–`4096` |
| `TALKING_HEAD_RATE_LIMIT` | `10` | `1`–`60` |
