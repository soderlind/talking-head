# Talking Head

AI-generated podcast-style audio from turn-based conversations in WordPress.

## Description

Talking Head lets you write multi-speaker conversations in the WordPress block editor, then generate podcast-quality audio using AI text-to-speech. Each speaker ("head") gets their own voice, and the plugin stitches the segments together into a single audio file with configurable silence gaps.

### Features

- **Episode editor** — Gutenberg blocks for writing turn-based conversations
- **Speaker profiles** — Custom post type for managing voices and personas
- **OpenAI TTS** — Generate speech using OpenAI's text-to-speech API (alloy, echo, fable, onyx, nova, shimmer)
- **Background processing** — Audio generation runs via Action Scheduler, with progress tracking
- **Audio stitching** — FFmpeg-based concatenation with silence gaps and loudness normalization
- **Player block** — Embed episode playback in any post or page, with optional transcript
- **Provider interface** — Extensible architecture for adding more TTS providers

## Requirements

- WordPress 6.8+
- PHP 8.3+
- [Action Scheduler](https://actionscheduler.org/) plugin
- FFmpeg installed on the server
- Node.js 18+ (for building blocks)

## Installation

1. Clone or download into `wp-content/plugins/talking-head/`
2. Install dependencies:

```bash
composer install
npm install
npm run build
```

3. Activate the plugin in WordPress admin
4. Install and activate the Action Scheduler plugin if not already present

## Configuration

Go to **Settings > Talking Head** and configure:

| Setting | Description |
|---------|-------------|
| OpenAI API Key | Your OpenAI API key for TTS |
| TTS Model | `tts-1` (standard) or `tts-1-hd` (high quality) |
| Default Voice | Default voice for new speaker profiles |
| FFmpeg Path | Absolute path to the FFmpeg binary |
| Output Format | MP3 or AAC |
| Output Bitrate | 128k / 192k / 256k / 320k |
| Silence Gap | Milliseconds of silence between turns |
| Max Segments | Maximum turns per episode |
| Max Characters | Maximum text length per turn |
| Rate Limit | API requests per minute |

Settings can also be set via constants in `wp-config.php` (highest priority) or environment variables:

```php
define( 'TALKING_HEAD_OPENAI_API_KEY', 'sk-...' );
define( 'TALKING_HEAD_FFMPEG_PATH', '/usr/bin/ffmpeg' );
```

## Usage

### 1. Create Speaker Profiles

Go to **Talking Head > Heads** and create speaker profiles. Each head has:
- A name
- A voice ID (e.g., `nova`, `onyx`)
- A provider (currently `openai`)
- Optional speaking style notes and avatar

### 2. Write an Episode

Go to **Talking Head > Add New Episode**. The editor loads with an Episode container block and one Turn block. For each turn:
- Select a speaker from the dropdown
- Write the dialogue text

Add more turns with the block appender.

### 3. Generate Audio

In the episode sidebar panel, click **Generate Audio**. The plugin:
1. Validates the manuscript (speakers assigned, text within limits)
2. Creates a background job via Action Scheduler
3. Generates TTS audio for each turn via OpenAI
4. Stitches segments with FFmpeg into a single MP3
5. Stores the result in `wp-content/uploads/talking-head/`

Progress is shown in the sidebar via polling.

### 4. Embed the Player

Use the **Talking Head Player** block in any post or page. Set the Episode ID and optionally enable transcript display. The block renders a native `<audio>` element with a download link.

## REST API

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/talking-head/v1/heads` | `edit_posts` | List speaker profiles |
| `GET` | `/talking-head/v1/heads/{id}` | `edit_posts` | Get speaker profile |
| `GET` | `/talking-head/v1/episodes/{id}/manuscript` | `edit_posts` | Get canonical manuscript JSON |
| `POST` | `/talking-head/v1/episodes/{id}/manuscript/validate` | `edit_posts` | Validate manuscript |
| `POST` | `/talking-head/v1/jobs` | `edit_posts` | Create generation job |
| `GET` | `/talking-head/v1/jobs/{id}` | `edit_posts` | Poll job status |
| `POST` | `/talking-head/v1/jobs/{id}/cancel` | `edit_posts` | Cancel active job |
| `POST` | `/talking-head/v1/jobs/{id}/retry` | `edit_posts` | Retry failed job |
| `GET` | `/talking-head/v1/episodes/{id}/player` | Public | Episode data for player |

## Development

```bash
# Start development build with watch
npm start

# Production build
npm run build

# Lint PHP
composer lint:php

# Lint JS
npm run lint:js
```

### Architecture

```
src/
  Plugin.php              — Bootstrapper
  Enum/                   — PHP 8.3 backed enums (JobStatus, EpisodeStatus, AudioFormat)
  CPT/                    — Custom post types (Episode, Head)
  Database/               — Schema + repositories for jobs and assets tables
  REST/                   — REST API controllers
  Blocks/                 — Server-side block registration
  Manuscript/             — Block parsing, validation, JSON schema
  Provider/               — TTS provider interface + OpenAI implementation
  Job/                    — Action Scheduler integration, job runner pipeline
  Audio/                  — FFmpeg stitching, normalization, encoding
  Storage/                — File storage interface + local implementation
  Admin/                  — Settings page
blocks/                   — Gutenberg block source (episode, turn, player)
templates/                — PHP templates (player)
```

## License

GPL-2.0-or-later
