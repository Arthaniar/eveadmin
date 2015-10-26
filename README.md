EveAdmin
============

An Eve Online Portal and Corporation Management Dashboard

Current Version:
[![GitHub version](https://badge.fury.io/gh/jgrancell%2Feveadmin.png)](http://badge.fury.io/gh/jgrancell%2Feveadmin)

Issues / Features in Dev Branch:
[![Issues In Progress](https://badge.waffle.io/jgrancell/eveadmin.png?label=in%20progress&title=In%20Progress)](https://badge.waffle.io/jgrancell/eveadmin.png?label=in%20progress&title=In%20Progress)

Issues / Features In Progress:
[![Issues Fixed In Dev](https://badge.waffle.io/jgrancell/eveadmin.png?label=fixed%20in%20dev&title=Fixed%20In%20Dev)](https://badge.waffle.io/jgrancell/eveadmin.png?label=fixed%20in%20dev&title=Fixed%20In%20Dev)

#Table of Contents
* [Features](#features)
* [Requirements](#requirements)
* [Installation](#installation)
* [Changelog](#changelog)

## Summary

EveAdmin is a robust, standards-compliant and highly secure tool to provide character, corporation, and alliance management for Eve Online. EveAdmin easily allows for all aspects of corporation and alliance management, including:

##### Corporation / Alliance Features
* API Key Compliance - Completed
* New Applicant Vetting / Spychecking - In Progress
* New Applicant Management / Periodic Group Reviews -TBD
* Ship Doctrine and Fitting Management - Completed
* PvP Participation Statistics - TBD
* Ship Replacement Fund Management - TBD
* Operations Calendar - Completed
* Fleet Participation Tracker (PvE or PvP) - TBD

##### Character Features
* Character Skill Checks against Ship Doctrines - Completed
* Corp or Alliance-Recommended Minimum Skill Plans - Completed
* Character Market Activity and Management - TBD
* Web-based Evemail Viewing - Completed
* Web-based Asset Searching - TBD

##### Integration with External Tools
* Integration with Eve Dev Killboard - TBD
* Integration with 3rd Party Forum Software - Complete (PHPBB)
* Integration with Slack Chat - Complete
* Integration with TeamSpeak 3 - TBD

## Requirements
* Apache/Nginx/Litespeed Web Server
* PHP 5.4+ (PHP 5.6 is recommended for better performance, and is our base testing platform -- we also test in the latest 5.4.X and 5.5.X, however)
* MySQL 5.5+ (MariaDB 10.0+ recommended -- MySQL 5.6 preferred over MySQL 5.5, however all testing is done using Maria 10.0)
* SSL encryption for all web traffic. If you're unfamiliar with how to set up SSL for your website, contact me and I can help.

## Installation

I do not currently recommend that this be installed by anyone, in its present state. If you would like to test it out you certainly can do so, by following the steps below. This is undergoing active development, with new versions being pushed to Github multiple times per day, every day. There ARE bugs. A lot of the features listed above are NOT done yet, or are incredibly broken. You can see exactly what features are in what state above.

I can help with general questions and installation issues. Contact me via email with questions.

* Step 1: Ensure that your webserver is set up properly.
* Step 2: Clone this repository to your desired location.
* Step 3: Create your DB, and import the SQL file into it.
* Step 4: Register a new account through the web interface.
* Step 5: Set the "access" column within the MySQL database for your new account to "Admin"
* Step 6: Test everything, and submit issues, feature requests, and anything else using the Issues section of this Github repository.

## Changelog

During active development, I am not regularly updating this changelog, as we are routinely pushing 10-25 new updates via commit daily. Please see the commit history for updates as they come in, and know full well that new code may already be in testing as you are reading the previous code.

I am currently using a Semantic versioning system. All releases that begin with 0.X are considered to be pre-releases, and are not fit for use in a production environment. The base release version 1.0.0 will constitute the first production-ready release. Once 1.0.0 has been released I will be maintaining an ongoing changelog both within this section of the README file as well as in a separate changelog.txt file. 

## Licensing and Terms of Usage

EveAdmin is available under the MIT License, which can be found within the repository. As a condition of your accepting the license, you agree that EveAdmin is provided to you as-is, and any developer of EveAdmin cannot be held liable for any issues arising from the use of this software.