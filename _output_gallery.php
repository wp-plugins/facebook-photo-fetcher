<?
/*
 * This file handles the creation of a Facebook Gallery.
 * When a page containing the "magic tag" is saved, this function will:
 *   -Fetch the album from facebook
 *   -Fill in the album content between the tags, formatting it based on the parameters in the tag
 *   -Add postmeta "_fb_album_size" with the number of items in the album (which the user can optionally reference)
 *   -Copy the album's thumbnail to the local server and attach it as a post thumbnail (IF the add-from-server plugin is specified, and the option enabled)
 *   
 * Re-saving the same page will re-fetch the album from facebook and regenerate its content again.  
 */
add_action('wp_insert_post_data', 'fpf_run_main');
function fpf_run_main($data)
{
    //Don't process anything but POSTS and PAGES (i.e. no revisions)
    if( $data['post_type'] != 'post' && $data['post_type'] != 'page')
        return $data;
        
    //Check the content for our magic tag (and parse out everything we need if found)
    $parsed_content = fpf_find_tags($data['post_content']);
    if( !$parsed_content ) return $data;
        
    //Connect to Facebook and generate the album content
    $album_content = fpf_fetch_album_content($parsed_content['aid'], $parsed_content);
    
    //Update the post we're about to save
    $data['post_content'] = $parsed_content['before'] . 
                            $parsed_content['startTag'] . 
                            $album_content['content'] . 
                            $parsed_content['endTag'] . 
                            $parsed_content['after'];
    
    //Set postmeta with the album's size (can be optionally referenced by the user)
    //(Note: for some stupid reason, $data doesn't have the ID - we need to parse it out of the guid.)
    $post_ID = substr(strrchr($data['guid'], '='), 1);
    update_post_meta( $post_ID, '_fb_album_size', $album_content['count'] );
        
    //If the album has a thumbnail, download it from FB and add it as an attachment with add-from-server
    if( isset($GLOBALS['add-from-server']) && isset($album_content['thumb']) )
        fpf_attach_thumbnail($post_ID, $album_content['thumb']);
        
    //Done!
    return $data;
}


/**
  * Check a post's content for valid "magic tags".  If not found, return 0.  Otherwise, return:
  * $retVal['before']   //Content before the start tag
  * $retVal['after']    //Content after the end tag
  * $retVal['aid']      //The albumID parsed from the start tag
  * $retVal['startTag'] //The complete starttag
  * $retVal['endTag']   //The complete endTag
  * $retVal[....]       //Additional supported parameters found in the startTag.
  *                     //For a full list of what's available see fpf_fetch_album_content().
  */
function fpf_find_tags($post_content)
{ 
    //Start by splitting the content at startTag, and check for "none" or "too many" occurrences
    global $fpf_identifier;
    $result = preg_split("/(\<!--[ ]*".$fpf_identifier."[ ]*?([\d_-]+)[ ]*--\>)/", $post_content, -1, PREG_SPLIT_DELIM_CAPTURE );
    if( count($result) < 4 )            //No tags found
        return 0;
    if( count($result) > 4 )            //Too many tags found
    {
        echo "Sorry, this plugin currently supports only one Facebook gallery per page.<br />";
        return 0;
    }
    $retVal = Array();
    $retVal['before']   = $result[0];
    $retVal['startTag'] = $result[1];
    $retVal['aid']      = $result[2];
    $retVal['after']    = $result[3];
    
    //Now search the remaining content and split it at the endTag, again checking for "none" or "too many"
    $result = preg_split("/(\<!--[ ]*\/".$fpf_identifier."[ ]*--\>)/", $retVal['after'], -1, PREG_SPLIT_DELIM_CAPTURE);
    if( count($result) < 3 )
    {
        echo "Missing gallery end-tag.<br />";
        return 0;
    }
    if( count($result) > 3 )
    {
        echo "Duplicate gallery end-tag found.<br />";
        return 0;
    }
    $retVal['endTag'] = $result[1];
    $retVal['after']  = $result[2];
    
    //Check for optional params in the startGag:
    //1.0.0
    if( preg_match('/cols=(\d+)/', $retVal['startTag'], $matches) )     $retVal['cols']     = $matches[1];
    if( preg_match('/start=(\d+)/', $retVal['startTag'], $matches) )    $retVal['start']    = $matches[1];
    if( preg_match('/max=(\d+)/', $retVal['startTag'], $matches) )      $retVal['max']      = $matches[1];
    if( preg_match('/swapHead=(\d+)/', $retVal['startTag'], $matches) ) $retVal['swapHead'] = $matches[1]?true:false;
    if( preg_match('/hideHead=(\d+)/', $retVal['startTag'], $matches) ) $retVal['hideHead'] = $matches[1]?true:false;
    if( preg_match('/hideCaps=(\d+)/', $retVal['startTag'], $matches) ) $retVal['hideCaps'] = $matches[1]?true:false;
    if( preg_match('/noLB=(\d+)/', $retVal['startTag'], $matches) )     $retVal['noLB']     = $matches[1]?true:false;
    //1.0.2
    if( preg_match('/hideCred=(\d+)/', $retVal['startTag'], $matches) ) $retVal['hideCred'] = $matches[1]?true:false;
    //1.0.3
    if( preg_match('/rand=(\d+)/', $retVal['startTag'], $matches) )     $retVal['rand']     = $matches[1];
    //1.1.0
    if( preg_match('/isGroup=(\d+)/', $retVal['startTag'], $matches) )  $retVal['isGroup']  = $matches[1];
    //1.1.4
    if( preg_match('/isPage=(\d+)/', $retVal['startTag'], $matches) )   $retVal['isPage']   = $matches[1];
    if( preg_match('/isEvent=(\d+)/', $retVal['startTag'], $matches) )  $retVal['isEvent']  = $matches[1];
    return $retVal;
}



/**
  * Given a Facebook AlbumID, fetch its content and return:
  * $retVal['content'] - The generated HTML content we'll use to display the album
  * $retVal['thumb']   - The Facebook album's thumbnail
  * $retVal['count']   - The number of SHOWN photos in the album
  * 
  * $params is a array of extra options, parsed from the startTag by fpf_find_tags().
  * For a list of supported options and their meanings see the $defaults array below.   
  */
function fpf_fetch_album_content($aid, $params)
{
    //Combine optional parameters with default values
    $defaults = array('cols'    => 4,               //Number of columns of images (aka Number of images per row)
                      'start'   => 0,               //The first photo index to show (aka skip some initially)
                      'max'     => 99999999999,     //The max number of items to show
                      'swapHead'=> false,           //Swap the order of the 2 lines in the album header?
                      'hideHead'=> false,           //Hide the album header entirely?
                      'hideCaps'=> false,           //Hide the per-photo captions on the main listing?
                      'noLB'    => false,           //Suppress outputting the lightbox javascript?
                      'hideCred'=> false,           //Omit the "Generated by Facebook Photo Fetcher" footer (please don't :))
                      'rand'    => false,           //Randomly select n photos from the album (or from photos between "start" and "max")
                      'isGroup' => false,           //The ID number specifies a GROUP ID instead of an albumID
                      'isPage'  => false,           //The ID number specifies a FAN PAGE ID instad of an albumID.  It'll return all photos in all albums on that page (for now).
                      'isEvent' => false);          //NOT YET SUPPORTED - the fql query doesn't return what it should...
    $params = array_merge( $defaults, $params );
    $itemwidth = $params['cols'] > 0 ? floor(100/$params['cols']) : 100;
    $itemwidth -= (0.5/$params['cols']); //For stupid IE7, which rounds fractional percentages UP (shave off 0.5%, or the last item will wrap to the next row)
    $retVal = Array();
    
    //Connect to Facebook and restore our user's session
    global $appapikey, $appsecret;
    global $opt_fb_sess_key, $opt_fb_sess_sec;
    if(version_compare('5', PHP_VERSION, "<=")) require_once('facebook-platform/client/facebook.php');
    else                                        require_once('facebook-platform/php4client/facebook.php');
    $facebook = new Facebook($appapikey, $appsecret, null, true);  
    $facebook->api_client->session_key  = get_option($opt_fb_sess_key);
    $facebook->api_client->secret       = get_option($opt_fb_sess_sec);

    //Get the specified album, its photos, and its author
    //(Different methods of fetching the photos albums, groups, pages, etc)
    if( $params['isGroup'] )
    {
        //NOTE: According to http://wiki.developers.facebook.com/index.php/Photos.get,
        //you should be able to do this for events too - but it photos_get always returns null.
        $group = $facebook->api_client->groups_get('', $aid, '');
        if( !$group )
        {
            $retVal['content'] = "Invalid Group ID ($aid)";
            return $retVal;
        }
        $group = $group[0];
        $photos = $facebook->api_client->photos_get($aid, '', '');
        $album['link'] = "http://www.facebook.com/group.php?gid=$aid";
        $album['name'] = $group['name'];
        $retVal['thumb'] = $group['pic_big'];
    }
    else if( $params['isPage'] )
    {
        $page = $facebook->api_client->pages_getInfo($aid, array('name', 'pic_big'), null, null);
        if( !$page )
        {
            $retVal['content'] = "Invalid Page ID ($aid)";
            return $retVal;
        }
        $page = $page[0];
        $photos = $facebook->api_client->fql_query("SELECT pid, aid, owner, src, src_big, src_small, link, caption, created FROM photo WHERE aid IN (SELECT aid FROM album WHERE owner = $aid)");
        $album['link'] = "http://www.facebook.com/profile.php?id=$aid";
        $album['name'] = $page['name'];
        $retVal['thumb'] = $page['pic_big']; 
    }
    else if( $params['isEvent'] )
    {
        $retVal['content'] = "Events not yet supported.";
        return $retVal;                        
        //Should work but doesn't!:
        //$photos = $facebook->api_client->fql_query("SELECT pid FROM photo_tag WHERE subject=$eid");
    }
    else
    {
        $album = $facebook->api_client->photos_getAlbums(null, $aid);
        if( !$album )
        {
            $retVal['content'] = "Invalid Album ID ($aid)";
            return $retVal;
        }
        $album = $album[0];
        if( !$album )
        {
            $retVal['content'] = "Unable to connect to Facebook.  Please check its options and verify that it's been associated with your Facebook account.";
            return $retVal;
        }
        $photos = $facebook->api_client->photos_get(null, $aid, null);
        $author = $facebook->api_client->users_getInfo($album['owner'], array('name', 'profile_url'));
        $author = $author[0];
    }
    if( !is_array( $photos) ) $photos = array();
    $album['size'] = count($photos);
    
    //Slice the photo array as necessary
    if( count($photos) > 0 )
    {
        //Slice the photos between "start" and "max"
        if( $params['start'] > $album['size'] )
        {
            $retVal['content'] .= "<b>Error: Start index ". $params['start']." is greater than the total number of photos in this album; Defaulting to 0.</b><br /><br />";
            $params['start'] = 0;
        }
        if( $params['max'] > $album['size'] - $params['start'] )
            $params['max'] = $album['size'] - $params['start'];
        $photos = array_slice($photos, $params['start'], $params['max']); 
        
        //If "rand" is specified, randomize the order and slice again
        if( $params['rand'] )
        {
            shuffle($photos);
            $photos = array_slice($photos, 0, $params['rand']);
        }
    } 
    $retVal['count'] = count($photos);
    
    //Create a header with some info about the album
    if(!$params['hideHead'])
    {
        $headerTitle  = 'From <a href="' . htmlspecialchars($album['link']) . '">' . $album['name'] . '</a>';
        if( isset($author) && isset($album['created']) )
        {
            $headerTitle .= ', posted by <a href="' . htmlspecialchars($author['profile_url']) . '">' . $author['name'] . '</a>';
            $headerTitle .= ' on ' . date('n/d/Y', $album['created']);
        }
        if( $retVal['count'] < $album['size'])$headerTitle .= ' (Showing ' . $retVal['count'] . ' of ' . $album['size'] . " items)\n";
        else                                  $headerTitle .= ' (' . $retVal['count'] . " items)\n";
        $headerTitle .= '<br /><br />';            
        if( $album['description'] ) $headerDesc = '"'.$album['description'].'"<br /><br />'."\n";
        else                        $headerDesc = "";
    } 

    //Output the album!  Starting with a (hidden) timestamp, then the header, then each photo.
    global $fpf_version;
    $retVal['content'] .= "<!-- ID ". $aid ." Last fetched on " . date('m/d/Y H:i:s') . " v$fpf_version-->\n";
    if( $params['swapHead'] )   $retVal['content'] .= $headerTitle . $headerDesc;
    else                        $retVal['content'] .= $headerDesc . $headerTitle; 
    $retVal['content'] .= "<div class='gallery'>\n";
    foreach($photos as $photo)
    {
        //Store the filename of the album thumbnail when found
        //Note: we want the fullsize, because when WP uploads it it'll auto-resize it to an appropriate thumbnail for us.
        if( isset($album['cover_pid']) && strcmp($photo['pid'],$album['cover_pid']) == 0 )
            $retVal['thumb'] = $photo['src_big'];
        
        //Output this photo (must get rid of [], or WP will try to run it as shortcode)
        $caption = preg_replace("/\[/", "(", $photo['caption']);
        $caption = preg_replace("/\]/", ")", $caption);
        $caption = preg_replace("/\r/", "", $caption);
        $caption_with_br = htmlspecialchars(preg_replace("/\n/", "<br />", $caption));
        $caption_no_br = preg_replace("/\n/", " ", $caption);
        $link = '<a class="fbPhoto" href="'.$photo['src_big'] . '" title="'.$caption_with_br.' " ><img src="' . $photo['src'] . '" alt="" /></a>';
        $retVal['content'] .= "<dl class='gallery-item' style=\"width:$itemwidth%\">";
        $retVal['content'] .= "<dt class='gallery-icon'>$link</dt>";
        if(!$params['hideCaps'])
        {
            $retVal['content'] .= "<dd class='gallery-caption'>";
            $retVal['content'] .= substr($caption_no_br, 0, 85) . (strlen($caption_no_br)>85?"...":"");
            $retVal['content'] .= "</dd>";
        }
        $retVal['content'] .= "</dl>\n";
        
        //Move on to the next row?
        if( $params['cols'] > 0 && ++$i % $params['cols'] == 0 ) $retVal['content'] .= "<br style=\"clear: both\" />\n\n";
    }
    if( $i%$params['cols'] != 0 ) $retVal['content'] .= "<br style=\"clear: both\" />\n\n";
    $retVal['content'] .= "</div>\n";
    if( !$params['hideCred'] )    $retVal['content'] .= "<span class=\"fpfcredit\">Generated by <i>Facebook Photo Fetcher</i></span>\n";
    
    //Activate the lightbox when the user clicks a photo (only if the Lightbox plugin isn't already there)
    if( !$params['noLB'] && !function_exists('lightbox_2_options_page') )
    {
        $imagePath = plugins_url(dirname(plugin_basename(__FILE__))) . "/jquery-lightbox/images/";
        $retVal['content'] .= '<script type="text/javascript">
            jQuery(document).ready(function(){ jQuery(function(){ 
                jQuery(".gallery-icon a").lightBox({
                    imageBlank:"'.$imagePath.'lightbox-blank.gif",
                    imageBtnClose:"'.$imagePath.'lightbox-btn-close.gif",
                    imageBtnNext:"'.$imagePath.'lightbox-btn-next.gif",
                    imageBtnPrev:"'.$imagePath.'lightbox-btn-prev.gif",
                    imageLoading:"'.$imagePath.'lightbox-ico-loading.gif"
                }); }); });'.
             "\n</script>\n";
    }
    $retVal['content'] .= "<!-- End Album ". $aid ." -->\n";
    return $retVal;
}


/**
  * Copy $thumb to the local server, and attach it to $post_ID as a post thumbnail with add-from-server. 
  */
function fpf_attach_thumbnail($post_ID, $thumb)
{
    //Make sure this is a version of Wordpress that supports thumbs
    if( !function_exists('has_post_thumbnail') ) return;
    
    //Get the path where the user wants to store downloaded thumbs
    global $opt_thumb_path;
    $thumb_path = get_option($opt_thumb_path);
    if( !$thumb_path ) return;
    
    //If the post already has a thumbnail, delete it before fetching the new one
    if(has_post_thumbnail($post_ID))
       wp_delete_attachment( get_post_thumbnail_id($post_ID), true ); 
    
    //Copy the file from FB to the server
    @mkdir($thumb_path);
    $dstFile = $thumb_path . '/fbthumb_' . $post_ID . '.jpg';
    $res     = copy( $thumb, $dstFile );
    if( !$res )
    {
        echo "ERROR: Failed to copy thumbnail file from " . $thumb . " to " . $dst;
        return;
    }
            
    //Add the file as an attachment to this page
    $thumbID = $GLOBALS['add-from-server']->handle_import_file($dstFile, $post_ID);
    
    //Set the attachment as the page's thumbnail (see admin-ajax.php, case 'set-post-thumbnail')
    $thumbnail_html = wp_get_attachment_image( $thumbID, 'thumbnail' );
    if ( !empty( $thumbnail_html ) ) update_post_meta( $post_ID, '_thumbnail_id', $thumbID );
}
?>