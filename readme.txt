=== Plugin Name ===
Contributors: Justin_K
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=L32NVEXQWYN8A
Tags: facebook, photos, images, pictures, gallery, albums, fotobook, media
Requires at least: 2.5
Tested up to: 3.0.1
Stable tag: 1.2.8

Allows you to automatically create Wordpress photo galleries from any Facebook album you can access.  Simple to use and highly customizable.


== Description ==

This plugin allows you to quickly and easily generate Wordpress photo galleries from any Facebook album you can access.

The idea was inspired by [Fotobook](http://wordpress.org/extend/plugins/fotobook/), though its approach is fundamentally different: while Fotobook's emphasis is on automation, this plugin allows a great deal of customization. With it you can create galleries in any Post or Page you like, right alongside your regular content. You do this simply by putting a "magic HTML tag" in the post's content - much like [Wordpress Shortcode](http://codex.wordpress.org/Gallery_Shortcode). Upon saving, the tag will automatically be populated with the Facebook album content. Presentation is fully customizable via parameters to the "magic tag" - you can choose to show only a subset of an album's photos, change the number of photos per column, show photo captions, and more.

Also, Facebook Photo Fetcher does not limit you to just your own Facebook albums: you can create galleries from any album you can access, including groups and fanpages. This is very handy if you're not the main photo-poster in your social circle: just let your friend or family upload all those wedding pics, then import them directly to your blog!

Features:

* Uses Facebook's API to instantly create Wordpress photo galleries from Facebook albums.
* Galleries are fully customizable: you can import complete albums, select excerpts, random excerpts, album descriptions, photo captions, and more.
* Galleries can be organized however you like: in any post or page, alone or alongside your other content.
* Simple PHP template function allows programmers to manually embed albums in any template or widget.
* Galleries can be created from any Facebook album you can access: yours, your friends', your groups', or your fanpages.
* Built-in support for automatically downloading and attaching Post Thumbnails to any post or page that includes a gallery.
* Built-in support for LightBox: Photos appear in attractive pop-up overlays without the need for any other plugins.
* Admin panel handles all setup for you: Just click "Connect", login once, and you're ready to start making albums.
* Admin panel includes a utility to search for all Facebook albums you can access (and thus use to create galleries).
* Admin panel includes a utility to auto-traverse all your posts and pages, updating albums that may've changed on Facebook.
* No custom database modifications are performed: all it does is automate the creation of post/page content for you.

For a Demo Gallery, see the [plugin's homepage](http://www.justin-klein.com/projects/facebook-photo-fetcher).


== Installation ==

1. Download the most recent version of this plugin from [here](http://wordpress.org/extend/plugins/facebook-photo-fetcher/), unzip it, and upload the extracted files to your `/wp-content/plugins` directory.

2. Activate the plugin via your Wordpress admin panel.

3. Head over to Settings -> FB Photo Fetcher.

4. Click the "Login to Facebook" button to popup a Facebook login page.  Enter your information, click "Login", then close the popup.  Next, click "Grant Photo Permissions."  Accept the permissions in the popup and when it says "Success," close it.  Finally, click "Save Facebook Session."  It should now say "This plugin is successfully connected with xxxxxxx's Facebook account."

5. Now we need to get the ID of an album you'd like to import. Click the "Search for Albums" button; It will automatically connect to Facebook and produce a list of all the albums you can access, each with an associated ID number. Let's use the example 1234567890123456789.

6. Create a new post or page and enter the following tags, replacing the sample ID number with the one you'd like import.    Note that if you use the Visual editor, you must change to "HTML" mode first or the tags won't be recognized:

`&lt;!--FBGallery 1234567890123456789--&gt;&lt;!--/FBGallery--&gt;`

7. Click "Save", and you're done! You can now view your new album.

Note: The above instructions only include the most basic setup; this plugin provides far more ways to customize the appearance and behavior of your gallery.  For customization instructions, please visit the [plugin's website](http://www.justin-klein.com/projects/facebook-photo-fetcher#customizing).


== Frequently Asked Questions ==

[FAQ](http://www.justin-klein.com/projects/facebook-photo-fetcher#faq)


== Screenshots ==

[Demo Gallery](http://www.justin-klein.com/projects/facebook-photo-fetcher#demo)


== Changelog ==
= 1.2.8 (2010-11-02) =
* Remove unneeded debug code

= 1.2.7 (2010-10-30) =
* Add return URL to paypal donate button

= 1.2.6 (2010-10-28) =
* Error check if the user denies necessary permissions while connecting to Facebook

= 1.2.5 (2010-10-14) =
* Marked as compatible up to 3.0.1

= 1.2.4 (2010-10-14) =
* Bug fix: Categories were getting lost when using "Re-Fetch All Albums in Posts" 

= 1.2.3 (2010-08-08) =
* Oops - forgot to add a check in one more spot

= 1.2.2 (2010-08-08) =
* Added a check for other plugins globally including the Facebook API

= 1.2.1 (2010-08-07) = 
* Something got left out of the 1.2.0 commit...

= 1.2.0 (2010-08-07) =
* Update the Facebook client library so it'll play nice with newer plugins
* The minimum requirement is now PHP5.

= 1.1.13 (2010-07-24) =
* Update connection process for Facebook's new privacy policies (to address the bug where no albums were returned by search)

= 1.1.12 (2010-07-15) =
* Fix bug where thumbnails were not downloaded for non-group/page albums where only a portion of the album is shown.

= 1.1.11 (2010-03-16) =
* Use php long tags instead of short tags; should work on XAMPP servers now.

= 1.1.10 (2010-03-14) =
* Sorry - 1.1.9 broke regexp's again for 64-bit userID's. Should be fixed.

= 1.1.9 (2010-03-14) =
* Oops - regexp mistake required a space after the albumID in the start tag; fixed.

= 1.1.8 (2010-03-14) =
* The last version broke isPage; fixed.

= 1.1.7 (2010-03-13) =
* Added support for 64-bit userIDs (aka albumID's with dashes and minuses)

= 1.1.6 (2010-03-13) =
* Added a check for has_post_thumbnail exists (so it won't die on pre-2.9 wordpress installations)

= 1.1.5 (2010-03-11) =
* Fix an issue where the last row of photos weren't clearing their floats properly; YOU'LL NEED TO REGENERATE YOUR GALLERIES for this fix to be applied.
* Always explicitly prompt for infinite session (many users seemed to be getting this error)

= 1.1.4 (2010-03-10) =
* Add isPage parameter - now you can get photos from fan pages!

= 1.1.3 (2010-03-09) =
* Include close/next/prev/loading images for lightbox

= 1.1.2 (2010-03-09) =
* Add version number to plugin code
* Small fixes & cleanups
* Update instructions to clear up a common issue

= 1.1.1 (2010-03-08) =
* Fix bug if photo captions are enabled and contain square brackets

= 1.1.0 (2010-03-08) =
* Add support for GROUP photo albums (in addition to USERs)
* Some code restructuring

= 1.0.3 (2010-03-08) =
* Add support for "rand" argument (randomized album excerpts)
* Add links to FAQ when fail to connect with facebook
* Minor cleanups

= 1.0.2 (2010-03-07) =
* Add support for PHP4

= 1.0.1 (2010-03-06) =
* Add default stylesheet

= 1.0.0 (2010-03-06) =
* First Release


== Support ==

Please direct all support requests [here](http://www.justin-klein.com/projects/facebook-photo-fetcher#feedback)