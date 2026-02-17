#!/usr/bin/env bash
#
# Seed example data for the Talking Head plugin.
#
# Creates 3 speaker profiles (heads) and 1 episode with a 3-turn conversation.
# Useful for development, demos, and onboarding new contributors.
#
# Usage:
#   ./bin/seed-example-data.sh [--url=<site-url>]
#
# Options:
#   --url   WordPress site URL passed to WP-CLI (required for multisite).
#           Example: --url=http://plugins.local/subsite20
#
# Prerequisites:
#   - WP-CLI installed and available in PATH
#   - Talking Head plugin activated on the target site
#
# Examples:
#   ./bin/seed-example-data.sh
#   ./bin/seed-example-data.sh --url=http://plugins.local/subsite20

set -euo pipefail

URL_FLAG="${1:-}"

# Read the global TTS provider from plugin settings (defaults to 'openai').
PROVIDER=$(wp eval 'echo TalkingHead\Admin\SettingsPage::get("tts_provider");' ${URL_FLAG} 2>/dev/null)
PROVIDER="${PROVIDER:-openai}"

# --- Heads (speaker profiles) ------------------------------------------------

echo "Creating heads (provider: ${PROVIDER})..."

ALICE_ID=$(wp post create \
  --post_type=talking_head_head \
  --post_title='Alice' \
  --post_status=publish \
  ${URL_FLAG} \
  --porcelain)

wp post meta update "$ALICE_ID" _th_voice_id nova ${URL_FLAG}
wp post meta update "$ALICE_ID" _th_provider "$PROVIDER" ${URL_FLAG}

BOB_ID=$(wp post create \
  --post_type=talking_head_head \
  --post_title='Bob' \
  --post_status=publish \
  ${URL_FLAG} \
  --porcelain)

wp post meta update "$BOB_ID" _th_voice_id onyx ${URL_FLAG}
wp post meta update "$BOB_ID" _th_provider "$PROVIDER" ${URL_FLAG}

CHARLIE_ID=$(wp post create \
  --post_type=talking_head_head \
  --post_title='Charlie' \
  --post_status=publish \
  ${URL_FLAG} \
  --porcelain)

wp post meta update "$CHARLIE_ID" _th_voice_id echo ${URL_FLAG}
wp post meta update "$CHARLIE_ID" _th_provider "$PROVIDER" ${URL_FLAG}

echo "  Alice   (ID: ${ALICE_ID}) — voice: nova, provider: ${PROVIDER}"
echo "  Bob     (ID: ${BOB_ID}) — voice: onyx, provider: ${PROVIDER}"
echo "  Charlie (ID: ${CHARLIE_ID}) — voice: echo, provider: ${PROVIDER}"

# --- Episode ------------------------------------------------------------------

echo "Creating episode..."

CONTENT=$(cat <<BLOCKS
<!-- wp:talking-head/episode -->
<div class="wp-block-talking-head-episode th-episode"><!-- wp:talking-head/turn {"headId":${ALICE_ID},"headName":"Alice"} -->
<div class="wp-block-talking-head-turn th-turn"><div class="th-turn__header"><span class="th-turn__speaker">Alice</span></div><div class="th-turn__text">Welcome to the show! Today we are going to discuss the future of AI assistants and how they are changing the way we work and communicate. I have two great guests with me.</div></div>
<!-- /wp:talking-head/turn -->

<!-- wp:talking-head/turn {"headId":${BOB_ID},"headName":"Bob"} -->
<div class="wp-block-talking-head-turn th-turn"><div class="th-turn__header"><span class="th-turn__speaker">Bob</span></div><div class="th-turn__text">Thanks for having me, Alice. I think AI assistants are at an inflection point. We have moved beyond simple chatbots into genuine productivity tools that understand context and can take meaningful actions on our behalf.</div></div>
<!-- /wp:talking-head/turn -->

<!-- wp:talking-head/turn {"headId":${CHARLIE_ID},"headName":"Charlie"} -->
<div class="wp-block-talking-head-turn th-turn"><div class="th-turn__header"><span class="th-turn__speaker">Charlie</span></div><div class="th-turn__text">I agree with Bob, but I also think we need to be thoughtful about the boundaries. The most effective assistants will be the ones that know when to act autonomously and when to ask for human input. That balance is the real challenge.</div></div>
<!-- /wp:talking-head/turn --></div>
<!-- /wp:talking-head/episode -->
BLOCKS
)

EPISODE_ID=$(wp post create \
  --post_type=talking_head_episode \
  --post_title='The Future of AI Assistants' \
  --post_status=publish \
  --post_content="$CONTENT" \
  ${URL_FLAG} \
  --porcelain)

echo "  Episode (ID: ${EPISODE_ID}) — 3 turns (Alice, Bob, Charlie)"

echo ""
echo "Done. Example data created successfully."
