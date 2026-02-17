<?php

declare(strict_types=1);

namespace TalkingHead\Enum;

defined( 'ABSPATH' ) || exit;

enum AudioFormat: string {
	case Mp3 = 'mp3';
	case Wav = 'wav';
	case Aac = 'aac';

	public function mimeType(): string {
		return match ( $this ) {
			self::Mp3 => 'audio/mpeg',
			self::Wav => 'audio/wav',
			self::Aac => 'audio/aac',
		};
	}

	public function extension(): string {
		return $this->value;
	}
}
