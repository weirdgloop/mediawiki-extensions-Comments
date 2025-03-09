> ## DO NOT USE THIS EXTENSION!
> It is not yet ready for production.

## Comments
MediaWiki extension which allows users to leave comments on a page, which is displayed underneath the page content.

## Dependencies
Requires MediaWiki 1.41+ and the [VisualEditor](https://www.mediawiki.org/wiki/Extension:VisualEditor) extension installed.

## Installing
1. Enable the extension using `wfLoadExtension( 'Comments' );`
2. Run `update.php` to create the required database tables

To allow users to be blocked from leaving comments, `$wgEnablePartialActionBlocks = true;` should also be set.
