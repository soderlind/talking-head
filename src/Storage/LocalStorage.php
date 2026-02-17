<?php

declare(strict_types=1);

namespace TalkingHead\Storage;

defined( 'ABSPATH' ) || exit;

final class LocalStorage implements StorageInterface {

	private const UPLOAD_SUBDIR = 'talking-head';

	public function store( string $filename, string $data, string $subdir = '' ): string {
		$dir  = $this->ensure_dir( $subdir );
		$file = trailingslashit( $dir ) . sanitize_file_name( $filename );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $file, $data );

		return $file;
	}

	public function url( string $path ): string {
		$upload_dir = wp_upload_dir();
		$base_dir   = $upload_dir['basedir'];
		$base_url   = $upload_dir['baseurl'];

		return str_replace( $base_dir, $base_url, $path );
	}

	public function delete( string $path ): bool {
		if ( file_exists( $path ) ) {
			wp_delete_file( $path );
			return ! file_exists( $path );
		}
		return false;
	}

	public function ensure_dir( string $subdir = '' ): string {
		$upload_dir = wp_upload_dir();
		$dir        = trailingslashit( $upload_dir['basedir'] ) . self::UPLOAD_SUBDIR;

		if ( $subdir !== '' ) {
			$dir = trailingslashit( $dir ) . ltrim( $subdir, '/' );
		}

		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );

			// Prevent directory listing and direct access to non-audio files.
			$index_file = trailingslashit( $dir ) . 'index.php';
			if ( ! file_exists( $index_file ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				file_put_contents( $index_file, "<?php\n// Silence is golden." );
			}
		}

		return $dir;
	}

	public static function ensure_upload_dir(): void {
		( new self() )->ensure_dir();
	}
}
