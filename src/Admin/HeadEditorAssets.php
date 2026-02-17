<?php

declare(strict_types=1);

namespace TalkingHead\Admin;

use TalkingHead\CPT\HeadCPT;

defined( 'ABSPATH' ) || exit;

final class HeadEditorAssets {

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
			$asset['dependencies'],
			$asset['version'],
			true
		);
	}
}
