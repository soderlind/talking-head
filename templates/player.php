<?php
/**
 * Player template.
 *
 * Available variables: $title, $audio_url_esc, $episode_id, $show_transcript, $stitching_mode
 *
 * @package TalkingHead
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="th-player" data-episode-id="<?php echo esc_attr( (string) $episode_id ); ?>" data-stitching-mode="<?php echo esc_attr( $stitching_mode ); ?>">
	<div class="th-player__header">
		<h3 class="th-player__title"><?php echo $title; // Already escaped. ?></h3>
	</div>
	<?php if ( $stitching_mode === 'virtual' ) : ?>
		<audio class="th-player__audio" controls controlsList="nodownload" preload="none">
			<?php esc_html_e( 'Your browser does not support the audio element.', 'talking-head' ); ?>
		</audio>
	<?php else : ?>
		<audio class="th-player__audio" controls preload="metadata" src="<?php echo $audio_url_esc; // Already escaped. ?>">
			<?php esc_html_e( 'Your browser does not support the audio element.', 'talking-head' ); ?>
		</audio>
	<?php endif; ?>
	<?php if ( $show_transcript ) : ?>
		<div class="th-player__transcript" data-episode-id="<?php echo esc_attr( (string) $episode_id ); ?>">
			<!-- Transcript loaded via view.js from REST API -->
		</div>
	<?php endif; ?>
</div>
