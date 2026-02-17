const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );
const CopyPlugin = require( 'copy-webpack-plugin' );

module.exports = {
	...defaultConfig,
	entry: {
		'blocks/episode/index': path.resolve( __dirname, 'blocks/episode/index.js' ),
		'blocks/turn/index': path.resolve( __dirname, 'blocks/turn/index.js' ),
		'blocks/player/index': path.resolve( __dirname, 'blocks/player/index.js' ),
		'blocks/player/view': path.resolve( __dirname, 'blocks/player/view.js' ),
		'head-sidebar/index': path.resolve( __dirname, 'src/head-sidebar/index.js' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'build' ),
	},
	plugins: [
		...( defaultConfig.plugins || [] ),
		new CopyPlugin( {
			patterns: [
				{
					from: 'blocks/*/block.json',
					to: '[path][name][ext]',
				},
			],
		} ),
	],
};
