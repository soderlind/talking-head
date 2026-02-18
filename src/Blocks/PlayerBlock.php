<?php

declare(strict_types=1);

namespace TalkingHead\Blocks;

use TalkingHead\Admin\SettingsPage;
use TalkingHead\CPT\EpisodeCPT;

defined( 'ABSPATH' ) || exit;

final class PlayerBlock {

	public function register(): void {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	public function register_block(): void {
		if ( file_exists( TALKING_HEAD_DIR . 'build/blocks/player' ) ) {
			register_block_type(
				TALKING_HEAD_DIR . 'build/blocks/player',
				[
					'render_callback' => [ $this, 'render' ],
				]
			);
		}
	}

	/**
	 * Server-side render callback for the player block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered HTML.
	 */
	public function render( array $attributes ): string {
		$episode_id = (int) ( $attributes[ 'episodeId' ] ?? 0 );
		if ( $episode_id <= 0 ) {
			return '';
		}

		$post = get_post( $episode_id );
		if ( ! $post || $post->post_type !== EpisodeCPT::POST_TYPE ) {
			return '';
		}

		// Resolve stitching mode: per-episode override > global setting.
		$episode_mode   = get_post_meta( $episode_id, EpisodeCPT::META_KEY_STITCHING_MODE, true );
		$stitching_mode = ( $episode_mode !== '' ) ? $episode_mode : SettingsPage::get( 'stitching_mode' );

		$audio_url = get_post_meta( $episode_id, EpisodeCPT::META_KEY_AUDIO_URL, true );

		// For file mode, require an audio URL.  For virtual mode, audio is loaded client-side.
		if ( $stitching_mode !== 'virtual' && empty( $audio_url ) ) {
			return '<div class="th-player th-player--empty">'
				. esc_html__( 'Audio not yet generated.', 'talking-head' )
				. '</div>';
		}

		// For virtual mode, check that status is generated.
		if ( $stitching_mode === 'virtual' ) {
			$status = get_post_meta( $episode_id, EpisodeCPT::META_KEY_STATUS, true );
			if ( $status !== 'generated' ) {
				return '<div class="th-player th-player--empty">'
					. esc_html__( 'Audio not yet generated.', 'talking-head' )
					. '</div>';
			}
		}

		$title           = esc_html( $post->post_title );
		$audio_url_esc   = esc_url( $audio_url );
		$show_transcript = ! empty( $attributes[ 'showTranscript' ] );

		ob_start();
		include TALKING_HEAD_DIR . 'templates/player.php';
		return ob_get_clean();
	}
}
