<?php
/**
 * PHPUnit bootstrap file for Brain Monkey integration.
 *
 * @package TalkingHead\Tests
 */

declare(strict_types=1);

// Composer autoloader.
require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

// Define WordPress constants used by the plugin.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}

// Plugin constants.
if ( ! defined( 'TALKING_HEAD_VERSION' ) ) {
	define( 'TALKING_HEAD_VERSION', '0.3.4' );
}
if ( ! defined( 'TALKING_HEAD_FILE' ) ) {
	define( 'TALKING_HEAD_FILE', dirname( __DIR__, 2 ) . '/talking-head.php' );
}
if ( ! defined( 'TALKING_HEAD_DIR' ) ) {
	define( 'TALKING_HEAD_DIR', dirname( __DIR__, 2 ) . '/' );
}
if ( ! defined( 'TALKING_HEAD_URL' ) ) {
	define( 'TALKING_HEAD_URL', 'https://example.com/wp-content/plugins/talking-head/' );
}
if ( ! defined( 'TALKING_HEAD_BASENAME' ) ) {
	define( 'TALKING_HEAD_BASENAME', 'talking-head/talking-head.php' );
}

// Minimal WP_Error stub for unit tests.
if ( ! class_exists( 'WP_Error' ) ) {
	// phpcs:ignore
	class WP_Error {
		public $errors     = [];
		public $error_data = [];

		public function __construct( $code = '', $message = '', $data = '' ) {
			if ( $code ) {
				$this->errors[ $code ][] = $message;
				if ( $data ) {
					$this->error_data[ $code ] = $data;
				}
			}
		}

		public function get_error_code() {
			$codes = array_keys( $this->errors );
			return $codes[0] ?? '';
		}

		public function get_error_message( $code = '' ) {
			if ( ! $code ) {
				$code = $this->get_error_code();
			}
			return $this->errors[ $code ][0] ?? '';
		}

		public function has_errors() {
			return ! empty( $this->errors );
		}
	}
}
