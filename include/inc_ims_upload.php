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



function _getMaterialByXMLArray($material_item, $values_array,$directory,$citation_style='harvard'){
   global $environment;
   $material_item->setVersionID(0);
   $material_item->setContextID($environment->getCurrentContextID());
   $user = $environment->getCurrentUserItem();
   $material_item->setCreatorItem($user);
   $material_item->setCreationDate(getCurrentDateTimeInMySQL());


   $title = '';
   $beluga_url = '';
   $availability = '';
   $authors_array = array();

/**Daten für die Bibliografischen Angaben: werden später durch das xslt ersetzt**/
   $contributors_array = array();
   $editor_array = array();
   $bib_kind = 'buch';
   $edition = '';
   $location = '';
   $publisher = '';
   $jounal_location = '';
   $jounal_publisher = '';
/********************************************************************************/


   $pub_date = '';
   $bib_val = '';
   $abstract = '';
   $table_of_content = '';
   $full_text = '';
   $files = array();
   $jounal_typ = 'article';

   $qualification_type = '';
   $academic_institution = '';
   $book_title = '';
   $start_page = '';
   $end_page = '';
   $volume_number = '';
   $issue_number = '';
   $number = '';
   $journal_title = '';
   $is_diss = false;
   $is_electronic_publication = false;

   $i = 0;
#   pr($values_array);
   foreach($values_array as $key => $value){
     switch ($value['tag']){
       case 'beluga_url':
            $beluga_url = utf8_decode($values_array[$key]['value']);
            break;
       case 'availability':
            $availability = utf8_decode($values_array[$key]['value']);
            if ($availability == 'none'){
            	$availability = getMessage('BELUGA_NO_AVAILABILITY_INFORMATION');
            }
            break;


/**Daten f�r die Bibliografischen Angaben: werden sp�ter durch das xslt ersetzt**/
       case 'dc:contributor':
            if (isset($values_array[$key]['value'])){
               $contributors_array[] = utf8_decode($values_array[$key]['value']);
            }
            break;
       case 'dc:medium':
            if (isset($values_array[$key]['value']) and $values_array[$key]['value'] == 'Elektronische Publikation'){
               $is_electronic_publication = true;
            }
            break;
       case 'dc:editor':
            if (isset($values_array[$key]['value'])){
               $editor_array[] = utf8_decode($values_array[$key]['value']);
            }
            break;
       case 'dcterms:descritpion':
            if ( isset($values_array[$key]['attributes']['art']) and  $values_array[$key]['attributes']['art'] ==  'HSS'){
               $is_diss = true;
            }
            break;
       case 'dc:type':
            if (isset($values_array[$key]['attributes']['voc'])
                and $values_array[$key]['attributes']['voc'] ==  'vap:objekttypen'
                and (
                   $values_array[$key]['value'] == 'Buch'
                   or $values_array[$key]['value'] == 'Dissertation'
                   or $values_array[$key]['value'] == 'Zeitschrift'
                   or $values_array[$key]['value'] == 'Aufsatz'
                   or $values_array[$key]['value'] == 'Schriftenreihe'
                )
            ){
                $bib_kind = utf8_decode($values_array[$key]['value']);
            }
            break;
       case 'dcterms:bibliographicCitation':
            if (isset($values_array[$i+1]['attributes']['rfe.jtitle'])){
               $jounal_typ = 'article';
               $journal_title = utf8_decode($values_array[$i+1]['attributes']['rfe.jtitle']);
            }
            elseif ( isset($values_array[$i+1]['attributes']['rfe.btitle']) ){
               $jounal_typ = 'chapter';
               $book_title = utf8_decode($values_array[$i+1]['attributes']['rfe.btitle']);
            }
            if (isset($values_array[$i+1]['attributes']['rfe.epage'])){
               $end_page = utf8_decode($values_array[$i+1]['attributes']['rfe.epage']);
            }
            if (isset($values_array[$i+1]['attributes']['rfe.spage'])){
               $start_page = utf8_decode($values_array[$i+1]['attributes']['rfe.spage']);
            }
            if (isset($values_array[$i+1]['attributes']['rfe.issue'])){
               $issue_number = utf8_decode($values_array[$i+1]['attributes']['rfe.issue']);
            }
            if (isset($values_array[$i+1]['attributes']['rfe.publisher'])){
               $jounal_publisher = utf8_decode($values_array[$i+1]['attributes']['rfe.publisher']);
            }
            if (isset($values_array[$i+1]['attributes']['rfe.location'])){
               $jounal_location = utf8_decode($values_array[$i+1]['attributes']['rfe.location']);
            }
            if (isset($values_array[$i+1]['attributes']['rfe_val_fmt'])){
               if (strstr($values_array[$i+1]['attributes']['rfe_val_fmt'],'dissertation')){
                  $qualification_type = 'Dissertation';
               }
            }
            break;
           case 'dc:publisher':
            if (isset($values_array[$key]['value'])){
               $publisher = utf8_decode($values_array[$key]['value']);
            }
            if (isset($values_array[$key]['attributes']['Location'])){
               $location = utf8_decode($values_array[$key]['attributes']['Location']);
            }
            break;
/********************************************************************************/



       case 'dc:title':
            $title = utf8_decode($values_array[$key]['value']);
            break;
       case 'dc:creator':
           if (isset($values_array[$key]['value'])){
               $authors_array[] = utf8_decode($values_array[$key]['value']);
            }
            break;
       case 'dcterms:issued':
           if (isset($values_array[$key]['value'])){
               $pub_date = utf8_decode($values_array[$key]['value']);
           }
           break;
       case 'dcterms:abstract':
            if (isset($values_array[$key]['value'])){
               $abstract = utf8_decode($values_array[$key]['value']);
            }
            break;
       case 'dcterms:tableOfContents':
            if (isset($values_array[$i+1]['attributes']['url'])){
               $table_of_content = $values_array[$i+1]['attributes']['url'];
            }
            break;
      }
      $i++;

   }
   if($is_diss){
   	$bib_kind = Dissertation;
   }


/**Daten f�r die Bibliografischen Angaben: werden sp�ter durch das xslt ersetzt**/
   $biblio = '';
   switch ($bib_kind) {
      case 'Buch':
      //Harvard-Style:  FAMILY NAME, INITIAL(S). ed. Year. Title. City of publication: Publisher
      $with_creator = true;
      if ($citation_style == 'apa'){
         if (!isset($authors_array[0])){
            $with_creator = false;
            $authors_array = $contributors_array;
         }
         $names = '';
         $i = 0;
         foreach ($authors_array as $author_string){
            $i++;
            $name = '';
            $author_array = explode(',',$author_string);
            $firstname = '';
            if (isset($author_array[1])){
               $first_name_array = explode(' ',$author_array[1]);
               $lastname = $author_array[0];
               $j = 0;
               foreach($first_name_array as $fname){
                  $j++;
                  if (!empty($fname)){
                     $firstname .= strtoupper(substr($fname,0,1));
                     $firstname .= '.';
                     if (isset($first_name_array[$j])){
                        $firstname .= ' ';
                     }
                  }
               }
               $name = $lastname.', '.$firstname;
               if (!empty($names)){
                  if (!isset($authors_array[$i])){
                     $names .= '& ';
                  }else{
                     $names .= ', ';
                  }
               }
               $names .= $name;
            }
         }
         $biblio .= $names;
         if (!$with_creator){
            $biblio .= ' Hrsg.';
         }
         if (!empty($pub_date)){
            $biblio .= ' ('.$pub_date.').';
         }
         if (!empty($title)){
            $biblio .= ' _'.$title.'_.';
         }
         if (!empty($location)){
            $biblio .= ' '.$location;
         }
         if (!empty($publisher)){
            $biblio .= ': '.$publisher.'.';
         }

      }else{
         if (!isset($authors_array[0])){
            $authors_array = $contributors_array;
            $with_creator = false;
         }
         $names = '';
         foreach ($authors_array as $author_string){
            $name = '';
            $author_array = explode(',',$author_string);
            $firstname = '';
            if (isset($author_array[1])){
               $first_name_array = explode(' ',$author_array[1]);
               $lastname = strtoupper($author_array[0]);
               $j = 0;
               foreach($first_name_array as $fname){
                  $j++;
                  if (!empty($fname)){
                     $firstname .= strtoupper(substr($fname,0,1));
                     $firstname .= '.';
                     if (isset($first_name_array[$j])){
                        $firstname .= ' ';
                     }
                  }
               }
               $name = $lastname.', '.$firstname;
               if (!empty($names)){
                  $names .= ', ';
               }
               $names .= $name;
            }
         }
         $biblio .= $names;
         if (!$with_creator){
            $biblio .= ' Hrsg.';
         }
         if (!empty($pub_date)){
            $biblio .= ' '.$pub_date.'.';
         }
         if (!empty($title)){
            $biblio .= ' _'.$title.'_.';
         }
         if (!empty($location)){
            $biblio .= ' '.$location;
         }
         if (!empty($publisher)){
            $biblio .= ': '.$publisher.'.';
         }

      }
      break;


      case 'Dissertation':
      //FAMILY NAME, INITIAL(S). Year. Title. Type of qualification, academic institution
      $with_creator = true;
      if ($citation_style == 'apa'){
         $names = '';
         $i = 0;
         foreach ($authors_array as $author_string){
            $i++;
            $name = '';
            $author_array = explode(',',$author_string);
            $firstname = '';
            if (isset($author_array[1])){
               $first_name_array = explode(' ',$author_array[1]);
               $lastname = $author_array[0];
               $j = 0;
               foreach($first_name_array as $fname){
                  $j++;
                  if (!empty($fname)){
                     $firstname .= strtoupper(substr($fname,0,1));
                     $firstname .= '.';
                     if (isset($first_name_array[$j])){
                        $firstname .= ' ';
                     }
                  }
               }
               $name = $lastname.', '.$firstname;
               if (!empty($names)){
                  if (!isset($authors_array[$i])){
                     $names .= '& ';
                  }else{
                     $names .= ', ';
                  }
               }
               $names .= $name;
            }
         }
         $biblio .= $names;
         if (!empty($pub_date)){
            $biblio .= ' ('.$pub_date.').';
         }
         if (!empty($title)){
            $biblio .= ' _'.$title.'_.';
         }
         if (!empty($qualification_type)){
            $biblio .= ' '.$qualification_type;
         }
         if (!empty($location)){
            $biblio .= ', '.$location;
         }
         $biblio .= '. ';

      }else{
         $names = '';
         foreach ($authors_array as $author_string){
            $name = '';
            $author_array = explode(',',$author_string);
            $firstname = '';
            if (isset($author_array[1])){
               $first_name_array = explode(' ',$author_array[1]);
               $lastname = strtoupper($author_array[0]);
               $j = 0;
               foreach($first_name_array as $fname){
                  $j++;
                  if (!empty($fname)){
                     $firstname .= strtoupper(substr($fname,0,1));
                     $firstname .= '.';
                     if (isset($first_name_array[$j])){
                        $firstname .= ' ';
                     }
                  }
               }
               $name = $lastname.', '.$firstname;
               if (!empty($names)){
                  $names .= ', ';
               }
               $names .= $name;
            }
         }
         $biblio .= $names;
         if (!empty($pub_date)){
            $biblio .= ' '.$pub_date.'.';
         }
         if (!empty($title)){
            $biblio .= ' _'.$title.'_.';
         }
         if (!empty($qualification_type)){
            $biblio .= ' '.$qualification_type;
         }
         if (!empty($location)){
            $biblio .= ', '.$location;
         }
         $biblio .= '. ';

      }
      break;

      case 'Zeitschrift':
      //Zeitschrift: "Journal title", Ort: Verlag.
      $with_creator = true;
      if ($citation_style == 'apa'){
         $names = '';
         $i = 0;
         foreach ($authors_array as $author_string){
            $i++;
            $name = '';
            $author_array = explode(',',$author_string);
            $firstname = '';
            if (isset($author_array[1])){
               $first_name_array = explode(' ',$author_array[1]);
               $lastname = $author_array[0];
               $j = 0;
               foreach($first_name_array as $fname){
                  $j++;
                  if (!empty($fname)){
                     $firstname .= strtoupper(substr($fname,0,1));
                     $firstname .= '.';
                     if (isset($first_name_array[$j])){
                        $firstname .= ' ';
                     }
                  }
               }
               $name = $lastname.', '.$firstname;
               if (!empty($names)){
                  if (!isset($authors_array[$i])){
                     $names .= '& ';
                  }else{
                     $names .= ', ';
                  }
               }
               $names .= $name;
            }
         }
         $biblio .= $names;
         $biblio .= ' (Zeitschrift).';
         if (!empty($title)){
            $biblio .= ' _'.$title.'_.';
         }
         if (!empty($pub_date)){
            $biblio .= ' ('.$pub_date.').';
         }
         if (!empty($location)){
            $biblio .= ' '.$location;
         }
         if (!empty($publisher)){
            $biblio .= ': '.$publisher.'.';
         }

      }else{
         $names = '';
         $i = 0;
         foreach ($authors_array as $author_string){
            $name = '';
            $author_array = explode(',',$author_string);
            $firstname = '';
            if (isset($author_array[1])){
               $first_name_array = explode(' ',$author_array[1]);
               $lastname = strtoupper($author_array[0]);
               $j = 0;
               foreach($first_name_array as $fname){
                  $j++;
                  if (!empty($fname)){
                     $firstname .= strtoupper(substr($fname,0,1));
                     $firstname .= '.';
                     if (isset($first_name_array[$j])){
                        $firstname .= ' ';
                     }
                  }
               }
               $name = $lastname.', '.$firstname;
               if (!empty($names)){
                  $names .= ', ';
               }
               $names .= $name;
            }
         }
         $biblio .= $names;
         $biblio .= ', Zeitschrift.';
         if (!empty($title)){
            $biblio .= ' _'.$title.'_.';
         }
         if (!empty($pub_date)){
            $biblio .= ' '.$pub_date.'.';
         }
         if (!empty($location)){
            $biblio .= ' '.$location;
         }
         if (!empty($publisher)){
            $biblio .= ': '.$publisher.'.';
         }

      }
      break;

      case 'Schriftenreihe':
      //Schriftenreihe: "Journal title", Ort: Verlag.
      $with_creator = true;
      if ($citation_style == 'apa'){
         $biblio .= 'Schriftenreihe: ';
         if (!empty($title)){
            $biblio .= ' _'.$title.'_.';
         }
         if (!empty($pub_date)){
            $biblio .= ' ('.$pub_date.').';
         }
         if (!empty($location)){
            $biblio .= ' '.$location;
         }
         if (!empty($publisher)){
            $biblio .= ': '.$publisher.'.';
         }

      }else{
         $biblio .= 'Schriftenreihe: ';
         if (!empty($title)){
            $biblio .= ' _'.$title.'_.';
         }
         if (!empty($pub_date)){
            $biblio .= ' '.$pub_date.'.';
         }
         if (!empty($location)){
            $biblio .= ' '.$location;
         }
         if (!empty($publisher)){
            $biblio .= ': '.$publisher.'.';
         }

      }
      break;




      case 'Aufsatz':
         if($jounal_typ == 'chapter'){
      //FAMILY NAME, INITIAL(S). Year. Chapter title. In: Initial(s) FAMILY NAME OF EDITOR(S), ed(s).
      //Title of book. City of publication: Publisher, Page numbers.
             //FAMILY NAME, INITIAL(S). Year. Title of article. Journal title. Volume (issue number), page numbers.
            if ($citation_style == 'apa'){
               if (!isset($authors_array[0])){
                  $with_creator = false;
                  $authors_array = $contributors_array;
               }
               $names = '';
               $i = 0;
               foreach ($authors_array as $author_string){
                  $i++;
                  $name = '';
                  $author_array = explode(',',$author_string);
                  $firstname = '';
                  if (isset($author_array[1])){
                     $first_name_array = explode(' ',$author_array[1]);
                     $lastname = $author_array[0];
                     $j = 0;
                     foreach($first_name_array as $fname){
                        $j++;
                        if (!empty($fname)){
                           $firstname .= strtoupper(substr($fname,0,1));
                           $firstname .= '.';
                           if (isset($first_name_array[$j])){
                              $firstname .= ' ';
                           }
                        }
                     }
                     $name = $lastname.', '.$firstname;
                     if (!empty($names)){
                        if (!isset($authors_array[$i])){
                           $names .= '& ';
                        }else{
                           $names .= ', ';
                        }
                     }
                     $names .= $name;
                  }
               }
               $biblio .= $names;

               if (!empty($pub_date)){
                  $biblio .= ' ('.$pub_date.').';
               }
               if (!empty($title)){
                  $biblio .= ' '.$title.'.';
               }
               $biblio .= ' In:';
               $names = '';
               $i = 0;
               if (!isset($editors_array[0])){
                  $names = ' ';
               }else{
                  foreach ($editors_array as $editor_string){
                     $i++;
                     $name = '';
                     $editor_array = explode(',',$editor_string);
                     $firstname = '';
                     if (isset($editor_array[1])){
                        $first_name_array = explode(' ',$editor_array[1]);
                        $lastname = $editor_array[0];
                        $j = 0;
                        foreach($first_name_array as $fname){
                           $j++;
                           if (!empty($fname)){
                              $firstname .= strtoupper(substr($fname,0,1));
                              $firstname .= '.';
                              if (isset($first_name_array[$j])){
                                 $firstname .= ' ';
                              }
                           }
                        }
                        $name = $lastname.', '.$firstname;
                        if (!empty($names)){
                           if (!isset($editors_array[$i])){
                              $names .= '& ';
                           }else{
                              $names .= ', ';
                           }
                        }
                        $names .= $name;
                     }
                  }
                  $biblio .= $names.'(Hrsg)';
               }

               if (!empty($book_title)){
                  $biblio .= ' _'.$book_title.'_.';
               }
               if (!empty($start_page)){
                  $biblio .= ', (S.'.$start_page;
                  if (!empty($end_page)){
                     $biblio .= ' - '.$end_page.'';
                  }
                  $biblio .= ')';
               }
               $biblio .= '.';
               if (!empty($jounal_location)){
                  $biblio .= ' '.$jounal_location;
               }
               if (!empty($jounal_publisher)){
                  $biblio .= ': '.$jounal_publisher.'.';
               }
            }else{
               if (!isset($authors_array[0])){
                  $authors_array = $contributors_array;
               }
               $names = '';
               foreach ($authors_array as $author_string){
                  $name = '';
                  $author_array = explode(',',$author_string);
                  $firstname = '';
                  if (isset($author_array[1])){
                     $first_name_array = explode(' ',$author_array[1]);
                     $lastname = strtoupper($author_array[0]);
                     $j = 0;
                     foreach($first_name_array as $fname){
                        $j++;
                        if (!empty($fname)){
                           $firstname .= strtoupper(substr($fname,0,1));
                           $firstname .= '.';
                           if (isset($first_name_array[$j])){
                              $firstname .= ' ';
                           }
                        }
                     }
                     $name = $lastname.', '.$firstname;
                     if (!empty($names)){
                        $names .= ', ';
                     }
                     $names .= $name;
                  }
               }
               $biblio .= $names;
               if (!empty($pub_date)){
                  $biblio .= ' '.$pub_date.'.';
               }
               if (!empty($title)){
                  $biblio .= ' '.$title.'.';
               }
               $biblio .= ' In:';
               $names = '';
               if (!isset($editors_array[0])){
                  $names = ' ';
               }else{
                   foreach ($editors_array as $editor_string){
                      $name = '';
                      $editor_array = explode(',',$editor_string);
                      $firstname = '';
                      if (isset($editor_array[1])){
                         $first_name_array = explode(' ',$editor_array[1]);
                         $lastname = strtoupper($editor_array[0]);
                         $j = 0;
                         foreach($first_name_array as $fname){
                            $j++;
                            if (!empty($fname)){
                               $firstname .= strtoupper(substr($fname,0,1));
                               $firstname .= '.';
                               if (isset($first_name_array[$j])){
                                  $firstname .= ' ';
                               }
                            }
                         }
                         $name = $lastname.', '.$firstname;
                         if (!empty($names)){
                            $names .= ', ';
                         }
                         $names .= $name;
                      }
                      $biblio .= $names.', Hrsg.';
                   }
               }

               if (!empty($book_title)){
                  $biblio .= ' _'.$book_title.'_.';
               }
               if (!empty($jounal_location)){
                  $biblio .= ' '.$jounal_location;
               }
               if (!empty($jounal_publisher)){
                  $biblio .= ': '.$jounal_publisher;
               }
               if (!empty($start_page)){
                  $biblio .= ', S.'.$start_page;
                  if (!empty($end_page)){
                     $biblio .= ' - '.$end_page.'';
                  }
               }
               $biblio .= '.';
            }

         }else{
             //FAMILY NAME, INITIAL(S). Year. Title of article. Journal title. Volume (issue number), page numbers.
            if ($citation_style == 'apa'){
               if (!isset($authors_array[0])){
                  $with_creator = false;
                  $authors_array = $contributors_array;
               }
               $names = '';
               $i = 0;
               foreach ($authors_array as $author_string){
                  $i++;
                  $name = '';
                  $author_array = explode(',',$author_string);
                  $firstname = '';
                  if (isset($author_array[1])){
                     $first_name_array = explode(' ',$author_array[1]);
                     $lastname = $author_array[0];
                     $j = 0;
                     foreach($first_name_array as $fname){
                        $j++;
                        if (!empty($fname)){
                           $firstname .= strtoupper(substr($fname,0,1));
                           $firstname .= '.';
                           if (isset($first_name_array[$j])){
                              $firstname .= ' ';
                           }
                        }
                     }
                     $name = $lastname.', '.$firstname;
                     if (!empty($names)){
                        if (!isset($authors_array[$i])){
                           $names .= '& ';
                        }else{
                           $names .= ', ';
                        }
                     }
                     $names .= $name;
                  }
               }
               $biblio .= $names;

               if (!empty($pub_date)){
                  $biblio .= ' ('.$pub_date.').';
               }
               if (!empty($title)){
                  $biblio .= ' '.$title.'.';
               }
               if (!empty($journal_title)){
                  $biblio .= ' _'.$journal_title.'_.';
               }
               if (!empty($volume_number)){
                  $biblio .= ' '.$volume_number;
               }
               if (!empty($issue_number)){
                  $biblio .= ' ('.$issue_number.')';
               }
               if (!empty($start_page)){
                  $biblio .= ', '.$start_page;
                  if (!empty($end_page)){
                     $biblio .= ' - '.$end_page.'';
                  }
               }
               $biblio .= '.';
            }else{
               if (!isset($authors_array[0])){
                  $authors_array = $contributors_array;
                  $with_creator = false;
               }
               $names = '';
               foreach ($authors_array as $author_string){
                  $name = '';
                  $author_array = explode(',',$author_string);
                  $firstname = '';
                  if (isset($author_array[1])){
                     $first_name_array = explode(' ',$author_array[1]);
                     $lastname = strtoupper($author_array[0]);
                     $j = 0;
                     foreach($first_name_array as $fname){
                        $j++;
                        if (!empty($fname)){
                           $firstname .= strtoupper(substr($fname,0,1));
                           $firstname .= '.';
                           if (isset($first_name_array[$j])){
                              $firstname .= ' ';
                           }
                        }
                     }
                     $name = $lastname.', '.$firstname;
                     if (!empty($names)){
                        $names .= ', ';
                     }
                     $names .= $name;
                  }
               }
               $biblio .= $names;
               if (!empty($pub_date)){
                  $biblio .= ' '.$pub_date.'.';
               }
               if (!empty($title)){
                  $biblio .= ' '.$title.'.';
               }
               if (!empty($journal_title)){
                  $biblio .= ' _'.$journal_title.'_.';
               }
               if (!empty($volume_number)){
                  $biblio .= ' '.$volume_number;
               }
               if (!empty($issue_number)){
                  $biblio .= ' ('.$issue_number.')';
               }
               if (!empty($start_page)){
                  $biblio .= ', S.'.$start_page;
                  if (!empty($end_page)){
                     $biblio .= ' - '.$end_page.'';
                  }
               }
               $biblio .= '.';
            }

         }
         break;




      default:
      //Harvard-Style:  FAMILY NAME, INITIAL(S). ed. Year. Title. City of publication: Publisher
      if ($citation_style == 'apa'){
         if (!isset($authors_array[0])){
            $authors_array = $contributors_array;
         }
         $names = '';
         $i = 0;
         foreach ($authors_array as $author_string){
            $i++;
            $name = '';
            $author_array = explode(',',$author_string);
            $firstname = '';
            if (isset($author_array[1])){
               $first_name_array = explode(' ',$author_array[1]);
               $lastname = $author_array[0];
               foreach($first_name_array as $fname){
                  if (!empty($fname)){
                     $firstname .= substr($fname,0,1);
                     $firstname .= '. ';
                  }
               }
               $name = $lastname.', '.$firstname;
               if (!empty($names)){
                  if (!isset($authors_array[$i])){
                     $names .= '& ';
                  }else{
                     $names .= ', ';
                  }
               }
               $names .= $name;
            }
         }
         $biblio .= $names;
         if (!empty($pub_date)){
            $biblio .= ' ('.$pub_date.').';
         }
         if (!empty($title)){
            $biblio .= ' _'.$title.'_.';
         }
         if (!empty($location)){
            $biblio .= ' '.$location;
         }
         if (!empty($publisher)){
            $biblio .= ': '.$publisher.'.';
         }

      }else{
         if (!isset($authors_array[0])){
            $authors_array = $contributors_array;
         }
         $names = '';
         foreach ($authors_array as $author_string){
            $name = '';
            $author_array = explode(',',$author_string);
            $firstname = '';
            if (isset($author_array[1])){
               $first_name_array = explode(' ',$author_array[1]);
               $lastname = strtoupper($author_array[0]);
               foreach($first_name_array as $fname){
                  if (!empty($fname)){
                     $firstname .= strtoupper(substr($fname,0,1));
                     $firstname .= '. ';
                  }
               }
               $name = $lastname.', '.$firstname;
               if (!empty($names)){
                  $names .= ', ';
               }
               $names .= $name;
            }
         }
         $biblio .= $names;
         if (!empty($pub_date)){
            $biblio .= ' '.$pub_date.'.';
         }
         if (!empty($title)){
            $biblio .= ' _'.$title.'_.';
         }
         if (!empty($location)){
            $biblio .= ' '.$location;
         }
         if (!empty($publisher)){
            $biblio .= ': '.$publisher.'.';
         }

      }
      break;
   }


   if ($is_electronic_publication){
//TBD
   }

   $material_item->setBibliographicValues($biblio);


   $file_man = $environment->getFileManager();
   $file_id_array = array();
   foreach ( $files as $file ) {
      $file_data = array();
      $file_name = basename($directory.$file);
      $file_data['tmp_name'] = $directory.$file;
      $file_data['name'] = $file_name;
      $file_data['file_id'] = $file_data['name'].'_'.getCurrentDateTimeInMySQL();
      $file_item = $file_man->getNewItem();
      $file_item->setPostFile($file_data);
      $file_item->save();
      $file_id_array[] = $file_item->getFileID();
   }
   $author = '';
   foreach ($authors_array as $author_string){
     if (!empty($author)){
        $author .='; ';
     }
     $author .= $author_string;
   }

   if (empty($title)){
      $title = getMessage('COMMON_NO_TITLE');
   }
   if (empty($author)){
      $author = getMessage('COMMON_NO_AUTHOR');
   }
/*   $abstract = '<table>';
   if (!empty($beluga_url)){
    $abstract .= '<tr><td class="ims_key">'.getMessage('BELUGA_LINK').': </td><td>'.$beluga_url.'</td></tr>';
   }
   $abstract .= '<tr><td class="ims_key">'.getMessage('BELUGA_AVAILABILITY').': </td><td>'.$availability.'</td></tr>';
   if ( !empty($table_of_content) ){
      $abstract .= '<tr><td class="ims_key">'.getMessage('COMMON_TABLE_OF_CONTENT').': </td><td>'.$table_of_content.'</td></tr>';
   }
   $abstract .= '</table>';
   if (!empty($file_id_array)){
      $material_item->setFileIDArray($file_id_array);
   }*/
   if (!empty($beluga_url)){
      $material_item->setBibURL($beluga_url);
   }
   if (!empty($availability)){
      $material_item->setBibAvailibility($availability);
   }
   if (!empty($table_of_content)){
      $material_item->setBibTOC($table_of_content);
   }
   $material_item->setTitle($title);
   $material_item->setAuthor($author);
   $material_item->setPublishingDate($pub_date);
   $material_item->setModificatorItem($user);
   $material_item->setDescription($abstract);
   return $material_item;
}


function _getMaterialListByXML($directory){
   global $environment;
   $xml_file_array = array();
   $xsl_file_array = array();
   $xml_directory = $directory.'/data/';
   if (is_dir($xml_directory)) {
      if ($dh = opendir($xml_directory)) {
         while (($file = readdir($dh)) !== false) {
            if ( strstr($file,'.xml') and $file != 'imsmanifest.xml' and $file != 'meta.xml'){
               $xml_file_array[] = $file;
            }
         }
         closedir($dh);
      }
    }
    $xsl_directory = $directory.'/styles/';
    if (is_dir($xsl_directory)) {
      if ($dh = opendir($xsl_directory)) {
         while (($file = readdir($dh)) !== false) {
          if ( strstr($file,'.xsl') ){
               $xsl_file_array[] = $file;
            }
         }
         closedir($dh);
      }
   }
   foreach($xml_file_array as $file){
      $tags = array();
      $values = array();
      $data = implode("", file($xml_directory.$file));
      $parser = xml_parser_create();
      xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
      xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
      xml_parse_into_struct($parser, $data, $values, $tags);
      $material_manager = $environment->getMaterialManager();
      $material_item = $material_manager->getNewItem();
      xml_parser_free($parser);
      $proc = new XSLTProcessor;
      $xml = new DOMDocument;

      $xml->loadXML($data);

      $xsl_filename = '';
      foreach($xsl_file_array as $xsl_file){
         if(strstr($data,$xsl_file)){
           $xsl_filename = $xsl_file;
        }
      }
//Datensätze über XSLT verarbeiten!!!
/*       if (!empty($xsl_filename)){
         $xsl = new DOMDocument;
         $xsl->load(utf8_encode($xsl_directory.$xsl_filename));
         $proc->importStyleSheet($xsl);
         $xml_doc = $proc->transformToXML($xml);
         $material_item->setBibliographicValues(utf8_decode($xml_doc));
         $material_item = _getMaterialByXMLArray($material_item,$values,$xml_directory);
         $material_item->save();
         unset($material_item);
      }
*/
    $citation_style = 'harvard';
    if (strstr($xsl_filename,'apa')){
       $citation_style = 'apa';
    }
    $material_item = _getMaterialByXMLArray($material_item,$values,$xml_directory,$citation_style);
    $material_item->save();
    unset($material_item);


   }
}


function getMaterialListByIMSZip($filename,$file_tmp_name, $target_directory,$environment){
   $has_manifest = false;
   $zip = new ZipArchive;
  $res = $zip->open($file_tmp_name);
   if ( $res === TRUE ) {
      if( $zip->extractTo($target_directory,'imsmanifest.xml') ) {
        $has_manifest = true;
         $indexfile = "imsmanifest.xml";
         unlink($target_directory.'/imsmanifest.xml');
      }
      if($has_manifest) {
         $filename = str_replace('.zip','',strtolower($filename));
         $zip->extractTo($target_directory.$filename);
         _getMaterialListByXML($target_directory.$filename);
      }
      _full_rmdir($target_directory);
      $zip->close();
   }
   unset($zip);
}

function _full_rmdir($dirname) {
   if ( $dirHandle = opendir($dirname) ) {
      $old_cwd = getcwd();
      chdir($dirname);
      while ($file = readdir($dirHandle)){
         if ($file == '.' || $file == '..') continue;
         if ( is_dir($file) ) {
            if ( !_full_rmdir($file) ) {
               chdir($old_cwd);
               return false;
            }
         } else {
            if ( !@unlink($file) ) {
               chdir($old_cwd);
               return false;
            }
         }
      }

      closedir($dirHandle);
      chdir($old_cwd);
      if (!rmdir($dirname)) return false;
      return true;
   } else {
      return false;
   }
}



?>