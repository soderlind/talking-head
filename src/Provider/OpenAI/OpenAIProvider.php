<?php

declare(strict_types=1);

namespace TalkingHead\Provider\OpenAI;

use TalkingHead\Provider\AudioChunk;
use TalkingHead\Provider\ProviderCapabilities;
use TalkingHead\Provider\ProviderInterface;

defined( 'ABSPATH' ) || exit;

final class OpenAIProvider implements ProviderInterface {

	private const API_URL = 'https://api.openai.com/v1/audio/speech';

	public function __construct(
		private readonly string $apiKey,
		private readonly string $model = 'tts-1',
	) {}

	public function synthesize( string $text, string $voiceId, array $options = [] ): AudioChunk {
		if ( empty( $this->apiKey ) ) {
			throw new \RuntimeException( 'OpenAI API key is not configured.' );
		}

		$speed           = (float) ( $options[ 'speed' ] ?? 1.0 );
		$response_format = $options[ 'format' ] ?? 'mp3';
		$instructions    = $options[ 'instructions' ] ?? '';

		$body = [
			'model'           => $this->model,
			'input'           => $text,
			'voice'           => $voiceId,
			'response_format' => $response_format,
			'speed'           => $speed,
		];

		if ( $instructions !== '' ) {
			$body[ 'instructions' ] = $instructions;
		}

		$response = wp_remote_post(
			self::API_URL,
			[
				'timeout' => 120,
				'headers' => [
					'Authorization' => 'Bearer ' . $this->apiKey,
					'Content-Type'  => 'application/json',
				],
				'body'    => wp_json_encode( $body ),
			]
		);

		if ( is_wp_error( $response ) ) {
			throw new \RuntimeException(
				'OpenAI TTS request failed: ' . $response->get_error_message()
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 200 ) {
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );
			$msg  = $data[ 'error' ][ 'message' ] ?? "HTTP {$status_code}";
			throw new \RuntimeException( "OpenAI TTS error: {$msg}" );
		}

		$audio_data = wp_remote_retrieve_body( $response );
		$size       = strlen( $audio_data );

		// Rough duration estimate for MP3 at ~192kbps (1 second ~ 24000 bytes).
		$estimated_duration_ms = (int) ( ( $size / 24000 ) * 1000 );

		return new AudioChunk(
			data: $audio_data,
			format: $response_format,
			durationMs: $estimated_duration_ms,
			sizeBytes: $size,
			voiceId: $voiceId,
			segmentIndex: (int) ( $options[ 'segmentIndex' ] ?? 0 ),
		);
	}

	public function capabilities(): ProviderCapabilities {
		return new ProviderCapabilities(
			maxCharsPerRequest: 4096,
			supportedFormats: [ 'mp3', 'opus', 'aac', 'flac' ],
			supportsSSML: false,
			supportsSpeakingStyle: true,
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
		return 'openai';
	}

	public function name(): string {
		return 'OpenAI TTS';
	}
}
