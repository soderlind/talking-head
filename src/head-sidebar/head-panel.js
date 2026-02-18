import { PluginDocumentSettingPanel } from '@wordpress/editor';
import {
	SelectControl,
	RangeControl,
	TextareaControl,
	Button,
	Flex,
	FlexBlock,
	FlexItem,
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { useState, useEffect, useRef, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const VOICE_OPTIONS = [
	{ label: 'Alloy', value: 'alloy' },
	{ label: 'Echo', value: 'echo' },
	{ label: 'Fable', value: 'fable' },
	{ label: 'Onyx', value: 'onyx' },
	{ label: 'Nova', value: 'nova' },
	{ label: 'Shimmer', value: 'shimmer' },
];

const PROVIDER_OPTIONS = [
	{ label: 'OpenAI', value: 'openai' },
	{ label: 'Azure OpenAI', value: 'azure_openai' },
];

/* global talkingHeadVoiceSamples */
const samples =
	typeof talkingHeadVoiceSamples !== 'undefined'
		? talkingHeadVoiceSamples
		: {};

function useVoicePreview() {
	const audioRef = useRef( null );
	const [ playing, setPlaying ] = useState( false );

	const stop = useCallback( () => {
		if ( audioRef.current ) {
			audioRef.current.pause();
			audioRef.current.currentTime = 0;
		}
		setPlaying( false );
	}, [] );

	const toggle = useCallback(
		( voice ) => {
			const url = samples[ voice ];
			if ( ! url ) {
				return;
			}

			if ( playing ) {
				stop();
				return;
			}

			if ( ! audioRef.current ) {
				audioRef.current = new Audio();
				audioRef.current.addEventListener( 'ended', () =>
					setPlaying( false )
				);
			}

			audioRef.current.src = url;
			audioRef.current.play();
			setPlaying( true );
		},
		[ playing, stop ]
	);

	// Cleanup on unmount.
	useEffect( () => {
		return () => {
			if ( audioRef.current ) {
				audioRef.current.pause();
				audioRef.current = null;
			}
		};
	}, [] );

	return { playing, toggle, stop };
}

export function HeadPanel() {
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	const meta = useSelect(
		( select ) =>
			select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {},
		[]
	);

	const { editPost } = useDispatch( 'core/editor' );
	const { playing, toggle, stop } = useVoicePreview();

	if ( postType !== 'talking_head_head' ) {
		return null;
	}

	const setMeta = ( key, value ) => editPost( { meta: { [ key ]: value } } );

	const voiceId = meta._th_voice_id || 'alloy';
	const hasSample = !! samples[ voiceId ];

	return (
		<PluginDocumentSettingPanel
			name="talking-head-head-settings"
			title={ __( 'Voice Settings', 'talking-head' ) }
		>
			<Flex align="flex-end" gap={ 2 }>
				<FlexBlock>
					<SelectControl
						label={ __( 'Voice', 'talking-head' ) }
						value={ voiceId }
						options={ VOICE_OPTIONS }
						onChange={ ( val ) => {
							stop();
							setMeta( '_th_voice_id', val );
						} }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</FlexBlock>
				{ hasSample && (
					<FlexItem>
						<Button
							icon={
								playing ? 'controls-pause' : 'controls-play'
							}
							label={
								playing
									? __( 'Stop preview', 'talking-head' )
									: __( 'Preview voice', 'talking-head' )
							}
							onClick={ () => toggle( voiceId ) }
							size="compact"
						/>
					</FlexItem>
				) }
			</Flex>
			<SelectControl
				label={ __( 'Provider', 'talking-head' ) }
				value={ meta._th_provider || 'openai' }
				options={ PROVIDER_OPTIONS }
				onChange={ ( val ) => setMeta( '_th_provider', val ) }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
			<RangeControl
				label={ __( 'Speed', 'talking-head' ) }
				value={ meta._th_speed ?? 1.0 }
				onChange={ ( val ) => setMeta( '_th_speed', val ) }
				min={ 0.25 }
				max={ 4.0 }
				step={ 0.05 }
				__nextHasNoMarginBottom
			/>
			<TextareaControl
				label={ __( 'Speaking Style / Instructions', 'talking-head' ) }
				help={ __(
					'Instructions for the TTS model (requires gpt-4o-mini-tts).',
					'talking-head'
				) }
				value={ meta._th_speaking_style || '' }
				onChange={ ( val ) => setMeta( '_th_speaking_style', val ) }
				__nextHasNoMarginBottom
			/>
		</PluginDocumentSettingPanel>
	);
}
