<?php

declare(strict_types=1);

use Brain\Monkey\Functions;
use TalkingHead\Chapter\ChapterTitleGenerator;

beforeEach( function () {
	Functions\stubs( [
		'sanitize_text_field' => static fn( $text ) => trim( strip_tags( $text ) ),
	] );
} );

it( 'returns empty array for empty segments', function () {
	$generator = new ChapterTitleGenerator();

	expect( $generator->generate( [] ) )->toBe( [] );
} );

it( 'returns AI-generated titles when wp_ai_client_prompt succeeds', function () {
	// Mock WordPress AI Client response.
	$mock_result = '["Welcome to the Show", "Discussing Tech Trends", "Final Thoughts"]';

	Functions\when( 'wp_ai_client_prompt' )->alias( function ( $prompt ) use ( $mock_result ) {
		// Return a mock builder object with generate_text method.
		return new class( $mock_result ) {
			private string $result;

			public function __construct( string $result ) {
				$this->result = $result;
			}

			public function generate_text(): string {
				return $this->result;
			}
		};
	} );

	Functions\when( 'is_wp_error' )->justReturn( false );

	$generator = new ChapterTitleGenerator();
	$segments  = [
		[ 'headName' => 'Alice', 'text' => 'Welcome everyone to our podcast!' ],
		[ 'headName' => 'Bob', 'text' => 'Today we are discussing the latest trends in technology.' ],
		[ 'headName' => 'Alice', 'text' => 'Thanks for listening, see you next time!' ],
	];

	$titles = $generator->generate( $segments );

	expect( $titles )->toBe( [
		'Welcome to the Show',
		'Discussing Tech Trends',
		'Final Thoughts',
	] );
} );

it( 'falls back to speaker names when AI fails', function () {
	// Mock wp_ai_client_prompt to throw.
	Functions\when( 'wp_ai_client_prompt' )->alias( function () {
		throw new RuntimeException( 'AI service unavailable' );
	} );

	$generator = new ChapterTitleGenerator();
	$segments  = [
		[ 'headName' => 'Alice', 'text' => 'Hello world.' ],
		[ 'headName' => 'Bob', 'text' => 'Goodbye world.' ],
	];

	// Suppress error_log output in test.
	$titles = @$generator->generate( $segments );

	expect( $titles )->toBe( [ 'Alice', 'Bob' ] );
} );

it( 'falls back when wp_ai_client_prompt function does not exist', function () {
	// Don't define wp_ai_client_prompt.
	$generator = new ChapterTitleGenerator();
	$segments  = [
		[ 'headName' => 'Host', 'text' => 'Some content here.' ],
	];

	// Suppress error_log output in test.
	$titles = @$generator->generate( $segments );

	expect( $titles )->toBe( [ 'Host' ] );
} );

it( 'truncates long AI-generated titles to 40 characters', function () {
	$mock_result = '["This is a very long chapter title that should be truncated"]';

	Functions\when( 'wp_ai_client_prompt' )->alias( function () use ( $mock_result ) {
		return new class( $mock_result ) {
			private string $result;

			public function __construct( string $result ) {
				$this->result = $result;
			}

			public function generate_text(): string {
				return $this->result;
			}
		};
	} );

	Functions\when( 'is_wp_error' )->justReturn( false );

	$generator = new ChapterTitleGenerator();
	$segments  = [
		[ 'headName' => 'Alice', 'text' => 'Some text.' ],
	];

	$titles = $generator->generate( $segments );

	expect( mb_strlen( $titles[0] ) )->toBeLessThanOrEqual( 40 );
	expect( $titles[0] )->toEndWith( '…' );
} );

it( 'extracts JSON from markdown code blocks', function () {
	$mock_result = "Here are the chapter titles:\n```json\n[\"Intro\", \"Main Topic\", \"Outro\"]\n```";

	Functions\when( 'wp_ai_client_prompt' )->alias( function () use ( $mock_result ) {
		return new class( $mock_result ) {
			private string $result;

			public function __construct( string $result ) {
				$this->result = $result;
			}

			public function generate_text(): string {
				return $this->result;
			}
		};
	} );

	Functions\when( 'is_wp_error' )->justReturn( false );

	$generator = new ChapterTitleGenerator();
	$segments  = [
		[ 'headName' => 'Host', 'text' => 'Welcome.' ],
		[ 'headName' => 'Guest', 'text' => 'Thanks for having me.' ],
		[ 'headName' => 'Host', 'text' => 'Goodbye.' ],
	];

	$titles = $generator->generate( $segments );

	expect( $titles )->toBe( [ 'Intro', 'Main Topic', 'Outro' ] );
} );

it( 'falls back when AI returns wrong number of titles', function () {
	// Return only 1 title for 3 segments.
	$mock_result = '["Only One Title"]';

	Functions\when( 'wp_ai_client_prompt' )->alias( function () use ( $mock_result ) {
		return new class( $mock_result ) {
			private string $result;

			public function __construct( string $result ) {
				$this->result = $result;
			}

			public function generate_text(): string {
				return $this->result;
			}
		};
	} );

	Functions\when( 'is_wp_error' )->justReturn( false );

	$generator = new ChapterTitleGenerator();
	$segments  = [
		[ 'headName' => 'Alice', 'text' => 'Text 1.' ],
		[ 'headName' => 'Bob', 'text' => 'Text 2.' ],
		[ 'headName' => 'Alice', 'text' => 'Text 3.' ],
	];

	$titles = $generator->generate( $segments );

	// Should fall back to speaker names.
	expect( $titles )->toBe( [ 'Alice', 'Bob', 'Alice' ] );
} );
