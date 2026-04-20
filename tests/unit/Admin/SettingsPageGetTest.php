<?php

declare(strict_types=1);

use Brain\Monkey\Functions;
use TalkingHead\Admin\SettingsPage;

afterEach( function () {
	// Clean up any env vars set during tests.
	putenv( 'TALKING_HEAD_DEFAULT_VOICE' );
	putenv( 'TALKING_HEAD_FFMPEG_PATH' );
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
	putenv( 'TALKING_HEAD_FFMPEG_PATH=/custom/ffmpeg' );

	Functions\stubs( [
		'get_option' => static fn() => [ 'ffmpeg_path' => '/usr/bin/ffmpeg' ],
	] );

	expect( SettingsPage::get( 'ffmpeg_path' ) )->toBe( '/custom/ffmpeg' );
} );
