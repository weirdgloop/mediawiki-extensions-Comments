# Yappin
MediaWiki extension which allows users to leave comments on a page, which is displayed underneath the page content.

## Dependencies
Requires MediaWiki 1.41+ and the [VisualEditor](https://www.mediawiki.org/wiki/Extension:VisualEditor) extension installed.

## Installing
1. Enable the extension using `wfLoadExtension( 'Yappin' );`
2. Run `update.php` to create the required database tables

To allow users to be blocked from leaving comments, `$wgEnablePartialActionBlocks = true;` should also be set.

## Configuration
| Variable                     | Description                                                                                                                                                              | Default                |
|------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------------------|
| $wgCommentsShowOnMainPage    | If enabled, show comments on the main page                                                                                                                               | `false`                |
| $wgCommentsResultsPerPage    | How many comments to load at a time by default. This value cannot be higher than 100 for performance reasons.                                                            | `50`                   |
| $wgCommentsReadOnly          | If enabled, new comments can be posted and existing comments can be edited                                                                                               | `false`                |
| $wgCommentsUseAbuseFilter    | If enabled, run comments through [AbuseFilter](https://www.mediawiki.org/wiki/Extension:AbuseFilter)                                                                     | `true`                 |
| $wgCommentsEnabledNamespaces | The namespaces that comments are enabled on. If a namespace is not in here, comments are not visible on that namespace's pages, and new comments cannot be left on them. | `$wgContentNamespaces` |

> Comments are disabled on talk pages, special pages, and non-existent pages, regardless of if the page's namespace is in `$wgCommentsEnabledNamespaces`.

## How does it work?
Each wiki page has a comments section displayed at the bottom, which loads the comments (default: `50`) when the user scrolls down to it. Users can leave new comments, or reply to existing comments, which will be attributed to their wiki account or their IP address (if anonymous).

When a user submits a comment, the HTML of the comment is converted (and sanitized) to wikitext syntax using MediaWiki's built-in Parsoid parser and stored. No new pages or namespaces are created by this extension; the comments are stored in their own table, `com_comment`.

Comments can be upvoted or downvoted, which will change the score displayed on each comment. This feature helps people find the most useful comments more easily. By default, the comments list is ordered by rating.

Actions users can perform (such as creating or editing a comment, or voting on a comment) are attributed to their entry in the core MediaWiki `actor` database table. This means that (logged out) users sharing the same IP address can edit that IP's comments, votes, etc.

### Moderation
Users with the `comments-manage` permission can delete other user's comments. Users can be blocked from editing using the standard Special:Block form by a user that has the `block` right (typically sysops).

#### AbuseFilter
If `$wgCommentsUseAbuseFilter` is enabled and the [AbuseFilter](https://www.mediawiki.org/wiki/Extension:AbuseFilter) extension is installed, comments will run through all enabled filters on the wiki with the "action" variable set to "comments" and the "new_wikitext" value set to the wikitext of the comment. An example of a rule that would match is: `action == 'comment' & new_wikitext irlike "badword"`
