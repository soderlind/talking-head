# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

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
