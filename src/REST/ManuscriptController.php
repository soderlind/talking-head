<?php

declare(strict_types=1);

namespace TalkingHead\REST;

use TalkingHead\CPT\EpisodeCPT;
use TalkingHead\Manuscript\ManuscriptBuilder;
use TalkingHead\Manuscript\ManuscriptValidator;
use WP_Error;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

final class ManuscriptController {

	private const NAMESPACE = 'talking-head/v1';

	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_action( 'save_post_' . EpisodeCPT::POST_TYPE, [ $this, 'on_save_episode' ], 20, 2 );
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE ,
			'/episodes/(?P<id>\d+)/manuscript',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_manuscript' ],
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
			'/episodes/(?P<id>\d+)/manuscript/validate',
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'validate_manuscript' ],
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

	public function get_manuscript( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$episode_id = absint( $request->get_param( 'id' ) );

		try {
			$builder    = new ManuscriptBuilder();
			$manuscript = $builder->build( $episode_id );
			return new WP_REST_Response( $manuscript, 200 );
		} catch (\InvalidArgumentException $e) {
			return new WP_Error(
				'invalid_episode',
				$e->getMessage(),
				[ 'status' => 404 ]
			);
		}
	}

	public function validate_manuscript( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$episode_id = absint( $request->get_param( 'id' ) );

		try {
			$builder    = new ManuscriptBuilder();
			$manuscript = $builder->build( $episode_id );
			$validator  = new ManuscriptValidator();
			$errors     = $validator->validate( $manuscript );

			if ( ! empty( $errors ) ) {
				return new WP_REST_Response(
					[
						'valid'  => false,
						'errors' => $errors,
					],
					200
				);
			}

			update_post_meta( $episode_id, EpisodeCPT::META_KEY_MANUSCRIPT, wp_json_encode( $manuscript ) );
			update_post_meta( $episode_id, EpisodeCPT::META_KEY_STATUS, 'ready' );

			return new WP_REST_Response(
				[
					'valid'      => true,
					'manuscript' => $manuscript,
				],
				200
			);
		} catch (\InvalidArgumentException $e) {
			return new WP_Error(
				'invalid_episode',
				$e->getMessage(),
				[ 'status' => 404 ]
			);
		}
	}

	/**
	 * Auto-build manuscript when episode is saved via block editor.
	 */
	public function on_save_episode( int $post_id, WP_Post $post ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		try {
			$builder    = new ManuscriptBuilder();
			$manuscript = $builder->build( $post_id );
			update_post_meta( $post_id, EpisodeCPT::META_KEY_MANUSCRIPT, wp_json_encode( $manuscript ) );
		} catch (\InvalidArgumentException) {
			// Silently skip — episode was likely an incomplete draft.
		}
	}
}
