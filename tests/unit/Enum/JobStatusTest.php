<?php

declare(strict_types=1);

use TalkingHead\Enum\JobStatus;

it( 'marks Succeeded as terminal', function () {
	expect( JobStatus::Succeeded->isTerminal() )->toBeTrue();
} );

it( 'marks Failed as terminal', function () {
	expect( JobStatus::Failed->isTerminal() )->toBeTrue();
} );

it( 'marks Canceled as terminal', function () {
	expect( JobStatus::Canceled->isTerminal() )->toBeTrue();
} );

it( 'marks Queued as non-terminal', function () {
	expect( JobStatus::Queued->isTerminal() )->toBeFalse();
} );

it( 'marks Running as non-terminal', function () {
	expect( JobStatus::Running->isTerminal() )->toBeFalse();
} );

it( 'marks only Failed as retryable', function () {
	expect( JobStatus::Failed->isRetryable() )->toBeTrue();
	expect( JobStatus::Succeeded->isRetryable() )->toBeFalse();
	expect( JobStatus::Canceled->isRetryable() )->toBeFalse();
	expect( JobStatus::Queued->isRetryable() )->toBeFalse();
	expect( JobStatus::Running->isRetryable() )->toBeFalse();
} );
