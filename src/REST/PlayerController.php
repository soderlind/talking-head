<?php

declare(strict_types=1);

namespace TalkingHead\REST;

use TalkingHead\CPT\EpisodeCPT;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

final class PlayerController {

	private const NAMESPACE = 'talking-head/v1';

	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/episodes/(?P<id>\d+)/player',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_player_data' ],
					'permission_callback' => '__return_true',
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

	public function get_player_data( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$episode_id = absint( $request->get_param( 'id' ) );
		$post       = get_post( $episode_id );

		if ( ! $post || $post->post_type !== EpisodeCPT::POST_TYPE || $post->post_status !== 'publish' ) {
			return new WP_Error(
				'not_found',
				__( 'Episode not found.', 'talking-head' ),
				[ 'status' => 404 ]
			);
		}

		$audio_url  = get_post_meta( $episode_id, EpisodeCPT::META_KEY_AUDIO_URL, true );
		$manuscript = json_decode(
			get_post_meta( $episode_id, EpisodeCPT::META_KEY_MANUSCRIPT, true ) ?: '{}',
			true
		);

		$transcript = array_map(
			fn( array $seg ) => [
				'speaker' => $seg['headName'] ?? '',
				'text'    => $seg['text'] ?? '',
			],
			$manuscript['segments'] ?? []
		);

		return new WP_REST_Response(
			[
				'episodeId'  => $episode_id,
				'title'      => $post->post_title,
				'audioUrl'   => $audio_url ?: null,
				'duration'   => (int) get_post_meta( $episode_id, EpisodeCPT::META_KEY_AUDIO_DURATION, true ),
				'transcript' => $transcript,
			],
			200
		);
	}
}
