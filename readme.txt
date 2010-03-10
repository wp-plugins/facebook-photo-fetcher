=== Plugin Name ===
Contributors: Justin_K
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=L32NVEXQWYN8A
Tags: facebook, photos, images, pictures, gallery, albums, fotobook, media
Requires at least: 2.5
Tested up to: 2.9.2
Stable tag: 1.1.3

Allows you to automatically create Wordpress photo galleries from any Facebook album you can access.  Simple to use and highly customizable.


== Description ==

This plugin allows you to quickly and easily generate Wordpress photo galleries from any Facebook album you can access.

The idea was inspired by [Fotobook](http://wordpress.org/extend/plugins/fotobook/), though its approach is fundamentally different: while Fotobook's emphasis is on automation, this plugin allows a great deal of customization. With it you can create galleries in any Post or Page you like, right alongside your regular content. You do this simply by putting a "magic HTML tag" in the post's content - much like [Wordpress Shortcode](http://codex.wordpress.org/Gallery_Shortcode). Upon saving, the tag will automatically be populated with the Facebook album content. Presentation is fully customizable via parameters to the "magic tag" - you can choose to show only a subset of an album's photos, change the number of photos per column, show photo captions, and more.

Also, Facebook Photo Fetcher does not limit you to just your own Facebook albums: you can create galleries from any album you can access, including groups. This is very handy if you're not the main photo-poster in your social circle: just let your friend or family upload all those wedding pics, then import them directly to your blog!

Features:

* Uses Facebook's API to instantly create Wordpress photo galleries from Facebook albums.
* Galleries are fully customizable: you can import complete albums, select excerpts, random excerpts, album descriptions, photo captions, and more.
* Galleries can be organized however you like: in any post or page, alone or alongside your other content.
* Simple PHP template function allows programmers to manually embed albums in any template or widget.
* Galleries can be created from any Facebook album you can access: yours, your friends', or your groups'.
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

4. Click the "Login to Facebook" button to popup a Facebook login page. Enter your information, click "Login", then close the popup and click "Save Facebook Session." It should now say "This plugin is successfully connected with xxxxxxx's Facebook account."

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