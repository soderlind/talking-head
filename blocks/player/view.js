/**
 * Front-end script for the Talking Head Player block.
 * Enhances the native <audio> element and loads transcript data.
 * Supports both file mode (single MP3) and virtual mode (sequential segment playback).
 */
document.addEventListener( 'DOMContentLoaded', () => {
	const players = document.querySelectorAll( '.th-player' );

	// Get REST API root URL from WordPress settings or derive from link tag.
	const getRestUrl = () => {
		if ( typeof wpApiSettings !== 'undefined' && wpApiSettings.root ) {
			return wpApiSettings.root;
		}
		// Fallback: look for REST API link in head.
		const link = document.querySelector( 'link[rel="https://api.w.org/"]' );
		if ( link ) {
			return link.href;
		}
		// Last resort: assume standard path.
		return '/wp-json/';
	};

	const restRoot = getRestUrl();

	players.forEach( ( player ) => {
		const episodeId = player.dataset.episodeId;
		const stitchingMode = player.dataset.stitchingMode || 'file';
		const transcriptEl = player.querySelector(
			'.th-player__transcript'
		);
		const audioEl = player.querySelector( '.th-player__audio' );

		if ( ! episodeId ) {
			return;
		}

		const apiUrl = restRoot.replace( /\/$/, '' ) + '/talking-head/v1/episodes/' + episodeId + '/player';

		if ( stitchingMode === 'virtual' ) {
			initVirtualPlayer( player, audioEl, apiUrl, transcriptEl );
		} else {
			initTranscript( apiUrl, transcriptEl );
		}
	} );

	/**
	 * Load and render the transcript for file-mode players.
	 */
	function initTranscript( apiUrl, transcriptEl ) {
		if ( ! transcriptEl ) {
			return;
		}

		fetch( apiUrl )
			.then( ( response ) => response.json() )
			.then( ( data ) => {
				renderTranscript( transcriptEl, data.transcript || [] );
			} )
			.catch( () => {} );
	}

	/**
	 * Initialize a virtual-mode player that plays segments sequentially.
	 */
	function initVirtualPlayer( player, audioEl, apiUrl, transcriptEl ) {
		if ( ! audioEl ) {
			return;
		}

		fetch( apiUrl )
			.then( ( response ) => response.json() )
			.then( ( data ) => {
				if ( ! data.segments || data.segments.length === 0 ) {
					return;
				}

				if ( transcriptEl ) {
					renderTranscript( transcriptEl, data.transcript || [] );
				}

				setupSegmentPlayer( player, audioEl, data.segments, data.silenceGapMs || 0, transcriptEl );
			} )
			.catch( () => {} );
	}

	/**
	 * Wire up the audio element to play segments sequentially.
	 */
	function setupSegmentPlayer( player, audioEl, segments, silenceGapMs, transcriptEl ) {
		let currentIndex = 0;
		let isPlaying = false;
		let silenceTimer = null;

		function loadSegment( index ) {
			if ( index >= segments.length ) {
				// All segments played -- reset to beginning.
				currentIndex = 0;
				isPlaying = false;
				highlightTranscriptTurn( transcriptEl, -1 );
				return;
			}

			currentIndex = index;
			audioEl.src = segments[ index ].url;
			audioEl.load();
			highlightTranscriptTurn( transcriptEl, index );
		}

		function playNext() {
			const nextIndex = currentIndex + 1;
			if ( nextIndex >= segments.length ) {
				// Finished all segments.
				currentIndex = 0;
				isPlaying = false;
				highlightTranscriptTurn( transcriptEl, -1 );
				return;
			}

			if ( silenceGapMs > 0 ) {
				silenceTimer = setTimeout( () => {
					silenceTimer = null;
					loadSegment( nextIndex );
					audioEl.play().catch( () => {} );
				}, silenceGapMs );
			} else {
				loadSegment( nextIndex );
				audioEl.play().catch( () => {} );
			}
		}

		// When a segment finishes, play the next one after the silence gap.
		audioEl.addEventListener( 'ended', () => {
			if ( isPlaying ) {
				playNext();
			}
		} );

		// Track play/pause state.
		audioEl.addEventListener( 'play', () => {
			isPlaying = true;
		} );

		audioEl.addEventListener( 'pause', () => {
			if ( ! audioEl.ended ) {
				isPlaying = false;
				if ( silenceTimer ) {
					clearTimeout( silenceTimer );
					silenceTimer = null;
				}
			}
		} );

		// Load the first segment so the player shows duration and is ready.
		loadSegment( 0 );
	}

	/**
	 * Render transcript as a definition list.
	 */
	function renderTranscript( container, transcript ) {
		if ( ! container || ! transcript || transcript.length === 0 ) {
			return;
		}

		const list = document.createElement( 'dl' );
		list.className = 'th-player__transcript-list';

		transcript.forEach( ( turn, index ) => {
			const dt = document.createElement( 'dt' );
			dt.className = 'th-player__transcript-speaker';
			dt.textContent = turn.speaker;
			dt.dataset.turnIndex = index;

			const dd = document.createElement( 'dd' );
			dd.className = 'th-player__transcript-text';
			dd.textContent = turn.text;
			dd.dataset.turnIndex = index;

			list.appendChild( dt );
			list.appendChild( dd );
		} );

		container.appendChild( list );
	}

	/**
	 * Highlight the currently playing transcript turn.
	 */
	function highlightTranscriptTurn( container, activeIndex ) {
		if ( ! container ) {
			return;
		}

		const items = container.querySelectorAll( '[data-turn-index]' );
		items.forEach( ( el ) => {
			const idx = parseInt( el.dataset.turnIndex, 10 );
			el.classList.toggle( 'th-player__transcript--active', idx === activeIndex );
		} );
	}
} );
