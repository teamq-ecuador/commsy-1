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

$time_start = getmicrotime();

// set to TRUE, to perform this script with write access
$do_it = !$test; // $test form master_update.php
$success = true;

// init database connection
$db = mysql_connect($DB_Hostname,$DB_Username,$DB_Password);
$db_link = mysql_select_db($DB_Name,$db);

echo ("This added indizes to table log.");

   $query  = "ALTER TABLE `log` ADD INDEX ( `cid` );";
   $result = mysql_query($query);
   if ( $error = mysql_error() ) {
      echo ('<hr>'.$error.". QUERY: ".$query.'<hr>');
   }
   $query  = "   ALTER TABLE `log` ADD INDEX ( `rid` );";
   $result = mysql_query($query);
   if ( $error = mysql_error() ) {
      echo ('<hr>'.$error.". QUERY: ".$query.'<hr>');
   }
   $query  = "   ALTER TABLE `log` ADD INDEX ( `timestamp` );";
   $result = mysql_query($query);
   if ( $error = mysql_error() ) {
      echo ('<hr>'.$error.". QUERY: ".$query.'<hr>');
   }

// end of execution time
echo(getProcessedTimeInHTML($time_start));
?>