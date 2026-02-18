# Talking Head 🗣️

AI-generated podcast-style audio from turn-based conversations in WordPress.


<p align="center">
  <video controls width="1280" src="https://github.com/user-attachments/assets/6827fa13-2061-4481-a87b-431331004617"></video>
</p>
<p align="center"><i>No point watching without audio</i></p>





## Description

Talking Head lets you write multi-speaker conversations in the WordPress block editor, then generate podcast-quality audio using AI text-to-speech. Each speaker ("head") gets their own voice, and the plugin stitches the segments together into a single audio file with configurable silence gaps — or serves segments individually using virtual stitching for faster publishing.

### Features

- **Episode editor** — Gutenberg blocks for writing turn-based conversations
- **Speaker profiles** — Custom post type for managing voices and personas
- **OpenAI TTS** — Generate speech using OpenAI's text-to-speech API (alloy, echo, fable, onyx, nova, shimmer)
- **Azure OpenAI TTS** — Alternative provider using Azure-hosted OpenAI deployments
- **Background processing** — Audio generation runs via Action Scheduler, with progress tracking
- **Audio stitching** — FFmpeg-based concatenation with silence gaps and loudness normalization, or pure PHP fallback
- **Virtual stitching** — Serve audio segments individually without server-side concatenation, with client-side sequential playback
- **Player block** — Embed episode playback in any post or page, with optional transcript
- **Provider interface** — Extensible architecture for adding more TTS providers

## Requirements

- WordPress 6.8+
- PHP 8.3+
- FFmpeg installed on the server (optional — PHP fallback available; **not needed for virtual stitching mode**)

## Installation

1. Download the latest [`talking-head.zip`](https://github.com/soderlind/talking-head/releases/latest/download/talking-head.zip).
2. In WordPress, go to **Plugins → Add New → Upload Plugin** and upload the zip.
3. Activate the plugin.

The plugin updates itself automatically via GitHub releases using [plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker).

## Configuration

Go to **Talking Head > Settings** and configure. The settings page has three tabs:

<table>
<thead>
<tr><th>Tab</th><th>Setting</th><th>Description</th></tr>
</thead>
<tbody>
<tr><td rowspan="8"><strong>Provider</strong></td><td>TTS Provider</td><td><code>OpenAI</code> or <code>Azure OpenAI</code></td></tr>
<tr><td>Default Voice</td><td>Default voice for new speaker profiles</td></tr>
<tr><td>OpenAI API Key</td><td>Your OpenAI API key for TTS</td></tr>
<tr><td>TTS Model</td><td><code>tts-1</code> (standard), <code>tts-1-hd</code> (high quality), or <code>gpt-4o-mini-tts</code> (supports instructions)</td></tr>
<tr><td>Azure OpenAI API Key</td><td>Your Azure OpenAI API key</td></tr>
<tr><td>Azure OpenAI Endpoint</td><td>Azure resource endpoint URL</td></tr>
<tr><td>Azure OpenAI Deployment ID</td><td>Name of your TTS deployment</td></tr>
<tr><td>Azure OpenAI API Version</td><td>API version string</td></tr>
<tr><td rowspan="5"><strong>Audio</strong></td><td>Stitching Mode</td><td>File (concatenate on server) or Virtual (serve segments individually)</td></tr>
<tr><td>FFmpeg Path</td><td>Absolute path to the FFmpeg binary (optional — PHP fallback if not found)</td></tr>
<tr><td>Output Format</td><td>MP3 or AAC</td></tr>
<tr><td>Output Bitrate</td><td>128k / 192k / 256k / 320k</td></tr>
<tr><td>Silence Gap</td><td>Milliseconds of silence between turns</td></tr>
<tr><td rowspan="3"><strong>Limits</strong></td><td>Max Segments</td><td>Maximum turns per episode (1–200)</td></tr>
<tr><td>Max Characters</td><td>Maximum text length per turn (100–4096)</td></tr>
<tr><td>Rate Limit</td><td>API requests per minute (1–60)</td></tr>
</tbody>
</table>

Settings can also be set via constants in `wp-config.php` (highest priority) or environment variables. See [CONFIG.md](CONFIG.md) for the full list of 16 constants.

## Usage

### 1. Create Speaker Profiles

Go to **Talking Head > Heads** and create speaker profiles. Each head has:
- A name
- A voice ID (e.g., `nova`, `onyx`)
- A provider (`openai` or `azure_openai`)
- Speed (0.25–4.0, default 1.0)
- Optional speaking style/instructions (used with `gpt-4o-mini-tts`)
- Optional avatar (featured image)

### 2. Write an Episode

Go to **Talking Head > Add New Episode**. The editor loads with an Episode container block and one Turn block. For each turn:
- Select a speaker from the dropdown
- Write the dialogue text

Add more turns with the block appender.

### 3. Generate Audio

Select the Episode block and click **Generate Audio** in the block toolbar. The plugin:
1. Validates the manuscript (speakers assigned, text within limits)
2. Creates a background job via Action Scheduler
3. Generates TTS audio for each turn via OpenAI
4. Stitches segments with FFmpeg into a single MP3 (file mode), or prepares segments for individual playback (virtual mode)
5. Stores the result in `wp-content/uploads/talking-head/`

Progress is shown in the sidebar via polling.

### 4. Embed the Player

Use the **Talking Head Player** block in any post or page. Select an episode from the searchable dropdown and optionally enable transcript display. The block renders a native `<audio>` element.

## Development

See [DEVELOPER.md](DEVELOPER.md) for build commands, REST API, architecture, and workflow details.

## License

GPL-2.0-or-later

## Changelog

See [CHANGELOG.md](CHANGELOG.md).
