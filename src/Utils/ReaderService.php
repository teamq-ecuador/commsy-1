<?php

namespace App\Utils;

use App\Services\LegacyEnvironment;
use App\Utils\ItemService;

class ReaderService
{
    /**
     * Read status constant that identifies a "new" item, i.e. an item that hasn't been seen before
     * @var string
     */
    public const READ_STATUS_NEW = 'new';

    /**
     * Read status constant that identifies a "changed" item, i.e. an item with unread changes
     * @var string
     */
    public const READ_STATUS_CHANGED = 'changed';

    /**
     * Read status constant that identifies a "seen" item, i.e. an item that has been read before
     * @var string
     */
    public const READ_STATUS_SEEN = 'seen';

    /**
     * Read status constant that identifies an item that has a "new annotation" which hasn't been seen before
     * @var string
     */
    public const READ_STATUS_NEW_ANNOTATION = 'new_annotation';

    /**
     * Read status constant that identifies an item that has a "changed annotation", i.e. an annotation with unread changes
     * @var string
     */
    public const READ_STATUS_CHANGED_ANNOTATION = 'changed_annotation';

    private $legacyEnvironment;
    private $readerManager;
    private $itemService;

    public function __construct(LegacyEnvironment $legacyEnvironment, ItemService $itemService)
    {
        $this->legacyEnvironment = $legacyEnvironment;
        $this->readerManager = $this->legacyEnvironment->getEnvironment()->getReaderManager();
        $this->itemService = $itemService;
    }

    public function getLatestReader($itemId)
    {
        $this->readerManager->resetLimits();
        return $this->readerManager->getLatestReader($itemId);
    }

    public function getChangeStatus($itemId)
    {
        $current_user = $this->legacyEnvironment->getEnvironment()->getCurrentUserItem();
        if ($current_user->isUser()) {
            return $this->getChangeStatusForUserByID($itemId, $current_user->getItemID());
        }
        return '';
    }

    public function getChangeStatusForUserByID($itemId, $userID)
    {
        $return = '';

        $readerManager = $this->readerManager;
        $reader = $readerManager->getLatestReaderForUserByID($itemId, $userID);
        $item = $this->itemService->getTypedItem($itemId);
        if (empty($reader)) {
            $currentUser = $this->legacyEnvironment->getEnvironment()->getCurrentUserItem();
            $itemIsCurrentUser = ($item instanceof \cs_user_item && $item->getUserID() === $currentUser->getUserID());
            if (!$itemIsCurrentUser) {
                $return = self::READ_STATUS_NEW;
            }
        } else if (!$item->isNotActivated() and $reader['read_date'] < $item->getModificationDate()) {
            $return = self::READ_STATUS_CHANGED;
        }

        if ($return == '') {

            // annotations
            $annotation_list = $item->getAnnotationList();
            $anno_item = $annotation_list->getFirst();
            $new = false;
            $changed = false;
            $date = "0000-00-00 00:00:00";
            while ($anno_item) {
                $reader = $readerManager->getLatestReaderForUserByID($anno_item->getItemID(), $userID);
                if (empty($reader)) {
                    if ($date < $anno_item->getModificationDate()) {
                        $new = true;
                        $changed = false;
                        $date = $anno_item->getModificationDate();
                    }
                } elseif ($reader['read_date'] < $anno_item->getModificationDate()) {
                    if ($date < $anno_item->getModificationDate()) {
                        $new = false;
                        $changed = true;
                        $date = $anno_item->getModificationDate();
                    }
                }
                $anno_item = $annotation_list->getNext();
            }

            if ($new) {
                $return = self::READ_STATUS_NEW_ANNOTATION;
            } else if ($changed) {
                $return = self::READ_STATUS_CHANGED_ANNOTATION;
            }
        }

        $itemType = $item->getItemType();

        if ($return == '' and ($itemType == 'material' or $itemType == 'discussion' or $itemType == 'todo')) {

            // sub-items
            if ($itemType == 'material') {
                $materialManager = $this->legacyEnvironment->getEnvironment()->getMaterialManager();
                $material = $materialManager->getItem($item->getItemID());
                $itemList = $material->getSectionList();
            }
            if ($itemType == 'discussion') {
                $discussionManager = $this->legacyEnvironment->getEnvironment()->getDiscussionManager();
                $discussion = $discussionManager->getItem($item->getItemID());
                $itemList = $discussion->getAllArticles();
            }
            if ($itemType == 'todo') {
                $todoManager = $this->legacyEnvironment->getEnvironment()->getToDoManager();
                $todo = $todoManager->getItem($item->getItemID());
                $itemList = $todo->getStepItemList();
            }

            $readerItem = $itemList->getFirst();
            $new = false;
            $changed = false;
            $date = "0000-00-00 00:00:00";
            while ($readerItem) {
                $reader = $readerManager->getLatestReaderForUserByID($readerItem->getItemID(), $userID);
                if (empty($reader)) {
                    if ($date < $readerItem->getModificationDate()) {
                        $new = true;
                        $changed = false;
                        $date = $readerItem->getModificationDate();
                    }
                } elseif ($reader['read_date'] < $readerItem->getModificationDate()) {
                    if ($date < $readerItem->getModificationDate()) {
                        $new = false;
                        $changed = true;
                        $date = $readerItem->getModificationDate();
                    }
                }
                $readerItem = $itemList->getNext();
            }

            if ($new) {
                $return = self::READ_STATUS_CHANGED;
            } else if ($changed) {
                $return = self::READ_STATUS_CHANGED;
            }
        }

        return $return;
    }

    public function getLatestReaderForUserByID($itemId, $userId)
    {
        $this->readerManager->resetLimits();
        return $this->readerManager->getLatestReaderForUserByID($itemId, $userId);
    }
}
