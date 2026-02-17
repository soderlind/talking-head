<?php

declare(strict_types=1);

use Brain\Monkey\Functions;
use TalkingHead\Manuscript\ManuscriptValidator;

beforeEach( function () {
	Functions\stubs( [
		'__' => static fn( $text ) => $text,
	] );

	// Control SettingsPage::get() via env vars (priority: constant > env > db > default).
	putenv( 'TALKING_HEAD_MAX_SEGMENTS=50' );
	putenv( 'TALKING_HEAD_MAX_SEGMENT_CHARS=4096' );

	// Stub get_option so the DB layer is inert.
	Functions\stubs( [
		'get_option' => static fn() => [],
	] );
} );

afterEach( function () {
	putenv( 'TALKING_HEAD_MAX_SEGMENTS' );
	putenv( 'TALKING_HEAD_MAX_SEGMENT_CHARS' );
} );

it( 'returns an error when segments are empty', function () {
	$validator = new ManuscriptValidator();
	$errors    = $validator->validate( [ 'segments' => [] ] );

	expect( $errors )->toHaveCount( 1 );
	expect( $errors[ 0 ] )->toContain( 'at least one turn' );
} );

it( 'returns an error when segment count exceeds max', function () {
	putenv( 'TALKING_HEAD_MAX_SEGMENTS=2' );

	$segments = [];
	for ( $i = 0; $i < 3; $i++ ) {
		$segments[] = [ 'headId' => 1, 'voiceId' => 'alloy', 'text' => 'Hello' ];
	}

	$validator = new ManuscriptValidator();
	$errors    = $validator->validate( [ 'segments' => $segments ] );

	expect( $errors )->not->toBeEmpty();
	expect( $errors[ 0 ] )->toContain( '3' );
	expect( $errors[ 0 ] )->toContain( '2' );
} );

it( 'returns an error when headId is missing', function () {
	$validator = new ManuscriptValidator();
	$errors    = $validator->validate( [
		'segments' => [
			[ 'headId' => 0, 'voiceId' => 'alloy', 'text' => 'Hello' ],
		],
	] );

	expect( $errors )->not->toBeEmpty();
	expect( $errors[ 0 ] )->toContain( 'no speaker' );
} );

it( 'returns an error when text is empty', function () {
	$validator = new ManuscriptValidator();
	$errors    = $validator->validate( [
		'segments' => [
			[ 'headId' => 1, 'voiceId' => 'alloy', 'text' => '' ],
		],
	] );

	expect( $errors )->not->toBeEmpty();
	expect( $errors[ 0 ] )->toContain( 'no text' );
} );

it( 'returns an error when text exceeds max characters', function () {
	putenv( 'TALKING_HEAD_MAX_SEGMENT_CHARS=10' );

	$validator = new ManuscriptValidator();
	$errors    = $validator->validate( [
		'segments' => [
			[ 'headId' => 1, 'voiceId' => 'alloy', 'text' => str_repeat( 'A', 11 ) ],
		],
	] );

	expect( $errors )->not->toBeEmpty();
	expect( $errors[ 0 ] )->toContain( 'exceeds' );
} );

it( 'returns no errors for a valid manuscript', function () {
	$validator = new ManuscriptValidator();
	$errors    = $validator->validate( [
		'segments' => [
			[ 'headId' => 1, 'voiceId' => 'alloy', 'text' => 'Hello world' ],
			[ 'headId' => 2, 'voiceId' => 'nova', 'text' => 'Hi there' ],
		],
	] );

	expect( $errors )->toBeEmpty();
} );
