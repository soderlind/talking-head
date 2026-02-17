<?php

declare(strict_types=1);

use Brain\Monkey\Functions;
use TalkingHead\CPT\EpisodeCPT;

beforeEach( function () {
	Functions\stubs( [
		'__' => static fn( $text ) => $text,
	] );
} );

it( 'inserts th_status and th_audio after title', function () {
	$cpt     = new EpisodeCPT();
	$columns = [
		'cb'    => '<input type="checkbox" />',
		'title' => 'Title',
		'date'  => 'Date',
	];

	$result = $cpt->add_columns( $columns );
	$keys   = array_keys( $result );

	expect( $keys )->toBe( [ 'cb', 'title', 'th_status', 'th_audio', 'date' ] );
} );

it( 'preserves existing columns in order', function () {
	$cpt     = new EpisodeCPT();
	$columns = [
		'cb'       => '<input type="checkbox" />',
		'title'    => 'Title',
		'author'   => 'Author',
		'taxonomy' => 'Category',
		'date'     => 'Date',
	];

	$result = $cpt->add_columns( $columns );
	$keys   = array_keys( $result );

	expect( $keys )->toBe( [ 'cb', 'title', 'th_status', 'th_audio', 'author', 'taxonomy', 'date' ] );
} );

it( 'labels Status and Audio columns', function () {
	$cpt     = new EpisodeCPT();
	$columns = [ 'title' => 'Title' ];

	$result = $cpt->add_columns( $columns );

	expect( $result['th_status'] )->toBe( 'Status' );
	expect( $result['th_audio'] )->toBe( 'Audio' );
} );
