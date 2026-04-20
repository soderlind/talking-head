<?php

declare(strict_types=1);

namespace TalkingHead\Chapter;

defined( 'ABSPATH' ) || exit;

/**
 * Generates short chapter titles from segment text using WordPress AI Client.
 */
final class ChapterTitleGenerator {

	private const MAX_TITLE_LENGTH = 40;

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

		// Build prompt with all segments.
		$segment_texts = [];
		foreach ( $segments as $i => $segment ) {
			$speaker = $segment['headName'] ?? 'Speaker';
			$text    = $segment['text'] ?? '';
			$segment_texts[] = sprintf( '%d. [%s]: %s', $i + 1, $speaker, $this->truncate_text( $text, 200 ) );
		}

		$prompt = $this->build_prompt( $segment_texts );

		// Try WordPress AI chat completion.
		try {
			$titles = $this->call_wordpress_ai( $prompt, count( $segments ) );
			if ( count( $titles ) === count( $segments ) ) {
				return $titles;
			}
		} catch ( \Throwable $e ) {
			error_log( 'Talking Head: Chapter title generation failed - WordPress AI: ' . $e->getMessage() );
		}

		// Fallback to speaker names.
		return $this->fallback_titles( $segments );
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
	 * Call WordPress AI chat completion API.
	 */
	private function call_wordpress_ai( string $prompt, int $expected_count ): array {
		if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
			throw new \RuntimeException( 'WordPress AI Client not available.' );
		}

		$result = wp_ai_client_prompt( $prompt )->generate_text();

		if ( is_wp_error( $result ) ) {
			throw new \RuntimeException( $result->get_error_message() );
		}

		return $this->parse_json_titles( $result, $expected_count );
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
