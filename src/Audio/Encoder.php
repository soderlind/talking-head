<?php

declare(strict_types=1);

namespace TalkingHead\Audio;

use TalkingHead\Admin\SettingsPage;
use TalkingHead\Enum\AudioFormat;

defined( 'ABSPATH' ) || exit;

final class Encoder {

	/**
	 * Convert an audio file from one format to another using FFmpeg.
	 *
	 * @param string      $input_path  Absolute path to the input file.
	 * @param string      $output_path Absolute path for the output file.
	 * @param AudioFormat $format      Target audio format.
	 * @return string The output path on success.
	 * @throws \RuntimeException If FFmpeg fails.
	 */
	public function encode( string $input_path, string $output_path, AudioFormat $format ): string {
		$ffmpeg = SettingsPage::get( 'ffmpeg_path' );
		if ( empty( $ffmpeg ) || ! is_executable( $ffmpeg ) ) {
			// No FFmpeg — skip encoding, copy input as-is.
			if ( $input_path !== $output_path ) {
				copy( $input_path, $output_path );
			}
			return $output_path;
		}

		$bitrate = SettingsPage::get( 'output_bitrate' );

		$codec = match ( $format ) {
			AudioFormat::Mp3 => 'libmp3lame',
			AudioFormat::Aac => 'aac',
			AudioFormat::Wav => 'pcm_s16le',
		};

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
		exec(
			sprintf(
				'%s -y -i %s -c:a %s -b:a %s -ar 44100 -ac 1 %s 2>&1',
				escapeshellarg( $ffmpeg ),
				escapeshellarg( $input_path ),
				escapeshellarg( $codec ),
				escapeshellarg( $bitrate ),
				escapeshellarg( $output_path )
			),
			$output,
			$return_code
		);

		if ( $return_code !== 0 ) {
			throw new \RuntimeException(
				'FFmpeg encoding failed (exit ' . $return_code . '): ' . implode( "\n", $output )
			);
		}

		return $output_path;
	}
}
