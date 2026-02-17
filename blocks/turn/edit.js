import {
	useBlockProps,
	RichText,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';

export default function Edit( { attributes, setAttributes } ) {
	const { headId, headName, text } = attributes;
	const blockProps = useBlockProps( { className: 'th-turn' } );
	const [ heads, setHeads ] = useState( [] );

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
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Speaker', 'talking-head' ) }>
					<SelectControl
						label={ __( 'Head', 'talking-head' ) }
						value={ headId }
						options={ headOptions }
						onChange={ onSelectHead }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<div className="th-turn__header">
					<SelectControl
						value={ headId }
						options={ headOptions }
						onChange={ onSelectHead }
						className="th-turn__speaker-select"
						__nextHasNoMarginBottom
					/>
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
			</div>
		</>
	);
}
