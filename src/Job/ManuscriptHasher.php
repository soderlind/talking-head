<?php

declare(strict_types=1);

namespace TalkingHead\Job;

defined( 'ABSPATH' ) || exit;

final class ManuscriptHasher {

	/**
	 * Produce a deterministic SHA-256 hash of the manuscript content.
	 * Used for idempotency: if the manuscript hasn't changed, don't re-generate.
	 *
	 * @param array $manuscript Canonical manuscript data.
	 * @return string 64-character hex hash.
	 */
	public function hash( array $manuscript ): string {
		$normalized = array_map(
			fn( array $segment ) => [
				'headId'        => $segment[ 'headId' ],
				'voiceId'       => $segment[ 'voiceId' ],
				'text'          => $segment[ 'text' ],
				'speed'         => $segment[ 'speed' ] ?? 1.0,
				'speakingStyle' => $segment[ 'speakingStyle' ] ?? '',
			],
			$manuscript[ 'segments' ] ?? []
		);

		return hash( 'sha256', wp_json_encode( $normalized ) );
	}
}
