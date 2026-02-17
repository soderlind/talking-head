<?php

declare(strict_types=1);

namespace TalkingHead\Manuscript;

use TalkingHead\Admin\SettingsPage;
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
			if ( $block[ 'blockName' ] !== 'talking-head/episode' ) {
				continue;
			}

			foreach ( ( $block[ 'innerBlocks' ] ?? [] ) as $inner ) {
				if ( $inner[ 'blockName' ] !== 'talking-head/turn' ) {
					continue;
				}

				$attrs   = $inner[ 'attrs' ] ?? [];
				$head_id = (int) ( $attrs[ 'headId' ] ?? 0 );

				// Extract text from the .th-turn__text element only,
				// avoiding the speaker name in .th-turn__speaker.
				$html = $inner[ 'innerHTML' ] ?? '';
				$text = '';

				if ( preg_match( '/<div class="th-turn__text">(.*?)<\/div>/s', $html, $matches ) ) {
					$text = wp_strip_all_tags( $matches[1] );
				}

				// Fallback to the attribute if extraction failed.
				if ( empty( trim( $text ) ) ) {
					$text = wp_strip_all_tags( $attrs[ 'text' ] ?? '' );
				}

				if ( $head_id <= 0 || empty( trim( $text ) ) ) {
					continue;
				}

				$voice_id       = get_post_meta( $head_id, HeadCPT::META_KEY_VOICE_ID, true ) ?: SettingsPage::get( 'default_voice' );
				$provider       = get_post_meta( $head_id, HeadCPT::META_KEY_PROVIDER, true ) ?: SettingsPage::get( 'tts_provider' );
				$speed          = (float) ( get_post_meta( $head_id, HeadCPT::META_KEY_SPEED, true ) ?: 1.0 );
				$speaking_style = get_post_meta( $head_id, HeadCPT::META_KEY_SPEAKING_STYLE, true ) ?: '';

				$segments[] = [
					'index'         => $index++,
					'headId'        => $head_id,
					'headName'      => get_the_title( $head_id ),
					'voiceId'       => $voice_id,
					'provider'      => $provider,
					'text'          => trim( $text ),
					'speed'         => $speed,
					'speakingStyle' => $speaking_style,
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
