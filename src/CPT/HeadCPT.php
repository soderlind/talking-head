<?php

declare(strict_types=1);

namespace TalkingHead\CPT;

defined( 'ABSPATH' ) || exit;

final class HeadCPT {

	public const POST_TYPE              = 'talking_head_head';
	public const META_KEY_VOICE_ID      = '_th_voice_id';
	public const META_KEY_PROVIDER      = '_th_provider';
	public const META_KEY_SPEAKING_STYLE = '_th_speaking_style';
	public const META_KEY_AVATAR_URL    = '_th_avatar_url';

	public function register(): void {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'init', [ $this, 'register_meta' ] );
	}

	public function register_post_type(): void {
		$labels = [
			'name'          => _x( 'Heads', 'Post type general name', 'talking-head' ),
			'singular_name' => _x( 'Head', 'Post type singular name', 'talking-head' ),
			'menu_name'     => _x( 'Heads', 'Admin menu text', 'talking-head' ),
			'add_new'       => __( 'Add New Head', 'talking-head' ),
			'add_new_item'  => __( 'Add New Head', 'talking-head' ),
			'edit_item'     => __( 'Edit Head', 'talking-head' ),
			'new_item'      => __( 'New Head', 'talking-head' ),
			'view_item'     => __( 'View Head', 'talking-head' ),
			'all_items'     => __( 'Heads', 'talking-head' ),
			'search_items'  => __( 'Search Heads', 'talking-head' ),
			'not_found'     => __( 'No heads found.', 'talking-head' ),
		];

		register_post_type(
			self::POST_TYPE,
			[
				'labels'          => $labels,
				'public'          => false,
				'show_ui'         => true,
				'show_in_rest'    => true,
				'rest_base'       => 'th-heads',
				'menu_icon'       => 'dashicons-admin-users',
				'supports'        => [ 'title', 'thumbnail', 'custom-fields' ],
				'show_in_menu'    => 'edit.php?post_type=talking_head_episode',
				'capability_type' => 'post',
			]
		);
	}

	public function register_meta(): void {
		$meta_definitions = [
			self::META_KEY_VOICE_ID      => [
				'type'              => 'string',
				'default'           => 'alloy',
				'sanitize_callback' => 'sanitize_key',
			],
			self::META_KEY_PROVIDER      => [
				'type'              => 'string',
				'default'           => 'openai',
				'sanitize_callback' => 'sanitize_key',
			],
			self::META_KEY_SPEAKING_STYLE => [
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			],
			self::META_KEY_AVATAR_URL    => [
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'esc_url_raw',
			],
		];

		foreach ( $meta_definitions as $key => $args ) {
			register_post_meta(
				self::POST_TYPE,
				$key,
				[
					'show_in_rest'  => true,
					'single'        => true,
					'auth_callback' => fn() => current_user_can( 'edit_posts' ),
					...$args,
				]
			);
		}
	}
}
