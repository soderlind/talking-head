<?php

declare(strict_types=1);

use Brain\Monkey\Functions;
use TalkingHead\Admin\SettingsPage;

afterEach( function () {
	// Clean up any env vars set during tests.
	putenv( 'TALKING_HEAD_DEFAULT_VOICE' );
	putenv( 'TALKING_HEAD_OPENAI_API_KEY' );
} );

it( 'returns empty string for an unknown key', function () {
	expect( SettingsPage::get( 'nonexistent_key' ) )->toBe( '' );
} );

it( 'returns env var value when set', function () {
	putenv( 'TALKING_HEAD_DEFAULT_VOICE=shimmer' );

	Functions\stubs( [
		'get_option' => static fn() => [],
	] );

	expect( SettingsPage::get( 'default_voice' ) )->toBe( 'shimmer' );
} );

it( 'returns database value when no constant or env var', function () {
	Functions\stubs( [
		'get_option' => static fn() => [ 'default_voice' => 'nova' ],
	] );

	expect( SettingsPage::get( 'default_voice' ) )->toBe( 'nova' );
} );

it( 'returns default value when nothing is set', function () {
	Functions\stubs( [
		'get_option' => static fn() => [],
	] );

	expect( SettingsPage::get( 'default_voice' ) )->toBe( 'alloy' );
} );

it( 'prefers env var over database value', function () {
	putenv( 'TALKING_HEAD_OPENAI_API_KEY=env-key-123' );

	Functions\stubs( [
		'get_option' => static fn() => [ 'openai_api_key' => 'db-key-456' ],
	] );

	expect( SettingsPage::get( 'openai_api_key' ) )->toBe( 'env-key-123' );
} );
