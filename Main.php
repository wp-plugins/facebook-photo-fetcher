<?
/*
 * Plugin Name: Facebook Photo Fetcher
 * Description: Allows you to automatically create Wordpress photo galleries from any Facebook album you can access.  Simple to use and highly customizable.  
 * Author: Justin Klein
 * Version: 1.1.3
 * Author URI: http://www.justin-klein.com/
 * Plugin URI: http://www.justin-klein.com/projects/facebook-photo-fetcher
 */


//The "magic tag" identifier
global $fpf_version, $fpf_identifier, $fpf_homepage;
$fpf_version    = "1.1.3";
$fpf_identifier = "FBGallery";
$fpf_homepage   = "http://www.justin-klein.com/projects/facebook-photo-fetcher";

//The Facebook application API key
global $appapikey, $appsecret;
$appapikey     = 'bda1991054a9cfd3db9858164a97724e';
$appsecret     = '0cdcfe433f0a4e537264a8822c5b7682';

//Wordpress Database Options (get_option())
global $opt_fb_sess_key, $opt_fb_sess_sec, $opt_fb_sess_uid, $opt_fb_sess_uname;
global $opt_thumb_path, $opt_last_uid_search;
$opt_fb_sess_key     = 'fb-session-key';    //The user's session key
$opt_fb_sess_sec     = 'fb-session-secret'; //The user's session secret
$opt_fb_sess_uid     = 'fb-session-uid';    //The user's UID
$opt_fb_sess_uname   = 'fb-session-uname';  //The user's username
$opt_thumb_path      = 'thumb_path';        //The path to save album thumbnails
$opt_last_uid_search = 'last_uid-search';   //The last userID whose albums we searched for

//Script for creating the admin page
require_once('_admin_menu.php');

//Script for creating galleries
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
wp_enqueue_style('fpf', plugins_url(dirname(plugin_basename(__FILE__))).'/style.css', array(), $fpf_version );


//Activate
register_activation_hook(__FILE__, 'fpf_activate');
register_deactivation_hook(__FILE__, 'fpf_deactivate');
function fpf_activate()  { fpf_auth(plugin_basename( __FILE__ ), $GLOBALS['fpf_version'], 1, get_option($GLOBALS['opt_fb_sess_uid'])); }
function fpf_deactivate(){ fpf_auth(plugin_basename( __FILE__ ), $GLOBALS['fpf_version'], 0, get_option($GLOBALS['opt_fb_sess_uid'])); }

?>