<?php

declare(strict_types=1);

namespace TalkingHead\Blocks;

use TalkingHead\Admin\SettingsPage;

defined( 'ABSPATH' ) || exit;

final class EpisodeBlock {

	public function register(): void {
		add_action( 'init', [ $this, 'register_block' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'inline_settings' ] );
	}

	public function register_block(): void {
		if ( file_exists( TALKING_HEAD_DIR . 'build/blocks/episode' ) ) {
			register_block_type( TALKING_HEAD_DIR . 'build/blocks/episode' );
		}
	}

	public function inline_settings(): void {
		wp_add_inline_script(
			'talking-head-episode-editor-script',
			'window.talkingHeadSettings = ' . wp_json_encode( [
				'maxSegments'     => (int) SettingsPage::get( 'max_segments' ),
				'maxSegmentChars' => (int) SettingsPage::get( 'max_segment_chars' ),
			] ) . ';',
			'before'
		);
	}
}
