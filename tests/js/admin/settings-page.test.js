import { describe, it, expect, afterEach, beforeAll } from 'vitest';

// Load the script once to expose window.TalkingHeadSettings
beforeAll( async () => {
	await import( '../../../src/Admin/settings-page.js' );
} );

describe( 'Settings Page Stitching Toggle', () => {
	let select, fileSection;

	const setupDOM = ( initialValue = 'file' ) => {
		document.body.innerHTML = `
			<select name="talking_head_options[stitching_mode]">
				<option value="file">File</option>
				<option value="virtual">Virtual</option>
			</select>
			<div id="th-stitching-file-section"></div>
		`;
		select = document.querySelector( 'select[name="talking_head_options[stitching_mode]"]' );
		select.value = initialValue;
		fileSection = document.getElementById( 'th-stitching-file-section' );
	};

	afterEach( () => {
		document.body.innerHTML = '';
	} );

	it( 'shows file section when stitching mode is file', () => {
		setupDOM( 'file' );
		window.TalkingHeadSettings.initStitchingToggle();

		expect( fileSection.classList.contains( 'is-active' ) ).toBe( true );
	} );

	it( 'hides file section when stitching mode is virtual', () => {
		setupDOM( 'virtual' );
		window.TalkingHeadSettings.initStitchingToggle();

		expect( fileSection.classList.contains( 'is-active' ) ).toBe( false );
	} );

	it( 'toggles sections when select value changes', () => {
		setupDOM( 'file' );
		window.TalkingHeadSettings.initStitchingToggle();

		// Initial state
		expect( fileSection.classList.contains( 'is-active' ) ).toBe( true );

		// Change to Virtual
		select.value = 'virtual';
		select.dispatchEvent( new Event( 'change' ) );

		expect( fileSection.classList.contains( 'is-active' ) ).toBe( false );

		// Change back to File
		select.value = 'file';
		select.dispatchEvent( new Event( 'change' ) );

		expect( fileSection.classList.contains( 'is-active' ) ).toBe( true );
	} );

	it( 'does nothing if elements are missing', () => {
		document.body.innerHTML = '<div>No settings form</div>';

		// Should not throw
		expect( () => window.TalkingHeadSettings.initStitchingToggle() ).not.toThrow();
	} );
} );
