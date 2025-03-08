> ## DO NOT USE THIS EXTENSION!
> It is not yet ready for production.

## Comments
MediaWiki extension which allows users to leave comments on a page, which is displayed underneath the page content.

## Installing
1. Enable the extension using `wfLoadExtension( 'Comments' );`
2. Run `update.php` to create the required database tables

To allow users to be blocked from leaving comments, `$wgEnablePartialActionBlocks = true;` should also be set.
