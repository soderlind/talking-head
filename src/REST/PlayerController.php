<?php

declare(strict_types=1);

namespace TalkingHead\REST;

use TalkingHead\Admin\SettingsPage;
use TalkingHead\CPT\EpisodeCPT;
use TalkingHead\Database\AssetRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

final class PlayerController {

	private const NAMESPACE = 'talking-head/v1';

	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE ,
			'/episodes/(?P<id>\d+)/player',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_player_data' ],
					'permission_callback' => '__return_true',
					'args'                => [
						'id' => [
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						],
					],
				],
			]
		);
	}

	public function get_player_data( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$episode_id = absint( $request->get_param( 'id' ) );
		$post       = get_post( $episode_id );

		if ( ! $post || $post->post_type !== EpisodeCPT::POST_TYPE || $post->post_status !== 'publish' ) {
			return new WP_Error(
				'not_found',
				__( 'Episode not found.', 'talking-head' ),
				[ 'status' => 404 ]
			);
		}

		$audio_url  = get_post_meta( $episode_id, EpisodeCPT::META_KEY_AUDIO_URL, true );
		$manuscript = json_decode(
			get_post_meta( $episode_id, EpisodeCPT::META_KEY_MANUSCRIPT, true ) ?: '{}',
			true
		);

		$transcript = array_map(
			fn( array $seg ) => [
				'speaker' => $seg[ 'headName' ] ?? '',
				'text'    => $seg[ 'text' ] ?? '',
			],
			$manuscript[ 'segments' ] ?? []
		);

		// Resolve stitching mode for this episode.
		$episode_mode   = get_post_meta( $episode_id, EpisodeCPT::META_KEY_STITCHING_MODE, true );
		$stitching_mode = ( $episode_mode !== '' ) ? $episode_mode : SettingsPage::get( 'stitching_mode' );

		$data = [
			'episodeId'     => $episode_id,
			'title'         => $post->post_title,
			'audioUrl'      => $audio_url ?: null,
			'duration'      => (int) get_post_meta( $episode_id, EpisodeCPT::META_KEY_AUDIO_DURATION, true ),
			'stitchingMode' => $stitching_mode,
			'transcript'    => $transcript,
		];

		if ( $stitching_mode === 'virtual' ) {
			$assets         = new AssetRepository();
			$chunks         = $assets->find_chunks_for_episode( $episode_id );
			$silence_gap_ms = (int) SettingsPage::get( 'silence_gap_ms' );

			$segments = [];
			foreach ( $chunks as $i => $chunk ) {
				$segments[] = [
					'index'      => (int) $chunk[ 'segment_index' ],
					'url'        => $chunk[ 'file_url' ],
					'durationMs' => (int) $chunk[ 'duration_ms' ],
					'speaker'    => $transcript[ $i ][ 'speaker' ] ?? '',
				];
			}

			$data[ 'segments' ]     = $segments;
			$data[ 'silenceGapMs' ] = $silence_gap_ms;
		}

		return new WP_REST_Response( $data, 200 );
	}
}
