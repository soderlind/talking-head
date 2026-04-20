<?php

declare(strict_types=1);

namespace TalkingHead\Chapter;

use TalkingHead\Admin\SettingsPage;

defined( 'ABSPATH' ) || exit;

/**
 * Generates short chapter titles from segment text using AI chat completion.
 */
final class ChapterTitleGenerator {

	private const OPENAI_CHAT_URL       = 'https://api.openai.com/v1/chat/completions';
	private const DEFAULT_MODEL         = 'gpt-4o-mini';
	private const MAX_TITLE_LENGTH      = 40;

	/**
	 * Generate chapter titles for all segments in a manuscript.
	 *
	 * @param array $segments Array of manuscript segments with 'text' and 'headName' keys.
	 * @return array<int, string> Array of chapter titles indexed by segment index.
	 */
	public function generate( array $segments ): array {
		if ( empty( $segments ) ) {
			return [];
		}

		$provider = SettingsPage::get( 'tts_provider' );

		// Build prompt with all segments.
		$segment_texts = [];
		foreach ( $segments as $i => $segment ) {
			$speaker = $segment['headName'] ?? 'Speaker';
			$text    = $segment['text'] ?? '';
			$segment_texts[] = sprintf( '%d. [%s]: %s', $i + 1, $speaker, $this->truncate_text( $text, 200 ) );
		}

		$prompt = $this->build_prompt( $segment_texts );

		// Try providers in order with fallback chain.
		$titles = null;
		$errors = [];

		// Try WordPress AI first if provider is wordpress and services are configured.
		$wp_ai_available = $provider === 'wordpress'
			&& function_exists( 'wp_ai_client_prompt' )
			&& function_exists( 'wp_ai_get_services' )
			&& ! empty( wp_ai_get_services() );

		if ( $wp_ai_available ) {
			try {
				$titles = $this->call_wordpress_ai( $prompt, count( $segments ) );
			} catch ( \Throwable $e ) {
				$errors[] = 'WordPress AI: ' . $e->getMessage();
			}
		}

		// Try OpenAI if configured and no titles yet.
		if ( $titles === null && ! empty( SettingsPage::get( 'openai_api_key' ) ) ) {
			try {
				$titles = $this->call_openai( $prompt, count( $segments ) );
			} catch ( \Throwable $e ) {
				$errors[] = 'OpenAI: ' . $e->getMessage();
			}
		}

		// Try Azure OpenAI chat if configured and no titles yet.
		if ( $titles === null && ! empty( SettingsPage::get( 'azure_openai_api_key' ) ) ) {
			try {
				$titles = $this->call_azure_openai( $prompt, count( $segments ) );
			} catch ( \Throwable $e ) {
				$errors[] = 'Azure OpenAI: ' . $e->getMessage();
			}
		}

		// Fallback to speaker names if all AI providers failed.
		if ( $titles === null ) {
			if ( ! empty( $errors ) ) {
				error_log( 'Talking Head: Chapter title generation failed - ' . implode( '; ', $errors ) );
			}
			return $this->fallback_titles( $segments );
		}

		// Ensure we have the right number of titles.
		if ( count( $titles ) !== count( $segments ) ) {
			return $this->fallback_titles( $segments );
		}

		return $titles;
	}

	/**
	 * Build the AI prompt for generating chapter titles.
	 */
	private function build_prompt( array $segment_texts ): string {
		$segments_text = implode( "\n", $segment_texts );

		return <<<PROMPT
You are generating short chapter titles for a podcast episode. Each segment is a turn in a conversation.

Generate a brief, descriptive title (max 40 characters) for each segment that captures the main topic or action. The title should be suitable for display as a chapter marker.

Return ONLY a JSON array of strings, one title per segment, in order. Example: ["Introduction", "Discussing AI Future", "Conclusion"]

Segments:
{$segments_text}
PROMPT;
	}

	/**
	 * Call OpenAI chat completion API.
	 */
	private function call_openai( string $prompt, int $expected_count ): array {
		$api_key = SettingsPage::get( 'openai_api_key' );

		if ( empty( $api_key ) ) {
			throw new \RuntimeException( 'OpenAI API key not configured.' );
		}

		$response = wp_remote_post(
			self::OPENAI_CHAT_URL,
			[
				'timeout' => 60,
				'headers' => [
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				],
				'body'    => wp_json_encode( [
					'model'       => self::DEFAULT_MODEL,
					'messages'    => [
						[ 'role' => 'user', 'content' => $prompt ],
					],
					'temperature' => 0.3,
					'max_tokens'  => 500,
				] ),
			]
		);

		return $this->parse_response( $response, $expected_count );
	}

	/**
	 * Call Azure OpenAI chat completion API.
	 */
	private function call_azure_openai( string $prompt, int $expected_count ): array {
		$api_key       = SettingsPage::get( 'azure_openai_api_key' );
		$endpoint      = SettingsPage::get( 'azure_openai_endpoint' );
		$api_version   = SettingsPage::get( 'azure_openai_api_version' );

		if ( empty( $api_key ) || empty( $endpoint ) ) {
			throw new \RuntimeException( 'Azure OpenAI not configured.' );
		}

		// Use gpt-4o-mini deployment for chat - assume same naming convention.
		$url = sprintf(
			'%s/openai/deployments/%s/chat/completions?api-version=%s',
			rtrim( $endpoint, '/' ),
			'gpt-4o-mini',
			rawurlencode( $api_version ?: '2024-08-01-preview' ),
		);

		$response = wp_remote_post(
			$url,
			[
				'timeout' => 60,
				'headers' => [
					'api-key'      => $api_key,
					'Content-Type' => 'application/json',
				],
				'body'    => wp_json_encode( [
					'messages'    => [
						[ 'role' => 'user', 'content' => $prompt ],
					],
					'temperature' => 0.3,
					'max_tokens'  => 500,
				] ),
			]
		);

		return $this->parse_response( $response, $expected_count );
	}

	/**
	 * Call WordPress AI chat completion API.
	 */
	private function call_wordpress_ai( string $prompt, int $expected_count ): array {
		if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
			throw new \RuntimeException( 'WordPress AI not available.' );
		}

		$result = wp_ai_client_prompt( $prompt )
			->as_text()
			->get();

		if ( is_wp_error( $result ) ) {
			throw new \RuntimeException( $result->get_error_message() );
		}

		return $this->parse_json_titles( $result, $expected_count );
	}

	/**
	 * Parse API response and extract titles.
	 */
	private function parse_response( $response, int $expected_count ): array {
		if ( is_wp_error( $response ) ) {
			throw new \RuntimeException( $response->get_error_message() );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 200 ) {
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );
			$msg  = $data['error']['message'] ?? "HTTP {$status_code}";
			throw new \RuntimeException( "Chat completion error: {$msg}" );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		$content = $data['choices'][0]['message']['content'] ?? '';

		return $this->parse_json_titles( $content, $expected_count );
	}

	/**
	 * Parse JSON array from AI response.
	 */
	private function parse_json_titles( string $content, int $expected_count ): array {
		// Extract JSON array from response (may be wrapped in markdown code blocks).
		if ( preg_match( '/\[.*\]/s', $content, $matches ) ) {
			$json = $matches[0];
		} else {
			$json = $content;
		}

		$titles = json_decode( $json, true );

		if ( ! is_array( $titles ) ) {
			throw new \RuntimeException( 'Failed to parse chapter titles from AI response.' );
		}

		// Sanitize and truncate titles.
		$result = [];
		foreach ( $titles as $i => $title ) {
			if ( ! is_string( $title ) ) {
				$title = 'Segment ' . ( $i + 1 );
			}
			$result[] = $this->truncate_text( sanitize_text_field( $title ), self::MAX_TITLE_LENGTH );
		}

		return $result;
	}

	/**
	 * Fallback to using speaker names as chapter titles.
	 */
	private function fallback_titles( array $segments ): array {
		$titles = [];
		foreach ( $segments as $segment ) {
			$titles[] = $segment['headName'] ?? 'Segment';
		}
		return $titles;
	}

	/**
	 * Truncate text to a maximum length.
	 */
	private function truncate_text( string $text, int $max_length ): string {
		$text = trim( $text );
		if ( mb_strlen( $text ) <= $max_length ) {
			return $text;
		}
		return mb_substr( $text, 0, $max_length - 1 ) . '…';
	}
}
