import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import Save from '../../../../blocks/turn/save';

describe( 'Turn Save', () => {
	it( 'renders speaker name in a span', () => {
		render(
			<Save attributes={ { headName: 'Alice', text: '<p>Hello</p>' } } />
		);

		expect( screen.getByText( 'Alice' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Alice' ).className ).toBe( 'th-turn__speaker' );
	} );

	it( 'renders text via RichText.Content', () => {
		render(
			<Save attributes={ { headName: 'Bob', text: '<p>Hi there</p>' } } />
		);

		expect( screen.getByTestId( 'RichText.Content' ) ).toBeInTheDocument();
	} );

	it( 'applies th-turn class to wrapper', () => {
		const { container } = render(
			<Save attributes={ { headName: 'Alice', text: 'Test' } } />
		);

		expect( container.querySelector( '.th-turn' ) ).toBeInTheDocument();
	} );
} );
