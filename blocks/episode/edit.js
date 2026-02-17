import {
	useBlockProps,
	InnerBlocks,
	InspectorControls,
} from '@wordpress/block-editor';
import {
	PanelBody,
	Button,
	Spinner,
	Notice,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { useState, useCallback, useEffect, useRef } from '@wordpress/element';

const ALLOWED_BLOCKS = [ 'talking-head/turn' ];
const TEMPLATE = [ [ 'talking-head/turn', {} ] ];
const POLL_INTERVAL = 3000;

export default function Edit() {
	const blockProps = useBlockProps( { className: 'th-episode' } );
	const [ generating, setGenerating ] = useState( false );
	const [ jobStatus, setJobStatus ] = useState( null );
	const pollRef = useRef( null );

	const postId = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostId(),
		[]
	);

	const episodeStatus = useSelect( ( select ) => {
		const meta =
			select( 'core/editor' ).getEditedPostAttribute( 'meta' );
		return meta?._th_episode_status || 'draft';
	}, [] );

	const audioUrl = useSelect( ( select ) => {
		const meta =
			select( 'core/editor' ).getEditedPostAttribute( 'meta' );
		return meta?._th_audio_url || '';
	}, [] );

	const stopPolling = useCallback( () => {
		if ( pollRef.current ) {
			clearInterval( pollRef.current );
			pollRef.current = null;
		}
	}, [] );

	const pollJobStatus = useCallback(
		( jobId ) => {
			stopPolling();
			pollRef.current = setInterval( async () => {
				try {
					const status = await wp.apiFetch( {
						path: `/talking-head/v1/jobs/${ jobId }`,
					} );
					setJobStatus( status );
					if (
						[ 'succeeded', 'failed', 'canceled' ].includes(
							status.status
						)
					) {
						stopPolling();
					}
				} catch {
					stopPolling();
				}
			}, POLL_INTERVAL );
		},
		[ stopPolling ]
	);

	useEffect( () => {
		return () => stopPolling();
	}, [ stopPolling ] );

	const handleGenerate = async () => {
		setGenerating( true );
		try {
			const response = await wp.apiFetch( {
				path: '/talking-head/v1/jobs',
				method: 'POST',
				data: { episodeId: postId },
			} );
			setJobStatus( response );
			if ( response.jobId ) {
				pollJobStatus( response.jobId );
			}
		} catch ( err ) {
			setJobStatus( {
				status: 'failed',
				error: err.message,
			} );
		}
		setGenerating( false );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Episode', 'talking-head' ) }
					initialOpen
				>
					<p>
						<strong>
							{ __( 'Status:', 'talking-head' ) }
						</strong>{ ' ' }
						{ episodeStatus }
					</p>
					{ audioUrl && (
						<audio
							controls
							src={ audioUrl }
							style={ { width: '100%', marginBottom: '1rem' } }
						/>
					) }
					<Button
						variant="primary"
						onClick={ handleGenerate }
						disabled={ generating }
					>
						{ generating ? (
							<Spinner />
						) : (
							__( 'Generate Audio', 'talking-head' )
						) }
					</Button>
					{ jobStatus?.status === 'running' && (
						<Notice status="info" isDismissible={ false }>
							{ __( 'Generating...', 'talking-head' ) }
							{ ` ${ jobStatus.progress || 0 }%` }
						</Notice>
					) }
					{ jobStatus?.status === 'failed' && (
						<Notice status="error" isDismissible={ false }>
							{ jobStatus.error ||
								__(
									'Generation failed.',
									'talking-head'
								) }
						</Notice>
					) }
					{ jobStatus?.status === 'succeeded' && (
						<Notice status="success" isDismissible={ false }>
							{ __(
								'Audio generated successfully!',
								'talking-head'
							) }
						</Notice>
					) }
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<InnerBlocks
					allowedBlocks={ ALLOWED_BLOCKS }
					template={ TEMPLATE }
					renderAppender={ InnerBlocks.ButtonBlockAppender }
				/>
			</div>
		</>
	);
}
