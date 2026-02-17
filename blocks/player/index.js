import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import Save from './save';
import './style.scss';
import './editor.scss';

registerBlockType( 'talking-head/player', {
	edit: Edit,
	save: Save,
} );
