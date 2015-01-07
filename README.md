# Threadmarks

This add-on adds support to XenForo for tagging specific posts in a thread so they can be easily navigated to.

This is useful for long threads discussing a topic over a lengthy period of time and many pages. A threadmark allows a user to quickly jump to a specific post which is related to a certain event, date, or whatever is relevant to the thread.

![Threadmarks menu](http://f.cl.ly/items/252p3p132w26292J0L3c/threadmarks1.png)

## Example usage

Your forum has a thread discussing a television show spanning dozens of pages. The thread is usually bumped whenever a new episode of the show airs.

A threadmark on the first post related to each episode allows users to quickly jump to the posts about that episode, rather than guessing which page the discussion starts on.

## Demo

http://www.redcafe.net/threads/la-liga-2014-15.393896/

**Note:** The drop down menu on the `Threadmarks` button is not visible to guests.

## Installation

The usual XenForo way - install `addon-sidaneThreadmarks.xml` through the XenForo Admin.

## Using threadmarks

The threadmarks menu is positioned beside the pagination links at the top and bottom of threads.

![Threadmarks menu](http://f.cl.ly/items/3k0Y3u083p2r1W0Z3b2q/threadmarks8.png)

A drop down menu displays the most recent threadmarks.

For threads with many threadmarks, an overlay can be opened to access all of them.

![Threadmarks overlay](http://f.cl.ly/items/120M2w1Y0h0V0C2L0g1W/threadmarks5.png)

The number of threadmarks to show in the drop down can be configured under `Admin > Options > Threadmarks` (the default is eight threadmarks).

Post with a threadmark are clearly labelled.

![Threadmarker](http://f.cl.ly/items/3l0S3S0C3i351N2Z3k1G/threadmarks4.png)

## Permissions and managing threadmarks

This add-on adds a new `Manage threadmarks` permissions under the `Forum Moderator Permissions` group.

![Threadmark permission](http://f.cl.ly/items/0W1e0e0U07211k0R3x1P/threadmarks3.png)

A user with this permission will see a `Threadmark` link in each post.

![Threadmark link](http://f.cl.ly/items/1r030W3k3S1h0q2l1L1F/threadmarks2.png)

Clicking this will open an overlay where the label of the threadmark can be entered.

![New Threadmark](http://f.cl.ly/items/2w3I2i0J1p391N203X0x/threadmarks6.png)

Existing threadmarks can also be updated and deleted from this overlay.

![Update Threadmark](http://f.cl.ly/items/2w3o1l312u0D3i2j0Z1G/threadmarks7.png)

## Future improvements

* Show threadmarks menu on thread list pages
* Allow users to request a threadmark on a post
* Template hooks for extending threadmark templates

## Copyright

Copyright 2014 Niall Mullally. See [MIT-LICENCE](https://github.com/Sidane/xenforo-threadmarks/blob/master/MIT-LICENCE) for details.