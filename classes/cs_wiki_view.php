<?php
// $Id: cs_detail_view.php,v 1.16 2008/10/09 07:26:28 finck Exp $
//
// Release $Name:  $
//
// Copyright (c)2002-2007 Dirk Bl�ssl, Matthias Finck, Dirk Fust, Franz Gr�nig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
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

include_once('classes/cs_view.php');
include_once('classes/cs_list.php');
include_once('functions/curl_functions.php');

/**
 *  generic upper class for CommSy detail views
 */
class cs_wiki_view extends cs_view {

   /**
    * item - containing the item to display
    */
   var $_item = NULL;

   /**
    * subitems - cs_list containing the item to display below the actual item (e.g. sections)
    */
   var $_subitems = NULL;


   /** constructor: cs_wiki_view
    * the only available constructor, initial values for internal variables
    *
    * @param object  environment            the CommSy environment
    * @param string  viewname               a name for this view (e.g. news, dates)
    * @param boolean with_modifying_actions true: display with modifying functions
    *                                       false: display without modifying functions
    */
   function cs_wiki_view ($environment) {
      $this->cs_view($environment);
   }

   /**
    * Set the cs_item and optionally a list of subitems (also
    * of type cs_item) to display.
    */
   function setItem ($item){
      $this->_item = $item;
   }

   function getItem () {
      return $this->_item;
   }

   function setSubItemList ($subitems) {
      $this->_subitems = $subitems;
   }

   function getSubItemList () {
      return $this->_subitems;
   }

   function _format_image ( $text, $array ) {
      global $c_pmwiki_path_url;
      
      $retour = '';
      $image_text = '';
      if ( empty($array[1]) ) {
         // internal resource
         $file_name_array = $this->_getItemFileListForView();
         if ( !empty($array[2]) and !empty($file_name_array[$array[2]]) ) {
            $file = $file_name_array[$array[2]];
         }
         if ( isset($file) ) {
            if ( stristr(strtolower($file->getFilename()),'png')
                 or stristr(strtolower($file->getFilename()),'jpg')
                 or stristr(strtolower($file->getFilename()),'jpeg')
                 or stristr(strtolower($file->getFilename()),'gif')
               ) {
               $source = $c_pmwiki_path_url . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/uploads/CommSy/' . $file->getDiskFileNameWithoutFolder();
            }
         }
      } else {
         $source = $array[1].$array[2];
      }

      if ( !empty($array[3]) ) {
         $args = $this->_parseArgs($array[3]);
      } else {
         $args = array();
      }

      if ( !empty($args['alt']) ) {
         $alt = $args['alt'];
      } elseif ( !empty($source) )  {
         $alt = 'image: '.$source;
      }
      if ( !empty($args['gallery']) ) {
         $gallery = '['.$args['gallery'].']';
      } elseif ( !empty($source) )  {
         $gallery = '';
      }
      if ( !empty($args['float'])
           and ( $args['float'] == 'left'
                 or $args['float'] == 'right'
               )
         ) {
         $float = 'float:'.$args['float'].';';
      } elseif ( !empty($args['lfloat']) ) {
         $float = 'float:left;';
      } elseif ( !empty($args['rfloat']) ) {
         $float = 'float:right;';
      } else {
         $float = '';
      }
      if ( !empty($args['height'])
           and is_numeric($args['height'])
         ) {
         $height = 'height:'.$args['height'].'px;';
      } else {
         $height = '';
      }
      if ( !empty($args['width'])
           and is_numeric($args['width'])
         ) {
         $width = 'width:'.$args['width'].'px;';
      } elseif ( !empty($width_auto)
                 and empty($height)
               ) {
         $width = 'width:'.$width_auto.'px;';
      } else {
         $width = '';
      }
      
      if ( !empty($source) ) {
         $image_text .= '<div style="'.$float.$height.$width.' padding:5px;">';
         $image_text .= '<img style="'.$height.$width.'" src="'.$source.'" alt="'.$alt.'"/>';
         $image_text .= '</div>';
      }

      if ( !empty($image_text) ) {
         $text = str_replace($array[0],$image_text,$text);
      }

      $retour = $text;
      return $retour;
   }

   function _format_item ( $text, $array ) {
      global $c_commsy_domain;
      global $c_commsy_url_path;
      global $c_pmwiki_path_url;
      
      $retour = '';
      $link_text = '';
      if ( !empty($array[2]) ) {
         $args = $this->_parseArgs($array[2]);
      } else {
         $args = array();
      }

      if ( !empty($args['text']) ) {
         $word = $args['text'];
      } else {
         $word = '';
      }

      if ( !empty($args['target']) ) {
         $target = $args['target'];
      } elseif ( !empty($args['newwin']) ) {
         $target = '_blank';
      } else {
         $target = '';
      }

      if ( !empty($array[1]) ) {
          $params = array();
          $params['iid'] = $array[1];
          if ( empty($word) ) {
             $word = $array[1];
          }
          $material_manager = $this->_environment->getMaterialManager();
          $material_version_list = $material_manager->getVersionList($array[1]);
          $material_item = $material_version_list->getFirst();
          if(!empty($material_item) && $material_item->isExportToWiki()){
            $link_text = '<a href="' . $c_pmwiki_path_url . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/index.php?n=CommSy.Material' . $array[1] . '">' . $word . '</a>';
          } else {
            $link_text = '<a href="' . $c_commsy_domain . $c_commsy_url_path . '/commsy.php?cid=' . $this->_environment->getCurrentContextID() . '&mod=content&fct=detail&iid=' . $array[1] . '">' . $word . '</a>';
          }
      }
      if ( !empty($link_text) ) {
         $text = str_replace($array[0],$link_text,$text);
      }

      $retour = $text;
      return $retour;
   }
   
   function _format_file ( $text, $array ) {
      global $c_pmwiki_path_url;
      
      $retour = '';
      $link_text = '';
      if ( !empty($array[1]) ) {
         $file_name_array = $this->_getItemFileListForView();
         $file = $file_name_array[$array[1]];
         if ( isset($file) ) {

            if ( !empty($array[2]) ) {
               $args = $this->_parseArgs($array[2]);
            } else {
               $args = array();
            }

            if ( empty($args['icon'])
                 or ( !empty($args['icon'])
                      and $args['icon'] == 'true'
                    )
               ) {
               $icon = $file->getFileIcon().' ';
            } else {
               $icon = '';
            }
            if ( empty($args['size'])
                 or ( !empty($args['size'])
                      and $args['size'] == 'true'
                    )
               ) {
               $kb = ' ('.$file->getFileSize().' KB)';
            } else {
               $kb = '';
            }
            if ( !empty($args['text']) ) {
               $name = $args['text'];
            } else {
               $name = $file->getDisplayName();
            }

            if ( !empty($args['target']) ) {
               $target = ' target="'.$args['target'].'"';
            } elseif ( !empty($args['newwin']) ) {
               $target = ' target=_blank;';
            } else {
               $target = '';
            }
            //$source = $file->getUrl();
            $source = $c_pmwiki_path_url . '/wikis/' . $this->_environment->getCurrentPortalID() . '/' . $this->_environment->getCurrentContextID() . '/uploads/CommSy/' . $file->getDiskFileNameWithoutFolder();
            $link_text = '<a href="'.$source.'"'.$target.'>'.$name.'</a>'.$kb;
         }
      }

      if ( !empty($link_text) ) {
         $text = str_replace($array[0],$link_text,$text);
      }

      $retour = $text;
      return $retour;
   }
   
   function _format_flash ( $text, $array ) {
      $retour = '';
      if ( empty($array[1]) ) {
         // internal resource
         $file_name_array = $this->_getItemFileListForView();
         $file = $file_name_array[$array[2]];
         if ( isset($file) ) {
            $source = $file->getURL();
            $ext = $file->getExtension();
            $extern = false;
         }
      } else {
         $source = $array[1].$array[2];
         $ext = cs_strtolower(substr(strrchr($source,'.'),1));
         $extern = true;
      }
      if ( !empty($array[3]) ) {
         $args = $this->_parseArgs($array[3]);
      } else {
         $args = array();
      }

      if ( !empty($args['play']) ) {
         $play = $args['play'];
      } else {
         $play = 'false';
      }

      if ( !empty($args['float'])
           and ( $args['float'] == 'left'
                 or $args['float'] == 'right'
               )
         ) {
         $float = 'float:'.$args['float'].';';
      } elseif ( !empty($args['lfloat']) ) {
         $float = 'float:left;';
      } elseif ( !empty($args['rfloat']) ) {
         $float = 'float:right;';
      } else {
         $float = '';
      }

      if ( !empty($args['navigation'])
           and $args['navigation']
         ) {
         $with_player = true;
      } elseif ( !empty($args['navigation'])
                 and !$args['navigation']
               ) {
         $with_player = false;
      } elseif ( $ext == 'swf' ) {
         $with_player = false;
      } else {
         $with_player = true;
      }

      if ( !empty($source) ) {
         $link_text = '(:flash ' . $file->getDiskFileNameWithoutFolder() . ':)';
         $text = str_replace($array[0],$link_text,$text);
      }
      $retour = $text;
      return $retour;
   }
   
   function formatForWiki($text){
      $reg_exp_father_array = array();
      $reg_exp_father_array[]       = '/\\(:(.*?):\\)/e';

      $reg_exp_array = array();
      $reg_exp_array['(:flash']       = '/\\(:flash (.*?:){0,1}(.*?)(\\s.*?)?\\s*?:\\)/e';
      $reg_exp_array['(:quicktime']   = '/\\(:quicktime (.*?:){0,1}(.*?)(\\s.*?)?\\s*?:\\)/e';
      $reg_exp_array['(:wmplayer']    = '/\\(:wmplayer (.*?:){0,1}(.*?)(\\s.*?)?\\s*?:\\)/e';
      $reg_exp_array['(:image']       = '/\\(:image (.*?:){0,1}(.*?)(\\s.*?)?\\s*?:\\)/e';
      $reg_exp_array['(:item']        = '/\\(:item ([0-9]*?)(\\s.*?)?\\s*?:\\)/e';
      $reg_exp_array['(:link']        = '/\\(:link (.*?:){0,1}(.*?)(\\s.*?)?\\s*?:\\)/e';
      $reg_exp_array['(:file']        = '/\\(:file (.*?)(\\s.*?)?\\s*?:\\)/e';
      $reg_exp_array['(:zip']         = '/\\(:zip (.*?)(\\s.*?)?\\s*?:\\)/e';
      $reg_exp_array['(:youtube']     = '/\\(:youtube (.*?)(\\s.*?)?\\s*?:\\)/e';
      $reg_exp_array['(:googlevideo'] = '/\\(:googlevideo (.*?)(\\s.*?)?\\s*?:\\)/e';
      $reg_exp_array['(:vimeo']       = '/\\(:vimeo (.*?)(\\s.*?)?\\s*?:\\)/e';
      $reg_exp_array['(:mp3']         = '/\\(:mp3 (.*?:){0,1}(.*?)(\\s.*?)?\\s*?:\\)/e';
      if($this->_environment->isScribdAvailable()){
        $reg_exp_array['(:office']      = '/\\(:office (.*?)(\\s.*?)?\\s*?:\\)/e';
      }

      // jsMath for latex math fonts
      // see http://www.math.union.edu/~dpvc/jsMath/
      global $c_jsmath_enable;
      if ( isset($c_jsmath_enable)
           and $c_jsmath_enable
         ) {
         $reg_exp_father_array[]   = '/\\{\\$[\\$]{0,1}(.*?)\\$[\\$]{0,1}\\}/e';
         $reg_exp_array['{$$']     = '/\\{\\$\\$(.*?)\\$\$\\}/e'; // must be before next one
         $reg_exp_array['{$']      = '/\\{\\$(.*?)\\$\\}/e';
      }

      // is there wiki syntax ?
      if ( !empty($reg_exp_array) ) {
         $reg_exp_keys = array_keys($reg_exp_array);
         $clean_text = false;
         foreach ($reg_exp_keys as $key) {
            if ( stristr($text,$key) ) {
               $clean_text = true;
               break;
            }
         }
      }

      // clean wikistyle text from HTML-Code (via fckeditor)
      // and replace wikisyntax
      if ($clean_text) {
         $matches = array();
         foreach ($reg_exp_father_array as $exp) {
         $found = preg_match_all($exp,$text,$matches);
         if ( $found > 0 ) {
            $matches[0] = array_unique($matches[0]); // doppelte einsparen
            foreach ($matches[0] as $value) {
               $value_new = strip_tags($value);
               foreach ($reg_exp_array as $key => $reg_exp) {
                  if ( $key == '(:flash' and stristr($value_new,'(:flash') ) {
                     //$value_new = $this->_format_flash($value_new,$this->_getArgs($value_new,$reg_exp));
                     $value_new = '[=(:flash:)=] - ' . getMessage('EXPORT_TO_WIKI_NOT_SUPPOTED_YET');
                     break;
                  } elseif ( $key == '(:wmplayer' and stristr($value_new,'(:wmplayer') ) {
                     //$value_new = $this->_format_wmplayer($value_new,$this->_getArgs($value_new,$reg_exp));
                     $value_new = '[=(:wmplayer:)=] - ' . getMessage('EXPORT_TO_WIKI_NOT_SUPPOTED_YET');
                     break;
                  } elseif ( $key == '(:quicktime' and stristr($value_new,'(:quicktime') ) {
                     //$value_new = $this->_format_quicktime($value_new,$this->_getArgs($value_new,$reg_exp));
                     $value_new = '[=(:quicktime:)=] - ' . getMessage('EXPORT_TO_WIKI_NOT_SUPPOTED_YET');
                     break;
                  } elseif ( $key == '(:image' and stristr($value_new,'(:image') ) {
                     $value_new = $this->_format_image($value_new,$this->_getArgs($value_new,$reg_exp));
                     break;
                  } elseif ( $key == '(:item' and stristr($value_new,'(:item') ) {
                     $value_new = $this->_format_item($value_new,$this->_getArgs($value_new,$reg_exp));
                     break;
                  } elseif ( $key == '(:link' and stristr($value_new,'(:link') ) {
                     $value_new = $this->_format_link($value_new,$this->_getArgs($value_new,$reg_exp));
                     break;
                  } elseif ( $key == '(:file' and stristr($value_new,'(:file') ) {
                     $value_new = $this->_format_file($value_new,$this->_getArgs($value_new,$reg_exp));
                     break;
                  } elseif ( $key == '(:zip' and stristr($value_new,'(:zip') ) {
                     //$value_new = $this->_format_zip($value_new,$this->_getArgs($value_new,$reg_exp));
                     // Zip entpacken und als Webseite darstellen funktioniert nicht. Daher
                     // ersteinmal behandeln wie eine Datei.
                     $value_new = $this->_format_file($value_new,$this->_getArgs($value_new,$reg_exp));
                     break;
                  } elseif ( $key == '(:youtube' and stristr($value_new,'(:youtube') ) {
                     $value_new = $this->_format_youtube($value_new,$this->_getArgs($value_new,$reg_exp));
                     break;
                  } elseif ( $key == '(:googlevideo' and stristr($value_new,'(:googlevideo') ) {
                     $value_new = $this->_format_googlevideo($value_new,$this->_getArgs($value_new,$reg_exp));
                     break;
                  } elseif ( $key == '(:vimeo' and stristr($value_new,'(:vimeo') ) {
                     $value_new = $this->_format_vimeo($value_new,$this->_getArgs($value_new,$reg_exp));
                     break;
                  } elseif ( $key == '(:mp3' and stristr($value_new,'(:mp3') ) {
                     //$value_new = $this->_format_mp3($value_new,$this->_getArgs($value_new,$reg_exp));
                     $value_new = $value_new;
                     break;
                  } elseif ( $key == '(:office' and stristr($value_new,'(:office') ) {
                     //$value_new = $this->_format_office($value_new,$this->_getArgs($value_new,$reg_exp));
                     $value_new = '[=(:office:)=] - ' . getMessage('EXPORT_TO_WIKI_NOT_SUPPOTED_YET');
                     break;
                  } elseif ( $key == '{$' and stristr($value_new,'{$') ) {
                     $value_new = $this->_format_math1($value_new,$this->_getArgs($value_new,$reg_exp));
                     break;
                  } elseif ( $key == '{$$' and stristr($value_new,'{$$') ) {
                     $value_new = $this->_format_math2($value_new,$this->_getArgs($value_new,$reg_exp));
                     break;
                  }
               }
               $text = str_replace($value,$value_new,$text);
            }
         }
         }
      }
      return $text;
   }
}
?>