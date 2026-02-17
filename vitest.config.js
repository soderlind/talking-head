import { defineConfig } from 'vitest/config';
import path from 'path';
import { transform } from 'esbuild';

const mocks = path.resolve( import.meta.dirname, 'tests/js/mocks' );

/**
 * Custom plugin to handle JSX in .js files.
 * WordPress convention uses .js with Babel; Vite requires .jsx by default.
 */
function jsxPlugin() {
	return {
		name: 'jsx-in-js',
		enforce: 'pre',
		async transform( code, id ) {
			if ( ! id.endsWith( '.js' ) || id.includes( 'node_modules' ) ) {
				return null;
			}
			// Quick check: skip files that clearly have no JSX.
			if ( ! code.includes( '<' ) ) {
				return null;
			}
			const result = await transform( code, {
				loader: 'jsx',
				jsx: 'automatic',
				sourcefile: id,
			} );
			return { code: result.code, map: result.map };
		},
	};
}

export default defineConfig( {
	plugins: [ jsxPlugin() ],
	esbuild: {
		jsx: 'automatic',
	},
	resolve: {
		alias: {
			'@wordpress/element': path.join( mocks, 'wp-element.js' ),
			'@wordpress/data': path.join( mocks, 'wp-data.js' ),
			'@wordpress/i18n': path.join( mocks, 'wp-i18n.js' ),
			'@wordpress/components': path.join( mocks, 'wp-components.js' ),
			'@wordpress/block-editor': path.join( mocks, 'wp-block-editor.js' ),
			'@wordpress/editor': path.join( mocks, 'wp-editor.js' ),
			'@wordpress/blocks': path.join( mocks, 'wp-blocks.js' ),
			'@wordpress/plugins': path.join( mocks, 'wp-plugins.js' ),
		},
	},
	test: {
		environment: 'jsdom',
		globals: true,
		setupFiles: [ './tests/js/setup.js' ],
		css: false,
	},
} );
