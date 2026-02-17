<?php

declare(strict_types=1);

namespace TalkingHead\Database;

defined( 'ABSPATH' ) || exit;

final class Schema {

	private const DB_VERSION        = '1.0.0';
	private const DB_VERSION_OPTION = 'talking_head_db_version';

	public static function install(): void {
		self::create_jobs_table();
		self::create_assets_table();
		update_option( self::DB_VERSION_OPTION, self::DB_VERSION, false );
	}

	public static function maybe_upgrade(): void {
		$current = get_option( self::DB_VERSION_OPTION, '0.0.0' );
		if ( version_compare( $current, self::DB_VERSION, '>=' ) ) {
			return;
		}
		self::install();
	}

	public static function jobs_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'th_jobs';
	}

	public static function assets_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'th_assets';
	}

	private static function create_jobs_table(): void {
		global $wpdb;
		$table   = self::jobs_table();
		$charset = $wpdb->get_charset_collate();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				episode_id BIGINT UNSIGNED NOT NULL,
				manuscript_hash CHAR(64) NOT NULL,
				status VARCHAR(20) NOT NULL DEFAULT 'queued',
				progress TINYINT UNSIGNED NOT NULL DEFAULT 0,
				total_segments SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				completed_segments SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				error_message TEXT NULL,
				retry_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
				created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				started_at DATETIME NULL,
				completed_at DATETIME NULL,
				PRIMARY KEY (id),
				KEY idx_episode_status (episode_id, status),
				KEY idx_status_created (status, created_at)
			) {$charset}"
		);
	}

	private static function create_assets_table(): void {
		global $wpdb;
		$table   = self::assets_table();
		$charset = $wpdb->get_charset_collate();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				job_id BIGINT UNSIGNED NOT NULL,
				episode_id BIGINT UNSIGNED NOT NULL,
				segment_index SMALLINT UNSIGNED NULL,
				asset_type VARCHAR(20) NOT NULL DEFAULT 'chunk',
				file_path VARCHAR(500) NOT NULL,
				file_url VARCHAR(500) NOT NULL,
				file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
				duration_ms INT UNSIGNED NOT NULL DEFAULT 0,
				format VARCHAR(10) NOT NULL DEFAULT 'mp3',
				checksum CHAR(64) NULL,
				created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY idx_job_segment (job_id, segment_index),
				KEY idx_episode_type (episode_id, asset_type)
			) {$charset}"
		);
	}

	public static function drop(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( 'DROP TABLE IF EXISTS ' . self::assets_table() );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( 'DROP TABLE IF EXISTS ' . self::jobs_table() );
		delete_option( self::DB_VERSION_OPTION );
		delete_option( 'talking_head_options' );
	}
}
