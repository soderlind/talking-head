<?php

declare(strict_types=1);

namespace TalkingHead\REST;

use TalkingHead\Admin\SettingsPage;
use TalkingHead\CPT\HeadCPT;
use WP_Error;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

final class HeadController {

	private const NAMESPACE = 'talking-head/v1';

	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE ,
			'/heads',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'list_heads' ],
					'permission_callback' => fn() => current_user_can( 'edit_posts' ),
				],
			]
		);

		register_rest_route(
			self::NAMESPACE ,
			'/heads/(?P<id>\d+)',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_head' ],
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

	public function list_heads( WP_REST_Request $request ): WP_REST_Response {
		$posts = get_posts(
			[
				'post_type'      => HeadCPT::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		$heads = array_map( [ $this, 'format_head' ], $posts );

		return new WP_REST_Response( $heads, 200 );
	}

	public function get_head( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$post = get_post( absint( $request->get_param( 'id' ) ) );

		if ( ! $post || $post->post_type !== HeadCPT::POST_TYPE ) {
			return new WP_Error(
				'not_found',
				__( 'Head not found.', 'talking-head' ),
				[ 'status' => 404 ]
			);
		}

		return new WP_REST_Response( $this->format_head( $post ), 200 );
	}

	private function format_head( WP_Post $post ): array {
		return [
			'id'            => $post->ID,
			'name'          => $post->post_title,
			'voiceId'       => get_post_meta( $post->ID, HeadCPT::META_KEY_VOICE_ID, true ) ?: 'alloy',
			'provider'      => get_post_meta( $post->ID, HeadCPT::META_KEY_PROVIDER, true ) ?: SettingsPage::get( 'tts_provider' ),
			'speakingStyle' => get_post_meta( $post->ID, HeadCPT::META_KEY_SPEAKING_STYLE, true ) ?: '',
			'avatarUrl'     => get_post_meta( $post->ID, HeadCPT::META_KEY_AVATAR_URL, true ) ?: '',
			'thumbnail'     => get_the_post_thumbnail_url( $post->ID, 'thumbnail' ) ?: '',
		];
	}
}
