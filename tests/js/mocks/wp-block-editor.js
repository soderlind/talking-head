import React from 'react';

export const useBlockProps = ( props ) => ( { ...props } );
useBlockProps.save = ( props ) => ( { ...props } );

export function InspectorControls( { children } ) {
	return React.createElement( 'div', { 'data-testid': 'InspectorControls' }, children );
}

export function BlockControls( { children } ) {
	return React.createElement( 'div', { 'data-testid': 'BlockControls' }, children );
}

export const useInnerBlocksProps = ( blockProps ) => ( { ...blockProps } );

export const RichText = Object.assign(
	function MockRichText( { value, placeholder, tagName, className } ) {
		return React.createElement( tagName || 'div', { className, 'data-testid': 'RichText' }, value );
	},
	{
		Content: function MockRichTextContent( { value, tagName, className } ) {
			return React.createElement( tagName || 'div', {
				className,
				'data-testid': 'RichText.Content',
				dangerouslySetInnerHTML: { __html: value || '' },
			} );
		},
	}
);

export const InnerBlocks = {
	Content: function MockInnerBlocksContent() {
		return React.createElement( 'div', { 'data-testid': 'InnerBlocks.Content' } );
	},
};
