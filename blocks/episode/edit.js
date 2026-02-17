import {
	useBlockProps,
	useInnerBlocksProps,
	InspectorControls,
	BlockControls,
} from '@wordpress/block-editor';
import {
	PanelBody,
	Spinner,
	Notice,
	Icon,
	ToolbarGroup,
	ToolbarButton,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { useState, useCallback, useEffect, useRef } from '@wordpress/element';

const TEMPLATE = [ [ 'talking-head/turn', {} ] ];
const POLL_INTERVAL = 3000;

export default function Edit( { clientId } ) {
	const blockProps = useBlockProps( { className: 'th-episode' } );
	const [ generating, setGenerating ] = useState( false );
	const [ jobStatus, setJobStatus ] = useState( null );
	const pollRef = useRef( null );

	const { isSelected, hasChildSelected, hasInnerBlocks } = useSelect(
		( select ) => {
			const { isBlockSelected, hasSelectedInnerBlock, getBlockCount } =
				select( 'core/block-editor' );
			return {
				isSelected: isBlockSelected( clientId ),
				hasChildSelected: hasSelectedInnerBlock( clientId, true ),
				hasInnerBlocks: getBlockCount( clientId ) > 0,
			};
		},
		[ clientId ]
	);

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

	const showAppender = isSelected || hasChildSelected || ! hasInnerBlocks;

	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'th-episode__content' },
		{
			template: TEMPLATE,
			defaultBlock: { name: 'talking-head/turn' },
			directInsert: true,
			templateInsertUpdatesSelection: true,
			renderAppender: showAppender
				? undefined
				: false,
		}
	);

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
						setGenerating( false );
					}
				} catch {
					stopPolling();
					setGenerating( false );
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
			} else {
				setGenerating( false );
			}
		} catch ( err ) {
			setJobStatus( {
				status: 'failed',
				error: err.message,
			} );
			setGenerating( false );
		}
	};

	const statusClassName = `th-episode__status th-episode__status--${ episodeStatus }`;

	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					<ToolbarButton
						icon={ generating ? undefined : 'controls-play' }
						label={ __( 'Generate Audio', 'talking-head' ) }
						onClick={ handleGenerate }
						disabled={ generating }
					>
						{ generating && <Spinner /> }
					</ToolbarButton>
				</ToolbarGroup>
			</BlockControls>
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
					{ jobStatus?.status === 'queued' && (
						<Notice status="info" isDismissible={ false }>
							{ __( 'Queued — waiting for processing...', 'talking-head' ) }
						</Notice>
					) }
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
				<div className="th-episode__header">
					<Icon icon="microphone" size={ 16 } />
					<span className="th-episode__label">
						{ __( 'Episode', 'talking-head' ) }
					</span>
					{ episodeStatus !== 'draft' && (
						<span className={ statusClassName }>
							{ episodeStatus.charAt( 0 ).toUpperCase() +
								episodeStatus.slice( 1 ) }
						</span>
					) }
				</div>
				<div { ...innerBlocksProps } />
			</div>
		</>
	);
}
