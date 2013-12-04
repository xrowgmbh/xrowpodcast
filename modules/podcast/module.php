<?php

$Module = array( 'name' => 'podcast' );
$ViewList = array();

$ViewList['feed'] = array(
        'functions' => array('feed'),
        'script' => 'feed.php',
        'params' => array( 'podcast_id' => 'podcast_id' )
    );
$ViewList['allfeeds'] = array(
        'functions' => array('allfeeds'),
        'script' => 'allfeeds.php'
    );
$ViewList['feedauth'] = array(
        'functions' => array('feed'),
        'script' => 'feedauth.php',
        'params' => array( 'podcast_id' => 'podcast_id' )
    );

// Secret password-free access for Podtrac
$ViewList['podtrac-10hg0vc9addryq9nrhbs'] = array(
        'functions' => array( 'podtrac-10hg0vc9addryq9nrhbs' ),
        'script' => 'feed.php',
        'params' => array( 'podcast_id' => 'podcast_id' )
    );
$ViewList['podtrac-10hg0vc9addryq9nrhbs-allfeeds'] = array(
        'functions' => array( 'allfeeds' ),
        'script' => 'allfeeds.php'
    );

// TODO: figure out if we need a separate function for 'allfeeds' or if we can just use 'feeds'
//       Also see extension/podcast/settings/site.ini.append.php
$FunctionList = array();
$FunctionList['feed'] = array();
$FunctionList['allfeeds'] = array();
$FunctionList['podtrac-10hg0vc9addryq9nrhbs'] = array();

?>
