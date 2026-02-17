<?php
/**
 * Player template.
 *
 * Available variables: $title, $audio_url_esc, $episode_id, $show_transcript
 *
 * @package TalkingHead
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="th-player" data-episode-id="<?php echo esc_attr( (string) $episode_id ); ?>">
	<div class="th-player__header">
		<h3 class="th-player__title"><?php echo $title; // Already escaped. ?></h3>
	</div>
	<audio
		class="th-player__audio"
		controls
		preload="metadata"
		src="<?php echo $audio_url_esc; // Already escaped. ?>"
	>
		<?php esc_html_e( 'Your browser does not support the audio element.', 'talking-head' ); ?>
	</audio>
	<div class="th-player__controls">
		<a class="th-player__download" href="<?php echo $audio_url_esc; // Already escaped. ?>" download>
			<?php esc_html_e( 'Download', 'talking-head' ); ?>
		</a>
	</div>
	<?php if ( $show_transcript ) : ?>
		<div class="th-player__transcript" data-episode-id="<?php echo esc_attr( (string) $episode_id ); ?>">
			<!-- Transcript loaded via view.js from REST API -->
		</div>
	<?php endif; ?>
</div>
