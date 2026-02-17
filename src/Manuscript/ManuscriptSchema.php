<?php

declare(strict_types=1);

namespace TalkingHead\Manuscript;

defined( 'ABSPATH' ) || exit;

final class ManuscriptSchema {

	/**
	 * Get the JSON Schema for a manuscript as a PHP array.
	 *
	 * @return array JSON Schema definition.
	 */
	public static function schema(): array {
		return [
			'type'       => 'object',
			'required'   => [ 'version', 'episodeId', 'title', 'segments' ],
			'properties' => [
				'version'   => [
					'type'  => 'string',
					'const' => '1.0',
				],
				'episodeId' => [
					'type'    => 'integer',
					'minimum' => 1,
				],
				'title'     => [
					'type'      => 'string',
					'minLength' => 1,
					'maxLength' => 200,
				],
				'segments'  => [
					'type'     => 'array',
					'minItems' => 1,
					'maxItems' => 200,
					'items'    => self::segment_schema(),
				],
			],
		];
	}

	private static function segment_schema(): array {
		return [
			'type'       => 'object',
			'required'   => [ 'index', 'headId', 'headName', 'voiceId', 'provider', 'text' ],
			'properties' => [
				'index'    => [
					'type'    => 'integer',
					'minimum' => 0,
				],
				'headId'   => [
					'type'    => 'integer',
					'minimum' => 1,
				],
				'headName' => [
					'type'      => 'string',
					'minLength' => 1,
				],
				'voiceId'  => [
					'type'      => 'string',
					'minLength' => 1,
				],
				'provider' => [
					'type' => 'string',
					'enum' => [ 'openai', 'azure_openai' ],
				],
				'text'     => [
					'type'      => 'string',
					'minLength' => 1,
					'maxLength' => 4096,
				],
			],
		];
	}
}
