<?php

  /* The purpose of this file is to confirm whether the person is authorized to
   * access the given podcast.  It is needed for Stitcher. */

eZDebug::writeDebug( eZSys::serverVariable( 'REMOTE_ADDR' ) );
$Module = $Params['Module'];
$sys = eZSys::instance();

if ( ! is_numeric( $Params['podcast_id'] ) && ! empty( $Params['podcast_id'] ) ) {
    $podcast_path = str_replace( '-', '_', implode( '/', $Params['Parameters'] ) );
    if (strpos($podcast_path, 'podcasts/') !== 0) {
        $podcast_path = "podcasts/$podcast_path";
    }
    $podcast = eZContentObjectTreeNode::fetchByURLPath( $podcast_path );

    if ( ! $podcast instanceof ezContentObjectTreeNode ) {
        return $Module->handleError( eZError::KERNEL_NOT_FOUND, 'kernel' );
    }
} else {
    $podcast_path = eZContentObjectTreeNode::fetch( $Params['podcast_id'] );
    if ( ! $podcast_path instanceof ezContentObjectTreeNode )
    {
        return $Module->handleError( eZError::KERNEL_NOT_FOUND, 'kernel' );
    }
    $podcast_path = $podcast_path->attribute( 'path_identification_string' );
    $podcast = eZContentObjectTreeNode::fetchByURLPath( $podcast_path );
}

// We must authorize using basic HTTP authentication (like htpasswd), which
// sends credentials in plaintext on the wire, so redirect to SSL if not
// already SSL
if ( ! $sys->isSSLNow() ) {
    $podcast_path_for_redirect = preg_replace( '/^podcasts\//', '', str_replace('_', '-', $podcast_path ) );
    $path = $Params['Module']->attribute( 'functions' );
    $path = $path['feed']['uri'] . '/' . $podcast_path_for_redirect;
    $parameters = array( 
        'protocol' => 'https' 
    );
    $status = '301';
    return eZHTTPTool::redirect( $path, $parameters, $status );
}

// This call will check the HTTP authentication
$user =  podcastTools::feedAuth( $podcast, null );

// Set header settings
$lastModified = gmdate( 'D, d M Y H:i:s', time() ) . ' GMT';
header( 'Last-Modified: ' . $lastModified );
if ( $user->isAnonymous() === false ) {
    header( 'Cache-Control: no-cache, private' );
} else {
    header( 'Cache-Control: max-age=1800, must-revalidate; public' );
}

echo "AUTHORIZED\n";

eZExecution::cleanExit();

?>
