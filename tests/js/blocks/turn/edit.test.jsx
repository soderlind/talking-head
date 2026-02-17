import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import Edit from '../../../../blocks/turn/edit';

describe( 'Turn Edit', () => {
	const setAttributes = vi.fn();

	beforeEach( () => {
		vi.clearAllMocks();
		wp.apiFetch.mockResolvedValue( [
			{ id: 1, name: 'Alice' },
			{ id: 2, name: 'Bob' },
		] );
	} );

	it( 'shows speaker dropdown when no head is selected', () => {
		render(
			<Edit
				attributes={ { headId: 0, headName: '', text: '' } }
				setAttributes={ setAttributes }
			/>
		);

		expect( screen.getByTestId( 'SelectControl' ) ).toBeInTheDocument();
	} );

	it( 'shows speaker name button when head is selected', () => {
		render(
			<Edit
				attributes={ { headId: 1, headName: 'Alice', text: 'Hello' } }
				setAttributes={ setAttributes }
			/>
		);

		expect( screen.getByText( 'Alice' ) ).toBeInTheDocument();
		expect( screen.getByRole( 'button', { name: 'Alice' } ) ).toBeInTheDocument();
	} );

	it( 'renders RichText for dialogue', () => {
		render(
			<Edit
				attributes={ { headId: 1, headName: 'Alice', text: 'Hello world' } }
				setAttributes={ setAttributes }
			/>
		);

		expect( screen.getByTestId( 'RichText' ) ).toBeInTheDocument();
	} );

	it( 'fetches heads on mount', () => {
		render(
			<Edit
				attributes={ { headId: 0, headName: '', text: '' } }
				setAttributes={ setAttributes }
			/>
		);

		expect( wp.apiFetch ).toHaveBeenCalledWith( {
			path: '/talking-head/v1/heads',
		} );
	} );
} );
