> ## DO NOT USE THIS EXTENSION!
> It is not yet ready for production.

# Comments
MediaWiki extension which allows users to leave comments on a page, which is displayed underneath the page content.

## Dependencies
Requires MediaWiki 1.41+ and the [VisualEditor](https://www.mediawiki.org/wiki/Extension:VisualEditor) extension installed.

## Installing
1. Enable the extension using `wfLoadExtension( 'Comments' );`
2. Run `update.php` to create the required database tables

To allow users to be blocked from leaving comments, `$wgEnablePartialActionBlocks = true;` should also be set.

## Configuration
| Variable                  | Description                                                                                                   | Default |
|---------------------------|---------------------------------------------------------------------------------------------------------------|---------|
| $wgCommentsShowOnMainPage | If enabled, show comments on the main page                                                                    | `false` |
| $wgCommentsResultsPerPage | How many comments to load at a time by default. This value cannot be higher than 100 for performance reasons. | `50`    |
| $wgCommentsReadOnly       | If enabled, new comments can be posted and existing comments can be edited                                    | `false` |
| $wgCommentsUseAbuseFilter | If enabled, run comments through [AbuseFilter](https://www.mediawiki.org/wiki/Extension:AbuseFilter)          | `true`  |


## How does it work?
Each wiki page has a comments section displayed at the bottom, which loads the comments (default: `50`) when the user scrolls down to it. Users can leave new comments, or reply to existing comments, which will be attributed to their wiki account (their entry in the core MediaWiki `actor` database table).

When a user submits a comment, the HTML of the comment is converted (and sanitized) to wikitext syntax using MediaWiki's built-in Parsoid parser and stored. No new pages or namespaces are created by this extension; the comments are stored in their own table, `com_comment`.

Comments can be upvoted or downvoted, which will change the score displayed on each comment. This feature helps people find the most useful comments more easily. By default, the comments list is ordered by rating.

### Moderation
Users with the `comments-manage` permission can delete other user's comments. Users can be blocked from editing using the standard Special:Block form by a user that has the `block` right (typically sysops).

#### AbuseFilter
If `$wgCommentsUseAbuseFilter` is enabled and the [AbuseFilter](https://www.mediawiki.org/wiki/Extension:AbuseFilter) extension is installed, comments will run through all enabled filters on the wiki with the "action" variable set to "comments" and the "new_wikitext" value set to the wikitext of the comment. An example of a rule that would match is: `action == 'comment' & new_wikitext irlike "badword"`
