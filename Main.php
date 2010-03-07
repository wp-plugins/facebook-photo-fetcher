<?
/*
 * Plugin Name: Facebook Photo Fetcher
 * Description: Allows you to automatically create Wordpress photo galleries from any Facebook album you can access.  Simple to use and highly customizable.  
 * Author: Justin Klein
 * Version: 1.0.2
 * Author URI: http://www.justin-klein.com/
 * Plugin URI: http://www.justin-klein.com/projects/facebook-photo-fetcher
 */

//The "magic tag" identifier
$gallery_identifier = "FBGallery";

//The Facebook application API key
$appapikey     = 'bda1991054a9cfd3db9858164a97724e';
$appsecret     = '0cdcfe433f0a4e537264a8822c5b7682';

//Wordpress Database Options (get_option())
$opt_fb_sess_key     = 'fb-session-key';    //The user's session key
$opt_fb_sess_sec     = 'fb-session-secret'; //The user's session secret
$opt_fb_sess_uid     = 'fb-session-uid';    //The user's UID
$opt_fb_sess_uname   = 'fb-session-uname';  //The user's username
$opt_thumb_path      = 'thumb_path';        //The path to save album thumbnails
$opt_last_uid_search = 'last_uid-search';   //The last userID whose albums we searched for

//Create the admin page
add_action('admin_menu', 'fpf_add_admin_page');
add_filter('plugin_action_links', 'fpf_add_plugin_links', 10, 2);
require_once('_admin_menu.php');

//Create the albums (on save)
add_action('wp_insert_post_data', 'fpf_run_main');
require_once('_output_gallery.php');

//If there's no Lightbox plugin, include our own lightbox code
add_action('plugins_loaded', 'add_lightbox');
function add_lightbox()
{
    if(!function_exists('lightbox_2_options_page'))
    {
        wp_enqueue_script('jquery-lightbox', plugins_url(dirname(plugin_basename(__FILE__))).'/jquery-lightbox/jquery.lightbox-0.5.pack.js', array('jquery'), "0.5");
        wp_enqueue_style('jquery-lightbox', plugins_url(dirname(plugin_basename(__FILE__))).'/jquery-lightbox/jquery.lightbox-0.5.css', array(), "0.5" );
    }  
}

//Add a default stylesheet
wp_enqueue_style('fpf', plugins_url(dirname(plugin_basename(__FILE__))).'/style.css' );

?>