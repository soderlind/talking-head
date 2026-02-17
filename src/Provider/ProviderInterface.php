<?php

declare(strict_types=1);

namespace TalkingHead\Provider;

defined( 'ABSPATH' ) || exit;

interface ProviderInterface {

	/**
	 * Synthesize text to audio.
	 *
	 * @param string $text    Text content to convert to speech.
	 * @param string $voiceId Provider-specific voice identifier.
	 * @param array  $options Optional settings (speed, format, etc.).
	 * @return AudioChunk Generated audio data.
	 */
	public function synthesize( string $text, string $voiceId, array $options = [] ): AudioChunk;

	/**
	 * Describe the capabilities of this provider.
	 */
	public function capabilities(): ProviderCapabilities;

	/**
	 * List available voices.
	 *
	 * @return array<array{id: string, name: string, gender: string}>
	 */
	public function voices(): array;

	/**
	 * Unique slug for this provider.
	 */
	public function slug(): string;

	/**
	 * Human-readable display name.
	 */
	public function name(): string;
}
