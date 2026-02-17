<?php

declare(strict_types=1);

namespace TalkingHead\Manuscript;

use TalkingHead\Admin\SettingsPage;

defined( 'ABSPATH' ) || exit;

final class ManuscriptValidator {

	/**
	 * Validate a manuscript array.
	 *
	 * @param array $manuscript Canonical manuscript data.
	 * @return list<string> Validation error messages. Empty if valid.
	 */
	public function validate( array $manuscript ): array {
		$errors       = [];
		$max_segments = (int) SettingsPage::get( 'max_segments' );
		$max_chars    = (int) SettingsPage::get( 'max_segment_chars' );

		if ( empty( $manuscript['segments'] ) ) {
			$errors[] = __( 'Episode must have at least one turn.', 'talking-head' );
			return $errors;
		}

		$count = count( $manuscript['segments'] );
		if ( $count > $max_segments ) {
			$errors[] = sprintf(
				/* translators: 1: current count, 2: maximum allowed */
				__( 'Episode has %1$d segments (max %2$d).', 'talking-head' ),
				$count,
				$max_segments
			);
		}

		foreach ( $manuscript['segments'] as $i => $segment ) {
			if ( empty( $segment['headId'] ) || (int) $segment['headId'] <= 0 ) {
				$errors[] = sprintf(
					/* translators: %d: segment number */
					__( 'Segment %d has no speaker assigned.', 'talking-head' ),
					$i + 1
				);
			}

			$text = trim( $segment['text'] ?? '' );
			if ( empty( $text ) ) {
				$errors[] = sprintf(
					/* translators: %d: segment number */
					__( 'Segment %d has no text.', 'talking-head' ),
					$i + 1
				);
			}

			if ( mb_strlen( $text ) > $max_chars ) {
				$errors[] = sprintf(
					/* translators: 1: segment number, 2: max characters */
					__( 'Segment %1$d exceeds %2$d characters.', 'talking-head' ),
					$i + 1,
					$max_chars
				);
			}
		}

		return $errors;
	}
}
