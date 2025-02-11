## Comments
MediaWiki extension which allows users to leave comments on a page, which is displayed underneath the page content.

## Installing
1. Enable the extension using `wfLoadExtension( 'Comments' );`
2. Run `update.php` to create the required database tables

To allow users to be blocked from leaving comments, `$wgEnablePartialActionBlocks = true;` should also be set.

## Developing
We use Webpack (an asset bundler) for building the frontend JS required for the extension.

The source files are located in `src/frontend/`, and the compiled assets are in `src/resources`.

1. Run `npm install`
2. To start Webpack in watch mode, run `npm start`
3. When you are ready to commit changes, run `npm run build`
