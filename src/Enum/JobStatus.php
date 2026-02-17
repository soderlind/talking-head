<?php

declare(strict_types=1);

namespace TalkingHead\Enum;

defined( 'ABSPATH' ) || exit;

enum JobStatus: string {
	case Queued    = 'queued';
	case Running   = 'running';
	case Succeeded = 'succeeded';
	case Failed    = 'failed';
	case Canceled  = 'canceled';

	public function isTerminal(): bool {
		return match ( $this ) {
			self::Succeeded, self::Failed, self::Canceled => true,
			default                                       => false,
		};
	}

	public function isRetryable(): bool {
		return $this === self::Failed;
	}
}
