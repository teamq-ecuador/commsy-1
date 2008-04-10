<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez
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

// Files
if ( isset($post_file_ids)
     and !empty($post_file_ids)
   ) {
   $file_ids = $post_file_ids;
} else {
   $file_ids = isset($_POST['filelist']) ? $_POST['filelist'] : array();
}
$files = $session->getValue($environment->getCurrentModule().'_add_files');
$file_id_array = array();
if ( !empty($files) ) {
   $file_man = $environment->getFileManager();
   foreach ( $files as $file_data ) {
      if ( in_array($file_data["file_id"], $file_ids) ) {
         if ( isset($file_data["tmp_name"]) and file_exists($file_data["tmp_name"]) ) { // create file entries for uploaded files
            $file_item = $file_man->getNewItem();
            $file_item->setPostFile($file_data);
            $file_item->save();
            unlink($file_data["tmp_name"]);  // Currently, the file manager does not unlink a file in its _saveOnDisk() method, because it is also used for copying files when copying material.
            $file_id_array[] = $file_item->getFileID();
         } else {
            $file_id_array[] = $file_data["file_id"];
         }
      }
   }
   $item_files_upload_to->setFileIDArray($file_id_array);
} else {
   $item_files_upload_to->setFileIDArray(array());
}
?>