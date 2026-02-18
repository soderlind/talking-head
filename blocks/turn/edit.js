import {
	useBlockProps,
	RichText,
} from '@wordpress/block-editor';
import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';

export default function Edit( { attributes, setAttributes } ) {
	const { headId, headName, text } = attributes;
	const blockProps = useBlockProps( { className: 'th-turn' } );
	const [ heads, setHeads ] = useState( [] );
	const [ editing, setEditing ] = useState( headId === 0 );

	const plainText = text ? text.replace( /<[^>]+>/g, '' ) : '';
	const charCount = plainText.length;
	const maxChars = window.talkingHeadSettings?.maxSegmentChars || 4096;
	const isOverLimit = charCount > maxChars;

	useEffect( () => {
		wp.apiFetch( { path: '/talking-head/v1/heads' } )
			.then( ( data ) => setHeads( data ) )
			.catch( () => {} );
	}, [] );

	const headOptions = [
		{
			label: __( '-- Select Speaker --', 'talking-head' ),
			value: 0,
		},
		...heads.map( ( h ) => ( { label: h.name, value: h.id } ) ),
	];

	const onSelectHead = ( value ) => {
		const id = parseInt( value, 10 );
		const selected = heads.find( ( h ) => h.id === id );
		setAttributes( {
			headId: id,
			headName: selected ? selected.name : '',
		} );
		if ( id > 0 ) {
			setEditing( false );
		}
	};

	return (
		<div { ...blockProps }>
			<div className="th-turn__header">
				{ editing || headId === 0 ? (
					<SelectControl
						value={ headId }
						options={ headOptions }
						onChange={ onSelectHead }
						className="th-turn__speaker-select"
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				) : (
					<button
						type="button"
						className="th-turn__speaker-label"
						onClick={ () => setEditing( true ) }
					>
						{ headName }
					</button>
				) }
			</div>
			<RichText
				tagName="div"
				className="th-turn__text"
				value={ text }
				onChange={ ( value ) =>
					setAttributes( { text: value } )
				}
				placeholder={ __(
					'Enter dialogue...',
					'talking-head'
				) }
				allowedFormats={ [ 'core/bold', 'core/italic' ] }
			/>
			<span className={ `th-turn__char-count${ isOverLimit ? ' th-turn__char-count--over' : '' }` }>
				{ charCount } / { maxChars }
			</span>
		</div>
	);
}
