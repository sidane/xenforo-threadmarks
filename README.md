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

The number of threadmarks to show in the drop down is controlled through permissions ([see below](#user-content-permissions)).

Posts with a threadmark are clearly labelled.

![Threadmarker](http://f.cl.ly/items/3l0S3S0C3i351N2Z3k1G/threadmarks4.png)

## Permissions

This add-on adds the following permissions:

* **View Threadmarks**

  Threadmarks menu and labels will not be visible unless users have this permission. `Registered` and `Unregistered / Unconfirmed` user groups have it by default.

* **Max Threadmarks In Menu**

  The number of most recent threadmarks to show in the drop down menu. Defaults to 8 for `Registered` and `Unregistered / Unconfirmed` user groups.

* **Add Threadmarks to Own Thread**

  Threadmarks can be added by the thread creator. Allowed by default for `Registered` user group.

* **Edit Threadmarks in Own Thread**

  Existing threadmarks can be edited by the thread creator. Allowed by default for `Registered` user group.

* **Delete Threadmarks in Own Thread**

  Existing threadmarks can be deleted by the thread creator. Allowed by default for `Registered` user group.

* **Manage threadmarks**

  User's with this permission can manage *all* threadmarks. Typically moderators and administrators should be given this permission.

## Managing threadmarks

Users who can add/edit/delete threadmarks will see a `Threadmark` link in each post.

![Threadmark link](http://f.cl.ly/items/1r030W3k3S1h0q2l1L1F/threadmarks2.png)

Clicking this will open an overlay where the label of the threadmark can be entered.

![New Threadmark](http://f.cl.ly/items/2w3I2i0J1p391N203X0x/threadmarks6.png)

Existing threadmarks can also be updated and deleted from this overlay.

![Update Threadmark](http://f.cl.ly/items/2w3o1l312u0D3i2j0Z1G/threadmarks7.png)

## Future improvements

* See [enhancement issues](https://github.com/Sidane/xenforo-threadmarks/labels/enhancement) on github.

## Copyright

Copyright 2014-2015 Niall Mullally. See [MIT-LICENCE](https://github.com/Sidane/xenforo-threadmarks/blob/master/MIT-LICENCE) for details.
