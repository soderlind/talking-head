<?php

declare(strict_types=1);

namespace TalkingHead\Audio;

use TalkingHead\Admin\SettingsPage;

defined( 'ABSPATH' ) || exit;

final class Normalizer {

	/**
	 * Apply loudness normalization to an audio file using FFmpeg's loudnorm filter.
	 *
	 * @param string $input_path  Absolute path to the input file.
	 * @param string $output_path Absolute path for the normalized output.
	 * @param float  $target_lufs Target integrated loudness in LUFS (default -16).
	 * @return string The output path on success.
	 * @throws \RuntimeException If FFmpeg fails.
	 */
	public function normalize( string $input_path, string $output_path, float $target_lufs = -16.0 ): string {
		$ffmpeg = SettingsPage::get( 'ffmpeg_path' );
		if ( empty( $ffmpeg ) || ! is_executable( $ffmpeg ) ) {
			// No FFmpeg — skip normalization, copy input as-is.
			if ( $input_path !== $output_path ) {
				copy( $input_path, $output_path );
			}
			return $output_path;
		}

		$bitrate = SettingsPage::get( 'output_bitrate' );

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
		exec(
			sprintf(
				'%s -y -i %s -af loudnorm=I=%s:TP=-1.5:LRA=11 -c:a libmp3lame -b:a %s -ar 44100 -ac 1 %s 2>&1',
				escapeshellarg( $ffmpeg ),
				escapeshellarg( $input_path ),
				escapeshellarg( (string) $target_lufs ),
				escapeshellarg( $bitrate ),
				escapeshellarg( $output_path )
			),
			$output,
			$return_code
		);

		if ( $return_code !== 0 ) {
			throw new \RuntimeException(
				'FFmpeg normalization failed (exit ' . $return_code . '): ' . implode( "\n", $output )
			);
		}

		return $output_path;
	}
}
