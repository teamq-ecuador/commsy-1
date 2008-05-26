<?PHP
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

/** cs_list is needed for storage of the commsy items
 */
include_once('classes/cs_list.php');


/** class for database connection to the database table "links"
 * this class implements a database manager for the table "links". Links between commsy items
 */
class cs_link_manager extends cs_manager {

  /**
   * integer - containing the error number if an error occured
   */
  var $_dberrno;

  /**
   * string - containing the error text if an error occured
   */
  var $_dberror;

  /**
   * integer - containing the item id, if an item was created
   */
  var $_create_id;

  /**
   * array - containing the data from the database -> cache data
   */
  var $_data = array();

  var $_cache = array();

  /**
   * string - containing the order limit for the select statement
   */
  var $_order;

  var $_discussion_type_limit;
  /**
   * limits for selecting link items
   */
  var $_linked_item = NULL;
  var $_second_linked_item = NULL;
  var $_link_type_limit = NULL;
  var $_link_type_array_limit = NULL;

  var $_entry_limit = NULL;
  var $_sorting_place_limit = NULL;

  /** constructor: cs_links_manager
    * the only available constructor, initial values for internal variables
    *
    * @param object cs_environment the environment
    */
  function cs_link_manager ($environment) {
      $this->cs_manager($environment);
      $this->_db_table = 'link_items';
  }


  /******************* reset methods ************/


  /** reset limits
    * reset limits of this class: context limit, delete limit
    */
   function resetLimits () {
      $this->_order = NULL;
      $this->_room_limit =NULL;
      $this->_linked_item = NULL;
      $this->_second_linked_item = NULL;
      #$this->_version_id_limit = NULL;
      $this->_link_type_limit = NULL;
      $this->_link_type_array_limit = array();
      $this->_sorting_place_limit = NULL;
      $this->_entry_limit = NULL;
      $this->_discussion_type_limit = NULL;
   }

  /** reset data
    * reset data of this class
    */
   function resetData () {
      $this->_data = array();
   }

  /** reset cache
    * reset cache of this class
    */
   private function _resetCache () {
      $this->_cache = array();
   }

   function setEntryLimit($count){
      $this->_entry_limit = $count;
   }

   /** reset type_limit
    * reset type_limit of this class
    */
   function resetTypeLimit () {
      $this->_link_type_limit = NULL;
      $this->_link_type_array_limit = array();
   }

  /** reset order
    * reset order of this class
    */
   function resetOrder () {
      unset($this->_order);
   }


  /************** set methods ******************/

  /** sets the type limit
    *
    * @param string
    */
   function setTypeLimit ($type) {
      $this->_link_type_limit = $type;
   }

  /** sets the type limit
    *
    * @param string
    */
   function setTypeArrayLimit ($type) {
      $this->_link_type_array_limit = $type;
   }

  /** sets the rubric type limit
    */
   function setMaterialLimit(){
      $this->setTypeLimit(CS_MATERIAL_TYPE);
   }
   function setInstitutionLimit(){
      $this->setTypeLimit(CS_INSTITUTION_TYPE);
   }
   function setTopicLimit(){
      $this->setTypeLimit(CS_TOPIC_TYPE);
   }
   function setRoomLimit($limit){
      $this->_room_limit = $limit;
   }

   function sortbySortingPlace () {
      $this->_setOrderLimit('sorting_place');
   }
   function setSortingPlaceLimit() {
      $this->_sorting_place_limit = true;
   }

   function _setOrderLimit ($value) {
      $this->_order = $value;
   }

   /** set linked_item
    * this method sets a linked-item as a limit
    *
    * @param object of a linked-item
    */
   function setLinkedItemLimit ($object) {
      $this->_linked_item = $object;
   }

   function setDiscussionTypeLimit ($limit) {
      $this->_discussion_type_limit = $limit;
   }

   /** set linked_item
    * this method sets a linked-item as a limit
    *
    * @param object of a linked-item
    */
   function setSecondLinkedItemLimit ($object) {
      $this->_second_linked_item = $object;
   }

   /*********************************************/

   /** build a new links item
    * this method returns a new EMTPY user item
    *
    * @return object cs_item a new EMPTY user
    */
   function getNewItem () {
      include_once('classes/cs_link_item.php');
      return new cs_link_item($this->_environment);
   }

  /** get all links
    * this method get all links
    *
    * @param string  type       type of the link
    * @param string  mode       one of count, select, select_with_item_type_from
    */
   function _performQuery ($mode = 'select', $with_linked_items= true) {
      $data = array();
      if ($mode == 'count') {
         $query = 'SELECT count( DISTINCT link_items.item_id) AS count';
      } elseif ($mode == 'id_array') {
         $query = 'SELECT DISTINCT link_items.item_id';
      } else {
         $query = 'SELECT DISTINCT link_items.*';
      }
      $query .= ' FROM link_items ';

      if (isset($this->_discussion_type_limit)){
         $query .= ' LEFT JOIN discussions AS discussion1 ON (discussion1.item_id=link_items.first_item_id)'; // modificator_id (TBD)
         $query .= ' LEFT JOIN discussions AS discussion2 ON (discussion2.item_id=link_items.second_item_id)'; // modificator_id (TBD)
      }
      $query .= ' WHERE 1';

      if (isset($this->_discussion_type_limit)){
         $query .= ' AND (discussion1.discussion_type ="'.encode(AS_DB,$this->_discussion_type_limit).'" OR discussion2.discussion_type ="'.encode(AS_DB,$this->_discussion_type_limit).'")';
      }

      if ( isset($this->_linked_item) ) {
         $query .= ' AND ( (first_item_id ="'.encode(AS_DB,$this->_linked_item->getItemID()).'"';
      }
      if (isset($this->_second_linked_item) ) {
         $query .= ' AND second_item_id ="'.encode(AS_DB,$this->_second_linked_item->getItemID()).'"';
      }
      if (!empty($this->_link_type_limit) ) {
         $query .= ' AND second_item_type ="'.encode(AS_DB,$this->_link_type_limit).'"';
      } elseif (!empty($this->_link_type_array_limit) ) {
         $query .= ' AND (';
         $first = true;
         foreach ($this->_link_type_array_limit as $limit){
            if ($first){
               $first = false;
               $query .= ' second_item_type ="'.encode(AS_DB,$limit).'"';
            } else {
               $query .= ' OR second_item_type ="'.encode(AS_DB,$limit).'"';
            }
         }
         $query .= ')';
      }
      if ( isset($this->_linked_item) ) {
         $query .= ')';
         $query .= ' OR (second_item_id ="'.encode(AS_DB,$this->_linked_item->getItemID()).'"';
      }
      if (isset($this->_second_linked_item) ) {
         $query .= ' AND first_item_id ="'.encode(AS_DB,$this->_second_linked_item->getItemID()).'"';
      }
      if (!empty($this->_link_type_limit) ) {
         $query .= ' AND first_item_type ="'.encode(AS_DB,$this->_link_type_limit).'"';
      } if (!empty($this->_link_type_array_limit) ) {
         $query .= ' AND (';
         $first = true;
         foreach ($this->_link_type_array_limit as $limit){
            if ($first){
               $first = false;
               $query .= ' first_item_type ="'.encode(AS_DB,$limit).'"';
            }else{
               $query .= ' OR first_item_type ="'.encode(AS_DB,$limit).'"';
            }
         }
         $query .= ' )';
      }
      if ( isset($this->_linked_item) ) {
         $query .= '))';
      }
      $query .= ' AND link_items.deleter_id IS NULL AND link_items.deletion_date IS NULL';
      if (isset($this->_room_limit)) {
         $query .= ' AND link_items.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
      }
#       else {
#         $query .= ' AND link_items.context_id = "'.$this->_environment->getCurrentContextID().'"';
#      }

      if ( isset($this->_sorting_place_limit)
           and !empty($this->_sorting_place_limit)
           and $this->_sorting_place_limit
         ) {
         $query .= ' AND '.$this->_db_table.'.sorting_place IS NOT NULL';
      }

      // group to eliminate versions
      // there are no version_ids in this table ???????????
      if ( isset($this->_linked_item) ) {
         $query .= ' GROUP BY link_items.item_id';
      }
      // order
      if ( !empty($this->_order) ) {
         if ( $this->_order == 'sorting_place') {
            $query .= ' ORDER BY '.$this->_db_table.'.sorting_place ASC, '.$this->_db_table.'.creation_date DESC';
         }
      } else {
         $query .= ' ORDER BY link_items.creation_date DESC';
      }
      if (isset($this->_entry_limit)) {
         $query .= ' LIMIT 0, '.encode(AS_DB,$this->_entry_limit);
      }

      $cache_exists = false;
      if (!empty($this->_cache)){
         foreach ($this->_cache as $cache_query){
            if ($cache_query['query'] == $query){
               $cache_exists = true;
               $result = $cache_query['result'];
            }
         }
      }

      if (!$cache_exists){
         // perform query
         $r = $this->_db_connector->performQuery($query);
         if (!isset($r)) {
            include_once('functions/error_functions.php');
            trigger_error('Problems with links: "'.$this->_dberror.'" from query: "'.$query.'"',E_USER_WARNING);
         } else {
            $temp = array();
            $temp['query'] = $query;
            $temp['result'] = $r;
            $this->_cache[] = $temp;
            $result = $r;
         }
      }
      return $result;

   }

   /** select items limited by limits
   * this method returns a list (cs_list) of items within the database limited by the limits.
   * depends on _performQuery(), which must be overwritten
   */
   function select ($with_linked_items = true) {
      if ( isset($this->_linked_item) ) {
         $result = $this->_performQuery('select', $with_linked_items);
         $this->_data = new cs_list();
         $this->_id_array = NULL;
         if (!$with_linked_items){
            foreach ($result as $query_result) {
               $item = $this->_buildItem($query_result);
               $this->_data->add($item);
            }
         } else {
            $link_list = new cs_list();
            $item_id_array = array();
            foreach ($result as $query_result) {
               if ($this->_linked_item->getItemID() == $query_result['first_item_id']) {
                  $item_id_array[] = $query_result['second_item_id'];
               } else {
                  $item_id_array[] = $query_result['first_item_id'];
               }
               $item = $this->_buildItem($query_result);
               $link_list->add($item);
            }
            $this->_data = $link_list;
         }
        if ( isset($this->_order)
             and !empty($this->_order)
             and $this->_order == 'sorting_place'
           ) {
           $item = $this->_data->getFirst();
           $link_list1 = new cs_list();
           $link_list2 = new cs_list();
           while ($item) {
              if ($item->getSortingPlace()) {
                 $link_list1->add($item);
              } else {
                 $link_list2->add($item);
              }
              $item = $this->_data->getNext();
           }
           $link_list1->addList($link_list2);
           $this->_data = $link_list1;
           unset($link_list1);
           unset($link_list2);
         }
      } else {
         parent::select();
      }
   }

   function _selectForExport () {
      $result = $this->_performQuery('select',false);
      $this->_id_array = NULL;
      if ( isset($this->_output_limit)
           and !empty($this->_output_limit)
           and $this->_output_limit == 'XML'
         ) {
         $this->_data = '<'.$this->_db_table.'_list>'.LF;
      } else {
         $this->_data = new cs_list();
      }
      foreach ($result as $query_result) {
         if ( isset($this->_output_limit)
              and !empty($this->_output_limit)
              and $this->_output_limit == 'XML'
            ) {
            if ( isset($query_result)
                 and !empty($query_result) ) {
               $this->_data .= '<'.$this->_db_table.'_item>'.LF;
               foreach ($query_result as $key => $value) {
                  $value = str_replace('<','lt_commsy_export',$value);
                  $value = str_replace('>','gt_commsy_export',$value);
                  $value = str_replace('&','and_commsy_export',$value);
                  if ( $key == 'extras' ) {
                     $value = serialize($value);
                  }
                  $this->_data .= '<'.$key.'>'.$value.'</'.$key.'>'.LF;
               }
               $this->_data .= '</'.$this->_db_table.'_item>'.LF;
            }
         } else {
            $item = $this->_buildItem($query_result);
            $this->_data->add($item);
         }
         //$this->_id_array[] = $query_result['item_id'];
      }
      if ( isset($this->_output_limit)
           and !empty($this->_output_limit)
           and $this->_output_limit == 'XML'
         ) {
         $this->_data .= '</'.$this->_db_table.'_list>'.LF;
      }
   }

  /** update a link item - internal, do not use -> use method save
    * this method updates a link item
    *
    * @param object cs_item link_item
    */
  function _update ($link_item) {   // wird nicht ben�tigt???
     parent::_update($link_item);
     $first_item = $link_item->getFirstLinkedItem();
     $second_item = $link_item->getSecondLinkedItem();
     $modificator = $link_item->getModificatorItem();
     $query = 'UPDATE link_items SET '.
              'modification_date="'.getCurrentDateTimeInMySQL().'",'.
              'first_item_id="'.encode(AS_DB,$first_item->getItemID()).'",'.
              'second_item_id="'.encode(AS_DB,$second_item->getItemID()).'",'.
              'first_item_type="'.encode(AS_DB,$link_item->getFirstLinkedItemType()).'",'.
              'second_item_type="'.encode(AS_DB,$link_item->getSecondLinkedItemType()).'",'.
              ' WHERE item_id="'.encode(AS_DB,$link_item->getItemID()).'"';

     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems updating link item from query: "'.$query.'"',E_USER_WARNING);
     }
  }

  /** create a link item - internal, do not use -> use method save
    * this method creates a link item
    *
    * @param object cs_item link_item
    */
  function _create ($link_item) {
     $query = 'INSERT INTO items SET '.
              'context_id="'.encode(AS_DB,$link_item->getContextID()).'",'.
              'modification_date="'.getCurrentDateTimeInMySQL().'",'.
              'type="link_item"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems creating link item from query: "'.$query.'"',E_USER_WARNING);
        $this->_create_id = NULL;
     } else {
        $this->_create_id = $result;
        $link_item->setItemID($this->getCreateID());

        $creator = $link_item->getCreatorItem();
        $creator_id = $creator->getItemID();
        $current_datetime = getCurrentDateTimeInMySQL();
        $query  = 'INSERT INTO link_items SET '.
                  'item_id="'.encode(AS_DB,$link_item->getItemID()).'",';
        $query .= 'context_id="'.encode(AS_DB,$link_item->getContextID()).'",';
        $first_item = $link_item->getFirstLinkedItem();
        if ( isset($first_item) ) {
           $first_item_id = $first_item->getItemID();
           $first_item_type = $first_item->getItemType();
        } else {
           $first_item_id = $link_item->getFristLinkedItemID();
           $first_item_type = $link_item->getFirstLinkedItemID();
        }
        $second_item = $link_item->getSecondLinkedItem();
        if ( isset($second_item) ) {
           $second_item_id = $second_item->getItemID();
           $second_item_type = $second_item->getItemType();
        } else {
           $second_item_id = $link_item->getSecondLinkedItemID();
           $second_item_type = $link_item->getSecondLinkedItemID();
        }
        $query .= 'creator_id="'.encode(AS_DB,$creator_id).'",'.
                  'creation_date="'.$current_datetime.'",'.
                  'modification_date="'.$current_datetime.'",'.
                  'first_item_id="'.encode(AS_DB,$first_item_id).'",'.
                  'second_item_id="'.encode(AS_DB,$second_item_id).'",'.
                  'first_item_type="'.encode(AS_DB,$first_item_type).'",'.
                  'second_item_type="'.encode(AS_DB,$second_item_type).'"';
        $result = $this->_db_connector->performQuery($query);
        if ( !isset($result) ) {
           include_once('functions/error_functions.php');
           trigger_error('Problems creating link item from query: "'.$query.'"',E_USER_WARNING);
           $query = 'DELETE FROM items WHERE item_id="'.$this->getCreateID().'"';
           $result = $this->_db_connector->performQuery($query);
           $this->_create_id = NULL;
        }
        unset($creator);
        unset($first_item);
        unset($second_item);
     }
  }

  /** delete a link_item
    *
    * @param integer item_id the link_item
    */
  function delete ($item_id) {
     $current_datetime = getCurrentDateTimeInMySQL();
     $current_user = $this->_environment->getCurrentUserItem();
     $user_id = $current_user->getItemID();
     $query = 'UPDATE link_items SET '.
              'deletion_date="'.$current_datetime.'",'.
              'deleter_id="'.encode(AS_DB,$user_id).'"'.
              ' WHERE item_id="'.encode(AS_DB,$item_id).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems deleting link_items from query: "'.$query.'"',E_USER_WARNING);
     } else {
        // delete item from table 'items'
        parent::delete($item_id);
     }

     // reset cache
     $this->_resetCache();
  }

  function deleteAllLinkItemsInCommunityRoom($item_id,$context_id){
     $query = 'UPDATE link_items SET '.
              'deletion_date="'.getCurrentDateTimeInMySQL().'",'.
              'deleter_id="'.encode(AS_DB,$this->_current_user->getItemID()).'"'.
              ' WHERE (first_item_id="'.encode(AS_DB,$item_id).'"';
     $query .= ' OR second_item_id="'.encode(AS_DB,$item_id).'"';
     $query .= ')';
     $query .= ' AND context_id ="'.encode(AS_DB,$context_id).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems deleting (updating) links of an item from query: "'.$query.'"',E_USER_WARNING);
     }

     // reset cache
     $this->_resetCache();
  }

   function getCountExistingLinkItemsOfUser($user_id){
     $query = 'SELECT count(link_items.item_id) AS count';
     $query .= ' FROM link_items';
     $query .= ' WHERE 1=1';

     if (isset($this->_room_limit)) {
        $query .= ' AND link_items.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
     } else {
        $query .= ' AND link_items.context_id = "'.encode(AS_DB,$this->_environment->getCurrentContextID()).'"';
     }
     $query .= ' AND NOT(link_items.first_item_type="user" AND link_items.second_item_type="group")';
     $query .= ' AND link_items.deleter_id IS NULL';
     $query .= ' AND link_items.creator_id ="'.encode(AS_DB,$user_id).'"';

     // perform query
     $result = $this->_db_connector->performQuery($query);
     if (!isset($result) or empty($result[0]['count'])) {
         include_once('functions/error_functions.php');
         trigger_error('Problems selecting items from query: "'.$query.'"',E_USER_WARNING);
     } else {
         return $result[0]['count'];
     }
   }

  /** delete link , but it is just an update
    * this method deletes all links from an item, but only as an update to restore it later and for evaluation
    *
    * @param integer item_id       id of the item
    * @param integer version_id    version id of the item
    */
  function deleteLinksBecauseItemIsDeleted ($item_id, $version_id=NULL) {
     $query = 'UPDATE link_items SET '.
              'deletion_date="'.getCurrentDateTimeInMySQL().'",'.
              'deleter_id="'.encode(AS_DB,$this->_current_user->getItemID()).'"'.
              ' WHERE (first_item_id="'.encode(AS_DB,$item_id).'"';
     $query .= ') OR (second_item_id="'.encode(AS_DB,$item_id).'"';
     $query .= ')';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems deleting (updating) links of an item from query: "'.$query.'"',E_USER_WARNING);
     }

     // reset cache
     $this->_resetCache();
  }

  function undeleteLinks ($item) {
     $query = 'UPDATE link_items SET '.
              'modification_date="'.getCurrentDateTimeInMySQL().'",'.
              'deletion_date=NULL,'.
              'deleter_id=NULL'.
              ' WHERE deletion_date>="'.encode(AS_DB,$item->getDeletionDate()).'"'.
              ' AND (first_item_id="'.encode(AS_DB,$item->getItemID()).'"'.
              ' OR second_item_id="'.encode(AS_DB,$item->getItemID()).'")';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems deleting (updating) links of an item from query: "'.$query.'"',E_USER_WARNING);
     }

     // reset cache
     $this->_resetCache();
  }

  function getModiefiedItemIDArray($type, $creator_id){
     $query ='';
     switch ( $type ) {
        case CS_MATERIAL_TYPE:
           $query ='SELECT DISTINCT materials.item_id FROM materials WHERE materials.creator_id ="'.encode(AS_DB,$creator_id).'" AND materials.deleter_id IS NULL AND materials.deletion_date IS NULL ORDER BY materials.modification_date DESC, materials.title ASC';
           break;
        case CS_PROJECT_TYPE:
           $query ='SELECT DISTINCT room.item_id FROM room WHERE room.creator_id ="'.encode(AS_DB,$creator_id).'" AND room.deleter_id IS NULL AND room.deletion_date IS NULL AND room.type="project" ORDER BY room.modification_date DESC, room.title ASC';
           break;
        case CS_ANNOUNCEMENT_TYPE:
           $query ='SELECT DISTINCT announcement.item_id FROM announcement WHERE announcement.creator_id ="'.encode(AS_DB,$creator_id).'" AND announcement.deleter_id IS NULL  AND announcement.deletion_date IS NULL ORDER BY announcement.modification_date DESC';
           break;
        case CS_DISCUSSION_TYPE:
           if (isset($this->_discussion_type_limit)){
              $query =  ' SELECT DISTINCT discussions.item_id FROM discussions';
              $query .= ' WHERE 1';
              $query .= ' AND discussions.creator_id ="'.encode(AS_DB,$creator_id).'" AND discussions.deleter_id IS NULL AND discussions.deletion_date IS NULL';
              $query .= ' AND discussions.discussion_type ="'.encode(AS_DB,$this->_discussion_type_limit).'"';
              $query .= ' ORDER BY discussions.modification_date DESC, discussions.title DESC';
           }else{
              $query ='SELECT DISTINCT discussions.item_id FROM discussions WHERE discussions.creator_id ="'.encode(AS_DB,$creator_id).'" AND discussions.deleter_id IS NULL AND discussions.deletion_date IS NULL ORDER BY discussions.modification_date DESC, discussions.title DESC';
           }
           break;
        case CS_TODO_TYPE:
           $query ='SELECT DISTINCT todos.item_id FROM todos WHERE todos.creator_id ="'.encode(AS_DB,$creator_id).'" AND todos.deleter_id IS NULL AND todos.deletion_date IS NULL ORDER BY todos.modification_date DESC';
           break;
        case CS_DATE_TYPE:
           $query ='SELECT DISTINCT dates.item_id FROM dates WHERE dates.creator_id ="'.encode(AS_DB,$creator_id).'" AND dates.deleter_id IS NULL AND dates.deletion_date IS NULL ORDER BY dates.datetime_start ASC';
           break;
     }
     // perform query

      $result = $this->_db_connector->performQuery($query);
      $id_array = array();
      if ( isset($result) ) {
         foreach ($result as $query_result) {
            $id_array[] = $query_result['item_id'];
         }
      }
      if ($type =='CS_DISCUSSION_TYPE'){
         $query ='SELECT DISTINCT discussionarticless.item_id FROM discussionarticles WHERE discussionarticles.creator_id ="'.encode(AS_DB,$creator_id).'" OR discussionarticles.deleter_id IS NULL  ORDER BY discussionarticle.modification_date DESC, discussionarticle.subject DESC';
         $result = $this->_db_connector->performQuery($query);
         $id_array = array();
         if ( isset($result) ) {
            foreach ($result as $query_result) {
               $id_array[] = $query_result['item_id'];
            }
         }
      }

      if ( !isset($id_array[0]) ) {
         return array();
      } else {
         return $id_array;
      }
  }

  function moveRoom ($roomMover) {
     $query = "UPDATE link_items SET ";
     $query .= " WHERE context_id = '".encode(AS_DB,$roomMover->getRoomId())."'";

     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');trigger_error('Problems creating links from query: "'.$query.'"',E_USER_WARNING);
     }
  }

   function mergeAccounts ($new_id, $old_id) {
     parent::mergeAccounts($new_id,$old_id);

     $query = 'SELECT * FROM link_items WHERE creator_id = "'.encode(AS_DB,$new_id).'" AND first_item_id ="'.encode(AS_DB,$old_id).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( isset($result) ) {
        foreach ( $result as $row ) {
           $update = "UPDATE link_items SET ";
           $update.= " first_item_id = ".encode(AS_DB,$new_id);
           $update.= " WHERE item_id = ".$row['item_id'];

           $result2 = $this->_db_connector->performQuery($update);
           if ( !isset($result2) or !$result2 ) {
              include_once('functions/error_functions.php');trigger_error('Problems creating link_items: "'.$this->_dberror.'" from query: "'.$query.'"',E_USER_WARNING);
           }
        }
     }

     $query = 'SELECT * FROM link_items WHERE creator_id = "'.encode(AS_DB,$new_id).'" AND second_item_id ="'.encode(AS_DB,$old_id).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( isset($result) ) {
        foreach ( $result as $row ) {
           $update = "UPDATE link_items SET ";
           $update.= " second_item_id = ".encode(AS_DB,$new_id);
           $update.= " WHERE item_id = ".$row['item_id'];

           $result2 = $this->_db_connector->performQuery($update);
           if ( !isset($result2) or !$result2 ) {
              include_once('functions/error_functions.php');
              trigger_error('Problems creating link_items from query: "'.$query.'"',E_USER_WARNING);
           }
        }
     }
   }

   function cleanSortingPlaces ($linked_item) {
      $item_id = $linked_item->getItemID();
      $query = 'UPDATE '.$this->_db_table.' SET sorting_place=NULL WHERE first_item_id="'.encode(AS_DB,$item_id).'" OR second_item_id="'.encode(AS_DB,$item_id).'";';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems cleaning sorting place at table '.$this->_db_table.' from query: "'.$query.'"',E_USER_WARNING);
      }
   }

   function saveSortingPlaces ($value_array) {
      if ( isset($value_array)
           and !empty($value_array)
           and is_array($value_array)
         ) {
         foreach ($value_array as $value) {
            $item_id = $value['item_id'];
            $place = $value['place'];

            $query = 'UPDATE '.$this->_db_table.' SET sorting_place="'.encode(AS_DB,$place).'" WHERE item_id="'.encode(AS_DB,$item_id).'";';
            $result = $this->_db_connector->performQuery($query);
            if ( !isset($result) or !$result ) {
               include_once('functions/error_functions.php');
               trigger_error('Problems saveing sorting place at table '.$this->_db_table.' from query: "'.$query.'"',E_USER_WARNING);
            }
         }
      }
   }

   public function saveLinkItemsMaterialToItem ($new_array,$item) {
      $type = CS_MATERIAL_TYPE;
      $this->setTypeLimit($type);
      $this->setLinkedItemLimit($item);
      $this->select(false);
      $result_list = $this->get();
      $insert_array = array();
      $nothing_array = array();
      $delete_array = array();
      if ( $result_list->isNotEmpty() ) {
         $link_item = $result_list->getFirst();
         while ($link_item) {
            if ( $link_item->getFirstLinkedItemType() == $type
                 and !in_array($link_item->getFirstLinkedItemID(),$new_array)
               ) {
               $delete_array[] = $link_item->getItemID();
            } elseif ( $link_item->getSecondLinkedItemType() == $type
                 and !in_array($link_item->getSecondeLinkedItemID(),$new_array)
               ) {
               $delete_array[] = $link_item->getItemID();
            } else {
               if ( $link_item->getFirstLinkedItemType() == $type ) {
                  $nothing_array[] = $link_item->getFirstLinkedItemID();
               } else {
                  $nothing_array[] = $link_item->getSecondLinkedItemID();
               }
            }
            $link_item = $result_list->getNext();
         }
      }
      unset($result_list);
      $insert_array = array_diff($new_array,$nothing_array);
      foreach ( $delete_array as $item_id ) {
         $this->delete($item_id);
      }
      foreach ($insert_array as $item_id) {
         $new_link_item = $this->getNewItem();
         $new_link_item->setFirstLinkedItemID($item_id);
         $new_link_item->setFirstLinkedItemType($type);
         $new_link_item->setSecondLinkedItemID($item->getItemID());
         $new_link_item->setSecondLinkedItemType($item->getType());
         $new_link_item->setContextID($this->_environment->getCurrentContextID());
         $new_link_item->setCreatorItem($this->_environment->getCurrentUserItem());
         $new_link_item->save();
      }
   }

   public function saveLinkItemsRubricToItem ($new_array,$item,$rubric) {
      $type = $rubric;
      $this->setTypeLimit($type);
      $this->setLinkedItemLimit($item);
      $this->select(false);
      $result_list = $this->get();
      $insert_array = array();
      $nothing_array = array();
      $delete_array = array();
      if ( $result_list->isNotEmpty() ) {
         $link_item = $result_list->getFirst();
         while ($link_item) {
            if ( $link_item->getFirstLinkedItemType() == $type
                 and !in_array($link_item->getFirstLinkedItemID(),$new_array)
               ) {
               $delete_array[] = $link_item->getItemID();
            } elseif ( $link_item->getSecondLinkedItemType() == $type
                 and !in_array($link_item->getSecondeLinkedItemID(),$new_array)
               ) {
               $delete_array[] = $link_item->getItemID();
            } else {
               if ( $link_item->getFirstLinkedItemType() == $type ) {
                  $nothing_array[] = $link_item->getFirstLinkedItemID();
               } else {
                  $nothing_array[] = $link_item->getSecondLinkedItemID();
               }
            }
            $link_item = $result_list->getNext();
         }
      }
      unset($result_list);
      $insert_array = array_diff($new_array,$nothing_array);
      foreach ( $delete_array as $item_id ) {
         $this->delete($item_id);
      }
      foreach ($insert_array as $item_id) {
         $new_link_item = $this->getNewItem();
         $new_link_item->setFirstLinkedItemID($item_id);
         $new_link_item->setFirstLinkedItemType($type);
         $new_link_item->setSecondLinkedItemID($item->getItemID());
         $new_link_item->setSecondLinkedItemType($item->getType());
         $new_link_item->setContextID($this->_environment->getCurrentContextID());
         $new_link_item->setCreatorItem($this->_environment->getCurrentUserItem());
         $new_link_item->save();
      }
   }

  /** save a commsy item
    * this method saves a commsy item
    *
    * @param cs_item
    */
  function saveItem ($item) {
     $item_id = $item->getItemID();

     $modifier = $item->getModificatorItem();
     if ( !isset($modifier) ) {
        $user = $this->_environment->getCurrentUser();
        $item->setModificatorItem($user);
     } else {
        $modifier_id = $modifier->getItemID();
        if (empty($modifier_id)) {
           $user = $this->_environment->getCurrentUser();
           $item->setModificatorItem($user);
        }
     }

     if (!empty($item_id)) {
        $this->_update($item);
     } else {
        $creator_id = $item->getCreatorID();
        if (empty($creator_id)) {
           $user = $this->_environment->getCurrentUser();
           $item->setCreatorItem($user);
        }
        $this->_create($item);
     }

     //Add modifier to all users who ever edited this section
     if ( $this->_update_with_changing_modification_information ) {
        $link_modifier_item_manager = $this->_environment->getLinkModifierItemManager();
        $link_modifier_item_manager->markEdited($item->getItemID());
     }

     // reset cache
     $this->_resetCache();
  }
}
?>