import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	ToggleControl,
	Placeholder,
	Spinner,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';

export default function Edit( { attributes, setAttributes } ) {
	const { episodeId, showTranscript } = attributes;
	const blockProps = useBlockProps( { className: 'th-player-editor' } );
	const [ episode, setEpisode ] = useState( null );
	const [ loading, setLoading ] = useState( false );

	useEffect( () => {
		if ( ! episodeId ) {
			setEpisode( null );
			return;
		}

		setLoading( true );
		wp.apiFetch( {
			path: `/talking-head/v1/episodes/${ episodeId }/player`,
		} )
			.then( ( data ) => {
				setEpisode( data );
				setLoading( false );
			} )
			.catch( () => {
				setEpisode( null );
				setLoading( false );
			} );
	}, [ episodeId ] );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Player Settings', 'talking-head' ) }
				>
					<TextControl
						label={ __( 'Episode ID', 'talking-head' ) }
						type="number"
						value={ episodeId || '' }
						onChange={ ( value ) =>
							setAttributes( {
								episodeId: parseInt( value, 10 ) || 0,
							} )
						}
					/>
					<ToggleControl
						label={ __(
							'Show Transcript',
							'talking-head'
						) }
						checked={ showTranscript }
						onChange={ ( value ) =>
							setAttributes( { showTranscript: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				{ ! episodeId && (
					<Placeholder
						icon="controls-play"
						label={ __(
							'Talking Head Player',
							'talking-head'
						) }
						instructions={ __(
							'Enter an Episode ID in the block settings to display the player.',
							'talking-head'
						) }
					/>
				) }
				{ episodeId > 0 && loading && <Spinner /> }
				{ episodeId > 0 && ! loading && episode && (
					<div className="th-player-preview">
						<h4>{ episode.title }</h4>
						{ episode.audioUrl ? (
							<audio
								controls
								src={ episode.audioUrl }
								style={ { width: '100%' } }
							/>
						) : (
							<p>
								{ __(
									'Audio not yet generated.',
									'talking-head'
								) }
							</p>
						) }
					</div>
				) }
				{ episodeId > 0 && ! loading && ! episode && (
					<Placeholder
						icon="warning"
						label={ __(
							'Episode not found',
							'talking-head'
						) }
					/>
				) }
			</div>
		</>
	);
}
