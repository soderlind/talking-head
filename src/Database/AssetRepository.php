<?php

declare(strict_types=1);

namespace TalkingHead\Database;

defined( 'ABSPATH' ) || exit;

final class AssetRepository {

	private readonly string $table;

	public function __construct() {
		$this->table = Schema::assets_table();
	}

	public function create(
		int $job_id,
		int $episode_id,
		?int $segment_index,
		string $asset_type,
		string $file_path,
		string $file_url,
		int $file_size,
		int $duration_ms,
		string $format,
		?string $checksum = null,
	): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$this->table,
			[
				'job_id'        => $job_id,
				'episode_id'    => $episode_id,
				'segment_index' => $segment_index,
				'asset_type'    => $asset_type,
				'file_path'     => $file_path,
				'file_url'      => $file_url,
				'file_size'     => $file_size,
				'duration_ms'   => $duration_ms,
				'format'        => $format,
				'checksum'      => $checksum,
			],
			[ '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s' ]
		);

		return (int) $wpdb->insert_id;
	}

	public function find( int $asset_id ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$asset_id
			),
			ARRAY_A
		);

		return $row ?: null;
	}

	public function find_for_job( int $job_id ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE job_id = %d ORDER BY segment_index ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$job_id
			),
			ARRAY_A
		) ?: [];
	}

	public function find_final_for_episode( int $episode_id ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table}
				 WHERE episode_id = %d AND asset_type = 'final'
				 ORDER BY id DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$episode_id
			),
			ARRAY_A
		);

		return $row ?: null;
	}

	public function delete_for_job( int $job_id ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->delete(
			$this->table,
			[ 'job_id' => $job_id ],
			[ '%d' ]
		);
	}
}
