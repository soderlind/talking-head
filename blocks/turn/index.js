import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import Save from './save';
import './editor.scss';

registerBlockType( 'talking-head/turn', {
	edit: Edit,
	save: Save,
} );
