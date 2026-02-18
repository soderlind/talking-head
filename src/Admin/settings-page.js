/**
 * Settings page provider toggle script.
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

	// Expose for testing
	if ( typeof root !== 'undefined' ) {
		root.TalkingHeadSettings = { initProviderToggle };
	}

	// Auto-initialize
	initProviderToggle();
}( typeof window !== 'undefined' ? window : this ) );
