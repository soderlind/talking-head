import { describe, it, expect } from 'vitest';
import Save from '../../../../blocks/player/save';

describe( 'Player Save', () => {
	it( 'returns null (dynamic server-rendered block)', () => {
		expect( Save() ).toBeNull();
	} );
} );
