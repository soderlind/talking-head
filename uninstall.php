<?php
/**
 * Uninstall handler for Talking Head plugin.
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

TalkingHead\Database\Schema::drop();

$post_types = [ 'talking_head_episode', 'talking_head_head' ];
foreach ( $post_types as $pt ) {
	$posts = get_posts(
		[
			'post_type'      => $pt,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		]
	);
	foreach ( $posts as $post_id ) {
		wp_delete_post( $post_id, true );
	}
}

$upload_dir = wp_upload_dir();
$th_dir     = trailingslashit( $upload_dir['basedir'] ) . 'talking-head';
if ( is_dir( $th_dir ) ) {
	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $th_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ( $files as $file ) {
		$file->isDir() ? rmdir( $file->getRealPath() ) : unlink( $file->getRealPath() );
	}
	rmdir( $th_dir );
}
