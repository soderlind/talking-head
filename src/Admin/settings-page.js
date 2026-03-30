/**
 * Settings page toggle scripts.
 *
 * @package TalkingHead
 */

( function ( root ) {
	/**
	 * Initialize the provider section toggle.
	 */
	function initProviderToggle() {
		const select = document.querySelector( 'select[name="talking_head_options[tts_provider]"]' );
		const openai = document.getElementById( 'th-section-openai' );
		const azure = document.getElementById( 'th-section-azure-openai' );
		const wordpress = document.getElementById( 'th-section-wordpress' );

		if ( ! select || ! openai || ! azure ) {
			return;
		}

		function toggle() {
			const val = select.value;
			[ openai, azure, wordpress ].forEach( function ( el ) {
				if ( el ) {
					el.classList.remove( 'is-active' );
				}
			} );

			const active = {
				openai: openai,
				azure_openai: azure,
				wordpress: wordpress,
			}[ val ];

			if ( active ) {
				active.classList.add( 'is-active' );
			}
		}

		select.addEventListener( 'change', toggle );
		toggle();
	}

	/**
	 * Initialize the stitching mode toggle — hide FFmpeg Path when virtual is selected.
	 */
	function initStitchingToggle() {
		const select = document.querySelector( 'select[name="talking_head_options[stitching_mode]"]' );
		if ( ! select ) {
			return;
		}

		const ffmpegInput = document.querySelector( 'input[name="talking_head_options[ffmpeg_path]"]' );
		const ffmpegRow = ffmpegInput ? ffmpegInput.closest( 'tr' ) : null;

		if ( ! ffmpegRow ) {
			return;
		}

		function toggle() {
			ffmpegRow.style.display = select.value === 'virtual' ? 'none' : '';
		}

		select.addEventListener( 'change', toggle );
		toggle();
	}

	// Expose for testing
	if ( typeof root !== 'undefined' ) {
		root.TalkingHeadSettings = { initProviderToggle, initStitchingToggle };
	}

	// Auto-initialize
	initProviderToggle();
	initStitchingToggle();
}( typeof window !== 'undefined' ? window : this ) );
