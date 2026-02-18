#!/usr/bin/env bash
# Generate static voice sample MP3 files using OpenAI or Azure OpenAI TTS API.
# Run once, then commit the resulting files in assets/voice-samples/.
#
# Usage (OpenAI):
#   OPENAI_API_KEY=sk-... bash bin/generate-voice-samples.sh
#
# Usage (Azure OpenAI):
#   AZURE_OPENAI_API_KEY=... \
#   AZURE_OPENAI_ENDPOINT=https://....openai.azure.com/ \
#   AZURE_OPENAI_DEPLOYMENT_NAME=tts-hd \
#   AZURE_OPENAI_API_VERSION=2025-01-01-preview \
#   bash bin/generate-voice-samples.sh

set -euo pipefail

OUTDIR="$(cd "$(dirname "$0")/.." && pwd)/assets/voice-samples"
mkdir -p "$OUTDIR"

VOICES=("alloy" "echo" "fable" "onyx" "nova" "shimmer")
TEXT="Hi there! I'm one of the available text-to-speech voices. Here's what I sound like when reading your podcast content aloud."

# Determine provider.
if [ -n "${AZURE_OPENAI_API_KEY:-}" ]; then
	ENDPOINT="${AZURE_OPENAI_ENDPOINT:?Set AZURE_OPENAI_ENDPOINT}"
	DEPLOYMENT="${AZURE_OPENAI_DEPLOYMENT_NAME:?Set AZURE_OPENAI_DEPLOYMENT_NAME}"
	API_VERSION="${AZURE_OPENAI_API_VERSION:-2025-01-01-preview}"
	# Strip trailing slash from endpoint.
	ENDPOINT="${ENDPOINT%/}"
	URL="${ENDPOINT}/openai/deployments/${DEPLOYMENT}/audio/speech?api-version=${API_VERSION}"
	AUTH_HEADER="api-key: ${AZURE_OPENAI_API_KEY}"
	echo "Using Azure OpenAI (${ENDPOINT})"
elif [ -n "${OPENAI_API_KEY:-}" ]; then
	URL="https://api.openai.com/v1/audio/speech"
	AUTH_HEADER="Authorization: Bearer ${OPENAI_API_KEY}"
	echo "Using OpenAI"
else
	echo "Error: Set OPENAI_API_KEY or AZURE_OPENAI_API_KEY." >&2
	exit 1
fi

for voice in "${VOICES[@]}"; do
	echo "Generating ${voice}.mp3 ..."
	curl -s "$URL" \
		-H "$AUTH_HEADER" \
		-H "Content-Type: application/json" \
		-d "{\"model\":\"tts-1\",\"input\":\"${TEXT}\",\"voice\":\"${voice}\",\"response_format\":\"mp3\"}" \
		-o "${OUTDIR}/${voice}.mp3"
done

echo "Done. Voice samples saved to ${OUTDIR}/"
