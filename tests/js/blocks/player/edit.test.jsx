import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import { useSelect } from '@wordpress/data';
import Edit from '../../../../blocks/player/edit';

describe( 'Player Edit', () => {
	const setAttributes = vi.fn();

	beforeEach( () => {
		vi.clearAllMocks();
		wp.apiFetch.mockReset();
	} );

	it( 'shows placeholder when no episode is selected', () => {
		vi.mocked( useSelect ).mockReturnValue( null );

		render(
			<Edit
				attributes={ { episodeId: 0, showTranscript: false } }
				setAttributes={ setAttributes }
			/>
		);

		expect( screen.getByText( 'Talking Head Player' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'Select an episode in the block settings to display the player.' )
		).toBeInTheDocument();
	} );

	it( 'renders episode preview when episode data is loaded', async () => {
		vi.mocked( useSelect ).mockReturnValue( null );
		wp.apiFetch.mockResolvedValue( {
			title: 'My Test Episode',
			audioUrl: 'https://example.com/audio.mp3',
		} );

		render(
			<Edit
				attributes={ { episodeId: 42, showTranscript: false } }
				setAttributes={ setAttributes }
			/>
		);

		const title = await screen.findByText( 'My Test Episode' );
		expect( title ).toBeInTheDocument();

		const audio = document.querySelector( 'audio' );
		expect( audio ).not.toBeNull();
		expect( audio.src ).toBe( 'https://example.com/audio.mp3' );
	} );

	it( 'shows "Audio not yet generated" when episode has no audio URL', async () => {
		vi.mocked( useSelect ).mockReturnValue( null );
		wp.apiFetch.mockResolvedValue( {
			title: 'Draft Episode',
			audioUrl: '',
		} );

		render(
			<Edit
				attributes={ { episodeId: 10, showTranscript: false } }
				setAttributes={ setAttributes }
			/>
		);

		expect( await screen.findByText( 'Audio not yet generated.' ) ).toBeInTheDocument();
	} );

	it( 'shows "Episode not found" when API fetch fails', async () => {
		vi.mocked( useSelect ).mockReturnValue( null );
		wp.apiFetch.mockRejectedValue( new Error( 'Not found' ) );

		render(
			<Edit
				attributes={ { episodeId: 999, showTranscript: false } }
				setAttributes={ setAttributes }
			/>
		);

		expect( await screen.findByText( 'Episode not found' ) ).toBeInTheDocument();
	} );

	it( 'builds episode options from useSelect results', () => {
		const episodes = [
			{ id: 1, title: { rendered: 'Episode One' } },
			{ id: 2, title: { rendered: 'Episode Two' } },
		];

		// useSelect is called once - for getEntityRecords
		vi.mocked( useSelect ).mockReturnValue( episodes );

		render(
			<Edit
				attributes={ { episodeId: 0, showTranscript: false } }
				setAttributes={ setAttributes }
			/>
		);

		// ComboboxControl should be rendered
		expect( screen.getByTestId( 'ComboboxControl' ) ).toBeInTheDocument();
	} );

	it( 'renders ToggleControl for transcript', () => {
		vi.mocked( useSelect ).mockReturnValue( null );

		render(
			<Edit
				attributes={ { episodeId: 0, showTranscript: true } }
				setAttributes={ setAttributes }
			/>
		);

		expect( screen.getByTestId( 'ToggleControl' ) ).toBeInTheDocument();
	} );
} );
