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

// move configuration of ads from cs_config to database
echo ('This script adds LANGUAGE to community rooms, portals and server and sets it to browser.'."\n");
$success = true;

// get cs_config.php
include_once('../../etc/cs_config.php');

$count_portal = array_shift(mysql_fetch_row(select("SELECT COUNT(portal.item_id) FROM portal WHERE portal.deletion_date IS NULL;")));
$count_community = array_shift(mysql_fetch_row(select("SELECT COUNT(community.item_id) FROM community WHERE community.deletion_date IS NULL;")));
$count_server = array_shift(mysql_fetch_row(select("SELECT COUNT(server.item_id) FROM server WHERE server.deletion_date IS NULL;")));
$count_rooms = $count_portal + $count_community + $count_server;
if ($count_rooms < 1) {
   echo "<br/>nothing to do.";
} else {
   init_progress_bar($count_rooms);

   // portals
   $query  = "SELECT portal.item_id, portal.extras FROM portal WHERE portal.deletion_date IS NULL ORDER BY portal.item_id;";
   $result = select($query);
   $row = mysql_fetch_row($result);
   $room_id = $row[0];
   $extra = $row[1];
   while ($room_id) {
      if ( !strstr($extra,'<LANGUAGE>') ) {
         $extra .= '<LANGUAGE>user</LANGUAGE>';
         $insert_query = 'UPDATE portal SET extras="'.addslashes($extra).'" WHERE item_id="'.$room_id.'"';
         $success = select($insert_query);
      }
      $row = mysql_fetch_row($result);
      $room_id = $row[0];
      $extra = $row[1];
      update_progress_bar($count_rooms);
   }

   // community rooms
   $query  = "SELECT community.item_id, community.extras FROM community WHERE community.deletion_date IS NULL ORDER BY community.item_id;";
   $result = select($query);
   $row = mysql_fetch_row($result);
   $room_id = $row[0];
   $extra = $row[1];
   while ($room_id) {
      if ( !strstr($extra,'<LANGUAGE>') ) {
         $extra .= '<LANGUAGE>user</LANGUAGE>';
         $insert_query = 'UPDATE community SET extras="'.addslashes($extra).'" WHERE item_id="'.$room_id.'"';
         $success = select($insert_query);
      }
      $row = mysql_fetch_row($result);
      $room_id = $row[0];
      $extra = $row[1];
      update_progress_bar($count_rooms);
   }

   // server
   $query  = "SELECT server.item_id, server.extras FROM server WHERE server.deletion_date IS NULL ORDER BY server.item_id;";
   $result = select($query);
   $row = mysql_fetch_row($result);
   $room_id = $row[0];
   $extra = $row[1];
   while ($room_id) {
      if ( !strstr($extra,'<LANGUAGE>') ) {
         $extra .= '<LANGUAGE>user</LANGUAGE>';
         $insert_query = 'UPDATE server SET extras="'.addslashes($extra).'" WHERE item_id="'.$room_id.'"';
         $success = select($insert_query);
      }
      $row = mysql_fetch_row($result);
      $room_id = $row[0];
      $extra = $row[1];
      update_progress_bar($count_rooms);
   }
}

if ($success) {
   echo('[ <font color="#00ff00">done</font> ]<br/>'."\n");
} else {
   echo('[ <font color="#ff0000">failed</font> ]<br/>'."\n");
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
echo "<br/>Execution time: ".sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>