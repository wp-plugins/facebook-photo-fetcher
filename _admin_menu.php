<?php

/*
 * Tell WP about the Admin page
 */
add_action('admin_menu', 'fpf_add_admin_page', 99);
function fpf_add_admin_page()
{
	global $fpf_name; 
    add_options_page("$fpf_name Options", 'FB Photo Fetcher', 'administrator', "fb-photo-fetcher", 'fpf_admin_page');
}


/**
  * Link to Settings on Plugins page 
  */
add_filter('plugin_action_links', 'fpf_add_plugin_links', 10, 2);
function fpf_add_plugin_links($links, $file)
{
    if( dirname(plugin_basename( __FILE__ )) == dirname($file) )
        $links[] = '<a href="options-general.php?page=' . "fb-photo-fetcher" .'">' . __('Settings','sitemap') . '</a>';
    return $links;
}


/**
  * Output the plugin's Admin Page 
  */
function fpf_admin_page()
{
	global $fpf_name, $fpf_version;
    global $appapikey, $appsecret;
    global $fpf_identifier, $fpf_homepage;
    global $opt_thumb_path, $opt_last_uid_search;
    global $opt_fb_sess_key, $opt_fb_sess_sec, $opt_fb_sess_uid, $opt_fb_sess_uname;
    global $opt_fpf_hidesponsor;
    
    ?><div class="wrap">
      <h2><?php echo $fpf_name ?></h2>

    <?php
    //Show a warning if they're using a naughty other plugin
    if( class_exists('Facebook') )
    {
        ?><div class="error"><p><strong>Warning:</strong> Another plugin has included the Facebook API throughout all of Wordpress.  I suggest you contact that plugin's author and ask them to include it only in pages where it's actually needed.<br /><br />Things may work fine as-is, but only if the API version included by the other plugin is at least as recent as the one required by Facebook Photo Fetcher.</p></div><?php
    }
    else
    {
        if(version_compare('5', PHP_VERSION, "<=")) require_once('facebook-platform/php/facebook.php');
        else                                        die("Sorry, but as of version 1.2.0, Facebook Photo Fetcher requires PHP5.");
    }
        
    //Connect to Facebook and create an auth token.
    //Note: We only care about $token when the user is creating/saving a session; otherwise it's irrelevant and we just ignore it.
    $facebook = new Facebook($appapikey, $appsecret, null, true);
    $facebook->api_client->secret = $appsecret;
    $token = $facebook->api_client->auth_createToken();
    if(!$token) echo 'Failed to create Facebook authentication token!'; 
    
    //Check $_POST for what we're doing, and update any necessary options
    if( isset($_POST[ 'save-facebook-session']) )  //User connected a facebook session (login+save)
    {
        //We're connecting the useraccount to facebook, and the user just did STEP 2
        //We need to use the connection token to create a new session and save it,
        //which we'll use from now on to reconnect as the authenticated user.
        $token = $_POST[ 'save-facebook-session' ];
        try
        {
            $new_session = $facebook->api_client->auth_getSession($token);
        }
        catch(Exception $e)
        {
            $new_session = 0;
        }
        $errorMsg = 0;
        if( !$new_session )             $errorMsg = "Failed to get an authenticated session.";
        if( !$new_session['secret'])    $errorMsg = "Failed to get a session secret.  See <a href=\"".$fpf_homepage."#faq3\">FAQ3</a>.";
        //if( $new_session['expires'] > 0)$errorMsg = "Failed to generate an infinite session."; NOTE: FACEBOOK DEPRECATES OFFLINE_ACCESS in October 2012, so I can no longer do this!
        
        //Success!  Save the key, secret, userID, and username
        if( !$errorMsg )
        {
            $user = $facebook->api_client->users_getInfo($new_session['uid'], array('name'));
            update_option( $opt_fb_sess_key, $new_session['session_key'] );
            update_option( $opt_fb_sess_sec, $new_session['secret'] );
            update_option( $opt_fb_sess_uid, $new_session['uid'] );
            update_option( $opt_fb_sess_uname, $user[0]['name'] );
            fpf_auth($fpf_name, $fpf_version, 2, "SET: " . $new_session['uid'] . " (" . $user[0]['name'] .")");
            ?><div class="updated"><p><strong><?php echo 'Facebook Session Saved. (UID: ' . $new_session['uid'] . ')' ?></strong></p></div><?php
        }
        else
        {
            update_option( $opt_fb_sess_key, 0 );
            update_option( $opt_fb_sess_sec, 0 );
            update_option( $opt_fb_sess_uid, 0 );
            update_option( $opt_fb_sess_uname, 0 );
            ?><div class="updated"><p><strong><?php echo 'An error occurred while linking with Facebook: ' . $errorMsg ?></strong></p></div><?php
        }
    }
	else if( isset($_POST['options_updated']) ) //User saved thumbnail path
    {
        update_option( $opt_thumb_path, $_POST[ $opt_thumb_path ] );
        ?><div class="updated"><p><strong><?php echo 'Options saved.'?></strong></p></div><?php
    }
    else if( isset($_POST[ $opt_last_uid_search ]) )    //User clicked "Search," which saves 'last searched uid'
    {
        update_option( $opt_last_uid_search, $_POST[ $opt_last_uid_search ] );
        ?><div class="updated"><p><strong><?php echo 'Album search completed.'?></strong></p></div><?php
    }
	else 												//Allow optional addons to perform actions
	{
		do_action('fpf_extra_panel_actions', $_POST);
	}
    
    //Get all the options from the database
    $thumb_path = get_option($opt_thumb_path);
    $search_uid = get_option($opt_last_uid_search);
    $session_key= get_option($opt_fb_sess_key);
    $session_sec= get_option($opt_fb_sess_sec);
    $my_uid     = get_option($opt_fb_sess_uid);
    $my_name    = get_option($opt_fb_sess_uname);
    if(!$search_uid) $search_uid = $my_uid;
    
    //Finally, OUTPUT THE ADMIN PAGE.
    ?>
      <div style="position:absolute; right:30px; margin-top:-50px;">
      <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
        <input type="hidden" name="cmd" value="_s-xclick" />
        <input type="hidden" name="hosted_button_id" value="L32NVEXQWYN8A" />
        <input type="hidden" name="return" value="http://www.justin-klein.com/thank-you" />
        <input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!" />
        <img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" />
      </form>
      </div>
    <hr />  
    
    <?php if(!get_option($opt_fpf_hidesponsor)): ?>
       <!-- Sponsorship message *was* here, until Automattic demanded they be removed from all plugins - see http://gregsplugins.com/lib/2011/11/26/automattic-bullies/ -->
    <?php endif; 
    if( isset($_REQUEST[$opt_fpf_hidesponsor]) )
      update_option($opt_fpf_hidesponsor, $_REQUEST[$opt_fpf_hidesponsor]);
    ?>
    
    <?php //SECTION - Overview?>
    <h3>Overview</h3>
    This plugin allows you to create Wordpress photo galleries from any Facebook album you can access.<br /><br />
    To get started, you must first connect with your Facebook account using the button below.  Once connected, you can create a gallery by making a new Wordpress post or page and pasting in one line of special HTML, like this:<br /><br />
    <b>&lt;!--<?php echo $fpf_identifier?> 1234567890123456789 --&gt;&lt;!--/<?php echo $fpf_identifier?>--&gt;</b><br /><br />
    Whenever you save a post or page containing these tags, this plugin will automatically download the album information and insert its contents between them.  You are free to include any normal content you like before or after, as usual.<br /><br />
    The example number above (1234567890123456789) is an ID that tells the plugin which Facebook album you'd like to import.  To find a list of available albums, you can use the "Search for Albums" feature below (visible once you've successfully connected).<br /><br />    
    That's all there is to it!  For more information on how to customize your albums, fetch photos from groups or fanpages, and a demo, please see the full documentation on the <a href="<?php echo $fpf_homepage?>"><b>plugin homepage</b></a>.<br /><br />    
    And if you like this plugin, please don't forget to donate a few bucks to buy me a beer (or a pitcher).  I promise to enjoy every ounce of it :)<br /><br />
    <hr />
    
    <?php //SECTION - Connect to Facebook.  See note at top of file.?>
    <h3>Connect with Facebook</h3><?php
    if( $my_uid ) echo "<i>This plugin is successfully connected with <b>$my_name</b>'s Facebook account and is ready to create galleries.</i>";
    else          echo "Before this plugin can be used, you must connect it with your Facebook account.<br /><br />Please click the following button and complete the pop-up login form.  When finished, click the button again to save your session. You will only have to do this once.";
    ?>
      <br /><br />
      <div id="step1wrap">
      <form method="get" id="step1Frm" action="http://www.facebook.com/login.php" target="_blank">
        <input type="hidden" name="api_key" value="<?php echo $appapikey ?>" />
        <input type="hidden" name="auth_token" value="<?php echo $token ?>" />
        <input type="hidden" name="popup" value="1" />      <?php //Style the window as a popup?>
        <input type="hidden" name="skipcookie" value="1" /> <?php //User must enter login info even if already logged in?>
        <input type="hidden" name="req_perms" value="offline_access,user_photos,friends_photos" /> <?php  //Require an infinite session?>
        <input type="hidden" name="v" value="1.0" />
        <input type="submit" class="button-secondary" id="step1Btn" value="<?php echo $my_uid?"Change Facebook Account":"Login to Facebook"; ?>" />
      </form>
      </div>
      
      <div id="step2wrap" style="display:none;">
      <form method="post" action="">
        <input type="hidden" name="save-facebook-session" value="<?php echo $token ?>" />
        <input type="submit" class="button-secondary" style="font-weight:bold;background:#00FF00;" value="Save Facebook Session" />
      </form>
      </div>
            
      <script type="text/javascript">
      jQuery(document).ready(function() {
    	  jQuery('#step1Frm').submit(function() {
        	  jQuery('#step1wrap').toggle();
        	  jQuery('#step2wrap').toggle();
        	});
    	});
      </script>
    <hr />

    <?php
    //All features below here require connection with facebook
    if( $my_uid ):
    ?>
        
       <?php //SECTION - Search for albums?>
       <h3>Search for Albums</h3><?php
       if( isset($_POST[ $opt_last_uid_search ]) )
       {
           $facebook->api_client->session_key = $session_key;
           $facebook->api_client->secret      = $session_sec;
           $albums = $facebook->api_client->photos_getAlbums($search_uid, null);
           $user = $facebook->api_client->users_getInfo($search_uid, array('name'));
           
           //NOTE: Remove this outer check to show albums for FAN PAGES as well as users
           //(but those don't work because their AID's are weird, containing underscores, etc - why?)
           if( is_array($user) )
           {
                if( is_array($user) )    echo "<b>Available Facebook albums for ".$user[0]['name']." ( uid $search_uid ):</b><br />";
                else                     echo "<b>Available Facebook Albums for ID $search_uid</b><br />";    
                echo "<small>";
                if( is_array($albums) )
                    foreach($albums as $album) echo '&lt;!--'.$fpf_identifier. ' ' . $album['aid'] . ' --&gt;&lt;!--/FBGallery--&gt; (<a href="'.$album['link'].'">'. $album['name'] .'</a>)<br />';
                else
                    echo "None found.<br />";
                echo "</small><br />";
           }
           else echo "<b>Userid $search_uid not found.</b><br /><br />";
       }
       ?>
       <form name="listalbums" method="post" action="">
           To get a list of album ID's that you can use to create galleries, enter a Facebook user ID below and click "Search."<br /><br />
           Your UserID is <b><?php echo $my_uid?></b>.  To get a friend's ID, click on one of their photos - the URL will be something like <b>facebook.com/photo.php?fbid=012&amp;set=a.345.678.900</b>. The last set of numbers (900 in this example) is their ID.<br /><br />
           Note that searching only works for personal albums; for groups and pages, see "Customizing" in the plugin documentation <a href="http://www.justin-klein.com/projects/facebook-photo-fetcher">here</a>.<br /><br /> 
           <input type="text" name="<?php echo $opt_last_uid_search?>" value="<?php echo $search_uid?>" size="20"><br /><br />
           <input type="submit" class="button-secondary"  name="Submit" value="Search" />
       </form>
       <hr />


       <?php //SECTION - Thumbs?>
       <h3>Album Thumbnails</h3>
       <?php
         if( !isset($GLOBALS['add-from-server']) ):
            echo 'If you install the <a target="_blank" href="http://wordpress.org/extend/plugins/add-from-server">Add From Server</a> plugin, album thumbnails can be automatically copied from Facebook and attached to your galleries.  If you do not install it, the galleries will still work fine but they will not automatically have <a target="_blank" href="http://markjaquith.wordpress.com/2009/12/23/new-in-wordpress-2-9-post-thumbnail-images/">Post Thumbnails</a>.';
         else: 
            echo 'This plugin can automatically download album thumbnails from Facebook and attach them as Wordpress <a target="_blank" href="http://markjaquith.wordpress.com/2009/12/23/new-in-wordpress-2-9-post-thumbnail-images/">Post Thumbnails</a>.  Please select the full path to where you\'d like the thumbnails downloaded, or leave it blank to skip them.<br /><br />';
       ?>
       <form name="formOptions" method="post" action="">
           <input type="text" name="<?php echo $opt_thumb_path; ?>" value="<?= $thumb_path; ?>" size="90" <?= (isset($GLOBALS['add-from-server'])?"":"disabled='disabled'")?>>
           <br />
            (Hint: Your document root is <?php echo $_SERVER["DOCUMENT_ROOT"]?>)
           <br /><br />
           <input type="submit" class="button-secondary" name="Submit" value="Update Options" />
           <input type="hidden" name="options_updated" value="Y">
       </form>
       <?php endif;?>
       <hr />
        

       <?php //SECTION - Fetch all albums ?>
       <h3>Refresh Albums from Facebook</h3>
           This will scan all your posts and pages for galleries created with this plugin, 
           and regenerate each one it finds by re-fetching its information from Facebook.
           The only reason to use this would be if you've changed or updated something in many of your albums and want those changes to be reflected here as well.  It can be slow if you have lots of galleries, so use with caution.<br /><br />
           
           <div class="postbox" style="width:400px; height:80px; padding:10px; float:left; text-align:center;">
           <form name="fetchallposts" method="post" action="">
             <input type="hidden" name="fetch_pages" value="Y">
             <input type="submit" class="button-secondary" name="Submit" value="Re-Fetch All Albums in Pages" />
            </form>
            <br />
            <form name="fetchallpages" method="post" action="">
              <input type="hidden" name="fetch_posts" value="Y">
              <input type="submit" class="button-secondary" name="Submit" value="Re-Fetch All Albums in Posts" />
            </form>
        </div>
        <?php 
        if( function_exists('fpf_output_cron_panel') ) fpf_output_cron_panel();
        ?>
        <br clear="all" />
            <?php
            //When we click one of the "fetch now" buttons  
            if( isset($_POST[ 'fetch_pages' ]) || isset($_POST[ 'fetch_posts' ]) )
            {
                //Get the collection of pages or posts
                if( isset($_POST[ 'fetch_pages' ]) )
                {
                    echo "<b>Checking All Pages for Facebook Albums</b>:<br />";
                    $pages = get_pages(array('post_status'=>'publish,private'));
                }
                else
                {
                    echo "<b>Checking All Posts for Facebook Albums</b>:<br />";
                    $pages = get_posts('post_type=post&numberposts=-1&post_status=publish,private');
                }

                echo "<div class='postbox' style='width:90%;padding:10px;'><pre>";
                echo fpf_refetch_all($pages, true);
                echo "</pre></div>";
            }
        ?>
        <hr />
        <?php endif; //Must connect with Facebook?>
        
      <h4>Development</h4>
      Many hours have gone into making this plugin as versatile and easy to use as possible, far beyond my own personal needs. Although I offer it to you freely, please keep in mind that each hour spent extending and supporting it was an hour that could've also gone towards income-generating work. If you find it useful, a small donation would be greatly appreciated :)
      <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
        <input type="hidden" name="cmd" value="_s-xclick" />
        <input type="hidden" name="hosted_button_id" value="L32NVEXQWYN8A" />
        <input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!" />
        <img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" />
      </form>

    </div>
    <?php
}


/*
 * Go through each post/page and if it contains the magic tags, re-save it (which will cause the wp_insert_post filter to run)
 * Display or return a string summarizing what was done.
 */
function fpf_refetch_all($pages, $printProgress=false)
{
    //Increase the timelimit of the script to make sure it can finish
    if(!ini_get('safe_mode') && !strstr(ini_get('disabled_functions'), 'set_time_limit')) set_time_limit(500);

    $outputString = "";
    $total = count($pages);
    $index = 0;
    foreach($pages as $page)
    {
        $index++;
        $outputString .= "Checking $index/$total: $page->post_title......";
        if( !fpf_find_tags($page->post_content) )
        {
            $outputString .= "No gallery tag found.\n";
        }
        else
        {
            //Categories need special handling; before re-saving the post, we need to explicitly place a list of cats or they'll be lost.
            $cats = get_the_category($page->ID);
            $page->post_category = array();
            foreach($cats as $cat) array_push($page->post_category, $cat->cat_ID);
            
            $outputString .= "Found!\n.........Fetching......";
            if($printProgress) { echo $outputString; $outputString = ""; }
            wp_insert_post( $page );
            $outputString .= get_post_meta($page->ID, '_fb_album_size', true) . " photos fetched.\n";
        }
        if($printProgress) { echo $outputString; $outputString = ""; }
    } 
    return $outputString;
}



/*
 * Authenticate
 */
function fpf_auth($name, $version, $event, $message=0)
{
    $AuthVer = 1;
    $data = serialize(array(
             'plugin'      => $name,
             'pluginID'	   => '2342',
             'version'     => $version,
             'wp_version'  => $GLOBALS['wp_version'],
             'php_version' => PHP_VERSION,
             'event'       => $event,
             'message'     => $message,                  
             'SERVER'      => array(
               'SERVER_NAME'    => $_SERVER['SERVER_NAME'],
               'HTTP_HOST'      => $_SERVER['HTTP_HOST'],
               'SERVER_ADDR'    => $_SERVER['SERVER_ADDR'],
               'REMOTE_ADDR'    => $_SERVER['REMOTE_ADDR'],
               'SCRIPT_FILENAME'=> $_SERVER['SCRIPT_FILENAME'],
               'REQUEST_URI'    => $_SERVER['REQUEST_URI'])));
    $args = array( 'blocking'=>false, 'body'=>array(
                            'auth_plugin' => 1,
                            'AuthVer'     => $AuthVer,
                            'hash'        => md5($AuthVer.$data),
                            'data'        => $data));
    wp_remote_post("http://auth.justin-klein.com", $args);
}

/*
Notes:
->How Facebook authentication works: http://wiki.developers.facebook.com/index.php/How_Connect_Authentication_Works
->Another summary: http://forum.developers.facebook.com/viewtopic.php?pid=148426

->Connecting with facebook takes 2 steps: Logging into facebook with the current token,
  Then re-loading this form with the token as a POST variable so we can use it to generate
  a session and retain that session for future use.  After the user has logged in, I use jQuery to hide the login button
  and replace it with the button that POSTS the token back to this form, so it can be saved.
 
->Important Note: Normally when creating a session, we call auth_createToken() to make the token then pass
it to auth_getSession().  However, this returns a session that expires.  Because we don't want the user
to have to keep logging in all the time, we need to get an infinite session, which require two things:
1) Set the Application Type to "Desktop" (http://www.facebook.com/developers, advanced tab) 
2) Make sure to set the $facebook->api_client->secret = $appsecret; (aka set the API_CLIENT's secret
   to the APPLICATION's secret) before calling auth_createToken().  If you do that, the session
   returned by auth_getSession() will not expire.
 ***NOTE*** THIS IS NO LONGER TRUE; in Oct2012, Facebook deprecated the concept of the "infinite session!" See
 * https://developers.facebook.com/roadmap/offline-access-removal.
 * I fixed it so the app will still work, but I may have to do something like i.e. schedule a cronjob to
 * periodically query Facebook to renew the session...
*/

?>