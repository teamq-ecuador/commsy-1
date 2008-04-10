<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos� Manuel Gonz�lez V�zquez
//
//    This file is part of CommSy.
//
//    CommSy is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    CommSy is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You have received a copy of the GNU General Public License
//    along with CommSy.

include_once('../migration.conf.php');
include_once('../db_link.dbi.php');
include_once('../update_functions.php');

// time management for this script
$time_start = getmicrotime();

// create database log_ads
echo ('This script sets the visibility of all of all users that where visible to none (visible = 0) to visible to logged in members (visible = 1).'."\n");
$query = "UPDATE user SET visible='1' WHERE visible='0'";
$success = select($query);

if ($success) {
   echo('<br/>[ <font color="#00ff00">done updating visibity</font> ]<br/>'."\n");
} else {
   echo('<br/>[ <font color="#ff0000">failed updating visibity</font> ]<br/>'."\n");
}

$query = "ALTER TABLE `user` CHANGE `visible` `visible` TINYINT( 4 ) NOT NULL DEFAULT '1'";
$success = select($query);

if ($success) {
   echo('[ <font color="#00ff00">done changing default visibility to 1</font> ]<br/>'."\n");
} else {
   echo('[ <font color="#ff0000">failed changing default visibility to 1</font> ]<br/>'."\n");
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>