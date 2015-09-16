<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

use CommsyBundle\Filter\AnnouncementFilterType;

class AnnouncementController extends Controller
{
    /**
     * @Route("/room/{roomId}/announcement/feed/{start}")
     * @Template()
     */
    public function feedAction($roomId, $max = 10, $start = 0, Request $request)
    {
        // setup filter form
        $defaultFilterValues = array(
            'activated' => true
        );
        $filterForm = $this->createForm(new AnnouncementFilterType(), $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_announcement_list', array('roomId' => $roomId)),
        ));

        // get the material manager service
        $announcementService = $this->get('commsy_legacy.announcement_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in material manager
            $announcementService->setFilterConditions($filterForm);
        }

        // get material list from manager service 
        $announcements = $announcementService->getListAnnouncements($roomId, $max, $start);

        $readerService = $this->get('commsy.reader_service');

        $readerList = array();
        foreach ($announcements as $item) {
            $reader = $readerService->getLatestReader($item->getItemId());
            if ( empty($reader) ) {
               $readerList[$item->getItemId()] = 'new';
            } elseif ( $reader['read_date'] < $item->getModificationDate() ) {
               $readerList[$item->getItemId()] = 'changed';
            }
        }

        return array(
            'roomId' => $roomId,
            'announcements' => $announcements,
            'readerList' => $readerList
        );
    }
    
    /**
     * @Route("/room/{roomId}/announcement")
     * @Template()
     */
    public function listAction($roomId, Request $request)
    {
        // setup filter form
        $defaultFilterValues = array(
            'activated' => true
        );
        $filterForm = $this->createForm(new AnnouncementFilterType(), $defaultFilterValues, array(
            'action' => $this->generateUrl('commsy_announcement_list', array('roomId' => $roomId)),
        ));

        // get the material manager service
        $announcementService = $this->get('commsy_legacy.announcement_service');

        // apply filter
        $filterForm->handleRequest($request);
        if ($filterForm->isValid()) {
            // set filter conditions in material manager
            $announcementService->setFilterConditions($filterForm);
        }

        return array(
            'roomId' => $roomId,
            'form' => $filterForm->createView(),
            'module' => 'announcement'
        );
    }
    
    /**
     * @Route("/room/{roomId}/announcement/{itemId}")
     * @Template()
     */
    public function detailAction($roomId, $itemId, Request $request)
    {
        $announcementService = $this->get('commsy_legacy.announcement_service');
        $itemService = $this->get('commsy.item_service');
        
        $announcement = $announcementService->getAnnouncement($itemId);
 
 
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $item = $announcement;
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

        
        $itemArray = array($announcement);

        $readerService = $this->get('commsy.reader_service');
        
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
            'announcement' => $announcementService->getAnnouncement($itemId),
            'readerList' => $readerList,
            'modifierList' => $modifierList
        );
    }
}