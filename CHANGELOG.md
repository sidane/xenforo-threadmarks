# CHANGELOG

## v1.3.1 (2016-11-24)

- Fix compatibility with pre-XF 1.5 versions

## v1.3.0 (2016-11-17)

- Add threadmarks from quick reply/reply
- Implement require extendability for Word Count support
- Expose threadmark cache rebuild to the rebuild cache page
- Reduce data fetching on threads with no threadmarks
- Fix displaying post user avatar on threadmark preview.
- Update jsTree library

## v1.2.1 (2016-11-04)

- Use local copy of jstree
- Fix typo in uninstaller
- Fix ElasticSearch/XenForo Enhanced Search detection
- Fix "Click to lock" phrase

## v1.2.0 (2016-11-03)

- Style properties for theming
- Optional next/previous/index navigation for threadmarked posts.
- Ability to sortable threadmarks from a thread's threadmark index
- Threadmark history, and on hover information for who created it.
- News feed integration
- Expose threadmarks to the search

## v1.1.5 (2015-02-17)

- Add more whitespace to a SQL query for compatibility with other addons.

## v1.1.4 (2015-02-16)

- Fix incorrect database index in v1.1.3.

## v1.1.3 (2015-02-16)

- Ensure threadmarks are immediately visible when moving threadmarked posts to another thread. *Note:* Threadmarks drop down menu still doesn't appear until deferred task runs.

## v1.1.2 (2015-02-13)

- Drop down menu was showing no threadmarks when menu limit permission was set to unlimited. Now shows all threadmarks.

## v1.1.1 (2015-02-12)

- Update moderator log threadmark entries to include label when creating/editing/deleting

## v1.1 (2015-02-09)

- respect post visibility when listing threadmarks (i.e. threadmarks on hidden posts aren't listed)
- more fine grained permissions
- threadmark label length increased from 100 characters to 255
- ensure threadmarks remain on posts when moved to a different thread (note: uses deferred task so threadmarks will take a few seconds to appear in target thread)
- threadmarks button is now clickable to open all threadmarks list
- display threadmarks menu on single page threads
- ensure threadmark still shows after editing a post
- do not show threadmarks dropdown menu when javascript is disabled
- use template modifications instead of deprecated template hooks
- remove unnecessary phrase database queries

## v1.0 (2015-01-07)

- It's alive!
