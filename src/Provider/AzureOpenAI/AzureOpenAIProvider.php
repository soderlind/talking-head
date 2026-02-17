<?php

declare(strict_types=1);

namespace TalkingHead\Provider\AzureOpenAI;

use TalkingHead\Provider\AudioChunk;
use TalkingHead\Provider\ProviderCapabilities;
use TalkingHead\Provider\ProviderInterface;

defined( 'ABSPATH' ) || exit;

final class AzureOpenAIProvider implements ProviderInterface {

	public function __construct(
		private readonly string $apiKey,
		private readonly string $endpoint,
		private readonly string $deploymentId,
		private readonly string $apiVersion = '2024-05-01-preview',
	) {}

	public function synthesize( string $text, string $voiceId, array $options = [] ): AudioChunk {
		if ( empty( $this->apiKey ) ) {
			throw new \RuntimeException( 'Azure OpenAI API key is not configured.' );
		}
		if ( empty( $this->endpoint ) ) {
			throw new \RuntimeException( 'Azure OpenAI endpoint is not configured.' );
		}
		if ( empty( $this->deploymentId ) ) {
			throw new \RuntimeException( 'Azure OpenAI deployment ID is not configured.' );
		}

		$speed           = (float) ( $options['speed'] ?? 1.0 );
		$response_format = $options['format'] ?? 'mp3';

		$url = sprintf(
			'%s/openai/deployments/%s/audio/speech?api-version=%s',
			rtrim( $this->endpoint, '/' ),
			rawurlencode( $this->deploymentId ),
			rawurlencode( $this->apiVersion ),
		);

		$response = wp_remote_post(
			$url,
			[
				'timeout' => 120,
				'headers' => [
					'api-key'      => $this->apiKey,
					'Content-Type' => 'application/json',
				],
				'body'    => wp_json_encode(
					[
						'model'           => $this->deploymentId,
						'input'           => $text,
						'voice'           => $voiceId,
						'response_format' => $response_format,
						'speed'           => $speed,
					]
				),
			]
		);

		if ( is_wp_error( $response ) ) {
			throw new \RuntimeException(
				'Azure OpenAI TTS request failed: ' . $response->get_error_message()
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 200 ) {
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );
			$msg  = $data['error']['message'] ?? "HTTP {$status_code}";
			throw new \RuntimeException( "Azure OpenAI TTS error: {$msg}" );
		}

		$audio_data = wp_remote_retrieve_body( $response );
		$size       = strlen( $audio_data );

		// Rough duration estimate for MP3 at ~192kbps (1 second ~ 24000 bytes).
		$estimated_duration_ms = (int) ( ( $size / 24000 ) * 1000 );

		return new AudioChunk(
			data:         $audio_data,
			format:       $response_format,
			durationMs:   $estimated_duration_ms,
			sizeBytes:    $size,
			voiceId:      $voiceId,
			segmentIndex: (int) ( $options['segmentIndex'] ?? 0 ),
		);
	}

	public function capabilities(): ProviderCapabilities {
		return new ProviderCapabilities(
			maxCharsPerRequest:    4096,
			supportedFormats:      [ 'mp3', 'opus', 'aac', 'flac' ],
			supportsSSML:          false,
			supportsSpeakingStyle: false,
		);
	}

	public function voices(): array {
		return [
			[ 'id' => 'alloy',   'name' => 'Alloy',   'gender' => 'neutral' ],
			[ 'id' => 'echo',    'name' => 'Echo',     'gender' => 'male' ],
			[ 'id' => 'fable',   'name' => 'Fable',    'gender' => 'neutral' ],
			[ 'id' => 'onyx',    'name' => 'Onyx',     'gender' => 'male' ],
			[ 'id' => 'nova',    'name' => 'Nova',     'gender' => 'female' ],
			[ 'id' => 'shimmer', 'name' => 'Shimmer',  'gender' => 'female' ],
		];
	}

	public function slug(): string {
		return 'azure_openai';
	}

	public function name(): string {
		return 'Azure OpenAI TTS';
	}
}
