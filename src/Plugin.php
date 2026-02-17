<?php

declare(strict_types=1);

namespace TalkingHead;

use TalkingHead\Admin\HeadEditorAssets;
use TalkingHead\Admin\HeadMetaBox;
use TalkingHead\Admin\SettingsPage;
use TalkingHead\Blocks\EpisodeBlock;
use TalkingHead\Blocks\PlayerBlock;
use TalkingHead\Blocks\TurnBlock;
use TalkingHead\CPT\EpisodeCPT;
use TalkingHead\CPT\HeadCPT;
use TalkingHead\Database\Schema;
use TalkingHead\Job\JobRunner;
use TalkingHead\REST\HeadController;
use TalkingHead\REST\JobController;
use TalkingHead\REST\ManuscriptController;
use TalkingHead\REST\PlayerController;
use TalkingHead\Storage\LocalStorage;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	private static bool $booted = false;

	public static function boot(): void {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;

		load_plugin_textdomain(
			'talking-head',
			false,
			dirname( TALKING_HEAD_BASENAME ) . '/languages'
		);

		if ( ! function_exists( 'as_schedule_single_action' ) ) {
			add_action( 'admin_notices', [ self::class, 'missing_action_scheduler_notice' ] );
			return;
		}

		Schema::maybe_upgrade();

		( new EpisodeCPT() )->register();
		( new HeadCPT() )->register();

		( new EpisodeBlock() )->register();
		( new TurnBlock() )->register();
		( new PlayerBlock() )->register();

		( new HeadController() )->register();
		( new ManuscriptController() )->register();
		( new JobController() )->register();
		( new PlayerController() )->register();

		JobRunner::register();

		if ( is_admin() ) {
			new SettingsPage();
			( new HeadEditorAssets() )->register();
			( new HeadMetaBox() )->register();
		}
	}

	public static function activate(): void {
		Schema::install();
		LocalStorage::ensure_upload_dir();
		flush_rewrite_rules();
	}

	public static function deactivate(): void {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'talking_head_process_job' );
		}
		flush_rewrite_rules();
	}

	public static function missing_action_scheduler_notice(): void {
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__(
				'Talking Head requires the Action Scheduler plugin. Please install and activate it.',
				'talking-head'
			)
		);
	}
}
