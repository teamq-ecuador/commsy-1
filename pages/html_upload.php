<?php
//
// Copyright (c)2002-2007 Dirk Bloessl, Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Joseacute; Manuel Gonzaacute;lez Vaacute;zquez
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

set_time_limit(0);

function listfilenames ($dir, $pos=2,$fileitem,$environment,$namearray) {

   $handle = @opendir($dir);
   while ( $file = @readdir($handle) ) {
      if ( preg_match("=^\.{1,2}$=", $file) ) {
         continue;
      }
      $newfilename = strtolower($file);
      rename($dir.$file,$dir.$newfilename);
      $file = $newfilename;
      if ( is_dir($dir.$file) ) {
         $namearray = listfilenames($dir.$file."/", $pos + 3,$fileitem,$environment,$namearray);
      } else {
         $extension = strtolower(substr(strrchr($dir.$file,"."),1));
         if ( is_file($dir.$file) ) {
            $namearray['filename'][] = $file;
            $namearray['dirname'][] = $dir;
         }
      }
   }
   @closedir($handle);
   return $namearray;
}

function replace_files ($dir, $pos=2,$fileitem,$environment,$namearray) {
   $handle = @opendir($dir);
   while ( $file = @readdir($handle) ) {
      if ( preg_match("=^\.{1,2}$=", $file) ) {
         continue;
      }

      if ( is_dir($dir.$file) ) {
         replace_files($dir.$file."/", $pos + 3,$fileitem,$environment,$namearray);
      } else {
         $extension = strtolower(substr(strrchr($dir.$file,"."),1));
         if ( is_file($dir.$file) and ($extension == "htm" OR $extension == "html" OR $extension == "js" OR $extension == "xml" OR $extension == "xslt" OR $extension == "xsd")){
            $replacement = replacement($environment,$fileitem,$dir,$file,$namearray);
            $open = fopen($dir.$file,'w');
            fputs($open,$replacement);
            fclose($open);
         }
      }
   }
   @closedir($handle);
}


function replacement($environment,$file,$pfad,$datei,$namearray) {
   $filecontent = file_get_contents($pfad.$datei);

   $path = 'var/'.$environment->getCurrentPortalID().'/'.$environment->getCurrentContextID().'/html_'.$file->getDiskFileNameWithoutFolder().'/';
   $linkpath = "";
   if ( $path != $pfad ) {
      $linkpath = str_replace($path,'',$pfad);
   }
   foreach ( $namearray['filename'] as $name ) {
      //!'(.*?)show.gif!
      $pattern = "![\./\w������_-]{0,}".$name."!is";
      preg_match_all($pattern, $filecontent, $current_treffer);
      foreach ( $current_treffer[0] as $treffer ) {
         $trefferlowercase = strtolower($treffer);
         $replacement = 'commsy.php?cid='.$environment->getCurrentContextID().'&mod=material&fct=showzip_file&iid='.$file->getFileID().'&file='.$linkpath.$trefferlowercase;

         if ( !stristr($filecontent,$replacement) ) {
            $filecontent = str_replace($treffer, $replacement, $filecontent);
         }
      }
   }
   return $filecontent;
}

$zip = new ZipArchive;

$source_file = $file->getDiskFileName();
$target_directory = 'var/'.$this->_environment->getCurrentPortalID().'/'.$this->_environment->getCurrentContextID().'/html_'.$file->getDiskFileNameWithoutFolder().'/';

global $export_temp_folder;
if ( !isset($export_temp_folder) ) {
   $export_temp_folder = 'var/temp/zip_export';
}
$file->setHasHTML(1);
$res = $zip->open($source_file);
if ( $res === TRUE ) {
   if( $zip->extractTo($export_temp_folder,'index.htm') ) {
      $file->setHasHTML(2);
      $indexfile = "index.htm";
      unlink($export_temp_folder.'/index.htm');
   } elseif ( $zip->extractTo($export_temp_folder,'index.html') ) {
      $file->setHasHTML(2);
      $indexfile = "index.html";
      unlink($export_temp_folder.'/index.html');
   }
   $file->saveHasHTML();
   if($file->getHasHTML() == 2) {
      $zip->extractTo($target_directory);
   }
   $zip->close();
}
unset($zip);
if($file->getHasHTML() == 2) {
   $pfad = 'var/'.$this->_environment->getCurrentPortalID().'/'.$this->_environment->getCurrentContextID().'/html_'.$file->getDiskFileNameWithoutFolder().'/';
   $namearray['filename'] = array();
   $namearray['dirname'] = array();
   $namearray = listfilenames($pfad,2,$file,$this->_environment,$namearray);
   replace_files($pfad,2,$file,$this->_environment,$namearray);
}
?>