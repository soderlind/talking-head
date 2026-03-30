import { describe, it, expect, afterEach, beforeAll } from 'vitest';

// Load the script once to expose window.TalkingHeadSettings
beforeAll( async () => {
	await import( '../../../src/Admin/settings-page.js' );
} );

describe( 'Settings Page Provider Toggle', () => {
	let select, openaiSection, azureSection, wordpressSection;

	const setupDOM = ( initialValue = 'openai' ) => {
		document.body.innerHTML = `
			<select name="talking_head_options[tts_provider]">
				<option value="openai">OpenAI</option>
				<option value="azure_openai">Azure OpenAI</option>
				<option value="wordpress">WordPress AI (Core)</option>
			</select>
			<div id="th-section-openai" class="th-provider-section"></div>
			<div id="th-section-azure-openai" class="th-provider-section"></div>
			<div id="th-section-wordpress" class="th-provider-section"></div>
		`;
		select = document.querySelector( 'select[name="talking_head_options[tts_provider]"]' );
		select.value = initialValue;
		openaiSection = document.getElementById( 'th-section-openai' );
		azureSection = document.getElementById( 'th-section-azure-openai' );
		wordpressSection = document.getElementById( 'th-section-wordpress' );
	};

	afterEach( () => {
		document.body.innerHTML = '';
	} );

	it( 'shows OpenAI section and hides Azure when provider is openai', () => {
		setupDOM( 'openai' );
		window.TalkingHeadSettings.initProviderToggle();

		expect( openaiSection.classList.contains( 'is-active' ) ).toBe( true );
		expect( azureSection.classList.contains( 'is-active' ) ).toBe( false );
		expect( wordpressSection.classList.contains( 'is-active' ) ).toBe( false );
	} );

	it( 'shows Azure section and hides OpenAI when provider is azure_openai', () => {
		setupDOM( 'azure_openai' );
		window.TalkingHeadSettings.initProviderToggle();

		expect( openaiSection.classList.contains( 'is-active' ) ).toBe( false );
		expect( azureSection.classList.contains( 'is-active' ) ).toBe( true );
		expect( wordpressSection.classList.contains( 'is-active' ) ).toBe( false );
	} );

	it( 'shows WordPress section when provider is wordpress', () => {
		setupDOM( 'wordpress' );
		window.TalkingHeadSettings.initProviderToggle();

		expect( openaiSection.classList.contains( 'is-active' ) ).toBe( false );
		expect( azureSection.classList.contains( 'is-active' ) ).toBe( false );
		expect( wordpressSection.classList.contains( 'is-active' ) ).toBe( true );
	} );

	it( 'toggles sections when select value changes', () => {
		setupDOM( 'openai' );
		window.TalkingHeadSettings.initProviderToggle();

		// Initial state
		expect( openaiSection.classList.contains( 'is-active' ) ).toBe( true );
		expect( azureSection.classList.contains( 'is-active' ) ).toBe( false );

		// Change to Azure
		select.value = 'azure_openai';
		select.dispatchEvent( new Event( 'change' ) );

		expect( openaiSection.classList.contains( 'is-active' ) ).toBe( false );
		expect( azureSection.classList.contains( 'is-active' ) ).toBe( true );

		// Change to WordPress
		select.value = 'wordpress';
		select.dispatchEvent( new Event( 'change' ) );

		expect( azureSection.classList.contains( 'is-active' ) ).toBe( false );
		expect( wordpressSection.classList.contains( 'is-active' ) ).toBe( true );

		// Change back to OpenAI
		select.value = 'openai';
		select.dispatchEvent( new Event( 'change' ) );

		expect( openaiSection.classList.contains( 'is-active' ) ).toBe( true );
		expect( wordpressSection.classList.contains( 'is-active' ) ).toBe( false );
	} );

	it( 'does nothing if elements are missing', () => {
		document.body.innerHTML = '<div>No settings form</div>';

		// Should not throw
		expect( () => window.TalkingHeadSettings.initProviderToggle() ).not.toThrow();
	} );
} );
