import React from 'react';

export function PluginDocumentSettingPanel( { children, title } ) {
	return React.createElement( 'div', { 'data-testid': 'PluginDocumentSettingPanel', 'data-title': title }, children );
}
