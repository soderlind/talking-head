<?php

declare(strict_types=1);

namespace TalkingHead\Database;

use TalkingHead\Enum\JobStatus;

defined( 'ABSPATH' ) || exit;

final class JobRepository {

	private readonly string $table;

	public function __construct() {
		$this->table = Schema::jobs_table();
	}

	public function create(
		int $episode_id,
		string $manuscript_hash,
		int $total_segments,
	): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$this->table,
			[
				'episode_id'      => $episode_id,
				'manuscript_hash' => $manuscript_hash,
				'status'          => JobStatus::Queued->value,
				'total_segments'  => $total_segments,
			],
			[ '%d', '%s', '%s', '%d' ]
		);

		return (int) $wpdb->insert_id;
	}

	public function find( int $job_id ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$job_id
			),
			ARRAY_A
		);

		return $row ?: null;
	}

	public function latest_for_episode( int $episode_id ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE episode_id = %d ORDER BY id DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$episode_id
			),
			ARRAY_A
		);

		return $row ?: null;
	}

	public function transition( int $job_id, JobStatus $new_status, ?string $error = null ): bool {
		global $wpdb;

		$data   = [ 'status' => $new_status->value ];
		$format = [ '%s' ];

		if ( $error !== null ) {
			$data[ 'error_message' ] = $error;
			$format[]              = '%s';
		}

		match ( $new_status ) {
			JobStatus::Running  => ( function () use (&$data, &$format) {
					$data[ 'started_at' ] = current_time( 'mysql', true );
					$format[]           = '%s';
				} )(),
			JobStatus::Succeeded,
			JobStatus::Failed,
			JobStatus::Canceled => ( function () use (&$data, &$format) {
					$data[ 'completed_at' ] = current_time( 'mysql', true );
					$format[]             = '%s';
				} )(),
			default             => null,
		};

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->update(
			$this->table,
			$data,
			[ 'id' => $job_id ],
			$format,
			[ '%d' ]
		);

		return $updated !== false;
	}

	public function increment_progress( int $job_id ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->table}
				 SET completed_segments = completed_segments + 1,
				     progress = LEAST(100, ROUND(((completed_segments + 1) / total_segments) * 100))
				 WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$job_id
			)
		);
	}

	public function active_job_exists( int $episode_id, string $manuscript_hash ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table}
				 WHERE episode_id = %d
				   AND manuscript_hash = %s
				   AND status IN ('queued', 'running')", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$episode_id,
				$manuscript_hash
			)
		);

		return $count > 0;
	}
}
