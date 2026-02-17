import { registerPlugin } from '@wordpress/plugins';
import { HeadPanel } from './head-panel';

registerPlugin( 'talking-head-head-sidebar', {
	render: HeadPanel,
} );
