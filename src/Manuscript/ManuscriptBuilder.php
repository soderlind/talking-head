<?php

declare(strict_types=1);

namespace TalkingHead\Manuscript;

use TalkingHead\CPT\EpisodeCPT;
use TalkingHead\CPT\HeadCPT;

defined( 'ABSPATH' ) || exit;

final class ManuscriptBuilder {

	/**
	 * Parse an episode's post_content blocks into canonical manuscript JSON.
	 *
	 * @param int $episode_id Episode post ID.
	 * @return array{version: string, episodeId: int, title: string, segments: list<array>}
	 * @throws \InvalidArgumentException If post is not an episode.
	 */
	public function build( int $episode_id ): array {
		$post = get_post( $episode_id );

		if ( ! $post || $post->post_type !== EpisodeCPT::POST_TYPE ) {
			throw new \InvalidArgumentException(
				sprintf( 'Post %d is not an episode.', $episode_id )
			);
		}

		$blocks   = parse_blocks( $post->post_content );
		$segments = [];
		$index    = 0;

		foreach ( $blocks as $block ) {
			if ( $block['blockName'] !== 'talking-head/episode' ) {
				continue;
			}

			foreach ( ( $block['innerBlocks'] ?? [] ) as $inner ) {
				if ( $inner['blockName'] !== 'talking-head/turn' ) {
					continue;
				}

				$attrs   = $inner['attrs'] ?? [];
				$head_id = (int) ( $attrs['headId'] ?? 0 );

				// Extract text from the rendered inner HTML.
				$text = wp_strip_all_tags( $inner['innerHTML'] ?? '' );

				// Fallback to the attribute if innerHTML is empty.
				if ( empty( trim( $text ) ) ) {
					$text = wp_strip_all_tags( $attrs['text'] ?? '' );
				}

				if ( $head_id <= 0 || empty( trim( $text ) ) ) {
					continue;
				}

				$voice_id = get_post_meta( $head_id, HeadCPT::META_KEY_VOICE_ID, true ) ?: 'alloy';
				$provider = get_post_meta( $head_id, HeadCPT::META_KEY_PROVIDER, true ) ?: 'openai';

				$segments[] = [
					'index'    => $index++,
					'headId'   => $head_id,
					'headName' => get_the_title( $head_id ),
					'voiceId'  => $voice_id,
					'provider' => $provider,
					'text'     => trim( $text ),
				];
			}
		}

		return [
			'version'   => '1.0',
			'episodeId' => $episode_id,
			'title'     => $post->post_title,
			'segments'  => $segments,
		];
	}
}
