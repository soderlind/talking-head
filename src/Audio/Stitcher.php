<?php

declare(strict_types=1);

namespace TalkingHead\Audio;

use TalkingHead\Admin\SettingsPage;

defined( 'ABSPATH' ) || exit;

final class Stitcher {

	/**
	 * MPEG1 Layer 3 frame header for 128 kbps, 44100 Hz, mono, no CRC.
	 *
	 * Byte 0: 0xFF — sync
	 * Byte 1: 0xFB — sync + MPEG1 + Layer 3 + no CRC
	 * Byte 2: 0x90 — 128 kbps + 44100 Hz + no padding
	 * Byte 3: 0xC4 — mono + original
	 */
	private const MP3_SILENT_FRAME_HEADER = "\xFF\xFB\x90\xC4";

	/**
	 * Frame size in bytes: floor(144 * 128000 / 44100) = 417.
	 */
	private const MP3_FRAME_SIZE = 417;

	/**
	 * Duration of one frame in milliseconds: 1152 / 44100 * 1000 ≈ 26.122.
	 */
	private const MP3_FRAME_DURATION_MS = 26.122;

	/**
	 * Stitch audio chunk files into a single output file with silence gaps.
	 *
	 * Uses FFmpeg when available, otherwise falls back to PHP-based
	 * binary MP3 concatenation.
	 *
	 * @param list<string> $chunk_paths Absolute paths to chunk files.
	 * @param string       $output_path Absolute path for the final file.
	 * @param int          $silence_ms  Silence gap between chunks in milliseconds.
	 * @return string The output path on success.
	 * @throws \InvalidArgumentException If no chunks provided.
	 * @throws \RuntimeException On write failure.
	 */
	public function stitch( array $chunk_paths, string $output_path, int $silence_ms = 500 ): string {
		if ( empty( $chunk_paths ) ) {
			throw new \InvalidArgumentException( 'No audio chunks to stitch.' );
		}

		// Single chunk: just copy it.
		if ( count( $chunk_paths ) === 1 ) {
			copy( $chunk_paths[0], $output_path );
			return $output_path;
		}

		$ffmpeg = SettingsPage::get( 'ffmpeg_path' );

		if ( ! empty( $ffmpeg ) && is_executable( $ffmpeg ) ) {
			return $this->stitch_ffmpeg( $ffmpeg, $chunk_paths, $output_path, $silence_ms );
		}

		return $this->stitch_php( $chunk_paths, $output_path, $silence_ms );
	}

	/**
	 * Stitch using FFmpeg concat demuxer.
	 */
	private function stitch_ffmpeg( string $ffmpeg, array $chunk_paths, string $output_path, int $silence_ms ): string {
		// Generate a silence segment.
		$silence_file = sys_get_temp_dir() . '/th_silence_' . uniqid() . '.mp3';
		$this->generate_silence_ffmpeg( $ffmpeg, $silence_file, $silence_ms );

		// Build concat list file.
		$concat_list = sys_get_temp_dir() . '/th_concat_' . uniqid() . '.txt';
		$lines       = [];

		foreach ( $chunk_paths as $i => $path ) {
			$lines[] = 'file ' . escapeshellarg( $path );
			if ( $i < count( $chunk_paths ) - 1 ) {
				$lines[] = 'file ' . escapeshellarg( $silence_file );
			}
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $concat_list, implode( "\n", $lines ) );

		$bitrate = SettingsPage::get( 'output_bitrate' );

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
		exec(
			sprintf(
				'%s -y -f concat -safe 0 -i %s -c:a libmp3lame -b:a %s -ar 44100 -ac 1 %s 2>&1',
				escapeshellarg( $ffmpeg ),
				escapeshellarg( $concat_list ),
				escapeshellarg( $bitrate ),
				escapeshellarg( $output_path )
			),
			$output,
			$return_code
		);

		// Cleanup temp files.
		@unlink( $silence_file );
		@unlink( $concat_list );

		if ( $return_code !== 0 ) {
			throw new \RuntimeException(
				'FFmpeg stitching failed (exit ' . $return_code . '): ' . implode( "\n", $output )
			);
		}

		return $output_path;
	}

	/**
	 * Generate a silence audio file using FFmpeg.
	 */
	private function generate_silence_ffmpeg( string $ffmpeg, string $output, int $duration_ms ): void {
		$duration_sec = $duration_ms / 1000;

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
		exec(
			sprintf(
				'%s -y -f lavfi -i anullsrc=r=44100:cl=mono -t %s -c:a libmp3lame -b:a 128k %s 2>&1',
				escapeshellarg( $ffmpeg ),
				escapeshellarg( (string) $duration_sec ),
				escapeshellarg( $output )
			),
			$output_lines,
			$return_code
		);

		if ( $return_code !== 0 ) {
			throw new \RuntimeException( 'Failed to generate silence file.' );
		}
	}

	/**
	 * Stitch using pure PHP binary MP3 concatenation.
	 *
	 * MP3 is a frame-based format — decoders scan for sync words and process
	 * each frame independently, so raw concatenation produces valid output.
	 * Silence gaps are generated as valid MPEG1 Layer 3 frames with zero
	 * audio data.
	 */
	private function stitch_php( array $chunk_paths, string $output_path, int $silence_ms ): string {
		$handle = fopen( $output_path, 'wb' );
		if ( ! $handle ) {
			throw new \RuntimeException( 'Cannot open output file for writing: ' . $output_path );
		}

		$silence = $this->generate_silence_mp3( $silence_ms );

		foreach ( $chunk_paths as $i => $path ) {
			$data = file_get_contents( $path );
			if ( $data === false ) {
				fclose( $handle );
				throw new \RuntimeException( 'Cannot read chunk file: ' . $path );
			}
			fwrite( $handle, $data );

			// Insert silence between chunks (not after the last one).
			if ( $silence !== '' && $i < count( $chunk_paths ) - 1 ) {
				fwrite( $handle, $silence );
			}
		}

		fclose( $handle );

		return $output_path;
	}

	/**
	 * Generate silent MP3 data as a string of valid MPEG1 Layer 3 frames.
	 *
	 * Each frame is 417 bytes (128 kbps, 44100 Hz, mono, no padding) and
	 * covers ~26.12 ms. The side-information and main-data are zeroed,
	 * which signals part2_3_length = 0 to the decoder — i.e. silence.
	 *
	 * @param int $duration_ms Desired silence duration in milliseconds.
	 * @return string Raw MP3 bytes.
	 */
	private function generate_silence_mp3( int $duration_ms ): string {
		if ( $duration_ms <= 0 ) {
			return '';
		}

		$frame_count = (int) ceil( $duration_ms / self::MP3_FRAME_DURATION_MS );
		$padding     = str_repeat( "\x00", self::MP3_FRAME_SIZE - 4 );
		$frame       = self::MP3_SILENT_FRAME_HEADER . $padding;

		return str_repeat( $frame, $frame_count );
	}
}
