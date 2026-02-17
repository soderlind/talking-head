# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [1.0.1] - 2026-02-18

### Fixed

- Exclude dev files (`bin/`, `blocks/` source, `webpack.config.js`) from release zip
- Remove non-functional `!` negation patterns from zip exclusions (`zip -x` does not support negation)

## [1.0.0] - 2026-02-18

### Fixed

- Opt into new `wp.components` styles (`__nextHasNoMarginBottom`, `__next40pxDefaultSize`) to silence deprecation warnings in WP 6.7+

### Changed

- Upgraded `@wordpress/scripts` from 31.0.0 to 31.4.0

## [0.3.3] - 2026-02-17

### Added

- Episode picker in Player block — searchable dropdown replaces manual Episode ID input
- Vitest JavaScript test suite with `@wordpress/*` module mocks (6 suites, 22 tests)

### Removed

- Unused `@vitejs/plugin-react` devDependency (replaced by custom esbuild JSX plugin)

## [0.3.2] - 2026-02-17

### Added

- Voice Settings meta box on Head edit screen — voice, provider, speed, and speaking style controls
- Per-head speed (0.25–4.0) and speaking style/instructions wired through the full TTS pipeline
- `gpt-4o-mini-tts` model option in settings (supports OpenAI `instructions` parameter)
- Head sidebar plugin for the block editor (loaded when block editor is active)

### Changed

- Default voice fallback reads from global settings instead of hardcoded `alloy`
- Speaking style sanitizer changed to `sanitize_textarea_field` to preserve newlines
- Removed redundant Custom Fields panel from Head CPT

### Fixed

- Speed and speaking style were stored as meta but never passed to the TTS provider

## [0.3.1] - 2026-02-17

### Added

- GitHub plugin updater for automatic updates via GitHub releases
- GitHub Actions release workflows (on-release and manual build)
- Pest PHP unit test suite with Brain Monkey for WordPress function mocking (45 tests)

## [0.3.0] - 2026-02-17

### Added

- Status and Audio columns on the Episodes admin list table — color-coded badge and inline `<audio>` player
- Episode block header bar with microphone icon, label, and status badge for easy selection
- "Generate Audio" toolbar button (moved from sidebar to block toolbar)
- `directInsert` on episode block — clicking [+] inserts a turn directly without block picker
- Conditional appender — [+] button only visible when episode is selected or empty
- Speaker label pill on turn block — click to toggle back to dropdown for editing
- Selection highlight styles for both episode and turn blocks

### Changed

- Episode block migrated from `<InnerBlocks>` component to `useInnerBlocksProps` hook
- Turn block sidebar speaker panel removed (inline control is the single source)

## [0.2.2] - 2026-02-17

### Fixed

- Action Scheduler job processing in multisite — passes `blog_id` and calls `switch_to_blog()` so the runner operates on the correct site's tables
- Generate Audio button now stays disabled with spinner and shows "Queued" / "Generating" status notices throughout the entire job lifecycle instead of resetting immediately

## [0.2.1] - 2026-02-17

### Fixed

- Provider fallback uses global `tts_provider` setting instead of hardcoded `openai` in ManuscriptBuilder and HeadController
- ManuscriptBuilder text extraction targets `.th-turn__text` element only, preventing speaker names from being prepended to dialogue text
- Episode template lock changed from `insert` to `false`, allowing turns to be added and removed freely in the editor
- Seed script reads global `tts_provider` setting instead of hardcoding `openai` for all heads

## [0.2.0] - 2026-02-17

### Added

- Azure OpenAI TTS provider (`src/Provider/AzureOpenAI/AzureOpenAIProvider.php`)
- Provider selector dropdown on settings page — only the selected provider's fields are shown
- PHP fallback for audio stitching when FFmpeg is not available (binary MP3 concatenation with
  generated MPEG1 Layer 3 silence frames)
- Example data seeding script (`bin/seed-example-data.sh`) for creating heads and an episode via WP-CLI

### Changed

- FFmpeg is now optional — Stitcher, Normalizer, and Encoder gracefully degrade without it
- Settings page restructured into separate OpenAI and Azure OpenAI sections
- JobRunner resolves provider per-segment from head meta, enabling mixed-provider episodes
- ManuscriptSchema provider enum expanded to include `azure_openai`
- Audio Processing section description updated to note FFmpeg is optional

## [0.1.0] - 2026-02-17

### Added

- Episode custom post type with Gutenberg block template
- Head (speaker profile) custom post type with voice and provider meta
- Episode container block with `InnerBlocks` limited to turn blocks
- Turn block with speaker selector and rich text dialogue input
- Player block with server-side rendering, audio playback, and optional transcript
- OpenAI TTS provider integration (voices: alloy, echo, fable, onyx, nova, shimmer)
- Action Scheduler-based background job processing with progress tracking
- Job state machine: queued, running, succeeded, failed, canceled
- Idempotent job creation via manuscript content hashing (SHA-256)
- FFmpeg audio stitching with configurable silence gaps between turns
- Loudness normalization via FFmpeg `loudnorm` filter
- Audio format encoding (MP3, AAC, WAV)
- Local file storage with adapter interface for future external storage
- Settings page with config priority: constant > environment variable > database > default
- Manuscript builder that parses block content into canonical JSON
- Manuscript validator with configurable limits (segments, characters)
- REST API with 9 endpoints for heads, manuscripts, jobs, and player data
- Custom database tables for jobs (`wp_th_jobs`) and assets (`wp_th_assets`)
- Uninstall handler that cleans up tables, posts, options, and uploaded files
