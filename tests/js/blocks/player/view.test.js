import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

describe( 'Player View (front-end transcript)', () => {
	let originalFetch;

	beforeEach( () => {
		originalFetch = globalThis.fetch;
		document.body.innerHTML = '';
	} );

	afterEach( () => {
		globalThis.fetch = originalFetch;
	} );

	function setupDOM( episodeId, includeTranscript = true ) {
		const player = document.createElement( 'div' );
		player.className = 'th-player';
		player.dataset.episodeId = episodeId;

		if ( includeTranscript ) {
			const transcript = document.createElement( 'div' );
			transcript.className = 'th-player__transcript';
			player.appendChild( transcript );
		}

		document.body.appendChild( player );
		return player;
	}

	async function loadViewScript() {
		vi.resetModules();
		await import( '../../../../blocks/player/view.js' );
		document.dispatchEvent( new Event( 'DOMContentLoaded' ) );
	}

	it( 'builds transcript dl from fetched data', async () => {
		setupDOM( '5' );

		globalThis.fetch = vi.fn().mockResolvedValue( {
			json: () =>
				Promise.resolve( {
					transcript: [
						{ speaker: 'Alice', text: 'Hello there.' },
						{ speaker: 'Bob', text: 'Hi Alice!' },
					],
				} ),
		} );

		await loadViewScript();

		await vi.waitFor( () => {
			expect( document.querySelector( '.th-player__transcript-list' ) ).not.toBeNull();
		} );

		const dl = document.querySelector( '.th-player__transcript-list' );
		expect( dl.tagName ).toBe( 'DL' );

		const dts = dl.querySelectorAll( 'dt' );
		expect( dts ).toHaveLength( 2 );
		expect( dts[ 0 ].textContent ).toBe( 'Alice' );
		expect( dts[ 1 ].textContent ).toBe( 'Bob' );

		const dds = dl.querySelectorAll( 'dd' );
		expect( dds ).toHaveLength( 2 );
		expect( dds[ 0 ].textContent ).toBe( 'Hello there.' );
		expect( dds[ 1 ].textContent ).toBe( 'Hi Alice!' );
	} );

	it( 'does nothing when transcript is empty', async () => {
		setupDOM( '5' );

		globalThis.fetch = vi.fn().mockResolvedValue( {
			json: () => Promise.resolve( { transcript: [] } ),
		} );

		await loadViewScript();
		// Allow microtasks to settle.
		await new Promise( ( r ) => setTimeout( r, 10 ) );

		const dl = document.querySelector( '.th-player__transcript-list' );
		expect( dl ).toBeNull();
	} );

	it( 'handles fetch error gracefully', async () => {
		setupDOM( '5' );

		globalThis.fetch = vi.fn().mockRejectedValue( new Error( 'Network error' ) );

		await loadViewScript();
		await new Promise( ( r ) => setTimeout( r, 10 ) );

		const dl = document.querySelector( '.th-player__transcript-list' );
		expect( dl ).toBeNull();
	} );

	it( 'skips players without transcript element', async () => {
		setupDOM( '5', false );

		globalThis.fetch = vi.fn();

		await loadViewScript();

		expect( globalThis.fetch ).not.toHaveBeenCalled();
	} );
} );
