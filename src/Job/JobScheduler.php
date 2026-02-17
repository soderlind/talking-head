<?php

declare(strict_types=1);

namespace TalkingHead\Job;

use TalkingHead\CPT\EpisodeCPT;
use TalkingHead\Database\JobRepository;
use TalkingHead\Enum\EpisodeStatus;
use TalkingHead\Manuscript\ManuscriptBuilder;
use TalkingHead\Manuscript\ManuscriptValidator;

defined( 'ABSPATH' ) || exit;

final class JobScheduler {

	public function __construct(
		private readonly JobRepository $jobs = new JobRepository(),
		private readonly ManuscriptBuilder $builder = new ManuscriptBuilder(),
		private readonly ManuscriptValidator $validator = new ManuscriptValidator(),
		private readonly ManuscriptHasher $hasher = new ManuscriptHasher(),
	) {}

	/**
	 * Validate the manuscript, create a job record, and enqueue for background processing.
	 *
	 * @param int $episode_id Episode post ID.
	 * @return array{jobId: int, status: string, message?: string}
	 * @throws \DomainException If manuscript validation fails.
	 */
	public function schedule( int $episode_id ): array {
		$manuscript = $this->builder->build( $episode_id );
		$errors     = $this->validator->validate( $manuscript );

		if ( ! empty( $errors ) ) {
			throw new \DomainException( implode( ' ', $errors ) );
		}

		$hash = $this->hasher->hash( $manuscript );

		// Idempotency: check for an active job with the same content hash.
		if ( $this->jobs->active_job_exists( $episode_id, $hash ) ) {
			$latest = $this->jobs->latest_for_episode( $episode_id );
			return [
				'jobId'   => (int) $latest['id'],
				'status'  => $latest['status'],
				'message' => __( 'Generation already in progress for this content.', 'talking-head' ),
			];
		}

		// Persist validated manuscript to post meta.
		update_post_meta( $episode_id, EpisodeCPT::META_KEY_MANUSCRIPT, wp_json_encode( $manuscript ) );
		update_post_meta( $episode_id, EpisodeCPT::META_KEY_STATUS, EpisodeStatus::Generating->value );

		// Create job record.
		$job_id = $this->jobs->create(
			episode_id:      $episode_id,
			manuscript_hash: $hash,
			total_segments:  count( $manuscript['segments'] ),
		);

		// Enqueue for background processing via Action Scheduler.
		as_schedule_single_action(
			time(),
			'talking_head_process_job',
			[ 'job_id' => $job_id ],
			'talking-head'
		);

		return [
			'jobId'  => $job_id,
			'status' => 'queued',
		];
	}
}
