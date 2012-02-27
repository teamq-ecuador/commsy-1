<?php
	require_once('classes/controller/cs_detail_controller.php');

	class cs_material_detail_controller extends cs_detail_controller {
		private $_sections = null;
		private $_version_list = null;

		/**
		 * constructor
		 */
		public function __construct(cs_environment $environment) {
			// call parent
			parent::__construct($environment);

			$this->_tpl_file = 'material_detail';
		}

		/*
		 * every derived class needs to implement an processTemplate function
		 */
		public function processTemplate() {
			// call parent
			parent::processTemplate();

			// assign rubric to template
			$this->assign('room', 'rubric', CS_MATERIAL_TYPE);
		}

		/*****************************************************************************/
		/******************************** ACTIONS ************************************/
		/*****************************************************************************/
		public function actionDetail() {
			// try to set the item
			$this->setItem();

			$this->setupInformation();

			$session = $this->_environment->getSessionItem();

			// check for version id
			if(isset($_GET['version_id'])) {
				$current_version_id = $_GET['version_id'];
				if(empty($current_version_id)) {
					$current_version_id = 0;
				}
			} else {
				$session->unsetValue('version_index_ids');
			}

			// TODO: include delete handling
			//include_once('include/inc_delete_entry.php');

			// check for right manager
			if(!($this->_manager instanceof cs_material_manager)) {
				throw new cs_detail_item_type_exception('wrong item type', 0);
			} else {
				// load the shown item
				$material_version_list = $this->_manager->getVersionList($this->_item->getItemID());
				$material_item = $material_version_list->getFirst();
				$current_user = $this->_environment->getCurrentUser();

				// check for deleted item
				if(empty($material_item)) {
					$this->_manager->setDeleteLimit(false);
					$item = $this->_manager->getItem($this->_item->getItemID());
					$this->_manager->setDeleteLimit(true);
					if(!empty($item) && $item->isDeleted()) {
						throw new cs_detail_item_type_exception('item deleted', 1);
					}
				}

				// check for access
				elseif($material_item->isNotActivated() && $current_user->getItemID() != $material_item->getCreatorID() && !$current_user->isModerator()) {
					// TODO: error handling
					/*
					 * $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
      $page->add($errorbox);
					 */
				}

				// check for viewing rights
				elseif(!$material_item->maySee($current_user) && !$material_item->mayExternalSee($current_user)) {
					//TODO: error handling
					/*
					 * $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
      $page->add($errorbox);
					 */
				} else {
					// all checks passed

					$params = $this->_environment->getCurrentParameterArray();
					if(isset($_GET['export_to_wiki'])) {
						$wiki_manager = $this->_environment->getWikiManager();
						global $c_use_soap_for_wiki;
						if(!$c_use_soap_for_wiki) {
							$wiki_manager->exportItemToWiki($this->_item->getItemID(), CS_MATERIAL_TYPE);
						} else {
							$wiki_manager->exportItemToWiki_soap($this->_item->getItemID(), CS_MATERIAL_TYPE);
						}

						unset($params['export_to_wiki']);
						redirect($this->_environment->getCurrentContextID(), 'material', 'detail', $params);
					}

					if(isset($_GET['remove_from_wiki'])) {
						$wiki_manager = $this->_environment->getWikiManager();
						global $c_use_soap_for_wiki;
						if($c_use_soap_for_wiki) {
							$wiki_manager->removeItemFromWiki_soap($this->_item->getItemID(), CS_MATERIAL_TYPE);
						}

						unset($params['remove_from_wiki']);
						redirect($this->_environment->getCurrentContextID(), 'material', 'detail', $params);
					}

					if(isset($_GET['export_to_wordpress'])) {
						$wordpress_manager = $this->environment->getWordpressManager();
						$wordpress_manager->exportItemToWordpress($this->_item->getItemID(), CS_MATERIAL_TYPE);

						unset($params['export_to_wordpress']);
						redirect($this->_environment->getCurrentContextID(), 'material', 'detail', $params);
					}

					if(isset($_GET['workflow_read'])) {
						$item_manager->markItemAsWorkflowRead($this->_item->getItemID(), $current_user->getItemID());

						unset($params['workflow_read']);
						redirect($this->_environment->getCurrentContextID(), 'material', 'detail', $params);
					}

					if(isset($_GET['workflow_not_read'])) {
						$item_manager->markItemAsWorkflowNotRead($this->_item->getItemID(), $current_user->getItemID());

						unset($params['workflow_not_read']);
						redirect($this->_environment->getCurrentContextID(), 'material', 'detail', $params);
					}

					// get clipboard
					$clipboard_id_array = array();
					if($session->issetValue('material_clipboard')) {
						$clipboard_id_array = $session->getValue('material_clipboard');
					}

					// copy to clipboard
					if(isset($_GET['add_to_material_clipboard']) && !in_array($this->_item->getItemID(), $clipboard_id_array)) {
						$clipboard_id_array[] = $this->_item->getItemID();
						$session->setValue('material_clipboard', $clipboard_id_array);
					}

					// make old version current
					if(isset($_GET['act_version'])) {
						$latest_version_item = $material_version_list->getFirst();
						$old_version_item = $material_version_list->getNext();
						while($old_version_item && $_GET['act_version'] != $old_version_item->getVersionId()) {
							$old_version_item = $material_version_list->getNext();
						}

						$clone_item = $old_version_item->cloneCopy(true);
						$latest_version_id = $latest_version_item->getVersionID();
						$clone_item->setVersionID($latest_version_id + 1);
						$clone_item->save();
						$old_version_item->delete();

						$params = array();
						$params['iid'] = $this->_item->getItemID();
						redirect($this->_environment->getCurrentContextID(), 'material', 'detail', $params);
					}

					// mark as read and noticed
					$this->markRead();
					$this->markNoticed();

					$context_item = $this->_environment->getCurrentContextItem();
					$latest_version_item = $material_version_list->getFirst();

					$id = $this->_item->getItemID();
					if(isset($id) && $latest_version_item->getVersionID() != $id) {
						// old version
						/*
						 * $params = array();
            $params['environment'] = $environment;
            $params['with_modifying_actions'] = $context_item->isOpen();
            $detail_view = $class_factory->getClass(MATERIAL_VERSION_DETAIL_VIEW,$params);
            unset($params);
            $detail_view->setVersionList($material_version_list, $current_version_id);
						 */
					} else {
						// current version
						/*
						 *       //used to signal which "creator infos" of annotations are expanded...
   $creatorInfoStatus = array();
   if (!empty($_GET['creator_info_max'])) {
      $creatorInfoStatus = explode('-',$_GET['creator_info_max']);
   }

            //current version
            $params = array();
            $params['environment'] = $environment;
            $params['with_modifying_actions'] = $context_item->isOpen();
            $params['creator_info_status'] = $creatorInfoStatus;
            $detail_view = $class_factory->getClass(MATERIAL_DETAIL_VIEW,$params);
            unset($params);
            $detail_view->setVersionList($material_version_list);
            $detail_view->setClipboardIDArray($clipboard_id_array);
						 */
					}

					// set up rubric connections and browsing

					/*
					 * $context_item = $environment->getCurrentContextItem();
      $current_room_modules = $context_item->getHomeConf();
      if ( !empty($current_room_modules) ){
         $room_modules = explode(',',$current_room_modules);
      } else {
         $room_modules =  $default_room_modules;
      }
      $first = array();
      $second = array();
      foreach ( $room_modules as $module ) {
         $link_name = explode('_', $module);
         if ( $link_name[1] != 'none' and $link_name[0] !=$_GET['mod']) {
            switch ($detail_view->_is_perspective($link_name[0])) {
               case true:
                  $first[] = $link_name[0];
               break;
               case false:
                  $second[] = $link_name[0];
               break;
            }
         }
      }
      $room_modules = array_merge($first,$second);
      $rubric_connections = array();
      foreach ($room_modules as $module){
         if ($context_item->withRubric($module) ) {
            $ids = $material_item->getLinkedItemIDArray($module);
            $session->setValue('cid'.$environment->getCurrentContextID().'_'.$module.'_index_ids', $ids);
            $rubric_connections[] = $module;
         }
      }

      $detail_view->setRubricConnections($rubric_connections);

            // Subitems
            if ($context_item->withRubric(CS_MATERIAL_TYPE) ) {
               $detail_view->setSubItemRubricConnections(array(CS_MATERIAL_TYPE));
            }
					 */

					// set up annotations
					$version_item = $material_version_list->getFirst();
					if(isset($current_version_id)) {
						while($version_item && $version_item->getVersionID() !== $current_version_id) {
							$version_item = $material_version_list->getNext();
						}
					}

					if(!empty($version_item)) {
						// get annotations
						$annotations = $version_item->getAnnotationList();

						// mark annotations as readed and noticed
						$this->markAnnotationsReadedAndNoticed($annotations);

						// get annotations
						$this->assign('detail', 'annotations', $this->getAnnotationInformation($annotations));

						// assessment
						$this->assign('detail', 'assessment', $this->getAssessmentInformation($version_item));
					}

					/*
					 * // highlight search words in detail views
         $session_item = $environment->getSessionItem();
         if ( $session->issetValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array') ) {
            $search_array = $session->getValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array');
            if ( !empty($search_array['search']) ) {
               $detail_view->setSearchText($search_array['search']);
            }
            unset($search_array);
         }

         $page->add($detail_view);
					 */

					$this->assign('detail', 'content', $this->getDetailContent());
				}
			}
		}

		/*****************************************************************************/
		/******************************** END ACTIONS ********************************/
		/*****************************************************************************/
		protected function setItem() {
			// try to set the item
			if(!empty($_GET['iid'])) {
				$current_item_id = $_GET['iid'];
			} else {
				include_once('functions/error_functions.php');
				trigger_error('A discussion item id must be given.', E_USER_ERROR);
			}

			if(isset($_GET['version_id'])) {
				$current_version_id = $_GET['version_id'];
				if(empty($current_version_id)) {
					$current_version_id = 0;
				}
			} else {
				$session = $this->_environment->getSessionItem();
				$session->unsetValue('version_index_ids');
			}

			$item_manager = $this->_environment->getItemManager();
			$current_item_iid = $_GET['iid'];
			$type = $item_manager->getItemType($_GET['iid']);

			$this->_manager = $this->_environment->getMaterialManager();
			$this->_version_list = $this->_manager->getVersionList($current_item_id);
			$this->_item = $this->_version_list->getFirst();
		}

		protected function setBrowseIDs() {
			$session = $this->_environment->getSessionItem();

			if($session->issetValue('cid' . $this->_environment->getCurrentContextID() . '_material_index_ids')) {
				$this->_browse_ids = array_values((array) $session->getValue('cid' . $this->_environment->getCurrentContextID() . '_material_index_ids'));
			}
		}

		protected function getDetailContent() {
            $converter = $this->_environment->getTextConverter();

            // TODO??? $html .= $this->_getPluginInfosForMaterialDetailAsHTML();

      		$desc = '';
      		$num_sections = sizeof($this->getSections());
      		if($num_sections === 0) {
      			$desc = $this->_item->getDescription();
      			if(!empty($desc)) {
      				$desc = $converter->cleanDataFromTextArea($desc);
      				$converter->setFileArray($this->getItemFileList());
      				$desc = $converter->text_as_html_long($desc);

      				/*
					$temp_string = $this->_text_as_html_long($this->_compareWithSearchText($this->_cleanDataFromTextArea($desc)));
         			  $html .= $this->getScrollableContent($temp_string,$item,'',$with_links);
         			  */
      			}
      		}

			$return = array(
				'title'			=> $this->_item->getTitle(),
				'creator'		=> $this->_item->getCreatorItem()->getFullName(),
				'creation_date'	=> getDateTimeInLang($this->_item->getCreationDate()),
				'version'		=> $this->_item->getVersionID(),
				'formal'		=> $this->getFormalData(),
				'sections'		=> $this->getSections(),
				'description'	=> $desc,
				'moredetails'		=> $this->getCreatorInformationAsArray($this->_item)
				//'material'			=> $this->getMaterialContent()
			);

			return $return;
		}

		private function getFormalData() {
			$return = array();
			$translator = $this->_environment->getTranslationObject();
			$converter = $this->_environment->getTextConverter();
			$context_item = $this->_environment->getCurrentContextItem();
			$current_user = $this->_environment->getCurrentUserItem();

			// bibliography
			$bib_kind = $this->_item->getBibKind() ? $this->_item->getBibKind() : 'none';
			$biblio = '';

			// author, year
			$temp_array = array();

			if($bib_kind === 'common') {
				$author = $this->_item->getAuthor();
				if(!empty($author)) {
					$temp_array[0] = $translator->getMessage('MATERIAL_AUTHORS');
					$temp_array[1] = $converter->text_as_html_short($this->_item->getAuthor());
					$return[] = $temp_array;
				}

				$pub_date = $this->_item->getPublishingDate();
				if(!empty($pub_data)) {
					$temp_array[0] = $translator->getMessage('MATERIAL_PUBLISHING_DATE');
					$temp_array[1] = $converter->text_as_html_short($this->_item->getPublishingDate());
					$return[] = $temp_array;
				}

				if(!empty($return)) {
					$html .= $this->_getFormalDataAsHTML($return);
		            if ( isset($html_script) and !empty($html_script) ) {
		               $html .= $html_script;
		            }
				}

				$return = array();
		         $temp_array = array();
		         $biblio = $this->_item->getBibliographicValues();
			} elseif($bib_kind === 'website') {
				$biblio = $this->_item->getAuthor() . ',';
			} elseif($bib_kind === 'document') {
				$biblio = '';
			} else {
				$biblio = $this->_item->getAuthor() . ' (' . $this->_item->getPublishingDate() . '). ';
			}

			if($bib_kind !== 'common') {
				// bibliographic
				switch($bib_kind) {
					case 'book':
					case 'collection':
						$biblio .= $this->_item->getAddress() . ': ' . $this->_item->getPublisher();
						if($this->_item->getEdition()) {
							$biblio .= ', ' . $translator->getMessage('MATERIAL_BIB_EDITION', $this->_item->getEdition());
						}
						if($this->_item->getSeries()) {
							$biblio .= ', ' . $translator->getMessage('MATERIAL_BIB_SERIES', $this->_item->getSeries());
						}
						if($this->_item->getVolume()) {
							$biblio .= ', ' . $translator->getMessage('MATERIAL_BIB_VOLUME', $this->_item->getVolume());
						}
						if($this->_item->getISBN()) {
							$biblio .= ', ' . $translator->getMessage('MATERIAL_BIB_ISBN', $this->_item->getISBN());
						}
						$biblio .= '.';
						if($this->_item->getURL()) {
							$biblio .= ' ' . $translator->getMessage('MATERIAL_BIB_URL', $this->_item->getURL());
						}
						if($this->_item->getURLDate()) {
							$biblio .= ' (' . $translator->getMessage('MATERIAL_BIB_URL_DATE', $this->_item->getURLDate()) . ')';
							$biblio .= '.';
						}
						break;
					case 'incollection':
						$editor = $this->_item->getEditor();
						if(!empty($editor)) {
							$biblio .= $translator->getMessage('MATERIAL_BIB_IN') . ': ';
							$biblio .= $translator->getMessage('MATERIAL_BIB_EDITOR', $this->_item->getEditor()) . ': ';
						}
						$biblio .= $this->_item->getBooktitle() . '. ';
						$biblio .= $this->_item->getAddress() . ': ' . $this->item->getPublisher();

						if($this->_item->getEdition()) {
							$biblio .= ', ' . $translator->getMessage('MATERIAL_BIB_EDITION', $this->_item->getEdition());
						}
						if($this->_item->getSeries()) {
							$biblio .= ', ' . $translator->getMessage('MATERIAL_BIB_SERIES', $this->_item->getSeries());
						}
						if($this->_item->getVolume()) {
							$biblio .= ', ' . $translator->getMessage('MATERIAL_BIB_VOLUME', $this->_item->getVolume());
						}
						if($this->_item->getISBN()) {
							$biblio .= ', ' . $translator->getMessage('MATERIAL_BIB_ISBN', $this->_item->getISBN());
						}
						$biblio .= ', ' . $translator->getMessage('MATERIAL_BIB_PAGES', $this->_item->getPages()) . '.';
						if($this->_item->getURL()) {
							$biblio .= ' ' . $translator->getMessage('MATERIAL_BIB_URL', $this->_item->getURL());
						}
						if($this->_item->getURLDate()) {
							$biblio .= ' (' . $translator->getMessage('MATERIAL_BIB_URL_DATE', $this->_item->getURLDate()) . ')';
							$biblio .= '.';
						}
						break;
					case 'article':
						$biblio .= $translator->getMessage('MATERIAL_BIB_IN') . ': ' . $this->_item->getJournal();
						if($this->_item->getVolume()) {
							$biblio .= ', ' . $this->_item->getVolume();
							if($this->_item->getIssue()) {
								$biblio .= ' (' . $this->_item->getIssue() . ')';
							}
						} elseif($this->_item->getIssue()) {
							$biblio .= ', ' . $this->_Item->getIssue();
						}
						$biblio .= ', ' . $translator->getMessage('MATERIAL_BIB_PAGES', $this->_item->getPages()) . '. ';

						$bib2 = '';
						if($this->_item->getAddress()) {
							$bib2 .= $this->_item->getAddress();
						}
						if($this->_item->getPublisher()) {
							$bib2 .= $bib2 ? ', ' : '';
							$bib2 .= $this->_item->getPublisher();
						}
						if($this->_item->getISSN()) {
							$bib2 .= $bib2 ? ', ' : '';
							$bib2 .= $this->_item->getISSN();
						}
						$bib2 .= $bib2 ? '. ' : '';

						$biblio .= $bib2 ? $bib2 : '';
						if($this->_item->getURL()) {
							$biblio .= ' ' . $translator->getMessage('MATERIAL_BIB_URL', $this->_item->getURL());
						}
						if($this->_item->getURLDate()) {
							$biblio .= ' (' . $translator->getMessage('MATERIAL_BIB_URL_DATE', $this->_item->getURLDate()) . ')';
							$biblio .= '.';
						}
						break;

						case 'inpaper':
               $biblio .= $translator->getMessage('MATERIAL_BIB_IN').': '.
                      $this->_item->getJournal();
               if ( $this->_item->getIssue() ) {
                  $biblio .= ', '.$this->_item->getIssue();
               }
               $biblio .= ', '.$translator->getMessage('MATERIAL_BIB_PAGES', $this->_item->getPages()).'. ';

               $bib2 = '';
               if ( $this->_item->getAddress() ) {
                  $bib2 .= $this->_item->getAddress();
               }
               if ( $this->_item->getPublisher() ) {
                  $bib2 .= $bib2 ? ', ' : '';
                  $bib2 .= $this->_item->getPublisher();
               }
               $bib2 .= $bib2 ? '. ' : '';

               $biblio .= $bib2 ? $bib2 : '';
               if ( $this->_item->getURL() ) {
                  $biblio .= ' '.$translator->getMessage('MATERIAL_BIB_URL', $this->_item->getURL());
        if( $this->_item->getURLDate() ) {
           $biblio .= ' ('.$translator->getMessage('MATERIAL_BIB_URL_DATE', $this->_item->getURLDate()).')';
             }
             $biblio .= '.';
               }
               break;
            case 'thesis':
               {
                  $temp_Thesis_Kind = mb_strtoupper($this->_item->getThesisKind(), 'UTF-8');
                  switch ( $temp_Thesis_Kind )
                  {
                     case 'BACHELOR':
                        $biblio  .= $translator->getMessage('MATERIAL_THESIS_BACHELOR').'. ';
                        break;
                     case 'DIPLOMA':
                        $biblio  .= $translator->getMessage('MATERIAL_THESIS_DIPLOMA').'. ';
                        break;
                     case 'DISSERTATION':
                        $biblio  .= $translator->getMessage('MATERIAL_THESIS_DISSERTATION').'. ';
                        break;
                     case 'EXAM':
                        $biblio  .= $translator->getMessage('MATERIAL_THESIS_EXAM').'. ';
                        break;
                     case 'KIND':
                        $biblio  .= $translator->getMessage('MATERIAL_THESIS_KIND').'. ';
                        break;
                     case 'KIND_DESC':
                        $biblio  .= $translator->getMessage('MATERIAL_THESIS_KIND_DESC').'. ';
                        break;
                     case 'MASTER':
                        $biblio  .= $translator->getMessage('MATERIAL_THESIS_MASTER').'. ';
                        break;
                     case 'OTHER':
                        $biblio  .= $translator->getMessage('MATERIAL_THESIS_OTHER').'. ';
                        break;
                     case 'POSTDOC':
                        $biblio  .= $translator->getMessage('MATERIAL_THESIS_POSTDOC').'. ';
                        break;
                     case 'TERM':
                        $biblio  .= $translator->getMessage('MATERIAL_THESIS_TERM').'. ';
                        break;
                     default:
                        $biblio  .= $translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_material_detail_view(446) ';
                        break;
                  }
               }
               $biblio .= $this->_item->getAddress().': '.$this->_item->getUniversity();
               if ( $this->_item->getFaculty() ) {
                  $biblio .= ', '.$this->_item->getFaculty();
               }
               $biblio .= '.';
               if ( $this->_item->getURL() ) {
                  $biblio .= ' '.$translator->getMessage('MATERIAL_BIB_URL', $this->_item->getURL());
                  if( $this->_item->getURLDate() ) {
                     $biblio .= ' ('.$translator->getMessage('MATERIAL_BIB_URL_DATE', $this->_item->getURLDate()).')';
                  }
                  $biblio .= '.';
               }
               break;
            case 'website':
               $biblio .= ' '.$translator->getMessage('MATERIAL_BIB_URL', $this->_item->getURL());
               if( $this->_item->getURLDate() ) {
                  $biblio .= ' ('.$translator->getMessage('MATERIAL_BIB_URL_DATE', $this->_item->getURLDate()).')';
               }
               $biblio .= '.';
               break;
            case 'manuscript':
               $biblio .= $this->_item->getBibliographicValues();
               if ( $this->_item->getAddress() ) {
                  $biblio .= ' '.$this->_item->getAddress();
                  $biblio .= '.';
               }
               if ( $this->_item->getURL() ) {
                  $biblio .= ' '.$translator->getMessage('MATERIAL_BIB_URL', $this->_item->getURL());
                  if( $this->_item->getURLDate() ) {
                     $biblio .= ' ('.$translator->getMessage('MATERIAL_BIB_URL_DATE', $this->_item->getURLDate()).')';
                  }
                  $biblio .= '.';
               }
               break;
            case 'document':
                $formal_data_bib = array();
                $html .= $translator->getMessage('MATERIAL_BIB_DOCUMENT_ADMINISTRATION_INFO');
        		if ( $this->_item->getDocumentEditor() ) {
                	$temp_array = array();
         			$temp_array[] = $translator->getMessage('MATERIAL_BIB_DOCUMENT_EDITOR');
         			$temp_array[] = $this->_item->getDocumentEditor();
         			$return[] = $temp_array;
         		}
               	if ( $this->_item->getDocumentMaintainer() ) {
                	$temp_array = array();
         			$temp_array[] = $translator->getMessage('MATERIAL_BIB_DOCUMENT_MAINTAINER');
         			$temp_array[] = $this->_item->getDocumentMaintainer();
         			$return[] = $temp_array;
               	}
               	if ( $this->_item->getDocumentReleaseNumber() ) {
                	$temp_array = array();
         			$temp_array[] = $translator->getMessage('MATERIAL_BIB_DOCUMENT_RELEASE_NUMBER');
         			$temp_array[] = $this->_item->getDocumentReleaseNumber();
         			$return[] = $temp_array;
               	}
               	if ( $this->_item->getDocumentReleaseDate() ) {
                	$temp_array = array();
         			$temp_array[] = $translator->getMessage('MATERIAL_BIB_DOCUMENT_RELEASE_DATE');
         			$temp_array[] = $this->_item->getDocumentReleaseDate();
         			$return[] = $temp_array;
               	}
               	break;
            case 'none':
            default:
               $biblio .= $this->_item->getBibliographicValues();
				}
			}

			$biblio_pur = strip_tags($biblio);
			$biblio_pur = str_ireplace('&nbsp;', '', $biblio_pur);
			$biblio_pur = trim($biblio_pur);
			if($bib_kind !== 'none' && !empty($biblio_pur)) {
				$temp_array = array();
				$temp_array[] = $translator->getMessage('MATERIAL_BIBLIOGRAPHIC');
				if(!empty($biblio)) {
					$converter->setFileArray($this->getItemFileList());
					$temp_array[] = $converter->text_as_html_long($converter->cleanDataFromTextArea($biblio));
				} else {
					$temp_array[] = '<span class="disabled"> ' . $translator->getMessage('COMON_NONE') . '</span>';
				}
				$return[] = $temp_array;
			}

			if($this->_item->issetBibTOC()) {
				$temp_array = array();
				$temp_array[] = $translator->getMessage('COMMON_TABLE_OF_CONTENT');
				$temp_array[] = '<a href"' . $this->_item->getBibTOC() . '" target="blank">' . chunkText($this->_item->getBibTOC(), 60) . '</a>';
				$return[] = $temp_array;
			}

			if($this->_item->issetBibURL()) {
				$temp_array = array();
				$temp_array[] = $translator->getMessage('BELUGA_LINK');
				$temp_array[] = '<a href="' . $this->_item->getBibURL() . '" target="balnk">' . chunkText($this->_item->getBibURL(), 60) . '</a>';
				$return[] = $temp_array;
			}

			if($this->_item->issetBibAvailibility()) {
				$temp_array = array();
				$temp_array[] = $translator->getMessage('BELUGA_AVAILABILITY');
				$link = $this->_item->getBibAvailibility();
				$temp_array[] = $link;
				$return[] = $temp_array;
			}

			if($context_item->isWikiActive()) {
				if($this->_item->isExportToWiki()) {
					$temp_array = array();
					$temp_array[] = $translator->getMessage('MATERIAL_EXPORT_TO_WIKI_LINK');
					$temp_array[] = $this->_item->getExportToWikiLink();
					$return[] = $temp_array;
				}
			}

			if($context_item->isWordpressActive()) {
				if($this->_item->isExporttoWordpress()) {
					$temp_array = array();
					$temp_array[] = $translator->getMessage('MATERIAL_EXPORT_TO_WORDPRESS_LINK');
					$temp_array[] = $this->_item->getExportToWordpressLink();
					$return[] = $temp_array;
				}
			}

			// sections
			$sections = $this->getSections();
			$sections_return = array();
			if(!empty($sections)) {
				$temp_array = array();
				$temp_array[] = $translator->getMessage('MATERIAL_ABSTRACT');
				$description = $this->_item->getDescription();
				if(!empty($description)) {
					$description = $converter->cleanDataFromTextArea($description);
					$converter->setFileArray($this->getItemFileList());
					$description = $converter->text_as_html_long($description);
					$description = $converter->showImages($description, $this->_item, true);
					$temp_array[] = $description;
				} else {
					$temp_array[] = $translator->getMessage('COMMON_NONE');
				}
				$return[] = $temp_array;

				foreach($sections as $section) {
					/*
					 *  // files
		            $fileicons = $this->_getItemFiles( $section,true);
		            if ( !empty($fileicons) ) {
		               $fileicons = '&nbsp;'.$fileicons;
		            }
		            */

					$section_title = $converter->text_as_html_short($section['title']);
					/*
					 * if( $with_links and !(isset($_GET['mode']) and $_GET['mode']=='print') ) {
		               $section_title = '<a href="#anchor'.$section->getItemID().'">'.$section_title.'</a>'.$fileicons.LF;
		            }
            		*/
					$sections_return[] = $section_title;
				}
				$temp_array = array();
				$temp_array[] = $translator->getMessage('MATERIAL_SECTIONS');
				$temp_array[] = implode(BRLF, $sections_return);
				$return[] = $temp_array;
			}

			// files
			$files = array();

			$file_list = $this->_item->getFileList();
			if(!$file_list->isEmpty()) {
				$file = $file_list->getFirst();
				while($file) {
					if(!(isset($_GET['mode']) && $_GET['mode'] === 'print') || (isset($_GET['download']) && $_GET['download'] === 'zip')) {
						if((!isset($_GET['download']) || $_GET['download'] !== 'zip') && in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))) {
							//$this->_with_slimbox = true;
							$file_string = '<a href="' . $file->getUrl() . '" rel="lightbox[gallery' . $this->_item->getItemID() . ']">' . $file->getFileIcon() . ' ' . ($converter->text_as_html_short($file->getDisplayName())) . '</a> (' . $file->getFileSize() . ' KB)';
						} else {
							$file_string = '<a href="' . $file->getUrl() . '" target="blank">' . $file->getFileIcon() . ' ' . ($converter->text_as_html_short($file->getDisplayName())) . '</a> (' . $file->getFileSize() . ' KB)';
						}
					} else {
						$file_string = $file->getFileIcon() . ' ' . $converter->text_as_html_short($file->getDisplayName());
					}

					$files[] = $file_string;

					$file = $file_list->getNext();
				}

				$temp_array = array();
				$temp_array[] = $translator->getMessage('MATERIAL_FILES');
				$temp_array[] = implode(BRLF, $files);
				$return[] = $temp_array;
			}

			// world-public status
			if($context_item->isCommunityRoom() && $context_item->isOpenForGuests()) {
				$temp_array = array();
				$wrold_public = $this->_item->getWorldPublic();
				if($world_public === 0) {
					$public_info = $translator->getMessage('MATERIAL_WORLD_PUBLISH_STATUS_0');
				} elseif($world_public === 1) {
					$public_info = $translator->getMessage('MATERIAL_WORLD_PUBLISH_STATUS_1');
				} elseif($world_public === 2) {
					$public_info = $translator->getMessage('MATERIAL_WORLD_PUBLISH_STATUS_2');
				}

				$temp_array[] = $translator->getMessage('MATERIAL_WROLD_PUBLISH');
				$temp_array[] = $public_info;
				$return[] = $temp_array;
			}

			$version_mode = 'long';
			$iid = 0;
			$params = $this->_environment->getCurrentParameterArray();
			if(isset($params['iid'])) {
				$iid = $params['iid'];
			}

			$show_version = 'false';
			if(isset($params[$iid . 'version_mode']) && $params[$iid . 'version_mode'] === 'long') {
				$sho_versions = 'true';
			}
			$params[$iid . 'version_mode'] = 'long';

			// versions
			$versions = array();
			if(!$this->_version_list->isEmpty()) {
				$version = $this->_version_list->getFirst();

				if($version->getVersionID() === $this->_item->getVersionID()) {
					$title = '&nbsp;&nbsp;'.$translator->getMessage('MATERIAL_CURRENT_VERSION_DATE').' '.getDateTimeInLang($version->getModificationDate());
				} else {
					// TODO:
					 $params = array();
           $params[$iid.'version_mode'] = 'long';
           $params['iid'] = $version->getItemID();
           $title = '&nbsp;&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(), 'material', 'detail', $params,$this->_translator->getMessage('MATERIAL_CURRENT_VERSION_DATE').' '.getDateTimeInLang($version->getModificationDate()));
           unset($params);
				}

				$version = $this->_version_list->getNext();
				$is_user = $current_user->isUser();

				while($version) {
					/*
					 *
					 if ( !$with_links
                 or ( !$is_user
                      and $this->_environment->inCommunityRoom()
                      and !$version->isWorldPublic()
                    )
                 or $item->getVersionID() == $version->getVersionID()
               ) {
               $versions[] = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$this->_translator->getMessage('MATERIAL_VERSION_DATE').' '.getDateTimeInLang($version->getModificationDate());
            } else {
               $params = array();
               $params[$iid.'version_mode'] = 'long';
               $params['iid'] = $version->getItemID();
               $params['version_id'] = $version->getVersionID();
               $versions[] = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(), 'material', 'detail', $params,$this->_translator->getMessage('MATERIAL_VERSION_DATE').' '.getDateTimeInLang($version->getModificationDate()));
               unset($params);
            }
					 *
					 */

					$version = $this->_version_list->getNext();
				}
				$count = $this->_version_list->getCount();

				if(!empty($version) && $count > 1) {
					$temp_array = array();
					$temp_array[] = $this->_translator->getMessage('MATERIAL_VERSION');
					// TODO:
			            $html_string ='&nbsp;<img id="toggle'.$item->getItemID().$item->getVersionID().'" src="images/more.gif"/>';
			            $html_string .= $title;
			            $html_string .= '<div id="creator_information'.$item->getItemID().$item->getVersionID().'">'.LF;
			            $html_string .= '<div class="creator_information_panel">     '.LF;
			            $html_string .= '<div>'.LF;
			            if ($show_versions == 'true'){
			               $html_script ='<script type="text/javascript">initCreatorInformations("'.$item->getItemID().$item->getVersionID().'",true)</script>';
			            }else{
			               $html_script ='<script type="text/javascript">initCreatorInformations("'.$item->getItemID().$item->getVersionID().'",false)</script>';
			            }
			            if($with_links) {
			               $html_string .= implode(BRLF, $versions);
			            } else {
			               $version_count = count ($versions);
			               $html_string .= "$version_count. ".$versions[0];
			            }
			            $html_string .= '</div>'.LF;
			            $html_string .= '</div>'.LF;
			            $html_string .= '</div>'.LF;
			            $temp_array[] = $html_string;
			            $formal_data1[] = $temp_array;
				}
			}

			// TODO:
			/*
			if(!empty($formal_data1)) {
					$html .= $this->_getFormalDataAsHTML($formal_data1);
		         if ( isset($html_script) and !empty($html_script) ) {
		            $html .= $html_script;
		         }
			}
			*/

			return $return;
		}

		protected function getAdditionalActions($perms) {
			//TODO
			/*
			 * $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';

      // wiki
      $html .= $this->_getWikiAction($item,$current_user,$current_context);
      // wordpress
      $html .= $this->_getWordpressAction($item,$current_user,$current_context);

      //workflow
      $html .= $this->_getWorkflowReadAction($item,$current_user,$current_context);

      return $html;
			 */
		}

		private function getSections() {
			// cache
			if($this->_sections !== null) return $this->_sections;

			$return = array();
			$converter = $this->_environment->getTextConverter();

			$section_list = $this->_item->getSectionList();
			if(!$section_list->isEmpty()) {
				$section = $section_list->getFirst();

				while($section) {


					/*
					// files
            $fileicons = $this->_getItemFiles( $section,true);
            if ( !empty($fileicons) ) {
               $fileicons = '&nbsp;'.$fileicons;
            }

            $section_title = $this->_text_as_html_short($this->_compareWithSearchText($section->getTitle()));
            if( $with_links and !(isset($_GET['mode']) and $_GET['mode']=='print') ) {
               $section_title = '<a href="#anchor'.$section->getItemID().'">'.$section_title.'</a>'.$fileicons.LF;
            }
            $sections[] = $section_title;
            */
					// prepare description
		            $description = $section->getDescription();
					$description = $converter->cleanDataFromTextArea($description);
					$converter->setFileArray($this->getItemFileList());
					$description = $converter->text_as_html_long($description);
					$description = $converter->showImages($description, $section, true);

					// files
					$files = array();

					$file_list = $section->getFileList();
					if(!$file_list->isEmpty()) {
						$file = $file_list->getFirst();
						while($file) {
							if(!(isset($_GET['mode']) && $_GET['mode'] === 'print') || (isset($_GET['download']) && $_GET['download'] === 'zip')) {
								if((!isset($_GET['download']) || $_GET['download'] != 'zip') && in_array($file->getExtension(), array('png', 'jpg', 'jpeg', 'gif'))) {
									//$this->_with_slimbox = true;
									$file_string = '<a href="'.$file->getUrl().'" rel="lightbox[gallery'.$section->getItemID().']">';
									$file->getFileIcon().' '.($this->_text_as_html_short($file->getDisplayName())).'</a> ('.$file->getFileSize().' KB)';
								} else {
									$file_string = '<a href="'.$file->getUrl().'" target="blank">'.
						                  $file->getFileIcon().' '.($this->_text_as_html_short($file->getDisplayName())).'</a> ('.$file->getFileSize().' KB)';
								}
							} else {
									 $file_string = '<a href="'.$file->getUrl().'" target="blank">'.
                					  $file->getFileIcon().' '.($this->_text_as_html_short($file->getDisplayName())).'</a> ('.$file->getFileSize().' KB)';
							}
							$files[] = $file_string;

							$file = $file_list->getNext();
						}
					}
					/*
					 *
					 // files
      $formal_data = array();
      $files = $this->_getFilesForFormalData($item);
      if ( !empty($files) ) {
         $temp_array = array();
         $temp_array[] = $this->_translator->getMessage('MATERIAL_FILES');
         $temp_array[] = implode(BRLF, $files);
         $formal_data[] = $temp_array;
      }

      if ( !empty($formal_data) ) {
         $html .= $this->_getFormalDataAsHTML($formal_data);
      }

      return $html;
					 */

					$return[] = array(
						'title'				=> $converter->text_as_html_short($section->getTitle()),
						'description'		=> $description,
						'num_attachments'	=> sizeof($files)
					);

					$section = $section_list->getNext();
				}

				/*

         $temp_array[] = $this->_translator->getMessage('MATERIAL_SECTIONS');
         $temp_array[] = implode(BRLF, $sections).'<br/><br/>';
         $formal_data1[] = $temp_array;
				 */
			}

			// store for cache
			$this->_sections = $return;

			return $return;
		}
	}