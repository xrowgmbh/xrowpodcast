0;115;0c<?php

$tpl = eZTemplate::factory();
eZDebug::writeDebug( eZSys::serverVariable( 'REMOTE_ADDR' ) );
$Module = $Params['Module'];
$sys = eZSys::instance();

// Apparently dashes must still be specified as underscores from PHP in calling fetchByURLPath...
$podcast_path = 'podcasts/all_podcasts';
$podcast = eZContentObjectTreeNode::fetchByURLPath( $podcast_path );

if ( ! $podcast instanceof ezContentObjectTreeNode ) {
    return $Module->handleError( eZError::KERNEL_NOT_FOUND, 'kernel' );
}
$podcast_path = $podcast->attribute( 'path_identification_string' );

$user = eZUser::currentUser();

// ONLY redirect to HTTPS if we need to authenticate the user!
// This should only occur if the current user does not have access to the podcast object!
// Free podcasts should be accessible via HTTP, with no redirect
if ( ! podcastTools::hasAccess( $podcast, $user ) ) {
//    eZDebug::writeError("current user ".$user->attribute('name')."does not have access");
    // The current user doesn't have access.  We need to get them authorized over HTTP Auth, which
    // is plaintext on the wire, so make sure they are on an encrypted connection first.
    if ( ! $sys->isSSLNow() ) {
        $path = $Params['Module']->attribute( 'functions' );
        $path = $path['allfeeds']['uri'];
        $parameters = array( 
            'protocol' => 'https' 
        );
        $status = '301';
        return eZHTTPTool::redirect( $path, $parameters, $status );
    }
}

$user = podcastTools::feedAuth( $podcast, $user );


$params = array();
$params['Limit'] = 50;
$params['Depth'] = 4;
$params['Limitation'] = array();
$params['ClassFilterType'] = 'include';
$params['ClassFilterArray'] = array( 'podcast_episode' );
$params['SortBy'] = array( 'published' , false );
$params['MainNodeOnly'] = true;
$params['LoadDataMap'] = true;
$episodes = eZContentObjectTreeNode::subTreeByNodeID( $params, 2 );

// TODO: What about empty podcasts with no episodes (yet)?
if ( ! $episodes[0] instanceof ezContentObjectTreeNode ) {
    return $Module->handleError( eZError::KERNEL_NOT_FOUND, 'kernel' );
}

$podcast_dm = $podcast->object()->dataMap();

$feed = new ezcFeed( 'rss2' );
$feed->generator = "Ricochet.com eZ Publish podcast module";
$id =  eZSys::serverURL() . '/podcast/allfeeds';
eZURI::transformURI( $id, true, 'full' );
$feed->id = $id;

$feed->title = htmlspecialchars( trim( $podcast->attribute( 'name' ) ) );

$link = '/podcast/allfeeds';
eZURI::transformURI( $link, true, 'full' );
$feed->link = $link;

$feed->description = htmlspecialchars( trim( $podcast_dm['description']->content() ) );
$feed->language = 'en-US';
$copyright_owner = $podcast_dm['copyright_owner']->content();
if ( ! empty( $copyright_owner ) ) {
    $feed->copyright = 'Copyright &#xA9; ' . gmdate( "Y" ) . ' by ' . htmlspecialchars( trim( $copyright_owner ) );
}
$managing_editor_email = $podcast_dm['managing_editor_email']->content();
if ( ! empty( $managing_editor_email ) ) {
    $feed->author = htmlspecialchars( trim( $managing_editor_email ) );
}
$webmaster_email = $podcast_dm['webmaster_email']->content();
if ( ! empty( $webmaster_email ) ) {
    $feed->webMaster = htmlspecialchars( trim( $webmaster_email ) );
}
if ( $episodes[0] instanceof ezContentObjectTreeNode ) {
    $episode_dm = $episodes[0]->dataMap();
    $published_date = $episode_dm['published_date'];
    if ( isset( $published_date ) && $published_date->hasAttribute( 'timestamp' ) && $published_date->attribute( 'timestamp' ) > 0 ) {
        // Use the manually specified publish date of most recent episode
        $feed->published = $published_date->attribute( 'timestamp' );
    } else {
        // Default to the published date of the most recent episode object
        $feed->published = $episodes[0]->object()->attribute( 'published' );
    }
}
$feed->updated = date( 'D, d M y H:i:s', time() - 0700 );
$category = $podcast_dm['category']->content();
if ( ! empty( $category ) ) {
    $category_element = $feed->add( 'category' );
    $category_element->term = htmlspecialchars( trim( $category ) );
}
$feed->docs = 'http://blogs.law.harvard.edu/tech/rss';
#$feed->ttl = 60;//it's optional but not recommended


$rss_image_url = $podcast_dm['rss_image_url']->content();
if ( ! empty( $rss_image_url ) ) {
    $image = $feed->add( 'image' );
    $image->url = $rss_image_url;
    $image->title = htmlspecialchars( trim( $podcast->attribute( 'name' ) ) );
    $image->link = $link;
    $rss_image_width = $podcast_dm['rss_image_width']->content();
    $rss_image_height = $podcast_dm['rss_image_height']->content();
    if ( $rss_image_width > 0 && $rss_image_height > 0 ) {
        $image->width = $rss_image_width;
        $image->height = $rss_image_height;
    }
} else {
    $rss_image_temp = $podcast_dm['rss_image']->content();
    if ( !empty($rss_image_temp) ) {
        // TODO: Fill the image tag with details from the eZ image datatype.  See the original podcast.tpl for details (in eztemplate language)
        $rss_image = $rss_image_temp->attribute( 'medium' );
        $rss_image_url_temp="http://".$_SERVER['HTTP_HOST']."/".$rss_image["url"];
        $image = $feed->add( 'image' );
        $image->url = $rss_image_url_temp;
        $image->title = htmlspecialchars( trim( $podcast->attribute( 'name' ) ) );
        $image->link = $link;
        $rss_image_width = $podcast_dm['rss_image_width']->content();
        $rss_image_height = $podcast_dm['rss_image_height']->content();
        if ( $rss_image_width > 0 && $rss_image_height > 0 ) {
            $image->width = $rss_image_width;
            $image->height = $rss_image_height;
        }
    }
}

$iTunes = $feed->addModule( 'iTunes' );
$image = $iTunes->add( 'image' );
$image->title = htmlspecialchars( trim( $podcast->attribute( 'name' ) ) );
$image->link = $podcast_dm['itunes_image_url']->content();
$iTunes->block = 'no';
$category = $podcast_dm['category']->content();
if ( ! empty( $category ) ) {
    $category_element = $iTunes->add( 'category' );
    $category_element->term = trim( $category );
}
$iTunes->explicit = 'no';
$keywords = $podcast_dm['keywords']->content();
if ( ! empty( $keywords ) ) {
    $iTunes->keywords = implode( ', ', $keywords->KeywordArray );
}
$owner_name = $podcast_dm['owner_name']->content();
$owner_email = $podcast_dm['owner_email']->content();
if ( ! empty( $owner_name ) || ! empty( $owner_email ) ) {
    $owner = $iTunes->add( 'owner' );
    if ( ! empty( $owner_name ) ) {
        $owner->name = $owner_name;
        $iTunes->author = $podcast_dm['owner_name']->content();
    }
    if ( ! empty( $owner_email ) ) {
        $owner->email = $owner_email;
    }
}
$subtitle = $podcast_dm['subtitle']->content();
if ( ! empty( $subtitle ) ) {
    $iTunes->subtitle = htmlspecialchars( trim( $subtitle ) );
}

foreach ( $episodes as $episode ) {
    $episode_dm = $episode->dataMap();
    $owner_dm = $episode->object()->attribute( 'owner' )->dataMap();

    $audio = trim( $episode_dm['audio']->content() );
    
    //$pen_name = $owner_dm['pen_name']->attribute( 'data_text' );
    //$author = ( ! empty( $pen_name ) ) ? $pen_name : $podcast->attribute( 'name' );
    $author = $podcast_dm['owner_name']->content();
    
    $item = $feed->add( 'item' );
    $published_date = $episode_dm['published_date']->content();
    if ( ! empty( $published_date ) && $published_date->hasAttribute( 'timestamp' ) && $published_date->attribute( 'timestamp' ) > 0 ) {
        // Use the manually specified publish date field
        $published_date = $published_date->attribute( 'timestamp' );
    } else {
        // Default to the published date of the episode object
        $published_date = $episode->object()->attribute( 'published' );
    }
    $item->published = $published_date;
    if ($published_date > mktime(0,0,0,9,11,2012)) {
        // Episodes after the date above should have the podcast title in their title
        $episode_podcast_dm = $episode->attribute( 'parent' )->dataMap();
        if ($episode_podcast_dm['title']) {
            $item->title = trim( $episode_podcast_dm['title']->content() );
            if ( $episode_dm['title'] ) {
                $item->title = $item->title . ": " . trim( $episode_dm['title']->content() );
            } elseif ($episode_dm['episode_number']) {
                $item->title = $item->title . ": Episode " . trim( $episode_dm['episode_number']->content() );
            }
        } else {
            $item->title = trim( $episode_dm['title']->content() );
        }
        $item->title = htmlspecialchars( $item->title );
    } else {
        // Preserve the titles of episodes that were in the feed before the change, to avoid throwing clients off
        $item->title = htmlspecialchars( trim( $episode_dm['title']->content() ) );
    }
    if ( ! empty( $audio ) ) {
        $item->link = $audio;
    } else {
        $link = $episode->urlAlias();
        eZURI::transformURI( $link, true, 'full' );
        $item->link = $link;
    }
    $item->description = htmlspecialchars( trim( $episode_dm['description']->content() ) );
    $category = $episode_dm['category']->content();
    if ( ! empty( $category ) ) {
        $category_element = $item->add( 'category' );
        $category_element->term = htmlspecialchars( trim( $category ) );
    }
    $discussion = $episode_dm['discussion']->content();
    if ( ! empty( $discussion ) ) {
        $discussion = $discussion->attribute( 'main_node' );
        if ( ! empty( $discussion ) ) {
            $discussion = $discussion->attribute( 'url' );
            if ( ! empty( $discussion ) ) {
                eZURI::transformURI( $discussion, true, 'full' );
                $item->comments = $discussion;
            }
        }
    }
    $enclosure = $item->add( 'enclosure' );
    $enclosure->url = $audio;
    $enclosure->length = $episode_dm['audio_length']->content();
    $enclosure->type = 'audio/x-mp3';
    $item->id = $audio;
    
    $iTunes = $item->addModule( 'iTunes' );
    if ( ! empty( $author ) ) {
        $iTunes->author = $author;
    }
    $iTunes->duration = $episode_dm['duration']->content();
    $iTunes->block = 'no';
    $explicit = $episode_dm['explicit']->content();
    $explicit = empty( $explicit ) ? 'no' : 'yes';
    $iTunes->explicit = $explicit;
    $keywords = $episode_dm['keywords']->content();
    if ( ! empty( $keywords ) ) {
        $iTunes->keywords = htmlspecialchars( implode( ', ', $keywords->KeywordArray ) );
    }
    $subtitle = $episode_dm['subtitle']->content();
    if ( ! empty( $subtitle ) ) {
        $iTunes->subtitle = htmlspecialchars( trim( $subtitle ) );
    }

    // itunes:summary is redundant with the description
   //  $iTunes->summary = htmlspecialchars( trim( $episode_dm['description']->content() ) );
}

$xml = $feed->generate( 'rss2' );

// Set header settings
$lastModified = gmdate( 'D, d M Y H:i:s', time() ) . ' GMT';

if ( $user->isAnonymous() === false && podcastTools::hasAccess( $podcast, $user ) === true ) {
    header( 'Cache-Control: no-cache, private' );
} else {
    header( 'Cache-Control: max-age=1800, must-revalidate; public' );
}
$httpCharset = eZTextCodec::httpCharset();
header( 'Last-Modified: ' . $lastModified );
header( 'Content-Type: application/xml; charset=' . $httpCharset );
header( 'Content-Length: ' . strlen( $xml ) );

while ( @ob_end_clean() );
echo $xml;

eZExecution::cleanExit();

?>
