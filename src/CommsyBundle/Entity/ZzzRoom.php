<?php

namespace CommsyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ZzzRoom
 *
 * @ORM\Table(name="zzz_room", indexes={@ORM\Index(name="context_id", columns={"context_id"}), @ORM\Index(name="creator_id", columns={"creator_id"}), @ORM\Index(name="type", columns={"type"}), @ORM\Index(name="activity", columns={"activity"}), @ORM\Index(name="room_description", columns={"room_description"}), @ORM\Index(name="contact_persons", columns={"contact_persons"}), @ORM\Index(name="title", columns={"title"}), @ORM\Index(name="modifier_id", columns={"modifier_id"}), @ORM\Index(name="status", columns={"status"}), @ORM\Index(name="lastlogin", columns={"lastlogin"})})
 * @ORM\Entity
 */
class ZzzRoom
{
    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $itemId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="context_id", type="integer", nullable=true)
     */
    private $contextId;

    /**
     * @var integer
     *
     * @ORM\Column(name="creator_id", type="integer", nullable=false)
     */
    private $creatorId = '0';

    /**
     * @var integer
     *
     * @ORM\Column(name="modifier_id", type="integer", nullable=true)
     */
    private $modifierId;

    /**
     * @var integer
     *
     * @ORM\Column(name="deleter_id", type="integer", nullable=true)
     */
    private $deleterId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=false)
     */
    private $creationDate = '0000-00-00 00:00:00';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modification_date", type="datetime", nullable=false)
     */
    private $modificationDate = '0000-00-00 00:00:00';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="deletion_date", type="datetime", nullable=true)
     */
    private $deletionDate;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="extras", type="text", length=16777215, nullable=true)
     */
    private $extras;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=20, nullable=false)
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="activity", type="integer", nullable=false)
     */
    private $activity = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=20, nullable=false)
     */
    private $type = 'project';

    /**
     * @var boolean
     *
     * @ORM\Column(name="public", type="boolean", nullable=false)
     */
    private $public = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_open_for_guests", type="boolean", nullable=false)
     */
    private $isOpenForGuests = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="continuous", type="boolean", nullable=false)
     */
    private $continuous = '-1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="template", type="boolean", nullable=false)
     */
    private $template = '-1';

    /**
     * @var string
     *
     * @ORM\Column(name="contact_persons", type="string", length=255, nullable=true)
     */
    private $contactPersons;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", length=65535, nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="room_description", type="string", length=10000, nullable=true)
     */
    private $roomDescription;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="lastlogin", type="datetime", nullable=true)
     */
    private $lastlogin;


}
