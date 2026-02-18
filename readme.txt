=== Talking Head ===
Contributors: PerS
Tags: podcast, audio, tts, text-to-speech, ai
Requires at least: 6.8
Tested up to: 6.9
Requires PHP: 8.3
Stable tag: 1.2.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-generated podcast-style audio from turn-based conversations.

== Description ==

Talking Head lets you write multi-speaker conversations in the WordPress block editor, then generate podcast-quality audio using AI text-to-speech.

Each speaker ("head") gets their own voice profile, and the plugin stitches all the segments together into a single audio file with configurable silence gaps between turns — or serves segments individually using virtual stitching for faster publishing.

= Features =

* **Episode editor** — Gutenberg blocks for writing turn-based conversations with speaker selection
* **Speaker profiles** — Manage voices and personas as a custom post type
* **OpenAI TTS** — Generate speech using OpenAI's text-to-speech API with six voice options
* **Azure OpenAI TTS** — Alternative provider using Azure-hosted OpenAI deployments
* **Background processing** — Audio generation runs via Action Scheduler with real-time progress tracking
* **Audio stitching** — FFmpeg-based concatenation with silence gaps and loudness normalization, or pure PHP fallback
* **Virtual stitching** — Serve audio segments individually without server-side concatenation, with client-side sequential playback
* **Player block** — Embed episode playback in any post or page with optional transcript display
* **Provider selector** — Settings page dropdown to switch between providers; only relevant fields are shown

= How It Works =

1. Create speaker profiles with assigned voices
2. Write a conversation using turn-based blocks in the episode editor
3. Click "Generate Audio" to produce speech via OpenAI TTS
4. The plugin stitches segments into a single MP3 using FFmpeg — or use virtual stitching to serve segments individually
5. Embed the player block in any post or page

= Requirements =

* WordPress 6.8 or higher
* PHP 8.3 or higher
* FFmpeg installed on the server (optional — PHP fallback available)

== Installation ==

1. Download the latest [talking-head.zip](https://github.com/soderlind/talking-head/releases/latest/download/talking-head.zip)
2. In WordPress, go to Plugins → Add New → Upload Plugin and upload the zip
3. Activate the plugin
4. Go to Talking Head > Settings to configure your API key and preferences

The plugin updates itself automatically via GitHub releases.

== Frequently Asked Questions ==

= What TTS providers are supported? =

OpenAI TTS and Azure OpenAI TTS are supported, both with voices: Alloy, Echo, Fable, Onyx, Nova, and Shimmer. Choose your provider under Talking Head > Settings. Each head can also be assigned a specific provider for mixed-provider episodes.

= Do I need FFmpeg? =

No. FFmpeg is optional. Without it, the plugin uses a pure PHP fallback for stitching audio segments (binary MP3 concatenation with generated silence frames). FFmpeg provides better results — re-encoded output, loudness normalization, and format conversion — but is not required. You can also use virtual stitching mode, which skips server-side concatenation entirely and serves segments individually via the client-side player.

= How does background processing work? =

Audio generation runs via Action Scheduler (bundled with the plugin). When you click "Generate Audio", a job is queued and processed in the background. The editor sidebar polls for progress updates every 3 seconds.

= Can I set the API key without storing it in the database? =

Yes. Define `TALKING_HEAD_OPENAI_API_KEY` as a constant in `wp-config.php` or set the `TALKING_HEAD_OPENAI_API_KEY` environment variable. Constants take priority over environment variables, which take priority over the database setting.

= What audio formats are supported? =

MP3 and AAC output formats are supported, with configurable bitrate (128k to 320k).

== Screenshots ==

1. Episode editor with turn-based conversation blocks
2. Speaker profile management
3. Settings page with provider configuration
4. Player block on the front end

== Changelog ==

= 1.2.1 =
* Voice sample preview — play/stop button next to voice selector in the Head editor (block editor and classic)
* Bundled MP3 voice samples for all six voices (alloy, echo, fable, onyx, nova, shimmer)
* Voice sample generation script supporting both OpenAI and Azure OpenAI
* Norwegian translations for virtual stitching and voice preview strings

= 1.2.0 =
* Virtual stitching mode — serve audio segments individually instead of concatenating into a single file
* Stitching mode setting in Audio tab (File or Virtual) with per-episode override
* Client-side sequential segment playback in the player block with configurable silence gaps
* Transcript turn highlighting during virtual playback
* Download disabled in player controls for virtual stitching (no single file to download)
* FFmpeg Path field hidden when virtual stitching is selected

= 1.1.1 =
* Fixed REST API URL detection in player for WordPress subdirectory installations
* Moved inline settings page script to external file
* Removed redundant download link from player (browser controls have download action)

= 1.1.0 =
* Settings page moved under Admin → Talking Head → Settings submenu
* 3-tab settings layout (Provider, Audio, Limits) with value preservation across tabs
* Character counter on turn blocks (red when over limit)
* Segment counter in episode block inspector panel
* Segments and Words columns on the Episodes admin list table
* Settings values injected into block editor for client-side validation

= 1.0.2 =
* i18n infrastructure with `wp i18n` toolchain (`npm run i18n`)
* Norwegian Bokmål (nb_NO) translation
* Script translations for head-sidebar editor script

= 1.0.1 =
* Exclude dev files (bin/, blocks/ source, webpack.config.js) from release zip
* Remove non-functional negation patterns from zip exclusions

= 1.0.0 =
* Opt into new wp.components styles (`__nextHasNoMarginBottom`, `__next40pxDefaultSize`) to silence deprecation warnings
* Upgraded @wordpress/scripts 31.0.0 → 31.4.0
* Housekeeping

= 0.3.3 =
* Episode picker in Player block — searchable dropdown replaces manual Episode ID input
* Vitest JavaScript test suite with WordPress module mocks (6 suites, 22 tests)
* Removed unused @vitejs/plugin-react devDependency

= 0.3.2 =
* Voice Settings meta box on Head edit screen (voice, provider, speed, speaking style)
* Per-head speed and speaking style/instructions wired through the full TTS pipeline
* gpt-4o-mini-tts model option (supports OpenAI instructions parameter)
* Default voice fallback reads from global settings instead of hardcoded alloy
* Removed redundant Custom Fields panel from Head CPT

= 0.3.1 =
* GitHub plugin updater for automatic updates via GitHub releases
* GitHub Actions release workflows (on-release and manual build)
* Pest PHP unit test suite with Brain Monkey for WordPress function mocking (45 tests)

= 0.3.0 =
* Status and Audio columns on the Episodes admin list table
* Episode block header bar with microphone icon for easy selection
* Generate Audio button moved to block toolbar
* Direct turn insertion — clicking [+] inserts a turn without block picker
* Conditional appender — [+] hidden when episode deselected
* Speaker label pill on turn block with click-to-edit toggle
* Episode block migrated to useInnerBlocksProps
* Duplicate sidebar speaker panel removed from turn block

= 0.2.2 =
* Fix: Action Scheduler job processing in multisite (blog context switching)
* Fix: Generate Audio button shows progress throughout the entire job lifecycle

= 0.2.1 =
* Fix: Provider fallback uses global setting instead of hardcoded `openai`
* Fix: Speaker names no longer prepended to dialogue text in manuscript
* Fix: Episode editor allows adding and removing turns freely
* Fix: Seed script respects global TTS provider setting

= 0.2.0 =
* Azure OpenAI TTS provider
* Provider selector on settings page with conditional field visibility
* PHP fallback for audio stitching when FFmpeg is not available
* FFmpeg is now optional
* Example data seeding script (bin/seed-example-data.sh)

= 0.1.0 =
* Initial release
* Episode and Head custom post types
* Episode container and Turn Gutenberg blocks
* Player block with server-side rendering and transcript support
* OpenAI TTS provider integration
* Action Scheduler-based background job processing
* FFmpeg audio stitching with configurable silence gaps
* Settings page with config priority (constant > env > database > default)
* REST API with 9 endpoints for heads, manuscripts, jobs, and player data
* Manuscript builder with block parsing and validation
* Local file storage with adapter interface

== Upgrade Notice ==

= 1.2.1 =
Adds voice sample preview in the Head editor so you can hear each voice before selecting it.

= 1.2.0 =
Adds virtual stitching mode for serving audio segments individually without server-side concatenation. Configurable per-episode or globally.

= 1.1.1 =
Fixes player transcript loading on subdirectory WordPress installations and removes redundant download link.

= 1.1.0 =
Settings moved under Talking Head menu, tabbed settings page, character and segment counters in editor, and Words/Segments columns on episode list.

= 1.0.2 =
Adds i18n support and Norwegian Bokmål translation.

= 1.0.1 =
Removes unnecessary dev files from the release zip.

= 1.0.0 =
Fixes deprecated wp.components warnings, upgrades @wordpress/scripts to 31.4.0, and general housekeeping.

= 0.3.3 =
Player block now uses a searchable episode dropdown instead of manual ID entry. Adds Vitest JS test suite.

= 0.3.2 =
Adds Voice Settings meta box for Head editing, per-head speed and speaking style support, and gpt-4o-mini-tts model option.

= 0.3.1 =
Adds GitHub updater, release workflows, and Pest unit test suite.

= 0.3.0 =
Improved editor UX: episode header bar, toolbar Generate button, speaker label pills, and admin list columns.

= 0.2.2 =
Fixes Action Scheduler multisite support and generation progress visibility.

= 0.2.1 =
Fixes provider fallback, manuscript text extraction, and episode template lock.

= 0.2.0 =
Adds Azure OpenAI TTS and makes FFmpeg optional with a PHP fallback.

= 0.1.0 =
Initial release.
