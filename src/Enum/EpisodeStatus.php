<?php

declare(strict_types=1);

namespace TalkingHead\Enum;

defined( 'ABSPATH' ) || exit;

enum EpisodeStatus: string {
	case Draft      = 'draft';
	case Ready      = 'ready';
	case Generating = 'generating';
	case Generated  = 'generated';
	case Failed     = 'failed';
}
