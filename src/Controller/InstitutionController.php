<?php

namespace App\Controller;

use App\Action\Download\DownloadAction;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Filter\InstitutionFilterType;
use App\Form\Type\GroupType;
use App\Form\Type\AnnotationType;

/**
 * Class InstitutionController
 * @package App\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
class InstitutionController extends BaseController
{
    /**
     * @Route("/room/{roomId}/institution/feed/{start}/{sort}")
     * @Template()
     */
    public function feedAction($roomId, $max = 10, $start = 0, $sort = 'date', Request $request)
    {
        $institutionFilter = $request->get('institutionFilter');
        if (!$institutionFilter) {
            $institutionFilter = $request->query->get('institution_filter');
        }

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        // get the institution service
        $institutionService = $this->get('commsy_legacy.institution_service');

        if ($institutionFilter) {
            $filterForm = $this->createFilterForm($roomItem);

            $filterForm->submit($institutionFilter);
            $institutionService->setFilterConditions($filterForm);
        } else {
            $institutionService->showNoNotActivatedEntries();
        }

        // get institution list from institution service 
        $institutions = $institutionService->getListInstitutions($roomId, $max, $start, $sort);

        $this->get('session')->set('sortInstitutions', $sort);

        $readerService = $this->get('commsy_legacy.reader_service');

        $readerList = array();
        $allowedActions = array();
        foreach ($institutions as $item) {
            $reader = $readerService->getLatestReader($item->getItemId());
            if ( empty($reader) ) {
               $readerList[$item->getItemId()] = 'new';
            } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
               $readerList[$item->getItemId()] = 'changed';
            }
            if ($this->isGranted('ITEM_EDIT', $item->getItemID())) {
                $allowedActions[$item->getItemID()] = array('markread', 'copy', 'save', 'delete');
            } else {
                $allowedActions[$item->getItemID()] = array('markread', 'copy', 'save');
            }
        }

        return array(
            'roomId' => $roomId,
            'institutions' => $institutions,
            'readerList' => $readerList,
            'allowedActions' => $allowedActions,
        );
    }
    
    /**
     * @Route("/room/{roomId}/institution")
     * @Template()
     */
    public function listAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $roomManager = $legacyEnvironment->getRoomManager();

        /** @var \cs_community_item $roomItem */
        $roomItem = $roomManager->getItem($roomId);

        $filterForm = $this->createFilterForm($roomItem);

        // get the institution service
        $institutionService = $this->get('commsy_legacy.institution_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in institution manager
            $institutionService->setFilterConditions($filterForm);
        } else {
            $institutionService->showNoNotActivatedEntries();
        }

        $itemsCountArray = $institutionService->getCountArray($roomId);

        $usageInfo = false;
        if ($roomItem->getUsageInfoTextForRubricInForm('institution') != '') {
            $usageInfo['title'] = $roomItem->getUsageInfoHeaderForRubric('institution');
            $usageInfo['text'] = $roomItem->getUsageInfoTextForRubricInForm('institution');
        }

        $currentUser = $this->get('commsy_legacy.environment')->getEnvironment()->getCurrentUser();
        $createContext = true;
        if ($currentUser->getStatus() == "" || !$currentUser->isAllowedToCreateContext()) {
            $createContext = false;
        }

        return array(
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'institution',
            'itemsCountArray' => $itemsCountArray,
            'usageInfo' => $usageInfo,
            'createContext' => $createContext
        );
    }

    /**
     * @Route("/room/{roomId}/institution/create")
     */
    public function createAction($roomId, Request $request)
    {
        $currentUser = $this->get('commsy_legacy.environment')->getEnvironment()->getCurrentUser();
        if ($currentUser->isAllowedToCreateContext()) {
            $institutionService = $this->get('commsy_legacy.institution_service');
            $institutionItem = $institutionService->getNewInstitution();
            $institutionItem->setDraftStatus(1);
            $institutionItem->setPrivateEditing(1);
            $institutionItem->save();
            return $this->redirectToRoute('app_institution_detail', array('roomId' => $roomId, 'itemId' => $institutionItem->getItemId()));
        } else {
            return $this->redirectToRoute('app_institution_list', array('roomId' => $roomId));
        }
    }

    /**
     * @Route("/room/{roomId}/institution/{itemId}", requirements={
     *     "itemId": "\d+"
     * }))
     * @Template()
     * @Security("is_granted('ITEM_SEE', itemId)")
     */
    public function detailAction($roomId, $itemId, Request $request)
    {

        $infoArray = $this->getDetailInfo($roomId, $itemId);

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $alert = null;
        if ($infoArray['institution']->isLocked()) {
            $translator = $this->get('translator');

            $alert['type'] = 'warning';
            $alert['content'] = $translator->trans('item is locked', array(), 'item');
        }

        $pathTopicItem = null;
        if ($request->query->get('path')) {
            $topicService = $this->get('commsy_legacy.topic_service');
            $pathTopicItem = $topicService->getTopic($request->query->get('path'));
        }

        return array(
            'roomId' => $roomId,
            'institution' => $infoArray['institution'],
            'readerList' => $infoArray['readerList'],
            'modifierList' => $infoArray['modifierList'],
            'institutionList' => $infoArray['institutionList'],
            'counterPosition' => $infoArray['counterPosition'],
            'count' => $infoArray['count'],
            'firstItemId' => $infoArray['firstItemId'],
            'prevItemId' => $infoArray['prevItemId'],
            'nextItemId' => $infoArray['nextItemId'],
            'lastItemId' => $infoArray['lastItemId'],
            'readCount' => $infoArray['readCount'],
            'readSinceModificationCount' => $infoArray['readSinceModificationCount'],
            'userCount' => $infoArray['userCount'],
            'draft' => $infoArray['draft'],
            'showRating' => $infoArray['showRating'],
            'showWorkflow' => $infoArray['showWorkflow'],
            'showHashtags' => $infoArray['showHashtags'],
            'showCategories' => $infoArray['showCategories'],
            'roomCategories' => $infoArray['roomCategories'],
            'members' => $infoArray['members'],
            'user' => $infoArray['user'],
            'annotationForm' => $form->createView(),
            'alert' => $alert,
            'pathTopicItem' => $pathTopicItem,
       );
    }


    /**
     * @Route("/room/{roomId}/institution/{itemId}/print")
     */
    public function printAction($roomId, $itemId)
    {

        $infoArray = $this->getDetailInfo($roomId, $itemId);

        // annotation form
        $form = $this->createForm(AnnotationType::class);

        $html = $this->renderView('institution/detail_print.html.twig', [
            'roomId' => $roomId,
            'institution' => $infoArray['institution'],
            'readerList' => $infoArray['readerList'],
            'modifierList' => $infoArray['modifierList'],
            'institutionList' => $infoArray['institutionList'],
            'counterPosition' => $infoArray['counterPosition'],
            'count' => $infoArray['count'],
            'firstItemId' => $infoArray['firstItemId'],
            'prevItemId' => $infoArray['prevItemId'],
            'nextItemId' => $infoArray['nextItemId'],
            'lastItemId' => $infoArray['lastItemId'],
            'readCount' => $infoArray['readCount'],
            'readSinceModificationCount' => $infoArray['readSinceModificationCount'],
            'userCount' => $infoArray['userCount'],
            'draft' => $infoArray['draft'],
            'showRating' => $infoArray['showRating'],
            'showWorkflow' => $infoArray['showWorkflow'],
            'showHashtags' => $infoArray['showHashtags'],
            'showCategories' => $infoArray['showCategories'],
            'members' => $infoArray['members'],
            'user' => $infoArray['user'],
            'annotationForm' => $form->createView(),
        ]);

        return $this->get('commsy.print_service')->buildPdfResponse($html);
    }

    private function getDetailInfo ($roomId, $itemId) {
        $infoArray = array();

        $institutionService = $this->get('commsy_legacy.institution_service');
        $itemService = $this->get('commsy_legacy.item_service');

        $annotationService = $this->get('commsy_legacy.annotation_service');

        $institution = $institutionService->getInstitution($itemId);

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $item = $institution;
        $reader_manager = $legacyEnvironment->getReaderManager();
        $reader = $reader_manager->getLatestReader($item->getItemID());
        if(empty($reader) || $reader['read_date'] < $item->getModificationDate()) {
            $reader_manager->markRead($item->getItemID(), $item->getVersionID());
        }

        $noticed_manager = $legacyEnvironment->getNoticedManager();
        $noticed = $noticed_manager->getLatestNoticed($item->getItemID());
        if(empty($noticed) || $noticed['read_date'] < $item->getModificationDate()) {
            $noticed_manager->markNoticed($item->getItemID(), $item->getVersionID());
        }

        $current_context = $legacyEnvironment->getCurrentContextItem();

        $roomManager = $legacyEnvironment->getRoomManager();
        $readerManager = $legacyEnvironment->getReaderManager();
        $roomItem = $roomManager->getItem($institution->getContextId());

        $userManager = $legacyEnvironment->getUserManager();
        $userManager->setContextLimit($legacyEnvironment->getCurrentContextID());
        $userManager->setUserLimit();
        $userManager->select();
        $user_list = $userManager->get();
        $all_user_count = $user_list->getCount();
        $read_count = 0;
        $read_since_modification_count = 0;

        $current_user = $user_list->getFirst();
        $id_array = array();
        while ( $current_user ) {
           $id_array[] = $current_user->getItemID();
           $current_user = $user_list->getNext();
        }
        $readerManager->getLatestReaderByUserIDArray($id_array,$institution->getItemID());
        $current_user = $user_list->getFirst();
        while ( $current_user ) {
            $current_reader = $readerManager->getLatestReaderForUserByID($institution->getItemID(), $current_user->getItemID());
            if ( !empty($current_reader) ) {
                if ( $current_reader['read_date'] >= $institution->getModificationDate() ) {
                    $read_count++;
                    $read_since_modification_count++;
                } else {
                    $read_count++;
                }
            }
            $current_user = $user_list->getNext();
        }
        $readerService = $this->get('commsy_legacy.reader_service');

        $readerList = array();
        $modifierList = array();
        $reader = $readerService->getLatestReader($institution->getItemId());
        if ( empty($reader) ) {
           $readerList[$item->getItemId()] = 'new';
        } elseif ( $reader['read_date'] < $institution->getModificationDate() ) {
           $readerList[$institution->getItemId()] = 'changed';
        }

        $modifierList[$institution->getItemId()] = $itemService->getAdditionalEditorsForItem($institution);

        $institutions = $institutionService->getListInstitutions($roomId);
        $institutionList = array();
        $counterBefore = 0;
        $counterAfter = 0;
        $counterPosition = 0;
        $foundInstitution = false;
        $firstItemId = false;
        $prevItemId = false;
        $nextItemId = false;
        $lastItemId = false;
        foreach ($institutions as $tempInstitution) {
            if (!$foundInstitution) {
                if ($counterBefore > 5) {
                    array_shift($institutionList);
                } else {
                    $counterBefore++;
                }
                $institutionList[] = $tempInstitution;
                if ($tempInstitution->getItemID() == $institution->getItemID()) {
                    $foundInstitution = true;
                }
                if (!$foundInstitution) {
                    $prevItemId = $tempInstitution->getItemId();
                }
                $counterPosition++;
            } else {
                if ($counterAfter < 5) {
                    $institutionList[] = $tempInstitution;
                    $counterAfter++;
                    if (!$nextItemId) {
                        $nextItemId = $tempInstitution->getItemId();
                    }
                } else {
                    break;
                }
            }
        }
        if (!empty($institutions)) {
            if ($prevItemId) {
                $firstItemId = $institutions[0]->getItemId();
            }
            if ($nextItemId) {
                $lastItemId = $institutions[sizeof($institutions)-1]->getItemId();
            }
        }
        // mark annotations as readed
        $annotationList = $institution->getAnnotationList();
        $annotationService->markAnnotationsReadedAndNoticed($annotationList);


        $membersList = $institution->getMemberItemList();
        $members = $membersList->to_array();

        $categories = array();
        if ($current_context->withTags()) {
            $roomCategories = $this->get('commsy_legacy.category_service')->getTags($roomId);
            $institutionCategories = $institution->getTagsArray();
            $categories = $this->getTagDetailArray($roomCategories, $institutionCategories);
        }

        $infoArray['institution'] = $institution;
        $infoArray['readerList'] = $readerList;
        $infoArray['modifierList'] = $modifierList;
        $infoArray['institutionList'] = $institutionList;
        $infoArray['counterPosition'] = $counterPosition;
        $infoArray['count'] = sizeof($institutions);
        $infoArray['firstItemId'] = $firstItemId;
        $infoArray['prevItemId'] = $prevItemId;
        $infoArray['nextItemId'] = $nextItemId;
        $infoArray['lastItemId'] = $lastItemId;
        $infoArray['readCount'] = $read_count;
        $infoArray['readSinceModificationCount'] = $read_since_modification_count;
        $infoArray['userCount'] = $all_user_count;
        $infoArray['draft'] = $itemService->getItem($itemId)->isDraft();
        $infoArray['showRating'] = $current_context->isAssessmentActive();
        $infoArray['showWorkflow'] = $current_context->withWorkflow();
        $infoArray['user'] = $legacyEnvironment->getCurrentUserItem();
        $infoArray['showCategories'] = $current_context->withTags();
        $infoArray['showHashtags'] = $current_context->withBuzzwords();
        $infoArray['roomCategories'] = $categories;
        $infoArray['members'] = $members;

        return $infoArray;
    }

    /**
     * @Route("/room/{roomId}/institution/{itemId}/edit")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function editAction($roomId, $itemId, Request $request)
    {
        $itemService = $this->get('commsy_legacy.item_service');
        $item = $itemService->getItem($itemId);

        $itemController = $this->get('commsy.item_controller');

        $institutionService = $this->get('commsy_legacy.institution_service');
        $transformer = $this->get('commsy_legacy.transformer.institution');

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $current_context = $legacyEnvironment->getCurrentContextItem();

        $categoriesMandatory = $current_context->withTags() && $current_context->isTagMandatory();
        $hashtagsMandatory = $current_context->withBuzzwords() && $current_context->isBuzzwordMandatory();

        $institutionItem = $institutionService->getInstitution($itemId);
        if (!$institutionItem) {
            throw $this->createNotFoundException('No institution found for id ' . $itemId);
        }
        $formData = $transformer->transform($institutionItem);
        $formData['categoriesMandatory'] = $categoriesMandatory;
        $formData['hashtagsMandatory'] = $hashtagsMandatory;
        $formData['category_mapping']['categories'] = $itemController->getLinkedCategories($item);
        $formData['hashtag_mapping']['hashtags'] = $itemController->getLinkedHashtags($itemId, $roomId, $legacyEnvironment);
        $formData['draft'] = $item->isDraft();
        $translator = $this->get('translator');
        $form = $this->createForm(GroupType::class, $formData, array(
            'action' => $this->generateUrl('app_institution_edit', array(
                'roomId' => $roomId,
                'itemId' => $itemId,
            )),
            'placeholderText' => '['.$translator->trans('insert title').']',
            'categoryMappingOptions' => [
                'categories' => $itemController->getCategories($roomId, $this->get('commsy_legacy.category_service'))
            ],
            'hashtagMappingOptions' => [
                'hashtags' => $itemController->getHashtags($roomId, $legacyEnvironment),
                'hashTagPlaceholderText' => $translator->trans('Hashtag', [], 'hashtag'),
                'hashtagEditUrl' => $this->generateUrl('app_hashtag_add', ['roomId' => $roomId])
            ],
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $institutionItem = $transformer->applyTransformation($institutionItem, $form->getData());

                // update modifier
                $institutionItem->setModificatorItem($legacyEnvironment->getCurrentUserItem());

                $institutionItem->save();

                if ($item->isDraft()) {
                    $item->setDraftStatus(0);
                    $item->saveAsItem();
                }
            } else if ($form->get('cancel')->isClicked()) {
                // ToDo ...
            }
            return $this->redirectToRoute('app_institution_save', array('roomId' => $roomId, 'itemId' => $itemId));
        }

        return array(
            'form' => $form->createView(),
            'institution' => $institutionItem,
            'showHashtags' => $current_context->withBuzzwords(),
            'showCategories' => $current_context->withTags(),
            'currentUser' => $legacyEnvironment->getCurrentUserItem(),
        );
    }

        /**
     * @Route("/room/{roomId}/institution/{itemId}/save")
     * @Template()
     * @Security("is_granted('ITEM_EDIT', itemId)")
     */
    public function saveAction($roomId, $itemId)
    {
        $itemService = $this->get('commsy_legacy.item_service');

        $institutionService = $this->get('commsy_legacy.institution_service');

        $institution = $institutionService->getInstitution($itemId);

        $itemArray = array($institution);
        $modifierList = array();
        foreach ($itemArray as $item) {
            $modifierList[$item->getItemId()] = $itemService->getAdditionalEditorsForItem($item);
        }

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $readerManager = $legacyEnvironment->getReaderManager();

        $userManager = $legacyEnvironment->getUserManager();
        $userManager->setContextLimit($legacyEnvironment->getCurrentContextID());
        $userManager->setUserLimit();
        $userManager->select();
        $user_list = $userManager->get();
        $all_user_count = $user_list->getCount();
        $read_count = 0;
        $read_since_modification_count = 0;

        $current_user = $user_list->getFirst();
        $id_array = array();
        while ( $current_user ) {
           $id_array[] = $current_user->getItemID();
           $current_user = $user_list->getNext();
        }
        $readerManager->getLatestReaderByUserIDArray($id_array,$institution->getItemID());
        $current_user = $user_list->getFirst();
        while ( $current_user ) {
            $current_reader = $readerManager->getLatestReaderForUserByID($institution->getItemID(), $current_user->getItemID());
            if ( !empty($current_reader) ) {
                if ( $current_reader['read_date'] >= $institution->getModificationDate() ) {
                    $read_count++;
                    $read_since_modification_count++;
                } else {
                    $read_count++;
                }
            }
            $current_user = $user_list->getNext();
        }
        $readerService = $this->get('commsy_legacy.reader_service');

        $readerList = array();
        $modifierList = array();
        foreach ($itemArray as $item) {
            $reader = $readerService->getLatestReader($item->getItemId());
            if ( empty($reader) ) {
               $readerList[$item->getItemId()] = 'new';
            } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
               $readerList[$item->getItemId()] = 'changed';
            }

            $modifierList[$item->getItemId()] = $itemService->getAdditionalEditorsForItem($item);
        }

        return array(
            'roomId' => $roomId,
            'item' => $institution,
            'modifierList' => $modifierList,
            'userCount' => $all_user_count,
            'readCount' => $read_count,
            'readSinceModificationCount' => $read_since_modification_count,
        );
    }

    /**
     * @Route("/room/{roomId}/institution/print/{sort}", defaults={"sort" = "none"})
     */
    public function printlistAction($roomId, Request $request, $sort)
    {
         $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem) {
            throw $this->createNotFoundException('The requested room does not exist');
        }

        $filterForm = $this->createForm(InstitutionFilterType::class, $this->defaultFilterValues, array(
            'action' => $this->generateUrl('app_institution_list', array(
                'roomId' => $roomId,
            )),
            'hasHashtags' => false,
            'hasCategories' => false,
        ));

        $institutionService = $this->get('commsy_legacy.institution_service');
        $numAllInstitutions = $institutionService->getCountArray($roomId)['countAll'];

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // set filter conditions in institution manager
            $institutionService->setFilterConditions($filterForm);
        }

        // get institution list from manager service
        if ($sort != "none") {
            $institutions = $institutionService->getListInstitutions($roomId, $numAllInstitutions, 0, $sort);
        }
        elseif ($this->get('session')->get('sortInstitutions')) {
            $institutions = $institutionService->getListInstitutions($roomId, $numAllInstitutions, 0, $this->get('session')->get('sortInstitutions'));
        }
        else {
            $institutions = $institutionService->getListInstitutions($roomId, $numAllInstitutions, 0, 'date');
        }

        $readerService = $this->get('commsy_legacy.reader_service');

        $readerList = array();
        foreach ($institutions as $item) {
            $readerList[$item->getItemId()] = $readerService->getChangeStatus($item->getItemId());
        }

        // get institution list from manager service
        $itemsCountArray = $institutionService->getCountArray($roomId);

        $html = $this->renderView('institution/list_print.html.twig', [
            'roomId' => $roomId,
            'institutions' => $institutions,
            'readerList' => $readerList,
            'showRating' => false,
            'module' => 'institution',
            'itemsCountArray' => $itemsCountArray,
            'showRating' => false,
            'showHashTags' => false,
            'showCategories' => false,
        ]);

        return $this->get('commsy.print_service')->buildPdfResponse($html);
    }

    /**
     * @Route("/room/{roomId}/institution/download")
     * @throws \Exception
     */
    public function downloadAction($roomId, Request $request)
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $action = $this->get(DownloadAction::class);
        return $action->execute($room, $items);
    }

    ###################################################################################################
    ## XHR Action requests
    ###################################################################################################

    /**
     * @Route("/room/{roomId}/institution/xhr/markread", condition="request.isXmlHttpRequest()")
     * @throws \Exception
     */
    public function xhrMarkReadAction($roomId, Request $request)
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $action = $this->get('commsy.action.mark_read.generic');
        return $action->execute($room, $items);
    }

    /**
     * @Route("/room/{roomId}/institution/xhr/delete", condition="request.isXmlHttpRequest()")
     * @throws \Exception
     */
    public function xhrDeleteAction($roomId, Request $request)
    {
        $room = $this->getRoom($roomId);
        $items = $this->getItemsForActionRequest($room, $request);

        $action = $this->get('commsy.action.delete.generic');
        return $action->execute($room, $items);
    }

    /**
     * @param Request $request
     * @param \cs_room_item $roomItem
     * @param boolean $selectAll
     * @param integer[] $itemIds
     * @return \cs_label_item[]
     */
    public function getItemsByFilterConditions(Request $request, $roomItem, $selectAll, $itemIds = [])
    {
        $institutionService = $this->get('commsy_legacy.institution_service');

        if ($selectAll) {
            if ($request->query->has('institution_filter')) {
                $currentFilter = $request->query->get('institution_filter');
                $filterForm = $this->createFilterForm($roomItem);

                // manually bind values from the request
                $filterForm->submit($currentFilter);

                // apply filter
                $institutionService->setFilterConditions($filterForm);
            } else {
                $institutionService->showNoNotActivatedEntries();
            }

            return $institutionService->getListInstitutions($roomItem->getItemID());
        } else {
            return $institutionService->getInstitutionsById($roomItem->getItemID(), $itemIds);
        }
    }

    /**
     * @param \cs_room_item $room
     * @return FormInterface
     */
    private function createFilterForm($room)
    {
        // setup filter form default values
        $defaultFilterValues = [
            'hide-deactivated-entries' => true,
        ];

        return $this->createForm(InstitutionFilterType::class, $defaultFilterValues, [
            'action' => $this->generateUrl('app_institution_list', [
                'roomId' => $room->getItemID(),
            ]),
            'hasHashtags' => $room->withBuzzwords(),
            'hasCategories' => $room->withTags(),
        ]);
    }

    private function getTagDetailArray ($baseCategories, $itemCategories) {
        $result = array();
        $tempResult = array();
        $addCategory = false;
        foreach ($baseCategories as $baseCategory) {
            if (!empty($baseCategory['children'])) {
                $tempResult = $this->getTagDetailArray($baseCategory['children'], $itemCategories);
            }
            if (!empty($tempResult)) {
                $addCategory = true;
            }
            $foundCategory = false;
            foreach ($itemCategories as $itemCategory) {
                if ($baseCategory['item_id'] == $itemCategory['id']) {
                    if ($addCategory) {
                        $result[] = array('title' => $baseCategory['title'], 'item_id' => $baseCategory['item_id'], 'children' => $tempResult);
                    } else {
                        $result[] = array('title' => $baseCategory['title'], 'item_id' => $baseCategory['item_id']);
                    }
                    $foundCategory = true;
                }
            }
            if (!$foundCategory) {
                if ($addCategory) {
                    $result[] = array('title' => $baseCategory['title'], 'item_id' => $baseCategory['item_id'], 'children' => $tempResult);
                }
            }
            $tempResult = array();
            $addCategory = false;
        }
        return $result;
    }
}