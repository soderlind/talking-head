<?php

declare(strict_types=1);

namespace TalkingHead\Blocks;

defined( 'ABSPATH' ) || exit;

final class TurnBlock {

	public function register(): void {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	public function register_block(): void {
		if ( file_exists( TALKING_HEAD_DIR . 'build/blocks/turn' ) ) {
			register_block_type( TALKING_HEAD_DIR . 'build/blocks/turn' );
		}
	}
}
