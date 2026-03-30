<?php

declare(strict_types=1);

namespace TalkingHead\Provider\WordPress;

use TalkingHead\Provider\AudioChunk;
use TalkingHead\Provider\ProviderCapabilities;
use TalkingHead\Provider\ProviderInterface;

defined( 'ABSPATH' ) || exit;

/**
 * WordPress Core AI Client TTS provider (WP 7.0+).
 *
 * Uses the built-in wp_ai_client_prompt() fluent API for text-to-speech.
 * Connector configuration is delegated to Settings → Connectors.
 */
final class WordPressAIProvider implements ProviderInterface {

	/**
	 * URL to the WP Core AI Connectors settings page.
	 */
	public const CONNECTORS_URL = 'options-general.php?page=connectors-wp-admin';

	/**
	 * Check if the WordPress AI Client API is available (WP 7.0+).
	 */
	public static function is_available(): bool {
		return function_exists( 'wp_ai_client_prompt' );
	}

	/**
	 * Check if at least one AI connector supports text-to-speech.
	 */
	public static function has_tts_connectors(): bool {
		if ( ! self::is_available() ) {
			return false;
		}

		try {
			return wp_ai_client_prompt( 'test' )->is_supported_for_text_to_speech_conversion();
		} catch ( \Exception $e ) {
			return false;
		}
	}

	public function synthesize( string $text, string $voiceId, array $options = [] ): AudioChunk {
		if ( ! self::is_available() ) {
			throw new \RuntimeException(
				'WordPress AI Client is not available. Requires WordPress 7.0 or later.'
			);
		}

		if ( ! self::has_tts_connectors() ) {
			throw new \RuntimeException(
				sprintf(
					'No AI connector with TTS support configured. Go to %s to add one.',
					admin_url( self::CONNECTORS_URL )
				)
			);
		}

		$builder = wp_ai_client_prompt( $text )
			->as_output_speech_voice( $voiceId );

		$result = $builder->convert_text_to_speech();

		if ( is_wp_error( $result ) ) {
			throw new \RuntimeException(
				'WordPress AI TTS error: ' . $result->get_error_message()
			);
		}

		// $result is a WordPress\AiClient\Files\DTO\File instance.
		$base64_data = $result->getBase64Data();
		if ( empty( $base64_data ) ) {
			throw new \RuntimeException(
				'WordPress AI TTS returned no audio data.'
			);
		}

		$audio_data = base64_decode( $base64_data, true );
		if ( $audio_data === false ) {
			throw new \RuntimeException(
				'WordPress AI TTS returned invalid base64 audio data.'
			);
		}

		$mime_type = $result->getMimeType();
		$format    = $this->mime_to_format( $mime_type );
		$size      = strlen( $audio_data );

		// Rough duration estimate for MP3 at ~192kbps (1 second ≈ 24 000 bytes).
		$estimated_duration_ms = (int) ( ( $size / 24000 ) * 1000 );

		return new AudioChunk(
			data: $audio_data,
			format: $format,
			durationMs: $estimated_duration_ms,
			sizeBytes: $size,
			voiceId: $voiceId,
			segmentIndex: (int) ( $options['segmentIndex'] ?? 0 ),
		);
	}

	public function capabilities(): ProviderCapabilities {
		return new ProviderCapabilities(
			maxCharsPerRequest: 4096,
			supportedFormats: [ 'mp3', 'opus', 'aac', 'flac' ],
			supportsSSML: false,
			supportsSpeakingStyle: false,
		);
	}

	public function voices(): array {
		return [
			[ 'id' => 'alloy', 'name' => 'Alloy', 'gender' => 'neutral' ],
			[ 'id' => 'echo', 'name' => 'Echo', 'gender' => 'male' ],
			[ 'id' => 'fable', 'name' => 'Fable', 'gender' => 'neutral' ],
			[ 'id' => 'onyx', 'name' => 'Onyx', 'gender' => 'male' ],
			[ 'id' => 'nova', 'name' => 'Nova', 'gender' => 'female' ],
			[ 'id' => 'shimmer', 'name' => 'Shimmer', 'gender' => 'female' ],
		];
	}

	public function slug(): string {
		return 'wordpress';
	}

	public function name(): string {
		return 'WordPress AI (Core)';
	}

	/**
	 * Map a MIME type to a short format string.
	 */
	private function mime_to_format( string $mime_type ): string {
		return match ( $mime_type ) {
			'audio/mpeg', 'audio/mp3' => 'mp3',
			'audio/ogg', 'audio/opus' => 'opus',
			'audio/aac'               => 'aac',
			'audio/flac'              => 'flac',
			'audio/wav'               => 'wav',
			default                   => 'mp3',
		};
	}
}
