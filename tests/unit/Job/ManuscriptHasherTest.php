<?php

declare(strict_types=1);

use Brain\Monkey\Functions;
use TalkingHead\Job\ManuscriptHasher;

beforeEach( function () {
	Functions\stubs( [
		'wp_json_encode' => static fn( $data ) => json_encode( $data ),
	] );
} );

it( 'produces a 64-character hex SHA-256 hash', function () {
	$hasher     = new ManuscriptHasher();
	$manuscript = [
		'segments' => [
			[ 'headId' => 1, 'voiceId' => 'alloy', 'text' => 'Hello' ],
		],
	];

	$hash = $hasher->hash( $manuscript );

	expect( $hash )->toBeString()->toHaveLength( 64 );
	expect( ctype_xdigit( $hash ) )->toBeTrue();
} );

it( 'is deterministic for the same input', function () {
	$hasher     = new ManuscriptHasher();
	$manuscript = [
		'segments' => [
			[ 'headId' => 1, 'voiceId' => 'alloy', 'text' => 'Hello world' ],
		],
	];

	expect( $hasher->hash( $manuscript ) )->toBe( $hasher->hash( $manuscript ) );
} );

it( 'produces different hashes for different text', function () {
	$hasher = new ManuscriptHasher();

	$a = [ 'segments' => [ [ 'headId' => 1, 'voiceId' => 'alloy', 'text' => 'Hello' ] ] ];
	$b = [ 'segments' => [ [ 'headId' => 1, 'voiceId' => 'alloy', 'text' => 'Goodbye' ] ] ];

	expect( $hasher->hash( $a ) )->not->toBe( $hasher->hash( $b ) );
} );

it( 'normalizes to only headId, voiceId, text — ignores extra keys', function () {
	$hasher = new ManuscriptHasher();

	$minimal = [
		'segments' => [
			[ 'headId' => 1, 'voiceId' => 'alloy', 'text' => 'Test' ],
		],
	];

	$extra = [
		'segments' => [
			[ 'index' => 0, 'headId' => 1, 'voiceId' => 'alloy', 'provider' => 'openai', 'text' => 'Test', 'headName' => 'Alice' ],
		],
	];

	expect( $hasher->hash( $minimal ) )->toBe( $hasher->hash( $extra ) );
} );

it( 'returns a hash even for empty segments', function () {
	$hasher = new ManuscriptHasher();
	$hash   = $hasher->hash( [ 'segments' => [] ] );

	expect( $hash )->toBeString()->toHaveLength( 64 );
} );
