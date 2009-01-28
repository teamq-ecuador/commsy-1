<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez
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

$this->includeClass(RUBRIC_FORM);

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_step_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

   var $_did = NULL; // ID of the todo this article belongs to
   var $_ref_position = '1'; // Position of the answered step
   var $_ref_did = NULL; // ID of the article this article answers

  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    *
    * @author CommSy Development Group
    */
   function cs_step_form($params) {
      $this->cs_rubric_form($params);
   }

   /** set materials from session
    * set an array with the materials from the session
    *
    * @param array array of materials out of session
    *
    * @author CommSy Development Group
    */
   function setSessionMaterialArray ($value) {
      $this->_session_material_array = (array)$value;
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    *
    * @author CommSy Development Group
    */
   function _initForm () {

      // headline
      if (!empty($this->_item)) {
         $this->_headline = getMessage('STEP_EDIT');
      } elseif (!empty($this->_form_post)) {
         if (!empty($this->_form_post['iid'])) {
            $this->_headline = getMessage('STEP_EDIT');
         } else {
            $this->_headline = getMessage('STEP_ENTER_NEW');
         }
      } else {
         $this->_headline = getMessage('STEP_ENTER_NEW');
      }

      // files
      $file_array = array();
      if (!empty($this->_session_file_array)) {
         foreach ( $this->_session_file_array as $file ) {
            $temp_array['text'] = $file['name'];
            $temp_array['value'] = $file['file_id'];
            $file_array[] = $temp_array;
         }
      } elseif (isset($this->_item)) {
         $file_list = $this->_item->getFileList();
         if ($file_list->getCount() > 0) {
            $file_item = $file_list->getFirst();
            while ($file_item) {
               $temp_array['text'] = $file_item->getDisplayname();
               $temp_array['value'] = $file_item->getFileID();
               $file_array[] = $temp_array;
               $file_item = $file_list->getNext();
            }
         }
      }
      $this->_file_array = $file_array;
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {

      // todo
      $this->_form->addHidden('iid','');
      $this->_form->addHidden('todo_id','');
      $this->_form->addHidden('ref_position','');
      $this->_form->addTitleField('subject','',getMessage('COMMON_TITLE'),getMessage('COMMON_TITLE_DESC'),200,45,true);
      $this->_form->addTextField('minutes','',getMessage('STEP_MINUTES'),getMessage('STEP_MINUTES_DESC'),200,4,false);
      $time_type = array();
      $time_type[] = array('text'  => getMessage('TODO_TIME_MINUTES'),
                           'value' => '1');
      $time_type[] = array('text'  => getMessage('TODO_TIME_HOURS'),
                           'value' => '2');
      $time_type[] = array('text'  => getMessage('TODO_TIME_DAYS'),
                           'value' => '3');
      $this->_form->combine('horizontal');
      $this->_form->addSelect('time_type',$time_type,'',getMessage('TODO_TIME_TYPE'),'', 1, false,false,false,'','','','',12,true);
      $format_help_link = ahref_curl($this->_environment->getCurrentContextID(), 'help', 'context',
                  array('module'=>$this->_environment->getCurrentModule(),'function'=>$this->_environment->getCurrentFunction(),'context'=>'HELP_COMMON_FORMAT'),
                  getMessage('HELP_COMMON_FORMAT_TITLE'), '', '_help', '', '',
                  'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"');
      $this->_form->addTextArea('description','',getMessage('DISCUSSION_ARTICLE'),getMessage('COMMON_CONTENT_DESC',$format_help_link),59);

      // rubric connections
      $this->_setFormElementsForConnectedRubrics();

      // files
      $this->_form->addAnchor('fileupload');
      $val = ini_get('upload_max_filesize');
      $val = trim($val);
      $last = $val[strlen($val)-1];
      switch($last) {
         case 'k':
         case 'K':
            $val = $val * 1024;
            break;
         case 'm':
         case 'M':
            $val = $val * 1048576;
            break;
      }
      $meg_val = round($val/1048576);
      if ( !empty($this->_file_array) ) {
         $this->_form->addCheckBoxGroup('filelist',$this->_file_array,'',getMessage('MATERIAL_FILES'),getMessage('MATERIAL_FILES_DESC', $meg_val),false,false);
         $this->_form->combine('vertical');
      }
      $this->_form->addHidden('MAX_FILE_SIZE', $val);
      $this->_form->addFilefield('upload', getMessage('MATERIAL_FILES'), getMessage('MATERIAL_UPLOAD_DESC',$meg_val), 12, false, getMessage('MATERIAL_UPLOADFILE_BUTTON'),'option',$this->_with_multi_upload);
      $this->_form->combine('vertical');
      if ($this->_with_multi_upload) {
         // do nothing
      } else {
         #$px = '245';
         $px = '331';
         $browser = $this->_environment->getCurrentBrowser();
         if ($browser == 'MSIE') {
            $px = '351';
         } elseif ($browser == 'OPERA') {
            $px = '321';
         } elseif ($browser == 'KONQUEROR') {
            $px = '361';
         } elseif ($browser == 'SAFARI') {
            $px = '380';
         } elseif ($browser == 'FIREFOX') {
            $operation_system = $this->_environment->getCurrentOperatingSystem();
            if (strtoupper($operation_system) == 'LINUX') {
               $px = '360';
            } elseif (strtoupper($operation_system) == 'MAC OS') {
               $px = '352';
            }
         } elseif ($browser == 'MOZILLA') {
            $operation_system = $this->_environment->getCurrentOperatingSystem();
            if (strtoupper($operation_system) == 'MAC OS') {
               $px = '336'; // camino
            }
         }
         $this->_form->addButton('option',getMessage('MATERIAL_BUTTON_MULTI_UPLOAD_YES'),'','',$px.'px');
      }
      $this->_form->combine('vertical');
      $this->_form->addText('max_size',$val,getMessage('MATERIAL_MAX_FILE_SIZE',$meg_val));

      // buttons
      $id = 0;
      if (isset($this->_item)) {
         $id = $this->_item->getItemID();
      } elseif (isset($this->_form_post)) {
         if (isset($this->_form_post['iid'])) {
            $id = $this->_form_post['iid'];
         }
      }
      if ( $id == 0 )  {
         $this->_form->addButtonBar('option',getMessage('STEP_SAVE_BUTTON'),getMessage('COMMON_CANCEL_BUTTON'));
      } else {
         $this->_form->addButtonBar('option',getMessage('STEP_CHANGE_BUTTON'),getMessage('COMMON_CANCEL_BUTTON'),'','','');
      }
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    *
    * @author CommSy Development Group
    */
   function _prepareValues () {
      $this->_values = array();

      if ( !empty($this->_form_post) ) {
         $this->_values = $this->_form_post; // no encode here - encode in form-views
      } elseif ( isset($this->_item) ) {
         $this->_values['iid'] = $this->_item->getItemID();
         $this->_values['todo_id'] = $this->_item->getTodoID();
         $this->_values['ref_position'] = $this->_ref_position;
         $this->_values['subject'] = $this->_item->getTitle();
         $this->_values['description'] = $this->_item->getDescription();
         $minutes = $this->_item->getMinutes();
         switch ($this->_item->getTimeType()){
            case 2: $minutes = $minutes/60;break;
            case 3: $minutes = ($minutes/60)/8;break;
         }
         $this->_values['minutes'] = $minutes;
         $this->_values['time_type'] = $this->_item->getTimeType();
         $this->_setValuesForRubricConnections();

         // file
         $file_array = array();
         $file_list = $this->_item->getFileList();
         if ($file_list->getCount() > 0) {
            $file_item = $file_list->getFirst();
            while ($file_item) {
               $file_array[] = $file_item->getFileID();
               $file_item = $file_list->getNext();
            }
         }
         if (isset($this->_form_post['filelist'])) {
            $this->_values['filelist'] = $this->_form_post['filelist'];
         } else {
            $this->_values['filelist'] = $file_array;
         }

      } elseif ( isset($this->_did) ) {
         $this->_values['todo_id'] = $this->_did;
         if (isset($this->_ref_did)){
            $step_manager = $this->_environment->getTodoArticlesManager();
            $step_item = $step_manager->getItem($this->_ref_did);
         }
         $this->_values['ref_position'] = $this->_ref_position;
      }
   }

   function _checkValues () {
      if ( isset($this->_form_post['minutes']) and !is_numeric($this->_form_post['minutes']) ){
         $this->_form->setFailure('minutes','mandatory');
         $this->_error_array[] = getMessage('COMMON_ERROR_MINUTES_INT',getMessage('COMMON_ERROR_MINUTES_INT'));
      }
   }


   function setTodoID($did) {
      $this->_did = $did;
   }
   function setRefPosition($did) {
      $this->_ref_position = $did;
   }
   function setRefDid($did) {
      $this->_ref_did = $did;
   }
}
?>
