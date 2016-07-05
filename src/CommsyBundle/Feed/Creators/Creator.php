<?php

namespace CommsyBundle\Feed\Creators;

use \Debril\RssAtomBundle\Protocol\Parser\Item;

use Symfony\Component\Translation\DataCollectorTranslator;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

abstract class Creator
{
    protected $isGuestAccess = false;
    protected $textConverter;
    protected $translator;
    protected $router;

    public function createItem($item)
    {
        if ($item->isDeleted() || $item->isNotActivated()) {
            throw new \RuntimeException("item is deleted or not active");
        }

        $feedItem = new Item();

        $feedItem->setTitle($this->getTitle($item));
        $feedItem->setUpdated(new \DateTime($item->getModificationDate()));

        if ($this->generateAuthor($item)) {
            $feedItem->setAuthor($this->getAuthor($item));
        }

        $feedItem->setDescription($this->getDescription($item));
        $feedItem->setLink($this->getLink($item));

        return $feedItem;
    }

    public function setGuestAccess($isGuestAccess)
    {
        $this->isGuestAccess = $isGuestAccess;
    }

    public function setTextConverter($textConverter)
    {
        $this->textConverter = $textConverter;
    }

    public function setTranslator(DataCollectorTranslator $translator)
    {
        $this->translator = $translator;
    }

    public function setRouter(Router $router)
    {
        $this->router = $router;
    }

    private function generateAuthor($item)
    {
        $contextItem = $item->getContextItem();
        $modifierItem = $item->getModificatorItem();

        if ($contextItem->isCommunityRoom()) {
            if ($this->isGuestAccess()) {
                return $modifierItem->isVisibleForAll();
            }
        }

        return $modifierItem->isEmailVisible();
    }

    public function getAuthor($item)
    {
        $modifierItem = $item->getModificatorItem();
        $modifierEmail = $modifierItem->getEmail();

        return $modifierEmail . ' (' . $modifierItem->getFullName() . ')';
    }

    abstract public function canCreate($rubric);

    abstract public function getTitle($item);

    abstract public function getDescription($item);

    abstract public function getLink($item);
}