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
  * 
  * And optionally (only if specified as params in the startTag):
  * $retVal['cols']     //Number of columns
  * $retval['max']      //The max number of items to show
  * $retVal['start']    //The first photo index to show (aka skip some initially)
  * $retVal['swapHead'] //If we should swap the order of the 2 lines in the album header
  * $retVal['hideHead'] //Hide the album header entirely
  * $retVal['hideCaps'] //Hide the per-photo captions on the main listing
  * $retVal['noLB']     //Suppress outputting the lightbox javascript
  */
function fpf_find_tags($post_content)
{ 
    //Start by splitting the content at startTag, and check for "none" or "too many" occurrences
    global $gallery_identifier;
    $result = preg_split("/(\<!--[ ]*".$gallery_identifier."[ ]*?(\d+).*?--\>)/", $post_content, -1, PREG_SPLIT_DELIM_CAPTURE );
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
    $result = preg_split("/(\<!--[ ]*\/".$gallery_identifier."[ ]*--\>)/", $retVal['after'], -1, PREG_SPLIT_DELIM_CAPTURE);
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
    if( preg_match('/cols=(\d+)/', $retVal['startTag'], $matches) )     $retVal['cols'] = $matches[1];
    if( preg_match('/max=(\d+)/', $retVal['startTag'], $matches) )      $retVal['max']  = $matches[1];
    if( preg_match('/start=(\d+)/', $retVal['startTag'], $matches) )    $retVal['start'] = $matches[1];
    if( preg_match('/swapHead=(\d+)/', $retVal['startTag'], $matches) ) $retVal['swapHead'] = $matches[1]?true:false;
    if( preg_match('/hideHead=(\d+)/', $retVal['startTag'], $matches) ) $retVal['hideHead'] = $matches[1]?true:false;
    if( preg_match('/hideCaps=(\d+)/', $retVal['startTag'], $matches) ) $retVal['hideCaps'] = $matches[1]?true:false;
    if( preg_match('/noLB=(\d+)/', $retVal['startTag'], $matches) )     $retVal['noLB'] = $matches[1]?true:false;
    return $retVal;
}



/**
  * Given a Facebook AlbumID, fetch its content and return:
  * $retVal['content'] - The generated HTML content we'll use to display the album
  * $retVal['thumb']   - The Facebook album's thumbnail
  * $retVal['count']   - The number of photos in the album
  * 
  * $params is an optional array of extra tags; For what's supported, see fpf_find_tags(). 
  */
function fpf_fetch_album_content($aid, $params)
{
    global $appapikey, $appsecret;
    global $opt_fb_sess_key, $opt_fb_sess_sec;
    $retVal = Array();
    
    //Combine any params with default values
    $defaults = array('cols'    => 4,
                      'max'     => 99999999999,
                      'start'   => 0,
                      'swapHead'=> false,
                      'hideHead'=> false,
                      'hideCaps'=> false,
                      'hideCred'=> false,
                      'noLB'    => false);
    $params = wp_parse_args( $params, $defaults );
    $itemwidth = $params['cols'] > 0 ? floor(100/$params['cols']) : 100;
    $itemwidth -= (0.5/$params['cols']); //For stupid IE7, which rounds fractional percentages UP (shave off 0.5%, or the last item will wrap to the next row)
    
    //Connect to Facebook and restore our user's session
    if(version_compare('5', PHP_VERSION, "<=")) require_once('facebook-platform/client/facebook.php');
    else                                        require_once('facebook-platform/php4client/facebook.php');
    $facebook = new Facebook($appapikey, $appsecret, null, true);  
    $facebook->api_client->session_key  = get_option($opt_fb_sess_key);
    $facebook->api_client->secret       = get_option($opt_fb_sess_sec);

    //Try to get the specified album
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

    //Success! We were able to get the album. Now get its author
    $author = $facebook->api_client->users_getInfo($album['owner'], array('name', 'profile_url'));
    $author = $author[0];
    
    //Calculate how many items will be shown
    if( $params['start'] > $album['size'] )
    {
        $retVal['content'] .= "<b>Error: Start index ". $params['start']." is greater than the total number of photos in this album; Defaulting to 0.</b><br /><br />";
        $params['start'] = 0;
    }
    if( $params['max'] > $album['size'] - $params['start'] )
        $params['max'] = $album['size'] - $params['start'];
    $retVal['count'] = $params['max'];

    //Create a header with some info about the album
    if(!$params['hideHead'])
    {
        $headerTitle  = 'From <a href="' . htmlspecialchars($album['link']) . '">' . $album['name'] . '</a>';
        $headerTitle .= ', posted by <a href="' . htmlspecialchars($author['profile_url']) . '">' . $author['name'] . '</a>';
        $headerTitle .= ' on ' . date('n/d/Y', $album['created']);
        if( $params['max'] < $album['size'])$headerTitle .= ' (Showing ' . $params['max'] . ' of ' . $album['size'] . " items)\n";
        else                                $headerTitle .= ' (' . $album['size'] . " items)\n";
        $headerTitle .= '<br /><br />';            
        if( $album['description'] ) $headerDesc = '"'.$album['description'].'"<br /><br />'."\n";
        else                        $headerDesc = "";
    } 

    //Output a (hidden) timestamp and the header
    $retVal['content'] .= "<!-- Last fetched on " . date('m/d/Y H:i:s') . "-->\n";
    if( $params['swapHead'] )   $retVal['content'] .= $headerTitle . $headerDesc;
    else                        $retVal['content'] .= $headerDesc . $headerTitle; 
    
    //Output each photo, keeping an eye out for whichever one is the album's thumbnail
    $retVal['content'] .= "<div class='gallery'>\n";
    $photos = $facebook->api_client->photos_get(null, $aid, null);
    $photos = array_slice($photos, $params['start'], $params['max']); 
    foreach($photos as $photo)
    {
        //Store the filename of the album thumbnail when found
        //Note: we want the fullsize, because when WP uploads it it'll auto-resize it to an appropriate thumbnail for us.
        if( strcmp($photo['pid'],$album['cover_pid']) == 0 ) $retVal['thumb'] = $photo['src_big'];
        
        //Output this photo
        $caption = preg_replace("/\r/", "", $photo['caption']);
        $caption_with_br = htmlspecialchars(preg_replace("/\n/", "<br />", $caption));
        $caption_no_br = preg_replace("/\n/", " ", $caption);
        $link = '<a class="fbPhoto" href="'.$photo['src_big'] . '" title="'.$caption_with_br.'" ><img src="' . $photo['src'] . '" alt="" /></a>';
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
        if( $params['cols'] > 0 && ++$i % $params['cols'] == 0 ) $retVal['content'] .= "<br style=\"clear: both\" />\n";
    }
    $retVal['content'] .= "</div>\n";
    if( $i%$params['cols'] != 0 ) $retVal['content'] .= '<br clear="all" />';
    if( !$params['hideCred'] )    $retVal['content'] .= "<span class=\"fpfcredit\">Generated by <i>Facebook Photo Fetcher</i></span>\n";
    
    //Activate the lightbox when the user clicks a photo (only if the Lightbox plugin isn't already there)
    if( !$params['noLB'] && !function_exists('lightbox_2_options_page') )
    {
        $retVal['content'] .= '<script type="text/javascript">
                    jQuery(document).ready(function(){
                        jQuery(function(){ jQuery(".gallery-icon a").lightBox(); }); 
                    });
                    </script>';
    }
    
    return $retVal;
}


/**
  * Copy $thumb to the local server, and attach it to $post_ID as a post thumbnail with add-from-server. 
  */
function fpf_attach_thumbnail($post_ID, $thumb)
{
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