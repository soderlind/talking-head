<?php

declare(strict_types=1);

namespace TalkingHead\Admin;

use TalkingHead\CPT\HeadCPT;

defined( 'ABSPATH' ) || exit;

final class HeadEditorAssets {

	private const VOICES = [ 'alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer' ];

	public function register(): void {
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue' ] );
	}

	public function enqueue(): void {
		$screen = get_current_screen();
		if ( ! $screen || $screen->post_type !== HeadCPT::POST_TYPE ) {
			return;
		}

		$asset_file = TALKING_HEAD_DIR . 'build/head-sidebar/index.asset.php';
		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			'talking-head-head-sidebar',
			TALKING_HEAD_URL . 'build/head-sidebar/index.js',
			$asset[ 'dependencies' ],
			$asset[ 'version' ],
			true
		);

		wp_set_script_translations(
			'talking-head-head-sidebar',
			'talking-head',
			TALKING_HEAD_DIR . 'languages'
		);

		wp_localize_script(
			'talking-head-head-sidebar',
			'talkingHeadVoiceSamples',
			self::voice_sample_urls()
		);
	}

	/**
	 * Return an associative array of voice-name => sample MP3 URL.
	 *
	 * @return array<string, string>
	 */
	public static function voice_sample_urls(): array {
		$urls = [];
		foreach ( self::VOICES as $voice ) {
			$file = 'assets/voice-samples/' . $voice . '.mp3';
			if ( file_exists( TALKING_HEAD_DIR . $file ) ) {
				$urls[ $voice ] = TALKING_HEAD_URL . $file;
			}
		}
		return $urls;
	}
}
