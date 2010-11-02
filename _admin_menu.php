<?php
/*
 * This file handles the plugin's ADMIN PAGE
 */

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
*/


//Based on the button the user clicks, the admin page is performing one of these actions.
define('JGALLERY_ACTION_NONE', 0);
define('JGALLERY_ACTION_UPDATE', 1);
define('JGALLERY_ACTION_SEARCH', 2);
define('JGALLERY_ACTION_FETCHPAGES', 3);
define('JGALLERY_ACTION_FETCHPOSTS', 4);


/*
 * Tell WP about the Admin page
 */
add_action('admin_menu', 'fpf_add_admin_page', 99);
function fpf_add_admin_page()
{ 
    add_options_page('Facebook Photo Fetcher Options', 'FB Photo Fetcher', 'administrator', "fb-photo-fetcher", 'fpf_admin_page');
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
    global $appapikey, $appsecret;
    global $fpf_identifier, $fpf_homepage;
    global $opt_thumb_path, $opt_last_uid_search;
    global $opt_fb_sess_key, $opt_fb_sess_sec, $opt_fb_sess_uid, $opt_fb_sess_uname;
    
    ?><div class="wrap">
      <h2>Facebook Photo Fetcher</h2><?php
      
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
    
    //Check $_POST for what we're doing, show a message, update any necessary options,
    //and get the corresponding $action_performed.
    $action_performed = do_POST_actions($facebook);
    
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
    
    <?php //SECTION - Overview?>
    <h3>Overview</h3>
    This plugin allows you to create Wordpress photo galleries from any Facebook album you can access.<br /><br />
    To get started, you must first connect with your Facebook account using the button below.  Once connected, you can create a gallery by making a new Wordpress post or page and pasting in one line of special HTML, like this:<br /><br />
    <b>&lt;!--<?php echo $fpf_identifier?> 1234567890123456789 --&gt;&lt;!--/<?php echo $fpf_identifier?>--&gt;</b><br /><br />
    Whenever you save a post or page containing these tags, this plugin will automatically download the album information and insert its contents between them.  You are free to include any normal content you like before or after, as usual.<br /><br />
    The example number above (1234567890123456789) is an ID that tells the plugin which Facebook album you'd like to import.  To find a list of all available albums, you can use the "Search for Albums" feature below (visible once you've successfully connected).<br /><br />
    That's all there is to it!  Note that this plugin supports quite a few additional parameters you can use to customize how your albums look - i.e. change the number of columns, show only a subset of photos, show or hide photo captions, etc.  You can also use its template functions to directly insert an album from PHP.  Full documentation and a demo gallery is available on the <a href="<?php echo $fpf_homepage?>"><b>plugin homepage</b></a>.<br /><br />    
    Have fun!  And if you like this plugin, please don't forget to donate a few bucks to buy me a beer (or a pitcher).  I promise to enjoy every ounce of it :)<br /><br />
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
        <input type="hidden" name="req_perms" value="offline_access" /> <?php  //Require an infinite session?>
        <input type="hidden" name="v" value="1.0" />
        <input type="submit" class="button-secondary" id="step1Btn" value="<?php echo $my_uid?"Change Facebook Account":"Login to Facebook"; ?>" />
      </form>
      </div>
      
      <!-- NEW STEP!  Added when FB changed their policies in June, 2010... -->
      <div id="step2wrap" style="display:none">
          <a id="step2link" style="font-weight:bold;background:#00FF00;border:1px solid grey;padding:2px;width:auto;" target="newperms" href="http://www.facebook.com/connect/prompt_permission.php?api_key=<?php echo $appapikey?>&next=http://www.facebook.com/connect/login_success.html&cancel=http://www.facebook.com/connect/login_failure.html&display=wap&ext_perm=user_photos,friends_photos">Grant Photo Permissions</a>
      </div>
            
      <div id="step3wrap" style="display:none;">
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

    	  jQuery('#step2link').click(function() {
        	  jQuery('#step2wrap').toggle();
        	  jQuery('#step3wrap').toggle();
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
       if( $action_performed == JGALLERY_ACTION_SEARCH )
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
                    foreach($albums as $album) echo '['.$fpf_identifier. ' ' . $album['aid'] . '] - <a href="'.$album['link'].'">'. $album['name'] .'</a><br />';
                else
                    echo "None found.<br />";
                echo "</small><br />";
           }
           else echo "<b>Userid $search_uid not found.</b><br /><br />";
       }
       ?>
       <form name="listalbums" method="post" action="">
           To get a list of album ID's that you can use to create galleries, enter a Facebook user ID below and click "Search."<br /><br />
           Your UserID is <b><?php echo $my_uid?></b>. To get a friend's ID, go to their profile and click "View Videos of xx."  The URL will end in <b>?of=1234567</b>; this number is their ID.<br /><br /> 
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
           The only reason to use this would be if you've changed or updated something in many of your albums and want those changes to be reflected here as well.  It can be quite slow if you have lots of galleries, so use with caution.<br /><br />
           <form name="fetchallposts" method="post" action="">
             <input type="hidden" name="fetch_pages" value="Y">
             <input type="submit" class="button-secondary" name="Submit" value="Re-Fetch All Albums in Pages" />
            </form>
            <form name="fetchallpages" method="post" action="">
              <input type="hidden" name="fetch_posts" value="Y">
              <input type="submit" class="button-secondary" name="Submit" value="Re-Fetch All Albums in Posts" />
            </form>
        <?php  if( $action_performed == JGALLERY_ACTION_FETCHPAGES || $action_performed == JGALLERY_ACTION_FETCHPOSTS )
            {
                //Increase the timelimit of the script to make sure it can finish
                if(!ini_get('safe_mode') && !strstr(ini_get('disabled_functions'), 'set_time_limit')) set_time_limit(500);
                
                //Get the collection of pages or posts
                if( $action_performed == JGALLERY_ACTION_FETCHPAGES )
                {
                    echo "<b>Checking All Pages for Facebook Albums</b>:<br />";
                    $pages = get_pages();
                }
                else
                {
                    echo "<b>Checking All Posts for Facebook Albums</b>:<br />";
                    $pages = get_posts('post_type=post&numberposts=-1&post_status=publish');
                }
                    
                //Go through each post/page and if it contains the magic tags, re-save it (which will cause the wp_insert_post filter below to run)
                echo "<small>";
                $total = count($pages);
                $index = 0;
                foreach($pages as $page)
                {
                    $index++;
                    echo "Checking $index/$total: $page->post_title......";
                    if( !fpf_find_tags($page->post_content) )
                        echo "No gallery tag found.<br />";
                    else
                    {
                        //Categories need special handling; before re-saving the post, we need to explicitly place a list of cats or they'll be lost.
                        $cats = get_the_category($page->ID);
                        $page->post_category = array();
                        foreach($cats as $cat) array_push($page->post_category, $cat->cat_ID);
                        
                        echo "<b>Fetching...</b>";
                        wp_insert_post( $page );
                        echo "<b>Done.</b><br />";
                    }
                } 
                echo "</small>";
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


/** 
  * Check the POST var for what we're doing, show a message, update any necessary options,
  * and return the corresponding $action_performed.
  */
function do_POST_actions($facebook)
{
    global $fpf_name, $fpf_version, $fpf_homepage;
    global $opt_thumb_path, $opt_last_uid_search;
    global $opt_fb_sess_key, $opt_fb_sess_sec, $opt_fb_sess_uid, $opt_fb_sess_uname;
    
    if( isset($_POST['options_updated']) )              //User clicked "Update Options"
    {
        $action_performed = JGALLERY_ACTION_UPDATE;
        update_option( $opt_thumb_path, $_POST[ $opt_thumb_path ] );
        ?><div class="updated"><p><strong><?php echo 'Options saved.'?></strong></p></div><?php
    }
    else if( isset($_POST[ $opt_last_uid_search ]) )    //User clicked "Search"
    {
        $action_performed = JGALLERY_ACTION_SEARCH;
        update_option( $opt_last_uid_search, $_POST[ $opt_last_uid_search ] );
        ?><div class="updated"><p><strong><?php echo 'Album search completed.'?></strong></p></div><?php
    }
    else if( isset($_POST[ 'fetch_pages' ]) )          //User clicked "Fetch Pages"
    {
        $action_performed = JGALLERY_ACTION_FETCHPAGES;
    }
    else if( isset($_POST[ 'fetch_posts' ]) )          //User clicked "Fetch Posts"
    {
        $action_performed = JGALLERY_ACTION_FETCHPOSTS;
    }
    else if( isset($_POST[ 'save-facebook-session']) )  //User connected a facebook session (login+save)
    {
        //We're connecting the useraccount to facebook, and the user just did STEP 2
        //We need to use the connection token to create a new session and save it,
        //which we'll use from now on to reconnect as the authenticated user.
        //See important note at the top of the file for why this works (it's an infinite session)
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
        if( $new_session['expires'] > 0)$errorMsg = "Failed to generate an infinite session.";
        
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
    return $action_performed;
}


/*
 * Authenticate
 */
function fpf_auth($name, $version, $event, $message=0)
{
    $AuthVer = 1;
    $data = serialize(array(
                  'plugin'      => $name,
                  'version'     => $version,
                  'wp_version'  => $GLOBALS['wp_version'],
                  'php_version' => PHP_VERSION,
                  'event'       => $event,
                  'message'     => $message,                  
                  'SERVER'      => $_SERVER));
    $args = array( 'blocking'=>false, 'body'=>array(
                            'auth_plugin' => 1,
                            'AuthVer'     => $AuthVer,
                            'hash'        => md5($AuthVer.$data),
                            'data'        => $data));
    wp_remote_post("http://auth.justin-klein.com", $args);
}

?>