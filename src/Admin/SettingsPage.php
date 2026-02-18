<?php

declare(strict_types=1);

namespace TalkingHead\Admin;

defined( 'ABSPATH' ) || exit;

final class SettingsPage {

	private const OPTION_NAME = 'talking_head_options';
	private const PAGE_SLUG   = 'talking-head-settings';

	private static array $config_map = [
		'tts_provider'               => [
			'env'     => 'TALKING_HEAD_TTS_PROVIDER',
			'const'   => 'TALKING_HEAD_TTS_PROVIDER',
			'default' => 'openai',
		],
		'openai_api_key'             => [
			'env'     => 'TALKING_HEAD_OPENAI_API_KEY',
			'const'   => 'TALKING_HEAD_OPENAI_API_KEY',
			'default' => '',
		],
		'openai_tts_model'           => [
			'env'     => 'TALKING_HEAD_OPENAI_TTS_MODEL',
			'const'   => 'TALKING_HEAD_OPENAI_TTS_MODEL',
			'default' => 'tts-1',
		],
		'default_voice'              => [
			'env'     => 'TALKING_HEAD_DEFAULT_VOICE',
			'const'   => 'TALKING_HEAD_DEFAULT_VOICE',
			'default' => 'alloy',
		],
		'ffmpeg_path'                => [
			'env'     => 'TALKING_HEAD_FFMPEG_PATH',
			'const'   => 'TALKING_HEAD_FFMPEG_PATH',
			'default' => '/opt/homebrew/bin/ffmpeg',
		],
		'output_format'              => [
			'env'     => 'TALKING_HEAD_OUTPUT_FORMAT',
			'const'   => 'TALKING_HEAD_OUTPUT_FORMAT',
			'default' => 'mp3',
		],
		'output_bitrate'             => [
			'env'     => 'TALKING_HEAD_OUTPUT_BITRATE',
			'const'   => 'TALKING_HEAD_OUTPUT_BITRATE',
			'default' => '192k',
		],
		'silence_gap_ms'             => [
			'env'     => 'TALKING_HEAD_SILENCE_GAP_MS',
			'const'   => 'TALKING_HEAD_SILENCE_GAP_MS',
			'default' => '500',
		],
		'max_segments'               => [
			'env'     => 'TALKING_HEAD_MAX_SEGMENTS',
			'const'   => 'TALKING_HEAD_MAX_SEGMENTS',
			'default' => '50',
		],
		'max_segment_chars'          => [
			'env'     => 'TALKING_HEAD_MAX_SEGMENT_CHARS',
			'const'   => 'TALKING_HEAD_MAX_SEGMENT_CHARS',
			'default' => '4096',
		],
		'rate_limit_per_min'         => [
			'env'     => 'TALKING_HEAD_RATE_LIMIT',
			'const'   => 'TALKING_HEAD_RATE_LIMIT',
			'default' => '10',
		],
		'azure_openai_api_key'       => [
			'env'     => 'TALKING_HEAD_AZURE_OPENAI_API_KEY',
			'const'   => 'TALKING_HEAD_AZURE_OPENAI_API_KEY',
			'default' => '',
		],
		'azure_openai_endpoint'      => [
			'env'     => 'TALKING_HEAD_AZURE_OPENAI_ENDPOINT',
			'const'   => 'TALKING_HEAD_AZURE_OPENAI_ENDPOINT',
			'default' => '',
		],
		'azure_openai_deployment_id' => [
			'env'     => 'TALKING_HEAD_AZURE_OPENAI_DEPLOYMENT_ID',
			'const'   => 'TALKING_HEAD_AZURE_OPENAI_DEPLOYMENT_ID',
			'default' => '',
		],
		'azure_openai_api_version'   => [
			'env'     => 'TALKING_HEAD_AZURE_OPENAI_API_VERSION',
			'const'   => 'TALKING_HEAD_AZURE_OPENAI_API_VERSION',
			'default' => '2024-05-01-preview',
		],
	];

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Resolve a setting value with priority: constant > env > database > default.
	 */
	public static function get( string $key ): string {
		$map = self::$config_map[ $key ] ?? null;
		if ( ! $map ) {
			return '';
		}

		// 1. PHP constant.
		if ( ! empty( $map[ 'const' ] ) && defined( $map[ 'const' ] ) ) {
			return (string) constant( $map[ 'const' ] );
		}

		// 2. Environment variable.
		if ( ! empty( $map[ 'env' ] ) ) {
			$env = getenv( $map[ 'env' ] );
			if ( $env !== false && $env !== '' ) {
				return $env;
			}
		}

		// 3. Database option.
		$options = get_option( self::OPTION_NAME, [] );
		if ( isset( $options[ $key ] ) && $options[ $key ] !== '' ) {
			return (string) $options[ $key ];
		}

		// 4. Default.
		return (string) $map[ 'default' ];
	}

	/**
	 * Check if a setting is locked by a constant or env var.
	 */
	private static function is_locked( string $key ): bool {
		$map = self::$config_map[ $key ] ?? null;
		if ( ! $map ) {
			return false;
		}

		if ( ! empty( $map[ 'const' ] ) && defined( $map[ 'const' ] ) ) {
			return true;
		}

		if ( ! empty( $map[ 'env' ] ) ) {
			$env = getenv( $map[ 'env' ] );
			if ( $env !== false && $env !== '' ) {
				return true;
			}
		}

		return false;
	}

	public function add_menu(): void {
		add_submenu_page(
			'edit.php?post_type=talking_head_episode',
			__( 'Talking Head Settings', 'talking-head' ),
			__( 'Settings', 'talking-head' ),
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'render_page' ]
		);
	}

	public function register_settings(): void {
		register_setting(
			self::PAGE_SLUG,
			self::OPTION_NAME,
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize_options' ],
			]
		);

		// TTS Provider section.
		add_settings_section(
			'th_provider',
			__( 'TTS Provider', 'talking-head' ),
			fn() => printf(
				'<p>%s</p>',
				esc_html__( 'Select which text-to-speech provider to use.', 'talking-head' )
			),
			self::PAGE_SLUG
		);

		$this->add_field(
			'tts_provider',
			__( 'Provider', 'talking-head' ),
			'th_provider',
			'select',
			[
				'openai'       => 'OpenAI',
				'azure_openai' => 'Azure OpenAI',
			]
		);
		$this->add_field(
			'default_voice',
			__( 'Default Voice', 'talking-head' ),
			'th_provider',
			'select',
			[
				'alloy'   => 'Alloy',
				'echo'    => 'Echo',
				'fable'   => 'Fable',
				'onyx'    => 'Onyx',
				'nova'    => 'Nova',
				'shimmer' => 'Shimmer',
			]
		);

		// OpenAI section.
		add_settings_section(
			'th_openai',
			__( 'OpenAI', 'talking-head' ),
			fn() => printf(
				'<p>%s</p>',
				esc_html__( 'Configure your OpenAI API credentials.', 'talking-head' )
			),
			self::PAGE_SLUG
		);

		$this->add_field( 'openai_api_key', __( 'API Key', 'talking-head' ), 'th_openai', 'password' );
		$this->add_field(
			'openai_tts_model',
			__( 'TTS Model', 'talking-head' ),
			'th_openai',
			'select',
			[
				'tts-1'           => 'TTS-1 (Standard)',
				'tts-1-hd'        => 'TTS-1-HD (High Quality)',
				'gpt-4o-mini-tts' => 'GPT-4o Mini TTS (Supports instructions)',
			]
		);

		// Azure OpenAI section.
		add_settings_section(
			'th_azure_openai',
			__( 'Azure OpenAI', 'talking-head' ),
			fn() => printf(
				'<p>%s</p>',
				esc_html__( 'Configure your Azure OpenAI API credentials.', 'talking-head' )
			),
			self::PAGE_SLUG
		);

		$this->add_field( 'azure_openai_endpoint', __( 'Endpoint', 'talking-head' ), 'th_azure_openai', 'text' );
		$this->add_field( 'azure_openai_api_key', __( 'API Key', 'talking-head' ), 'th_azure_openai', 'password' );
		$this->add_field( 'azure_openai_deployment_id', __( 'Deployment ID', 'talking-head' ), 'th_azure_openai', 'text' );
		$this->add_field( 'azure_openai_api_version', __( 'API Version', 'talking-head' ), 'th_azure_openai', 'text' );

		// Audio Processing section.
		add_settings_section(
			'th_audio',
			__( 'Audio Processing', 'talking-head' ),
			fn() => printf(
				'<p>%s</p>',
				esc_html__( 'Configure audio output settings. FFmpeg is optional — without it, audio stitching uses a PHP fallback (no loudness normalization or format conversion).', 'talking-head' )
			),
			self::PAGE_SLUG
		);

		$this->add_field( 'ffmpeg_path', __( 'FFmpeg Path', 'talking-head' ), 'th_audio', 'text' );
		$this->add_field(
			'output_format',
			__( 'Output Format', 'talking-head' ),
			'th_audio',
			'select',
			[
				'mp3' => 'MP3',
				'aac' => 'AAC',
			]
		);
		$this->add_field(
			'output_bitrate',
			__( 'Output Bitrate', 'talking-head' ),
			'th_audio',
			'select',
			[
				'128k' => '128 kbps',
				'192k' => '192 kbps',
				'256k' => '256 kbps',
				'320k' => '320 kbps',
			]
		);
		$this->add_field( 'silence_gap_ms', __( 'Silence Gap (ms)', 'talking-head' ), 'th_audio', 'number' );

		// Limits section.
		add_settings_section(
			'th_limits',
			__( 'Limits', 'talking-head' ),
			fn() => printf(
				'<p>%s</p>',
				esc_html__( 'Configure rate limits and content constraints.', 'talking-head' )
			),
			self::PAGE_SLUG
		);

		$this->add_field( 'max_segments', __( 'Max Segments per Episode', 'talking-head' ), 'th_limits', 'number' );
		$this->add_field( 'max_segment_chars', __( 'Max Characters per Segment', 'talking-head' ), 'th_limits', 'number' );
		$this->add_field( 'rate_limit_per_min', __( 'API Requests per Minute', 'talking-head' ), 'th_limits', 'number' );
	}

	private function add_field( string $key, string $label, string $section, string $type, array $choices = [] ): void {
		add_settings_field(
			'th_' . $key,
			$label,
			fn() => $this->render_field( $key, $type, $choices ),
			self::PAGE_SLUG,
			$section
		);
	}

	private function render_field( string $key, string $type, array $choices ): void {
		$value  = self::get( $key );
		$locked = self::is_locked( $key );
		$name   = self::OPTION_NAME . '[' . esc_attr( $key ) . ']';

		if ( $locked ) {
			printf(
				'<input type="text" value="%s" disabled class="regular-text" /><p class="description">%s</p>',
				esc_attr( $type === 'password' ? '••••••••' : $value ),
				esc_html__( 'Locked by constant or environment variable.', 'talking-head' )
			);
			return;
		}

		match ( $type ) {
			'password' => printf(
				'<input type="password" name="%s" value="%s" class="regular-text" autocomplete="off" />',
				esc_attr( $name ),
				esc_attr( $value )
			),
			'number'   => printf(
				'<input type="number" name="%s" value="%s" class="small-text" min="0" />',
				esc_attr( $name ),
				esc_attr( $value )
			),
			'select'   => $this->render_select( $name, $value, $choices ),
			default    => printf(
				'<input type="text" name="%s" value="%s" class="regular-text" />',
				esc_attr( $name ),
				esc_attr( $value )
			),
		};
	}

	private function render_select( string $name, string $current, array $choices ): void {
		echo '<select name="' . esc_attr( $name ) . '">';
		foreach ( $choices as $val => $label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $val ),
				selected( $current, $val, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
	}

	public function sanitize_options( array $input ): array {
		$sanitized = [];

		$sanitized[ 'tts_provider' ] = in_array( $input[ 'tts_provider' ] ?? '', [ 'openai', 'azure_openai' ], true )
			? $input[ 'tts_provider' ]
			: 'openai';

		$sanitized[ 'openai_api_key' ]   = sanitize_text_field( $input[ 'openai_api_key' ] ?? '' );
		$sanitized[ 'openai_tts_model' ] = in_array( $input[ 'openai_tts_model' ] ?? '', [ 'tts-1', 'tts-1-hd', 'gpt-4o-mini-tts' ], true )
			? $input[ 'openai_tts_model' ]
			: 'tts-1';
		$sanitized[ 'default_voice' ]    = in_array( $input[ 'default_voice' ] ?? '', [ 'alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer' ], true )
			? $input[ 'default_voice' ]
			: 'alloy';

		$ffmpeg_path = sanitize_text_field( $input[ 'ffmpeg_path' ] ?? '' );
		if ( $ffmpeg_path !== '' && ! is_executable( $ffmpeg_path ) ) {
			add_settings_error(
				self::OPTION_NAME,
				'ffmpeg_invalid',
				/* translators: %s: file path */
				sprintf( __( 'FFmpeg not found or not executable at: %s', 'talking-head' ), $ffmpeg_path ),
				'error'
			);
		}
		$sanitized[ 'ffmpeg_path' ] = $ffmpeg_path;

		$sanitized[ 'output_format' ]  = in_array( $input[ 'output_format' ] ?? '', [ 'mp3', 'aac' ], true )
			? $input[ 'output_format' ]
			: 'mp3';
		$sanitized[ 'output_bitrate' ] = in_array( $input[ 'output_bitrate' ] ?? '', [ '128k', '192k', '256k', '320k' ], true )
			? $input[ 'output_bitrate' ]
			: '192k';

		$sanitized[ 'silence_gap_ms' ]     = min( 5000, max( 0, absint( $input[ 'silence_gap_ms' ] ?? 500 ) ) );
		$sanitized[ 'max_segments' ]       = min( 200, max( 1, absint( $input[ 'max_segments' ] ?? 50 ) ) );
		$sanitized[ 'max_segment_chars' ]  = min( 4096, max( 100, absint( $input[ 'max_segment_chars' ] ?? 4096 ) ) );
		$sanitized[ 'rate_limit_per_min' ] = min( 60, max( 1, absint( $input[ 'rate_limit_per_min' ] ?? 10 ) ) );

		// Azure OpenAI settings.
		$sanitized[ 'azure_openai_api_key' ]       = sanitize_text_field( $input[ 'azure_openai_api_key' ] ?? '' );
		$sanitized[ 'azure_openai_endpoint' ]      = esc_url_raw( $input[ 'azure_openai_endpoint' ] ?? '' );
		$sanitized[ 'azure_openai_deployment_id' ] = sanitize_text_field( $input[ 'azure_openai_deployment_id' ] ?? '' );
		$sanitized[ 'azure_openai_api_version' ]   = sanitize_text_field( $input[ 'azure_openai_api_version' ] ?? '' );

		return $sanitized;
	}

	private const TABS = [
		'provider' => [
			'sections' => [ 'th_provider', 'th_openai', 'th_azure_openai' ],
			'keys'     => [
				'tts_provider', 'default_voice',
				'openai_api_key', 'openai_tts_model',
				'azure_openai_endpoint', 'azure_openai_api_key',
				'azure_openai_deployment_id', 'azure_openai_api_version',
			],
		],
		'audio'    => [
			'sections' => [ 'th_audio' ],
			'keys'     => [ 'ffmpeg_path', 'output_format', 'output_bitrate', 'silence_gap_ms' ],
		],
		'limits'   => [
			'sections' => [ 'th_limits' ],
			'keys'     => [ 'max_segments', 'max_segment_chars', 'rate_limit_per_min' ],
		],
	];

	private static function tab_labels(): array {
		return [
			'provider' => __( 'Provider', 'talking-head' ),
			'audio'    => __( 'Audio', 'talking-head' ),
			'limits'   => __( 'Limits', 'talking-head' ),
		];
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'provider';
		if ( ! isset( self::TABS[ $active_tab ] ) ) {
			$active_tab = 'provider';
		}

		$base_url = admin_url( 'edit.php?post_type=talking_head_episode&page=' . self::PAGE_SLUG );

		echo '<div class="wrap">';
		printf( '<h1>%s</h1>', esc_html( get_admin_page_title() ) );

		// Nav tabs.
		$tab_labels = self::tab_labels();
		echo '<nav class="nav-tab-wrapper">';
		foreach ( self::TABS as $slug => $tab ) {
			$url   = add_query_arg( 'tab', $slug, $base_url );
			$class = ( $slug === $active_tab ) ? 'nav-tab nav-tab-active' : 'nav-tab';
			printf(
				'<a href="%s" class="%s">%s</a>',
				esc_url( $url ),
				esc_attr( $class ),
				esc_html( $tab_labels[ $slug ] )
			);
		}
		echo '</nav>';

		echo '<form action="options.php" method="post">';
		settings_fields( self::PAGE_SLUG );

		// Render sections for the active tab.
		if ( $active_tab === 'provider' ) {
			$this->render_section( 'th_provider' );
			echo '<div id="th-section-openai" class="th-provider-section">';
			$this->render_section( 'th_openai' );
			echo '</div>';
			echo '<div id="th-section-azure-openai" class="th-provider-section">';
			$this->render_section( 'th_azure_openai' );
			echo '</div>';
		} else {
			foreach ( self::TABS[ $active_tab ]['sections'] as $section_id ) {
				$this->render_section( $section_id );
			}
		}

		// Render hidden fields for inactive tabs so their values are preserved.
		foreach ( self::TABS as $slug => $tab ) {
			if ( $slug === $active_tab ) {
				continue;
			}
			foreach ( $tab['keys'] as $key ) {
				if ( self::is_locked( $key ) ) {
					continue;
				}
				printf(
					'<input type="hidden" name="%s" value="%s" />',
					esc_attr( self::OPTION_NAME . '[' . $key . ']' ),
					esc_attr( self::get( $key ) )
				);
			}
		}

		submit_button();
		echo '</form>';
		echo '</div>';

		if ( $active_tab === 'provider' ) {
			$this->render_toggle_script();
		}
	}

	/**
	 * Render a single settings section with its fields.
	 */
	private function render_section( string $section_id ): void {
		global $wp_settings_sections, $wp_settings_fields;

		$section = $wp_settings_sections[ self::PAGE_SLUG ][ $section_id ] ?? null;
		if ( ! $section ) {
			return;
		}

		if ( $section[ 'title' ] ) {
			echo '<h2>' . esc_html( $section[ 'title' ] ) . '</h2>';
		}

		if ( $section[ 'callback' ] ) {
			call_user_func( $section[ 'callback' ], $section );
		}

		if ( ! isset( $wp_settings_fields[ self::PAGE_SLUG ][ $section_id ] ) ) {
			return;
		}

		echo '<table class="form-table" role="presentation">';
		do_settings_fields( self::PAGE_SLUG, $section_id );
		echo '</table>';
	}

	/**
	 * Inline JS to toggle provider sections based on the selector value.
	 */
	private function render_toggle_script(): void {
		?>
		<script>
			(function () {
				var select = document.querySelector('select[name="talking_head_options[tts_provider]"]');
				var openai = document.getElementById('th-section-openai');
				var azure = document.getElementById('th-section-azure-openai');

				if (!select || !openai || !azure) return;

				function toggle() {
					var val = select.value;
					openai.style.display = val === 'openai' ? '' : 'none';
					azure.style.display = val === 'azure_openai' ? '' : 'none';
				}

				select.addEventListener('change', toggle);
				toggle();
			})();
		</script>
		<?php
	}
}
