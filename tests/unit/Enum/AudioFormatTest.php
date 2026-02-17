<?php

declare(strict_types=1);

use TalkingHead\Enum\AudioFormat;

it( 'returns correct MIME type for mp3', function () {
	expect( AudioFormat::Mp3->mimeType() )->toBe( 'audio/mpeg' );
} );

it( 'returns correct MIME type for wav', function () {
	expect( AudioFormat::Wav->mimeType() )->toBe( 'audio/wav' );
} );

it( 'returns correct MIME type for aac', function () {
	expect( AudioFormat::Aac->mimeType() )->toBe( 'audio/aac' );
} );

it( 'returns extension matching backed value for each case', function () {
	foreach ( AudioFormat::cases() as $format ) {
		expect( $format->extension() )->toBe( $format->value );
	}
} );
