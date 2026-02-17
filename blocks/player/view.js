/**
 * Front-end script for the Talking Head Player block.
 * Enhances the native <audio> element and loads transcript data.
 */
document.addEventListener( 'DOMContentLoaded', () => {
	const players = document.querySelectorAll( '.th-player' );

	players.forEach( ( player ) => {
		const episodeId = player.dataset.episodeId;
		const transcriptEl = player.querySelector(
			'.th-player__transcript'
		);

		if ( ! transcriptEl || ! episodeId ) {
			return;
		}

		// Fetch transcript from REST API.
		fetch( `/wp-json/talking-head/v1/episodes/${ episodeId }/player` )
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
