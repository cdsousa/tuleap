<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

/*
	redirect to proper hostname to get around certificate problem on IE 5
*/

// Defines all of the Source Forge hosts, databases, etc.
// This needs to be loaded first becuase the lines below depend upon it.

require (getenv('SF_LOCAL_INC_PREFIX').'/etc/local.inc');

// Check URL for valid hostname and valid protocol
if (($HTTP_HOST != $GLOBALS['sys_default_domain']) && ($SERVER_NAME != 'localhost') && ($HTTP_HOST != $GLOBALS['sys_https_host'])) {
    if ($HTTPS == 'on'|| $GLOBALS['sys_force_ssl'] == 1) {
	$location = "Location: https://".$GLOBALS['sys_https_host']."$REQUEST_URI";
    } else {
	$location = "Location: http://".$GLOBALS['sys_default_domain']."$REQUEST_URI";
    }
}

// Force SSL mode if required except if request comes from localhost
// HTTP needed by fopen calls (e.g.  in www/include/cache.php)
if ($HTTPS != 'on' && $GLOBALS['sys_force_ssl'] == 1 && ($SERVER_NAME != 'localhost')) {
    $location = "Location: https://".$GLOBALS['sys_https_host']."$REQUEST_URI";
}

if ($location) {
    header($location);
    exit;
}   


//library to determine browser settings
require('browser.php');

//base error library for new objects
//require('Error.class');

//various html utilities
require('utils.php');

util_get_content('layout/osdn_sites');

// HTML layout class, may be overriden by the Theme class
require('Layout.class');

$HTML = new Layout();

//PHP4-like functions - only if running php3
if (substr(phpversion(),0,1) == "3") {
    require('utils_php4.php');
}

//database abstraction
require('database.php');

//security library
require('session.php');

//user functions like get_name, logged_in, etc
require('user.php');

//group functions like get_name, etc
require('Group.class');

//Project extends Group and includes preference accessors
require('Project.class');

//library to set up context help
require('help.php');

//exit_error library
require('exit.php');

//various html libs like button bar, themable
require('html.php');

//left-hand nav library, themable
require('menu.php');

$sys_datefmt = "Y-M-d H:i";

// #### Connect to db

db_connect();

if (!$conn) {
	print "Could Not Connect to Database".db_error();
	exit;
}

//determine if they're logged in
session_set();

// OSDN functions and defs
require('osdn.php');

//insert this page view into the database
require('logger.php');

/*

	Timezone must come after logger to prevent messups


*/
//set up the user's timezone if they are logged in
if (user_isloggedin()) {
	putenv('TZ='.user_get_timezone());
} else {
	//just use pacific time as always
}

//Set up the vars and theme functions 
require('theme.php');


// Check if anonymous user is allowed to browse the site
// Bypass the test for:
// a) all scripts where you are not logged in by definition
// b) if it is a local access from localhost 

/*
print "<p>DBG: SERVER_NAME = ".$SERVER_NAME;
print "<p>DBG: sys_allow_anon= ".$GLOBALS['sys_allow_anon'];
print "<p>DBG: user_isloggedin= ".user_isloggedin();
print "<p>DBG: SCRIPT_NAME = ".$SCRIPT_NAME";
*/

if ($SERVER_NAME != 'localhost' && 
    $GLOBALS['sys_allow_anon'] == 0 && !user_isloggedin() &&
    $SCRIPT_NAME != '/account/login.php'  && 
    $SCRIPT_NAME != '/account/register.php'&& 
    $SCRIPT_NAME != '/account/lostpw.php' &&
    $SCRIPT_NAME != '/account/lostlogin.php' &&
    $SCRIPT_NAME != '/account/lostpw-confirm.php' &&
    $SCRIPT_NAME != '/account/verify.php' ) {
    if ($GLOBALS['sys_force_ssl'] == 1 || $HTTPS == 'on')
	header("Location: https://".$GLOBALS['sys_https_host']."/account/login.php");
    else
	header("Location: http://".$GLOBALS['sys_default_domain']."/account/login.php");
    exit;
}
?>