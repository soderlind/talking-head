import { describe, it, expect, afterEach, beforeAll } from 'vitest';

// Load the script once to expose window.TalkingHeadSettings
beforeAll( async () => {
	await import( '../../../src/Admin/settings-page.js' );
} );

describe( 'Settings Page Provider Toggle', () => {
	let select, openaiSection, azureSection;

	const setupDOM = ( initialValue = 'openai' ) => {
		document.body.innerHTML = `
			<select name="talking_head_options[tts_provider]">
				<option value="openai">OpenAI</option>
				<option value="azure_openai">Azure OpenAI</option>
			</select>
			<div id="th-section-openai"></div>
			<div id="th-section-azure-openai"></div>
		`;
		select = document.querySelector( 'select[name="talking_head_options[tts_provider]"]' );
		select.value = initialValue;
		openaiSection = document.getElementById( 'th-section-openai' );
		azureSection = document.getElementById( 'th-section-azure-openai' );
	};

	afterEach( () => {
		document.body.innerHTML = '';
	} );

	it( 'shows OpenAI section and hides Azure when provider is openai', () => {
		setupDOM( 'openai' );
		window.TalkingHeadSettings.initProviderToggle();

		expect( openaiSection.style.display ).toBe( '' );
		expect( azureSection.style.display ).toBe( 'none' );
	} );

	it( 'shows Azure section and hides OpenAI when provider is azure_openai', () => {
		setupDOM( 'azure_openai' );
		window.TalkingHeadSettings.initProviderToggle();

		expect( openaiSection.style.display ).toBe( 'none' );
		expect( azureSection.style.display ).toBe( '' );
	} );

	it( 'toggles sections when select value changes', () => {
		setupDOM( 'openai' );
		window.TalkingHeadSettings.initProviderToggle();

		// Initial state
		expect( openaiSection.style.display ).toBe( '' );
		expect( azureSection.style.display ).toBe( 'none' );

		// Change to Azure
		select.value = 'azure_openai';
		select.dispatchEvent( new Event( 'change' ) );

		expect( openaiSection.style.display ).toBe( 'none' );
		expect( azureSection.style.display ).toBe( '' );

		// Change back to OpenAI
		select.value = 'openai';
		select.dispatchEvent( new Event( 'change' ) );

		expect( openaiSection.style.display ).toBe( '' );
		expect( azureSection.style.display ).toBe( 'none' );
	} );

	it( 'does nothing if elements are missing', () => {
		document.body.innerHTML = '<div>No settings form</div>';

		// Should not throw
		expect( () => window.TalkingHeadSettings.initProviderToggle() ).not.toThrow();
	} );
} );
