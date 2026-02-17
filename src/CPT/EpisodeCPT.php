<?php

declare(strict_types=1);

namespace TalkingHead\CPT;

defined( 'ABSPATH' ) || exit;

final class EpisodeCPT {

	public const POST_TYPE               = 'talking_head_episode';
	public const META_KEY_MANUSCRIPT     = '_th_manuscript';
	public const META_KEY_STATUS         = '_th_episode_status';
	public const META_KEY_AUDIO_URL      = '_th_audio_url';
	public const META_KEY_AUDIO_DURATION = '_th_audio_duration';

	public function register(): void {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'init', [ $this, 'register_meta' ] );
	}

	public function register_post_type(): void {
		$labels = [
			'name'               => _x( 'Episodes', 'Post type general name', 'talking-head' ),
			'singular_name'      => _x( 'Episode', 'Post type singular name', 'talking-head' ),
			'menu_name'          => _x( 'Talking Head', 'Admin menu text', 'talking-head' ),
			'add_new'            => __( 'Add New Episode', 'talking-head' ),
			'add_new_item'       => __( 'Add New Episode', 'talking-head' ),
			'edit_item'          => __( 'Edit Episode', 'talking-head' ),
			'new_item'           => __( 'New Episode', 'talking-head' ),
			'view_item'          => __( 'View Episode', 'talking-head' ),
			'all_items'          => __( 'Episodes', 'talking-head' ),
			'search_items'       => __( 'Search Episodes', 'talking-head' ),
			'not_found'          => __( 'No episodes found.', 'talking-head' ),
			'not_found_in_trash' => __( 'No episodes found in Trash.', 'talking-head' ),
		];

		register_post_type(
			self::POST_TYPE,
			[
				'labels'        => $labels,
				'public'        => true,
				'show_in_rest'  => true,
				'rest_base'     => 'th-episodes',
				'menu_icon'     => 'dashicons-microphone',
				'supports'      => [ 'title', 'editor', 'custom-fields', 'revisions' ],
				'has_archive'   => false,
				'rewrite'       => [ 'slug' => 'episode' ],
				'show_in_menu'  => true,
				'template'      => [
					[
						'talking-head/episode',
						[],
						[
							[ 'talking-head/turn', [] ],
						],
					],
				],
				'template_lock' => false,
			]
		);
	}

	public function register_meta(): void {
		register_post_meta(
			self::POST_TYPE,
			self::META_KEY_MANUSCRIPT,
			[
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
			]
		);

		register_post_meta(
			self::POST_TYPE,
			self::META_KEY_STATUS,
			[
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
				'default'           => 'draft',
				'sanitize_callback' => 'sanitize_key',
				'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
			]
		);

		register_post_meta(
			self::POST_TYPE,
			self::META_KEY_AUDIO_URL,
			[
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
			]
		);

		register_post_meta(
			self::POST_TYPE,
			self::META_KEY_AUDIO_DURATION,
			[
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'number',
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
			]
		);
	}
}
