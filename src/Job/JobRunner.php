<?php

declare(strict_types=1);

namespace TalkingHead\Job;

use TalkingHead\Admin\SettingsPage;
use TalkingHead\Audio\Stitcher;
use TalkingHead\CPT\EpisodeCPT;
use TalkingHead\Database\AssetRepository;
use TalkingHead\Database\JobRepository;
use TalkingHead\Enum\EpisodeStatus;
use TalkingHead\Enum\JobStatus;
use TalkingHead\Provider\AzureOpenAI\AzureOpenAIProvider;
use TalkingHead\Provider\OpenAI\OpenAIProvider;
use TalkingHead\Provider\ProviderInterface;
use TalkingHead\Storage\LocalStorage;
use TalkingHead\Storage\StorageInterface;

defined( 'ABSPATH' ) || exit;

final class JobRunner {

	/** @var array<string, ProviderInterface> */
	private array $providers = [];

	public function __construct(
		private readonly JobRepository $jobs,
		private readonly AssetRepository $assets,
		private readonly Stitcher $stitcher,
		private readonly StorageInterface $storage,
	) {}

	/**
	 * Register the Action Scheduler callback.
	 */
	public static function register(): void {
		add_action( 'talking_head_process_job', [ self::class, 'handle' ], 10, 2 );
	}

	/**
	 * Static entry point for Action Scheduler.
	 *
	 * In multisite, the async runner may fire in the wrong blog context.
	 * We pass blog_id alongside job_id and switch if necessary.
	 */
	public static function handle( int $job_id, int $blog_id = 0 ): void {
		$switched = false;
		if ( $blog_id > 0 && is_multisite() && get_current_blog_id() !== $blog_id ) {
			switch_to_blog( $blog_id );
			$switched = true;
		}

		try {
			$runner = new self(
				jobs: new JobRepository(),
				assets: new AssetRepository(),
				stitcher: new Stitcher(),
				storage: new LocalStorage(),
			);

			$runner->process( $job_id );
		} finally {
			if ( $switched ) {
				restore_current_blog();
			}
		}
	}

	/**
	 * Execute the full generation pipeline for a job.
	 */
	public function process( int $job_id ): void {
		$job = $this->jobs->find( $job_id );
		if ( ! $job ) {
			return;
		}

		$status = JobStatus::from( $job[ 'status' ] );
		if ( $status !== JobStatus::Queued ) {
			return;
		}

		$this->jobs->transition( $job_id, JobStatus::Running );

		try {
			$episode_id = (int) $job[ 'episode_id' ];
			$manuscript = json_decode(
				get_post_meta( $episode_id, EpisodeCPT::META_KEY_MANUSCRIPT, true ),
				true
			);

			if ( ! $manuscript || empty( $manuscript[ 'segments' ] ) ) {
				throw new \RuntimeException( 'Invalid manuscript data.' );
			}

			// Verify content hasn't changed since job was queued.
			$hasher       = new ManuscriptHasher();
			$current_hash = $hasher->hash( $manuscript );
			if ( $current_hash !== $job[ 'manuscript_hash' ] ) {
				throw new \RuntimeException( 'Manuscript changed since job was queued.' );
			}

			$chunk_paths = [];

			// Phase 1: Generate TTS audio for each segment.
			foreach ( $manuscript[ 'segments' ] as $segment ) {
				$provider = $this->resolve_provider( $segment[ 'provider' ] ?? 'openai' );
				$chunk    = $provider->synthesize(
					text: $segment[ 'text' ],
					voiceId: $segment[ 'voiceId' ],
					options: [
						'speed'        => 1.0,
						'segmentIndex' => $segment[ 'index' ],
					],
				);

				$filename = sprintf(
					'episode-%d-seg-%03d.%s',
					$episode_id,
					$segment[ 'index' ],
					$chunk->format
				);

				$path = $this->storage->store(
					$filename,
					$chunk->data,
					"episodes/{$episode_id}"
				);

				$this->assets->create(
					job_id: $job_id,
					episode_id: $episode_id,
					segment_index: $segment[ 'index' ],
					asset_type: 'chunk',
					file_path: $path,
					file_url: $this->storage->url( $path ),
					file_size: $chunk->sizeBytes,
					duration_ms: $chunk->durationMs,
					format: $chunk->format,
				);

				$chunk_paths[] = $path;
				$this->jobs->increment_progress( $job_id );

				// Rate limiting between API calls.
				$rate_limit = (int) SettingsPage::get( 'rate_limit_per_min' );
				if ( $rate_limit > 0 ) {
					usleep( (int) ( ( 60 / $rate_limit ) * 1_000_000 ) );
				}
			}

			// Phase 2: Stitch chunks into final audio file.
			$final_filename = sprintf( 'episode-%d-final.mp3', $episode_id );
			$output_dir     = $this->storage->ensure_dir( "episodes/{$episode_id}" );
			$final_path     = $this->stitcher->stitch(
				$chunk_paths,
				$output_dir . '/' . $final_filename,
				(int) SettingsPage::get( 'silence_gap_ms' ),
			);

			$final_url  = $this->storage->url( $final_path );
			$final_size = filesize( $final_path ) ?: 0;

			$this->assets->create(
				job_id: $job_id,
				episode_id: $episode_id,
				segment_index: null,
				asset_type: 'final',
				file_path: $final_path,
				file_url: $final_url,
				file_size: $final_size,
				duration_ms: 0,
				format: 'mp3',
			);

			// Update episode metadata.
			update_post_meta( $episode_id, EpisodeCPT::META_KEY_AUDIO_URL, $final_url );
			update_post_meta( $episode_id, EpisodeCPT::META_KEY_STATUS, EpisodeStatus::Generated->value );

			$this->jobs->transition( $job_id, JobStatus::Succeeded );

		} catch (\Throwable $e) {
			$this->jobs->transition( $job_id, JobStatus::Failed, $e->getMessage() );
			update_post_meta(
				(int) $job[ 'episode_id' ],
				EpisodeCPT::META_KEY_STATUS,
				EpisodeStatus::Failed->value
			);
		}
	}

	private function resolve_provider( string $slug ): ProviderInterface {
		if ( isset( $this->providers[ $slug ] ) ) {
			return $this->providers[ $slug ];
		}

		$provider = match ( $slug ) {
			'azure_openai' => new AzureOpenAIProvider(
				apiKey: SettingsPage::get( 'azure_openai_api_key' ),
				endpoint: SettingsPage::get( 'azure_openai_endpoint' ),
				deploymentId: SettingsPage::get( 'azure_openai_deployment_id' ),
				apiVersion: SettingsPage::get( 'azure_openai_api_version' ),
			),
			default        => new OpenAIProvider(
				apiKey: SettingsPage::get( 'openai_api_key' ),
				model: SettingsPage::get( 'openai_tts_model' ),
			),
		};

		$this->providers[ $slug ] = $provider;
		return $provider;
	}
}
