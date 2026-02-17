<?php

declare(strict_types=1);

use TalkingHead\Manuscript\ManuscriptSchema;

it( 'has required top-level properties', function () {
	$schema = ManuscriptSchema::schema();

	expect( $schema[ 'required' ] )->toContain( 'version', 'episodeId', 'title', 'segments' );
} );

it( 'defines version as a const string', function () {
	$schema = ManuscriptSchema::schema();

	expect( $schema[ 'properties' ][ 'version' ][ 'type' ] )->toBe( 'string' );
	expect( $schema[ 'properties' ][ 'version' ][ 'const' ] )->toBe( '1.0' );
} );

it( 'defines episodeId as an integer with minimum 1', function () {
	$schema = ManuscriptSchema::schema();

	expect( $schema[ 'properties' ][ 'episodeId' ][ 'type' ] )->toBe( 'integer' );
	expect( $schema[ 'properties' ][ 'episodeId' ][ 'minimum' ] )->toBe( 1 );
} );

it( 'defines segments as an array', function () {
	$schema = ManuscriptSchema::schema();

	expect( $schema[ 'properties' ][ 'segments' ][ 'type' ] )->toBe( 'array' );
	expect( $schema[ 'properties' ][ 'segments' ][ 'minItems' ] )->toBe( 1 );
} );

it( 'has required segment properties', function () {
	$schema        = ManuscriptSchema::schema();
	$segment       = $schema[ 'properties' ][ 'segments' ][ 'items' ];
	$required_keys = [ 'index', 'headId', 'headName', 'voiceId', 'provider', 'text' ];

	foreach ( $required_keys as $key ) {
		expect( $segment[ 'required' ] )->toContain( $key );
	}
} );

it( 'includes openai and azure_openai in provider enum', function () {
	$schema   = ManuscriptSchema::schema();
	$provider = $schema[ 'properties' ][ 'segments' ][ 'items' ][ 'properties' ][ 'provider' ];

	expect( $provider[ 'enum' ] )->toContain( 'openai', 'azure_openai' );
} );

it( 'defines optional speed property on segments', function () {
	$schema = ManuscriptSchema::schema();
	$speed  = $schema[ 'properties' ][ 'segments' ][ 'items' ][ 'properties' ][ 'speed' ];

	expect( $speed[ 'type' ] )->toBe( 'number' );
	expect( $speed[ 'minimum' ] )->toBe( 0.25 );
	expect( $speed[ 'maximum' ] )->toBe( 4.0 );
	expect( $speed[ 'default' ] )->toBe( 1.0 );
	expect( $schema[ 'properties' ][ 'segments' ][ 'items' ][ 'required' ] )->not->toContain( 'speed' );
} );

it( 'defines optional speakingStyle property on segments', function () {
	$schema        = ManuscriptSchema::schema();
	$speakingStyle = $schema[ 'properties' ][ 'segments' ][ 'items' ][ 'properties' ][ 'speakingStyle' ];

	expect( $speakingStyle[ 'type' ] )->toBe( 'string' );
	expect( $speakingStyle[ 'default' ] )->toBe( '' );
	expect( $schema[ 'properties' ][ 'segments' ][ 'items' ][ 'required' ] )->not->toContain( 'speakingStyle' );
} );
