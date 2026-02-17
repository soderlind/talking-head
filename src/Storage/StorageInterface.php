<?php

declare(strict_types=1);

namespace TalkingHead\Storage;

defined( 'ABSPATH' ) || exit;

interface StorageInterface {

	/**
	 * Store raw data to a file.
	 *
	 * @param string $filename File name.
	 * @param string $data     Raw file data.
	 * @param string $subdir   Optional subdirectory.
	 * @return string Absolute file path.
	 */
	public function store( string $filename, string $data, string $subdir = '' ): string;

	/**
	 * Get the public URL for a stored file.
	 *
	 * @param string $path Absolute file path.
	 * @return string Public URL.
	 */
	public function url( string $path ): string;

	/**
	 * Delete a file by its path.
	 *
	 * @param string $path Absolute file path.
	 * @return bool True on success.
	 */
	public function delete( string $path ): bool;

	/**
	 * Ensure a storage directory exists.
	 *
	 * @param string $subdir Optional subdirectory.
	 * @return string Absolute path to the directory.
	 */
	public function ensure_dir( string $subdir = '' ): string;
}
