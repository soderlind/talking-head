<?php

declare(strict_types=1);

namespace TalkingHead\Provider;

defined( 'ABSPATH' ) || exit;

final readonly class ProviderCapabilities {

	public function __construct(
		public int $maxCharsPerRequest = 4096,
		public array $supportedFormats = [ 'mp3', 'wav' ],
		public bool $supportsSSML = false,
		public bool $supportsSpeakingStyle = false,
		public float $maxCostPerChar = 0.0,
	) {}
}
