<?php

declare(strict_types=1);

use Brain\Monkey\Functions;
use TalkingHead\Admin\SettingsPage;

beforeEach( function () {
	Functions\stubs( [
		'sanitize_text_field' => static fn( $val ) => $val,
		'esc_url_raw'         => static fn( $val ) => $val,
		'absint'              => static fn( $val ) => abs( intval( $val ) ),
		'add_settings_error'  => static fn() => null,
		'__'                  => static fn( $text ) => $text,
	] );

	$this->page = new SettingsPage();
} );

it( 'accepts a valid provider', function () {
	$result = $this->page->sanitize_options( [ 'tts_provider' => 'azure_openai' ] );

	expect( $result['tts_provider'] )->toBe( 'azure_openai' );
} );

it( 'defaults to openai for an invalid provider', function () {
	$result = $this->page->sanitize_options( [ 'tts_provider' => 'invalid' ] );

	expect( $result['tts_provider'] )->toBe( 'openai' );
} );

it( 'accepts a valid voice', function () {
	$result = $this->page->sanitize_options( [ 'default_voice' => 'nova' ] );

	expect( $result['default_voice'] )->toBe( 'nova' );
} );

it( 'defaults to alloy for an invalid voice', function () {
	$result = $this->page->sanitize_options( [ 'default_voice' => 'invalid_voice' ] );

	expect( $result['default_voice'] )->toBe( 'alloy' );
} );

it( 'accepts a valid model', function () {
	$result = $this->page->sanitize_options( [ 'openai_tts_model' => 'tts-1-hd' ] );

	expect( $result['openai_tts_model'] )->toBe( 'tts-1-hd' );
} );

it( 'accepts gpt-4o-mini-tts as a valid model', function () {
	$result = $this->page->sanitize_options( [ 'openai_tts_model' => 'gpt-4o-mini-tts' ] );

	expect( $result['openai_tts_model'] )->toBe( 'gpt-4o-mini-tts' );
} );

it( 'defaults to tts-1 for an invalid model', function () {
	$result = $this->page->sanitize_options( [ 'openai_tts_model' => 'gpt-4' ] );

	expect( $result['openai_tts_model'] )->toBe( 'tts-1' );
} );

it( 'clamps silence_gap_ms to 0–5000', function () {
	$zero = $this->page->sanitize_options( [ 'silence_gap_ms' => 0 ] );
	$high = $this->page->sanitize_options( [ 'silence_gap_ms' => 9999 ] );

	expect( $zero['silence_gap_ms'] )->toBe( 0 );
	expect( $high['silence_gap_ms'] )->toBe( 5000 );
} );

it( 'clamps max_segments to 1–200', function () {
	$low  = $this->page->sanitize_options( [ 'max_segments' => 0 ] );
	$high = $this->page->sanitize_options( [ 'max_segments' => 999 ] );

	expect( $low['max_segments'] )->toBe( 1 );
	expect( $high['max_segments'] )->toBe( 200 );
} );

it( 'clamps max_segment_chars to 100–4096', function () {
	$low  = $this->page->sanitize_options( [ 'max_segment_chars' => 10 ] );
	$high = $this->page->sanitize_options( [ 'max_segment_chars' => 10000 ] );

	expect( $low['max_segment_chars'] )->toBe( 100 );
	expect( $high['max_segment_chars'] )->toBe( 4096 );
} );

it( 'clamps rate_limit_per_min to 1–60', function () {
	$low  = $this->page->sanitize_options( [ 'rate_limit_per_min' => 0 ] );
	$high = $this->page->sanitize_options( [ 'rate_limit_per_min' => 100 ] );

	expect( $low['rate_limit_per_min'] )->toBe( 1 );
	expect( $high['rate_limit_per_min'] )->toBe( 60 );
} );
