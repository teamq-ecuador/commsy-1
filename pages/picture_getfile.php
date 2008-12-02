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

$disc_manager = $environment->getDiscManager();
if (!empty($_GET['picture']) and $disc_manager->existsFile($_GET['picture'])) {
   header('Content-type: image');
   header('Pragma: no-cache');
   header('Expires: 0');
   readfile($disc_manager->getFilePath('picture').$_GET['picture']);
} else if(!empty($_GET['picture']) and withUmlaut($_GET['picture'])) {
     $filename = rawurlencode($_GET['picture']);
  	 if (file_exists($disc_manager->_getFilePath().$filename)) {
       header('Content-type: image');
   	   header('Pragma: no-cache');
   	   header('Expires: 0');
       readfile($disc_manager->getFilePath('picture').$filename);
     }
  }
exit();
?>