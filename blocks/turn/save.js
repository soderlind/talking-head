import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function Save( { attributes } ) {
	const { headName, text } = attributes;
	const blockProps = useBlockProps.save( { className: 'th-turn' } );
	return (
		<div { ...blockProps }>
			<div className="th-turn__header">
				<span className="th-turn__speaker">{ headName }</span>
			</div>
			<RichText.Content
				tagName="div"
				className="th-turn__text"
				value={ text }
			/>
		</div>
	);
}
