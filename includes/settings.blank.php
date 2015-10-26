<?php
//This file contains all of the user editable settings for this software
//Any settings changed outside of this file may break the software,
//or prevent it from loading properly during future updates.

/*****EVE SETTINGS*****/

define('SITE_NAME', 'EveAdmin');
define('SITE_ADDRESS', 'https://my.dogft.com');

// Who your members should contact in the event of problems (don't make this Ashkrall, I won't troubleshoot for your membership. I will only speak with site Admins and people with raw Database/Code access)
define('CONTACT', '');

// Define your document root here. This seems redundant, but it's not. Trust me.
define('DOCUMENT_ROOT', '/home/evemail/public_html');

/*****API SETTINGS*****/

// This is the minimum API key that you want people to provide. 1073741823 is a full API key
define('MINIMUM_API', '1073741823');

// This is the text that is presented on the accounts' API Key Management page about the API keys required. Modify it freely.
define('API_DISCLAIMER', 'API Key requirements will vary from group to group, with many groups requiring FULL API Keys. For the site to function properly, API keys must have at the minimum a key mask of <a href="https://community.eveonline.com/support/api-key/CreatePredefined?accessMask=50724872" target="_blank">50724872</a> (Character Sheet, Private Character Info, Skill Queue, SkillInTraining, and Account Status). Additional API parameters that can be used to unlock site features can be found <a href="account.php?page=optional">here</a>, but are not required .');

/*****DATABASE SETTINGS*****/

//MySQL Database Name
define('DB_NAME', '');

//MySQL Database Username
define('DB_USER', '');

//MySQL Database Password
define('DB_PASSWORD', '');

//MySQL Database Host. Default is localhost
define('DB_HOST', 'localhost');

//MySQL Database Charset. Default is utf8
define ('DB_CHARSET', 'utf8');

/*****DEVELOPER SETTINGS*****/

//Debug Mode
define('DEBUG_MODE', TRUE);

/*****SECURITY SETTINGS*****/

//Hash Cost - The number of hash cycles run when creating a password hash
define('HASH_COST', 12);

//Session Caching Time - The amount of time a user will remain logged in while inactive
define('SESSION_EXPIRATION', 1440);

//Brute Force Protection - Blocks an IP based on failed logins
define('BRUTE_MODE', TRUE);
define('BRUTE_ATTEMPTS', 5);			//The number of failed attempts an IP can generate before being banned
define('BRUTE_DISALLOW', 10);  			//The time, in minutes, that logins are limited for this IP

//Brute Force Global Protection - Blocks all logins globally based on global failed logins
define('BRUTE_GLOBAL_ATTEMPTS', 100);		//Total number of login failures sitewide acceptable within a 1 hour period before all logins are locked down.
define('BRUTE_GLOBAL_DISALLOW', 30);		//Defines the length of time, in minutes, that logins are restricted globally when GLOBAL_FAILS is reached


//Paranoid Security Mode - See security.txt for information
define('PARANOID_MODE', FALSE);
define('PARANOID_IP', '');