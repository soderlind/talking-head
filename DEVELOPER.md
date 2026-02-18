# Developer Guide

## Development

```bash
composer install
npm install

npm start          # Development build with watch
npm run build      # Production build
npm run test:js    # Run JS tests (Vitest)
npm run i18n       # Extract strings and compile translations

composer test      # Run tests (Pest)
composer lint:php  # Lint PHP (PHPCS)
npm run lint:js    # Lint JS
```

## GitHub Actions

Two workflows ship with the plugin:

- **On Release, Build release zip** — runs automatically when a release is published.
- **Manually Build release zip** — trigger manually with a tag to create and upload `talking-head.zip` to a release.

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

## Architecture

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
