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

$test = false;
#$test = true;

include_once('../migration.conf.php');
include_once('../db_link.dbi.php');
include_once('../update_functions.php');

$scripts[] = 'fix_disappeared_items';
$scripts[] = 'db_add_private_rooms';
$scripts[] = 'db_add_homepage_tables';
$scripts[] = 'db_move_auth';
$scripts[] = 'db_change_auth';

set_time_limit(0);

// start of execution time
$time_start_all = getmicrotime();

$title = 'Master Update Script for CommSy Update 4.4.0 to 4.5.0';

echo('<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">'."\n");
echo('<html>'."\n");
echo('<head>'."\n");
echo('<title>'.$title.'</title>'."\n");
echo('</head>'."\n");
echo('<body>'."\n");
echo('<h2>'.$title.'</h2>'."\n");
flush();

$first = true;
foreach ($scripts as $script) {
   $success = FALSE;
   if ($first) {
      $first = false;
   } else {
      echo "<br/><b>---------------------------------</b><br/>"."\n";
   }
   echo('<h3>'.$script);
   if ($test) {
      echo(' (testing)');
   } else {
      echo(' (executing)');
   }
   echo('</h3>'."\n");
   echo '<script type="text/javascript">window.scrollTo(1,10000000);</script>'."\n";
   flush();

   include_once($script.".php");
   echo '<script type="text/javascript">window.scrollTo(1,10000000);</script>'."\n";
   flush();

   if ($success == FALSE) {
      echo "<font color='#ff0000'><b> [failed]</b></font>"."\n";
      break;
   } else {
      echo "<font color='#00ff00'><b> [done]</b></font>"."\n";
   }
   echo('<br>');
   echo '<script type="text/javascript">window.scrollTo(1,10000000);</script>'."\n";
   flush();

   // um mysql eine verschnaufpause zwischen jedem script zu g�nnen
   sleep(5);
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start_all,3);
echo "<br/><br/><b>".count($scripts)." scripts processed in ".sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))." hours</b><br><br><br>\n";
echo '<script type="text/javascript">window.scrollTo(1,10000000);</script>';
echo('</body>'."\n");
echo('</html>'."\n");
flush();
?>