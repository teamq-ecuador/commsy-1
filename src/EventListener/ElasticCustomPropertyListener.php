<?php

namespace App\EventListener;

use Elastica\Pipeline;
use Elastica\Processor\Attachment;
use FOS\ElasticaBundle\Elastica\Client;
use FOS\ElasticaBundle\Event\IndexPopulateEvent;
use FOS\ElasticaBundle\Event\IndexResetEvent;
use FOS\ElasticaBundle\Event\TransformEvent;
use FOS\ElasticaBundle\Persister\Event\Events;
use FOS\ElasticaBundle\Persister\Event\PrePersistEvent;
use App\Services\LegacyEnvironment;
use FOS\ElasticaBundle\Persister\ObjectPersister;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;



class ElasticCustomPropertyListener implements EventSubscriberInterface
{
    private $legacyEnvironment;

    private $itemCache = [];

    private $client;

    private $pipeline;

    public function __construct(LegacyEnvironment $legacyEnvironment, Client $client)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->client = $client;
    }

    public static function getSubscribedEvents()
    {
        return [
            IndexResetEvent::POST_INDEX_RESET => 'onPostIndexReset', // DEBUG
            TransformEvent::POST_TRANSFORM => 'addCustomProperty',
            Events::PRE_PERSIST => 'onPrePersist',
        ];
    }

    // DEBUG
    public function onPostIndexReset(IndexResetEvent $event)
    {
        // NOTE: this seems to get called once for an index populate request
//        var_dump($event);
    }


    public function onPrePersist(PrePersistEvent $event)
    {
        $options = $event->getOptions();
        if($options['indexName'] !== 'commsy_room' && $options['indexName'] !== 'commsy_user'){
            /** @var ObjectPersister $objectPersister */
            $objectPersister = $event->getObjectPersister();
            $objectPersister->setOption('pipeline', 'attachment');
        }
    }


    public function addCustomProperty(TransformEvent $event)
    {
        $fields = $event->getFields();
        if (isset($fields['hashtags'])) {
            $this->addHashtags($event);
        }

        if (isset($fields['tags'])) {
            $this->addTags($event);
        }

        if (isset($fields['annotations'])) {
            $this->addAnnotations($event);
        }

        if (isset($fields['files'])) {
            $this->addFilesContent($event);
        }

        if (isset($fields['discussionarticles'])) {
            $this->addDiscussionArticles($event);
        }

        if (isset($fields['steps'])) {
            $this->addSteps($event);
        }

        if (isset($fields['sections'])) {
            $this->addSections($event);
        }

        if (isset($fields['parentId'])) {
            $this->addParentRoomIds($event);
        }
    }

    private function addHashtags(TransformEvent $event)
    {
        $item = $this->getItemCached($event->getObject()->getItemId());

        if ($item) {
            $hashtags = $item->getBuzzwordList();
            if ($hashtags->isNotEmpty()) {
                $objectHashtags = [];

                $hashtag = $hashtags->getFirst();
                while ($hashtag) {
                    if (!$hashtag->isDeleted()) {
                        $objectHashtags[] = $hashtag->getName();
                    }

                    $hashtag = $hashtags->getNext();
                }

                if (!empty($objectHashtags)) {
                    $event->getDocument()->set('hashtags', $objectHashtags);
                }
            }
        }
    }

    private function getItemCached($itemId)
    {
        // cache wiping
        if (sizeof($this->itemCache) >= 10000) {
            $this->itemCache = [];
        }

        // cache hit
        if (isset($this->itemCache[$itemId])) {
            return $this->itemCache[$itemId];
        }

        // cache miss
        $itemManager = $this->legacyEnvironment->getItemManager();
        $item = $itemManager->getItem($itemId);

        if ($item) {
            $this->itemCache[$itemId] = $item;
            return $item;
        }

        return null;
    }

    private function addTags(TransformEvent $event)
    {
        $item = $this->getItemCached($event->getObject()->getItemId());

        if (!$item) {
            return;
        }
    }

    private function addAnnotations(TransformEvent $event)
    {
        $item = $this->getItemCached($event->getObject()->getItemId());

        if ($item) {
            $annotations = $item->getAnnotationList();
            if ($annotations->isNotEmpty()) {
                $objectTags = [];

                $annotation = $annotations->getFirst();
                while ($annotation) {
                    if (!$annotation->isDeleted()) {
                        $objectTags[] = $annotation->getDescription();
                    }

                    $annotation = $annotations->getNext();
                }

                if (!empty($objectTags)) {
                    $event->getDocument()->set('annotations', $objectTags);
                }
            }
        }
    }

    private function addFilesContent(TransformEvent $event)
    {
        $item = $this->getItemCached($event->getObject()->getItemId());

        if ($item) {
            $fileContents = [];

            $files = $item->getFileList();

            if ($files->isNotEmpty()) {
                $file = $files->getFirst();

                while ($file) {
                    if (!$file->isDeleted()) {
                        $fileSize = $file->getFileSize();

                        if (round($fileSize / 1024) < 25) {
                            $content = $file->getContentBase64();
                            if (!empty($content)) {
                                $fileContents[] = $content;
                            }
                        }
                    }

                    $file = $files->getNext();
                }
            }

            $event->getDocument()->set('files', $fileContents);


            $this->attachFile($files, $event);
        }
    }

    public function createPipeline(Client $client){
        if(!$this->pipeline){
            $attachment = new Attachment('file_data');
            // TODO: Better create pipeline in response to a rarely called event? And only create the pipeline if it's not in getPipelines() yet
            $pipeline = new Pipeline($this->client);
            $pipeline->setId('attachment');
            $pipeline->setDescription('attachment pipeline');
            $pipeline->addProcessor($attachment);
            $pipeline->create();
        }
    }

    public function attachFile($files, $event){

        if($files->isNotEmpty()){
            $file = $files->getFirst();
            $filepath = $file->_data['filepath'];
            $doc = $event->getDocument();
            // TODO: how can we add multiple files per document?
            $doc->addFile('file_data', $filepath);
        }
    }

    public function addDiscussionArticles($event)
    {
        $discussionManager = $this->legacyEnvironment->getDiscussionManager();
        $discussion = $discussionManager->getItem($event->getObject()->getItemId());

        if ($discussion) {
            $articles = $discussion->getAllArticles();
            if ($articles->isNotEmpty()) {
                $articleContents = [];

                $article = $articles->getFirst();
                while ($article) {
                    if (!$article->isDeleted() && !$article->isDraft()) {
                        $articleContents[] = [
                            'subject' => $article->getSubject(),
                            'description' => $article->getDescription(),
                        ];
                    }

                    $article = $articles->getNext();
                }

                if (!empty($articleContents)) {
                    $event->getDocument()->set('discussionarticles', $articleContents);
                }
            }
        }
    }

    public function addSteps($event)
    {
        $todoManager = $this->legacyEnvironment->getTodoManager();
        $todo = $todoManager->getItem($event->getObject()->getItemId());

        if ($todo) {
            // when building the index from the CLI command, the context ID is not populated, thus we set it here explicitly
            $this->legacyEnvironment->setCurrentContextID($todo->getContextID());

            $steps = $todo->getStepItemList();
            if ($steps->isNotEmpty()) {
                $stepContents = [];

                $step = $steps->getFirst();
                while ($step) {
                    if (!$step->isDeleted() && !$step->isDraft()) {
                        $stepContents[] = [
                            'title' => $step->getTitle(),
                            'description' => $step->getDescription(),
                        ];
                    }

                    $step = $steps->getNext();
                }

                if (!empty($stepContents)) {
                    $event->getDocument()->set('steps', $stepContents);
                }
            }
        }
    }

    public function addSections($event)
    {
        $materialManager = $this->legacyEnvironment->getMaterialManager();
        $material = $materialManager->getItem($event->getObject()->getItemId());

        if ($material) {
            $sections = $material->getSectionList();
            if ($sections->isNotEmpty()) {
                $sectionContents = [];

                $section = $sections->getFirst();
                while ($section) {
                    if (!$section->isDeleted() && !$section->isDraft()) {
                        $sectionContents[] = [
                            'title' => $section->getTitle(),
                            'description' => $section->getDescription(),
                        ];
                    }

                    $section = $sections->getNext();
                }

                if (!empty($sectionContents)) {
                    $event->getDocument()->set('steps', $sectionContents);
                }
            }
        }
    }

    public function addParentRoomIds($event)
    {
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $room = $roomManager->getItem($event->getObject()->getItemId());

        if ($room) {
            if ($room instanceof \cs_project_item) {
                $communityRooms = $room->getCommunityList();

                if ($communityRooms->isNotEmpty()) {
                    $parentIds = [];

                    $communityRoom = $communityRooms->getFirst();

                    while ($communityRoom) {
                        $parentIds[] = $communityRoom->getItemId();

                        $communityRoom = $communityRooms->getNext();
                    }

                    if (!empty($parentIds)) {
                        $event->getDocument()->set('parentId', $parentIds);
                    }
                }
            }
        }
    }

}