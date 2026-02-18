/**
 * Voice preview for the classic meta box.
 *
 * @package
 */

( function () {
	/* global talkingHeadVoiceSamples */
	const samples =
		typeof talkingHeadVoiceSamples !== 'undefined'
			? talkingHeadVoiceSamples
			: {};

	let audio = null;
	let playing = false;

	function init() {
		const select = document.getElementById( 'th-voice-id' );
		if ( ! select ) {
			return;
		}

		const btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.className = 'button button-small';
		btn.style.marginLeft = '8px';
		btn.textContent = '\u25B6 Preview';
		select.parentNode.insertBefore( btn, select.nextSibling );

		function stop() {
			if ( audio ) {
				audio.pause();
				audio.currentTime = 0;
			}
			playing = false;
			btn.textContent = '\u25B6 Preview';
		}

		btn.addEventListener( 'click', function () {
			const url = samples[ select.value ];
			if ( ! url ) {
				return;
			}

			if ( playing ) {
				stop();
				return;
			}

			if ( ! audio ) {
				audio = new Audio(); // eslint-disable-line no-undef
				audio.addEventListener( 'ended', function () {
					playing = false;
					btn.textContent = '\u25B6 Preview';
				} );
			}

			audio.src = url;
			audio.play();
			playing = true;
			btn.textContent = '\u23F9 Stop';
		} );

		select.addEventListener( 'change', stop );
	}

	init();
} )();
