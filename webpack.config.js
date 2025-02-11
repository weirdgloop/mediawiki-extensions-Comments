/**
 * @see https://github.com/wikimedia/mediawiki-extensions-Popups/blob/master/webpack.config.js
 */
/* eslint-env node */
const { CleanWebpackPlugin } = require( 'clean-webpack-plugin' );
const path = require( 'path' );

const PUBLIC_PATH = '/w/extensions/Comments';

const distDir = path.resolve( __dirname, 'src/resources/dist' );

// The extension used for source map files.
const srcMapExt = '.map.json';

module.exports = ( env, argv ) => ( {
	stats: {
		all: false,
		builtAt: true,
		errors: true,
		warnings: true
	},

	// Fail on build errors in production
	bail: argv.mode === 'production',

	context: __dirname,

	entry: { index: './src/frontend' },

	module: {
		rules: [ {
			test: /\.js$/,
			exclude: /node_modules/,
			use: {
				loader: 'babel-loader',
				options: {
					cacheDirectory: true
				}
			}
		}, {
			test: /\.svg$/,
			loader: 'svg-inline-loader',
			options: {
				removeSVGTagAttrs: false // Keep width and height attributes.
			}
		}, {
			test: /\.css$/,
			use: [
				'style-loader',
				'css-loader'
			]
		} ]
	},
	optimization: {
		// Don't produce production output when a build error occurs.
		emitOnErrors: argv.mode !== 'production',

		// Use filenames instead of unstable numerical identifiers for file references. This
		// increases the gzipped bundle size some but makes the build products easier to debug and
		// appear deterministic. I.e., code changes will only alter the bundle they're packed in
		// instead of shifting the identifiers in other bundles.
		// https://webpack.js.org/guides/caching/#deterministic-hashes (namedModules replaces NamedModulesPlugin.)
		moduleIds: 'named'
	},

	output: {
		// Specify the destination of all build products.
		path: distDir,

		// Store outputs per module in files named after the modules. For the JavaScript entry
		// itself, append .js to each ResourceLoader module entry name. This value is tightly
		// coupled to sourceMapFilename.
		filename: '[name].js',

		// Rename source map extensions. Per T173491 files with a .map extension cannot be served
		// from prod.
		sourceMapFilename: `[file]${ srcMapExt }`,

		devtoolModuleFilenameTemplate: `${ PUBLIC_PATH }/[resource-path]`
	},

	// Accurate source maps at the expense of build time. The source map is intentionally exposed
	// to users via sourceMapFilename for prod debugging. This goes against convention as source
	// code is publicly distributed.
	devtool: 'source-map',

	plugins: [
		// Delete the output directory on each build.
		new CleanWebpackPlugin( {
			cleanOnceBeforeBuildPatterns: [ '**/*', '!.eslintrc.json' ]
		} )
	],

	performance: {
		hints: argv.mode === 'production' ? 'error' : false,
		assetFilter: ( filename ) => !filename.endsWith( srcMapExt )
	}
} );
