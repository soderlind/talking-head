/**
 * Front-end script for the Talking Head Player block.
 * Enhances the native <audio> element and loads transcript data.
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
		const transcriptEl = player.querySelector(
			'.th-player__transcript'
		);

		if ( ! transcriptEl || ! episodeId ) {
			return;
		}

		// Fetch transcript from REST API.
		const apiUrl = restRoot.replace( /\/$/, '' ) + '/talking-head/v1/episodes/' + episodeId + '/player';
		fetch( apiUrl )
			.then( ( response ) => response.json() )
			.then( ( data ) => {
				if ( ! data.transcript || data.transcript.length === 0 ) {
					return;
				}

				const list = document.createElement( 'dl' );
				list.className = 'th-player__transcript-list';

				data.transcript.forEach( ( turn ) => {
					const dt = document.createElement( 'dt' );
					dt.className = 'th-player__transcript-speaker';
					dt.textContent = turn.speaker;

					const dd = document.createElement( 'dd' );
					dd.className = 'th-player__transcript-text';
					dd.textContent = turn.text;

					list.appendChild( dt );
					list.appendChild( dd );
				} );

				transcriptEl.appendChild( list );
			} )
			.catch( () => {} );
	} );
} );
