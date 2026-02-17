import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import Save from './save';
import './editor.scss';

registerBlockType( 'talking-head/episode', {
	edit: Edit,
	save: Save,
} );
