<?php

declare(strict_types=1);

namespace TalkingHead\Blocks;

use TalkingHead\Admin\SettingsPage;
use TalkingHead\CPT\EpisodeCPT;
use TalkingHead\Database\AssetRepository;

defined( 'ABSPATH' ) || exit;

final class PlayerBlock {

	public function register(): void {
		add_action( 'init', [ $this, 'register_block' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_waveform_assets' ] );
	}

	/**
	 * Register waveform player assets for conditional loading.
	 */
	public function register_waveform_assets(): void {
		wp_register_style(
			'waveform-player',
			'https://unpkg.com/@arraypress/waveform-player@1.5.2/dist/waveform-player.css',
			[],
			'1.5.2'
		);
		wp_register_script(
			'waveform-player',
			'https://unpkg.com/@arraypress/waveform-player@1.5.2/dist/waveform-player.min.js',
			[],
			'1.5.2',
			[ 'in_footer' => true, 'strategy' => 'defer' ]
		);

		// Waveform playlist addon for chapter navigation.
		wp_register_style(
			'waveform-playlist',
			'https://unpkg.com/@arraypress/waveform-playlist@1.1.0/dist/waveform-playlist.css',
			[ 'waveform-player' ],
			'1.1.0'
		);
		wp_register_script(
			'waveform-playlist',
			'https://unpkg.com/@arraypress/waveform-playlist@1.1.0/dist/waveform-playlist.min.js',
			[ 'waveform-player' ],
			'1.1.0',
			[ 'in_footer' => true, 'strategy' => 'defer' ]
		);
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

		$title                = esc_html( $post->post_title );
		$audio_url_esc        = esc_url( $audio_url );
		$show_transcript      = ! empty( $attributes[ 'showTranscript' ] );
		$use_waveform_player  = ! empty( $attributes[ 'useWaveformPlayer' ] );
		$show_chapters        = ! empty( $attributes[ 'showChapters' ] ) && $use_waveform_player;

		// Enqueue waveform player assets if enabled.
		if ( $use_waveform_player ) {
			wp_enqueue_style( 'waveform-player' );
			wp_enqueue_script( 'waveform-player' );

			if ( $show_chapters ) {
				wp_enqueue_style( 'waveform-playlist' );
				wp_enqueue_script( 'waveform-playlist' );
			}
		}

		// Build chapter data from segments if chapters are enabled.
		$chapters = [];
		if ( $show_chapters && $stitching_mode !== 'virtual' ) {
			$chapters = $this->get_chapters_for_episode( $episode_id );
		}

		ob_start();
		include TALKING_HEAD_DIR . 'templates/player.php';
		return ob_get_clean();
	}

	/**
	 * Get chapter markers from episode segments with calculated timestamps.
	 *
	 * @param int $episode_id Episode post ID.
	 * @return array Array of chapters with speaker and time.
	 */
	private function get_chapters_for_episode( int $episode_id ): array {
		$manuscript = json_decode(
			get_post_meta( $episode_id, EpisodeCPT::META_KEY_MANUSCRIPT, true ) ?: '{}',
			true
		);

		$segments = $manuscript[ 'segments' ] ?? [];
		if ( empty( $segments ) ) {
			return [];
		}

		// Get segment durations from asset repository.
		$assets = new AssetRepository();
		$chunks = $assets->find_chunks_for_episode( $episode_id );

		if ( empty( $chunks ) ) {
			return [];
		}

		// Load AI-generated chapter titles if available.
		$ai_titles = json_decode(
			get_post_meta( $episode_id, EpisodeCPT::META_KEY_CHAPTER_TITLES, true ) ?: '[]',
			true
		);

		$chapters       = [];
		$cumulative_ms  = 0;
		$silence_gap_ms = (int) SettingsPage::get( 'silence_gap_ms' );

		foreach ( $chunks as $i => $chunk ) {
			// Use AI title if available, otherwise fall back to speaker name.
			$title = $ai_titles[ $i ] ?? ( $segments[ $i ][ 'headName' ] ?? ( 'Segment ' . ( $i + 1 ) ) );

			// Format time as M:SS or H:MM:SS.
			$time_seconds = (int) floor( $cumulative_ms / 1000 );
			$hours        = (int) floor( $time_seconds / 3600 );
			$minutes      = (int) floor( ( $time_seconds % 3600 ) / 60 );
			$seconds      = $time_seconds % 60;

			if ( $hours > 0 ) {
				$time_formatted = sprintf( '%d:%02d:%02d', $hours, $minutes, $seconds );
			} else {
				$time_formatted = sprintf( '%d:%02d', $minutes, $seconds );
			}

			$chapters[] = [
				'title' => $title,
				'time'  => $time_formatted,
			];

			// Add segment duration plus silence gap for next chapter's start time.
			$cumulative_ms += (int) $chunk[ 'duration_ms' ] + $silence_gap_ms;
		}

		return $chapters;
	}
}
