<?php
/**
 * Player template.
 *
 * Available variables: $title, $audio_url_esc, $episode_id, $show_transcript, $stitching_mode, $use_waveform_player, $show_chapters, $chapters
 *
 * @package TalkingHead
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="th-player<?php echo $use_waveform_player ? ' th-player--waveform' : ''; ?>" data-episode-id="<?php echo esc_attr( (string) $episode_id ); ?>" data-stitching-mode="<?php echo esc_attr( $stitching_mode ); ?>" data-use-waveform="<?php echo $use_waveform_player ? 'true' : 'false'; ?>">
	<div class="th-player__header">
		<h3 class="th-player__title"><?php echo $title; // Already escaped. ?></h3>
	</div>
	<?php if ( $use_waveform_player && $stitching_mode !== 'virtual' && ! empty( $audio_url_esc ) ) : ?>
		<?php if ( $show_chapters && ! empty( $chapters ) ) : ?>
			<div class="th-player__waveform" data-waveform-playlist data-waveform-style="mirror" data-height="80" data-show-playback-speed="true" data-expand-chapters="true">
				<div data-track
					data-url="<?php echo $audio_url_esc; // Already escaped. ?>"
					data-title="<?php echo $title; // Already escaped. ?>">
					<?php foreach ( $chapters as $chapter ) : ?>
						<div data-chapter data-time="<?php echo esc_attr( $chapter['time'] ); ?>"><?php echo esc_html( $chapter['speaker'] ); ?></div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php else : ?>
			<div class="th-player__waveform"
				data-waveform-player
				data-url="<?php echo $audio_url_esc; // Already escaped. ?>"
				data-title="<?php echo $title; // Already escaped. ?>"
				data-waveform-style="mirror"
				data-height="80"
				data-show-playback-speed="true">
			</div>
		<?php endif; ?>
	<?php elseif ( $stitching_mode === 'virtual' ) : ?>
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
