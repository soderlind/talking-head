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
	public const META_KEY_STITCHING_MODE = '_th_stitching_mode';

	public function register(): void {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'init', [ $this, 'register_meta' ] );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ $this, 'add_columns' ] );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ $this, 'render_column' ], 10, 2 );
		add_action( 'admin_head', [ $this, 'column_styles' ] );
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

		register_post_meta(
			self::POST_TYPE,
			self::META_KEY_STITCHING_MODE,
			[
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => static function ( string $value ): string {
					return in_array( $value, [ 'file', 'virtual', '' ], true ) ? $value : '';
				},
				'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
			]
		);
	}

	/**
	 * Add Status and Audio columns to the episodes list table.
	 *
	 * @param array<string,string> $columns Existing columns.
	 * @return array<string,string>
	 */
	public function add_columns( array $columns ): array {
		$new = [];
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( $key === 'title' ) {
				$new[ 'th_status' ]   = __( 'Status', 'talking-head' );
				$new[ 'th_audio' ]    = __( 'Audio', 'talking-head' );
				$new[ 'th_segments' ] = __( 'Segments', 'talking-head' );
				$new[ 'th_words' ]    = __( 'Words', 'talking-head' );
			}
		}
		return $new;
	}

	/**
	 * Render custom column content.
	 */
	public function render_column( string $column, int $post_id ): void {
		if ( $column === 'th_status' ) {
			$status = get_post_meta( $post_id, self::META_KEY_STATUS, true ) ?: 'draft';
			$label  = ucfirst( $status );
			printf(
				'<span class="th-badge th-badge--%s">%s</span>',
				esc_attr( $status ),
				esc_html( $label )
			);
		}

		if ( $column === 'th_audio' ) {
			$url = get_post_meta( $post_id, self::META_KEY_AUDIO_URL, true );
			if ( $url ) {
				printf(
					'<audio controls preload="none" style="max-width:200px;height:30px"><source src="%s" type="audio/mpeg"></audio>',
					esc_url( $url )
				);
			} else {
				echo '&mdash;';
			}
		}

		if ( $column === 'th_segments' || $column === 'th_words' ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				echo '&mdash;';
				return;
			}

			$blocks     = parse_blocks( $post->post_content );
			$turn_texts = [];

			foreach ( $blocks as $block ) {
				if ( ( $block[ 'blockName' ] ?? '' ) === 'talking-head/episode' ) {
					foreach ( $block[ 'innerBlocks' ] ?? [] as $inner ) {
						if ( ( $inner[ 'blockName' ] ?? '' ) === 'talking-head/turn' ) {
							$html = $inner[ 'innerHTML' ] ?? '';
							if ( preg_match( '/<div[^>]*class="[^"]*th-turn__text[^"]*"[^>]*>(.*?)<\/div>/s', $html, $m ) ) {
								$turn_texts[] = wp_strip_all_tags( $m[ 1 ] );
							}
						}
					}
				}
			}

			if ( $column === 'th_segments' ) {
				echo esc_html( (string) count( $turn_texts ) );
			}

			if ( $column === 'th_words' ) {
				$all_text   = implode( ' ', $turn_texts );
				$word_count = str_word_count( $all_text );
				echo esc_html( (string) $word_count );
			}
		}
	}

	/**
	 * Inline CSS for admin list table columns.
	 */
	public function column_styles(): void {
		$screen = get_current_screen();
		if ( ! $screen || $screen->id !== 'edit-' . self::POST_TYPE ) {
			return;
		}
		?>
		<style>
			.column-th_status {
				width: 90px;
			}

			.column-th_audio {
				width: 220px;
			}

			.column-th_segments {
				width: 80px;
			}

			.column-th_words {
				width: 80px;
			}

			.th-badge {
				display: inline-block;
				font-size: 12px;
				font-weight: 500;
				padding: 2px 8px;
				border-radius: 10px;
				line-height: 1.4;
			}

			.th-badge--draft {
				background: #ddd;
				color: #1e1e1e;
			}

			.th-badge--ready {
				background: #cce5ff;
				color: #004085;
			}

			.th-badge--generating {
				background: #fff3cd;
				color: #856404;
			}

			.th-badge--generated {
				background: #d4edda;
				color: #155724;
			}

			.th-badge--failed {
				background: #f8d7da;
				color: #721c24;
			}
		</style>
		<?php
	}
}
