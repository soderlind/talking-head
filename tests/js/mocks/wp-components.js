import React from 'react';

const passThrough = ( name ) =>
	function MockComponent( { children, label, title, help, ...rest } ) {
		return React.createElement(
			'div',
			{ 'data-testid': name, 'data-label': label, 'data-title': title, 'data-help': help },
			children,
			label && React.createElement( 'span', { className: 'label' }, label ),
		);
	};

export const PanelBody = passThrough( 'PanelBody' );
export const ComboboxControl = passThrough( 'ComboboxControl' );
export const SelectControl = passThrough( 'SelectControl' );
export const ToggleControl = passThrough( 'ToggleControl' );
export const RangeControl = passThrough( 'RangeControl' );
export const TextareaControl = passThrough( 'TextareaControl' );
export const TextControl = passThrough( 'TextControl' );
export const Notice = passThrough( 'Notice' );
export const Icon = passThrough( 'Icon' );
export const ToolbarGroup = passThrough( 'ToolbarGroup' );
export const ToolbarButton = passThrough( 'ToolbarButton' );

export function Placeholder( { label, instructions, children } ) {
	return React.createElement( 'div', { 'data-testid': 'Placeholder' },
		label && React.createElement( 'span', { className: 'label' }, label ),
		instructions && React.createElement( 'span', { className: 'instructions' }, instructions ),
		children,
	);
}

export function Spinner() {
	return React.createElement( 'div', { 'data-testid': 'Spinner' } );
}
