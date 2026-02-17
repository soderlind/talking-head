<?php

declare(strict_types=1);

namespace TalkingHead\REST;

use TalkingHead\Database\JobRepository;
use TalkingHead\Enum\JobStatus;
use TalkingHead\Job\JobScheduler;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

final class JobController {

	private const NAMESPACE = 'talking-head/v1';

	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE ,
			'/jobs',
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'create_job' ],
					'permission_callback' => fn() => current_user_can( 'edit_posts' ),
					'args'                => [
						'episodeId' => [
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						],
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE ,
			'/jobs/(?P<id>\d+)',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_job' ],
					'permission_callback' => fn() => current_user_can( 'edit_posts' ),
					'args'                => [
						'id' => [
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						],
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE ,
			'/jobs/(?P<id>\d+)/cancel',
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'cancel_job' ],
					'permission_callback' => fn() => current_user_can( 'edit_posts' ),
					'args'                => [
						'id' => [
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						],
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE ,
			'/jobs/(?P<id>\d+)/retry',
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'retry_job' ],
					'permission_callback' => fn() => current_user_can( 'edit_posts' ),
					'args'                => [
						'id' => [
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						],
					],
				],
			]
		);
	}

	public function create_job( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$episode_id = absint( $request->get_param( 'episodeId' ) );

		try {
			$scheduler = new JobScheduler();
			$result    = $scheduler->schedule( $episode_id );
			return new WP_REST_Response( $result, 201 );
		} catch (\DomainException $e) {
			return new WP_Error(
				'validation_failed',
				$e->getMessage(),
				[ 'status' => 422 ]
			);
		} catch (\Throwable $e) {
			return new WP_Error(
				'job_creation_failed',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}

	public function get_job( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$repo = new JobRepository();
		$job  = $repo->find( absint( $request->get_param( 'id' ) ) );

		if ( ! $job ) {
			return new WP_Error(
				'not_found',
				__( 'Job not found.', 'talking-head' ),
				[ 'status' => 404 ]
			);
		}

		return new WP_REST_Response(
			[
				'id'                => (int) $job[ 'id' ],
				'episodeId'         => (int) $job[ 'episode_id' ],
				'status'            => $job[ 'status' ],
				'progress'          => (int) $job[ 'progress' ],
				'totalSegments'     => (int) $job[ 'total_segments' ],
				'completedSegments' => (int) $job[ 'completed_segments' ],
				'error'             => $job[ 'error_message' ],
				'createdAt'         => $job[ 'created_at' ],
				'completedAt'       => $job[ 'completed_at' ],
			],
			200
		);
	}

	public function cancel_job( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$repo = new JobRepository();
		$job  = $repo->find( absint( $request->get_param( 'id' ) ) );

		if ( ! $job ) {
			return new WP_Error(
				'not_found',
				__( 'Job not found.', 'talking-head' ),
				[ 'status' => 404 ]
			);
		}

		$status = JobStatus::from( $job[ 'status' ] );
		if ( $status->isTerminal() ) {
			return new WP_Error(
				'cannot_cancel',
				__( 'Job is already finished.', 'talking-head' ),
				[ 'status' => 409 ]
			);
		}

		$repo->transition( (int) $job[ 'id' ], JobStatus::Canceled );

		return new WP_REST_Response( [ 'status' => 'canceled' ], 200 );
	}

	public function retry_job( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$repo = new JobRepository();
		$job  = $repo->find( absint( $request->get_param( 'id' ) ) );

		if ( ! $job ) {
			return new WP_Error(
				'not_found',
				__( 'Job not found.', 'talking-head' ),
				[ 'status' => 404 ]
			);
		}

		$status = JobStatus::from( $job[ 'status' ] );
		if ( ! $status->isRetryable() ) {
			return new WP_Error(
				'not_retryable',
				__( 'Only failed jobs can be retried.', 'talking-head' ),
				[ 'status' => 409 ]
			);
		}

		try {
			$scheduler = new JobScheduler();
			$result    = $scheduler->schedule( (int) $job[ 'episode_id' ] );
			return new WP_REST_Response( $result, 201 );
		} catch (\Throwable $e) {
			return new WP_Error(
				'retry_failed',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}
}
