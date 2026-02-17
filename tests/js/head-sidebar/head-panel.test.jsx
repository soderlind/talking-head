import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import { useSelect, useDispatch } from '@wordpress/data';
import { HeadPanel } from '../../../src/head-sidebar/head-panel';

describe( 'HeadPanel', () => {
	const editPost = vi.fn();

	beforeEach( () => {
		vi.clearAllMocks();
		vi.mocked( useDispatch ).mockReturnValue( { editPost } );
	} );

	it( 'returns null for non-head post types', () => {
		vi.mocked( useSelect ).mockImplementation( ( fn ) =>
			fn( () => ( {
				getCurrentPostType: () => 'post',
				getEditedPostAttribute: () => ( {} ),
			} ) )
		);

		const { container } = render( <HeadPanel /> );
		expect( container.innerHTML ).toBe( '' );
	} );

	it( 'renders Voice Settings panel for talking_head_head post type', () => {
		vi.mocked( useSelect ).mockImplementation( ( fn ) =>
			fn( () => ( {
				getCurrentPostType: () => 'talking_head_head',
				getEditedPostAttribute: () => ( {
					_th_voice_id: 'nova',
					_th_provider: 'openai',
					_th_speed: 1.0,
					_th_speaking_style: '',
				} ),
			} ) )
		);

		render( <HeadPanel /> );

		expect( screen.getByTestId( 'PluginDocumentSettingPanel' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'PluginDocumentSettingPanel' ).dataset.title ).toBe( 'Voice Settings' );
	} );

	it( 'renders all four controls', () => {
		vi.mocked( useSelect ).mockImplementation( ( fn ) =>
			fn( () => ( {
				getCurrentPostType: () => 'talking_head_head',
				getEditedPostAttribute: () => ( {} ),
			} ) )
		);

		render( <HeadPanel /> );

		const controls = screen.getAllByTestId( 'SelectControl' );
		expect( controls ).toHaveLength( 2 ); // Voice + Provider

		expect( screen.getByTestId( 'RangeControl' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'TextareaControl' ) ).toBeInTheDocument();
	} );

	it( 'shows Voice and Provider labels', () => {
		vi.mocked( useSelect ).mockImplementation( ( fn ) =>
			fn( () => ( {
				getCurrentPostType: () => 'talking_head_head',
				getEditedPostAttribute: () => ( {} ),
			} ) )
		);

		render( <HeadPanel /> );

		expect( screen.getByText( 'Voice' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Provider' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Speed' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Speaking Style / Instructions' ) ).toBeInTheDocument();
	} );
} );
