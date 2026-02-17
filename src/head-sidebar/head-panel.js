import { PluginDocumentSettingPanel } from '@wordpress/editor';
import {
	SelectControl,
	RangeControl,
	TextareaControl,
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
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

	if ( postType !== 'talking_head_head' ) {
		return null;
	}

	const setMeta = ( key, value ) =>
		editPost( { meta: { [ key ]: value } } );

	return (
		<PluginDocumentSettingPanel
			name="talking-head-head-settings"
			title={ __( 'Voice Settings', 'talking-head' ) }
		>
			<SelectControl
				label={ __( 'Voice', 'talking-head' ) }
				value={ meta._th_voice_id || 'alloy' }
				options={ VOICE_OPTIONS }
				onChange={ ( val ) => setMeta( '_th_voice_id', val ) }
				__nextHasNoMarginBottom
			/>
			<SelectControl
				label={ __( 'Provider', 'talking-head' ) }
				value={ meta._th_provider || 'openai' }
				options={ PROVIDER_OPTIONS }
				onChange={ ( val ) => setMeta( '_th_provider', val ) }
				__nextHasNoMarginBottom
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
				label={ __(
					'Speaking Style / Instructions',
					'talking-head'
				) }
				help={ __(
					'Instructions for the TTS model (requires gpt-4o-mini-tts).',
					'talking-head'
				) }
				value={ meta._th_speaking_style || '' }
				onChange={ ( val ) =>
					setMeta( '_th_speaking_style', val )
				}
			/>
		</PluginDocumentSettingPanel>
	);
}
