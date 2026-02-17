<?php
/**
 * Plugin Name: Talking Head
 * Description: AI-generated podcast-style audio from turn-based conversations.
 * Version:     0.3.2
 * Author:      Per Soderlind
 * Author URI:  https://soderlind.no
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.8
 * Requires PHP: 8.3
 * Text Domain: talking-head
 * Domain Path: /languages
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

define( 'TALKING_HEAD_VERSION', '0.3.2' );
define( 'TALKING_HEAD_FILE', __FILE__ );
define( 'TALKING_HEAD_DIR', plugin_dir_path( __FILE__ ) );
define( 'TALKING_HEAD_URL', plugin_dir_url( __FILE__ ) );
define( 'TALKING_HEAD_BASENAME', plugin_basename( __FILE__ ) );

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

// Load bundled Action Scheduler (self-bootstraps via plugins_loaded priority 0–1).
require __DIR__ . '/vendor/woocommerce/action-scheduler/action-scheduler.php';

if ( ! class_exists( \Soderlind\WordPress\GitHubUpdater::class ) ) {
	require_once __DIR__ . '/class-github-updater.php';
}
\Soderlind\WordPress\GitHubUpdater::init(
	github_url: 'https://github.com/soderlind/talking-head',
	plugin_file: TALKING_HEAD_FILE,
	plugin_slug: 'talking-head',
	name_regex: '/talking-head\.zip/',
	branch: 'main',
);

add_action( 'plugins_loaded', [ TalkingHead\Plugin::class, 'boot' ], 10 );

register_activation_hook( __FILE__, [ TalkingHead\Plugin::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ TalkingHead\Plugin::class, 'deactivate' ] );
