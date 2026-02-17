<?php

declare(strict_types=1);

namespace TalkingHead\Provider;

defined( 'ABSPATH' ) || exit;

final readonly class AudioChunk {

	public function __construct(
		public string $data,
		public string $format,
		public int $durationMs,
		public int $sizeBytes,
		public string $voiceId,
		public int $segmentIndex,
	) {}
}
