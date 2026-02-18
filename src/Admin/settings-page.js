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

		if ( ! select || ! openai || ! azure ) {
			return;
		}

		function toggle() {
			const val = select.value;
			openai.style.display = val === 'openai' ? '' : 'none';
			azure.style.display = val === 'azure_openai' ? '' : 'none';
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
