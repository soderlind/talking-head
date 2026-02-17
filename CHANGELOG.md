# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

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
