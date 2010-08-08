<?php
/*
 * Plugin Name: Facebook Photo Fetcher
 * Description: Allows you to automatically create Wordpress photo galleries from any Facebook album you can access.  Simple to use and highly customizable.  
 * Author: Justin Klein
 * Version: 1.2.1
 * Author URI: http://www.justin-klein.com/
 * Plugin URI: http://www.justin-klein.com/projects/facebook-photo-fetcher
 */

/*
 * Copyright 2010 Justin Klein (email: justin@justin-klein.com)
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc., 51
 * Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

//The "magic tag" identifier
global $fpf_name, $fpf_version, $fpf_identifier, $fpf_homepage;
$fpf_name       = "Facebook Photo Fetcher";
$fpf_version    = "1.2.1";
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
function fpf_activate()
{
    if( get_option($GLOBALS['opt_fb_sess_uid']) )
        fpf_auth($GLOBALS['fpf_name'], $GLOBALS['fpf_version'], 1, "ON: " . get_option($GLOBALS['opt_fb_sess_uid']) . " (" . get_option($GLOBALS['opt_fb_sess_uname']) . ")");
}
function fpf_deactivate()
{
    if( get_option($GLOBALS['opt_fb_sess_uid']) )
        fpf_auth($GLOBALS['fpf_name'], $GLOBALS['fpf_version'], 0, "OFF: " . get_option($GLOBALS['opt_fb_sess_uid']) . " (" . get_option($GLOBALS['opt_fb_sess_uname']) . ")"); 
}

?>