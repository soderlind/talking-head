<?php

declare(strict_types=1);

namespace TalkingHead\Admin;

use TalkingHead\CPT\HeadCPT;

defined( 'ABSPATH' ) || exit;

final class HeadMetaBox {

	private const NONCE_ACTION = 'talking_head_save_head_meta';
	private const NONCE_NAME   = '_th_head_meta_nonce';

	private const VOICE_OPTIONS = [
		'alloy'   => 'Alloy',
		'echo'    => 'Echo',
		'fable'   => 'Fable',
		'onyx'    => 'Onyx',
		'nova'    => 'Nova',
		'shimmer' => 'Shimmer',
	];

	private const PROVIDER_OPTIONS = [
		'openai'       => 'OpenAI',
		'azure_openai' => 'Azure OpenAI',
	];

	public function register(): void {
		add_action( 'add_meta_boxes_' . HeadCPT::POST_TYPE, [ $this, 'add_meta_box' ] );
		add_action( 'save_post_' . HeadCPT::POST_TYPE, [ $this, 'save' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_preview_script' ] );
	}

	public function enqueue_preview_script(): void {
		$screen = get_current_screen();
		if ( ! $screen || $screen->post_type !== HeadCPT::POST_TYPE ) {
			return;
		}

		// Skip in block editor — the sidebar JS handles preview there.
		if ( $screen->is_block_editor() ) {
			return;
		}

		wp_enqueue_script(
			'talking-head-voice-preview',
			TALKING_HEAD_URL . 'src/Admin/voice-preview.js',
			[],
			filemtime( TALKING_HEAD_DIR . 'src/Admin/voice-preview.js' ),
			true
		);

		wp_localize_script(
			'talking-head-voice-preview',
			'talkingHeadVoiceSamples',
			HeadEditorAssets::voice_sample_urls()
		);
	}

	public function add_meta_box(): void {
		add_meta_box(
			'talking-head-voice-settings',
			__( 'Voice Settings', 'talking-head' ),
			[ $this, 'render' ],
			HeadCPT::POST_TYPE,
			'normal',
			'high'
		);
	}

	public function render( \WP_Post $post ): void {
		$voice_id       = get_post_meta( $post->ID, HeadCPT::META_KEY_VOICE_ID, true ) ?: 'alloy';
		$provider       = get_post_meta( $post->ID, HeadCPT::META_KEY_PROVIDER, true ) ?: 'openai';
		$speed          = (float) ( get_post_meta( $post->ID, HeadCPT::META_KEY_SPEED, true ) ?: 1.0 );
		$speaking_style = get_post_meta( $post->ID, HeadCPT::META_KEY_SPEAKING_STYLE, true ) ?: '';

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="th-voice-id"><?php esc_html_e( 'Voice', 'talking-head' ); ?></label>
				</th>
				<td>
					<select id="th-voice-id" name="<?php echo esc_attr( HeadCPT::META_KEY_VOICE_ID ); ?>">
						<?php foreach ( self::VOICE_OPTIONS as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $voice_id, $value ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="th-provider"><?php esc_html_e( 'Provider', 'talking-head' ); ?></label>
				</th>
				<td>
					<select id="th-provider" name="<?php echo esc_attr( HeadCPT::META_KEY_PROVIDER ); ?>">
						<?php foreach ( self::PROVIDER_OPTIONS as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $provider, $value ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="th-speed"><?php esc_html_e( 'Speed', 'talking-head' ); ?></label>
				</th>
				<td>
					<input type="number" id="th-speed" name="<?php echo esc_attr( HeadCPT::META_KEY_SPEED ); ?>"
						value="<?php echo esc_attr( (string) $speed ); ?>" min="0.25" max="4.0" step="0.05"
						class="small-text" />
					<p class="description">
						<?php esc_html_e( 'Playback speed (0.25 – 4.0). Default is 1.0.', 'talking-head' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label
						for="th-speaking-style"><?php esc_html_e( 'Speaking Style / Instructions', 'talking-head' ); ?></label>
				</th>
				<td>
					<textarea id="th-speaking-style" name="<?php echo esc_attr( HeadCPT::META_KEY_SPEAKING_STYLE ); ?>" rows="4"
						class="large-text"><?php echo esc_textarea( $speaking_style ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Instructions for the TTS model (requires gpt-4o-mini-tts).', 'talking-head' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	public function save( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST[ self::NONCE_NAME ], self::NONCE_ACTION ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = [
			HeadCPT::META_KEY_VOICE_ID,
			HeadCPT::META_KEY_PROVIDER,
			HeadCPT::META_KEY_SPEED,
			HeadCPT::META_KEY_SPEAKING_STYLE,
		];

		foreach ( $fields as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				// Sanitization is handled by register_post_meta sanitize_callback.
				update_post_meta( $post_id, $key, $_POST[ $key ] );
			}
		}
	}
}
