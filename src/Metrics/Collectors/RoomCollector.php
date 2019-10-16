<?php


namespace App\Metrics\Collectors;


use App\Metrics\DataCollection;
use App\Metrics\DataPoint;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;

class RoomCollector implements MetricCollectorInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getDataCollection(): DataCollection
    {
        $collection = new DataCollection();

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('count', 'count');

        $query = $this->entityManager->createNativeQuery('
            SELECT
COUNT(room.item_id),
portal.title,
room.type
            FROM room
            INNER JOIN
            portal
            ON
            room.context_id = portal.item_id
            WHERE
                room.deleter_id IS NULL AND
                room.deletion_date IS NULL AND
                portal.deleter_id IS NULL AND
                portal.deletion_date IS NULL
GROUP BY
portal.title,
room.type
        ', $rsm);

        $now = new \DateTime('now');
        $query->setParameter('threshold', $now->sub(new \DateInterval('PT5M'))->format('Y-m-d H:i:s'));

        $activeSessions = $query->getResult();
        if ($activeSessions) {
            $collection->addDatapoint(new DataPoint('rooms', $activeSessions[0]['count']));
        }

        return $collection;
    }
}