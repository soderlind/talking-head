import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

export default function Save() {
	const blockProps = useBlockProps.save( { className: 'th-episode' } );
	return (
		<div { ...blockProps }>
			<InnerBlocks.Content />
		</div>
	);
}
