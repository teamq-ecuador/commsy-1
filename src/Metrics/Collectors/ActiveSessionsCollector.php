<?php


namespace App\Metrics\Collectors;


use App\Metrics\DataCollection;
use App\Metrics\DataPoint;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;

class ActiveSessionsCollector implements MetricCollectorInterface
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
            SELECT COUNT(id) AS count
            FROM session
            WHERE created >= :threshold
        ', $rsm);

        $now = new \DateTime('now');
        $query->setParameter('threshold', $now->sub(new \DateInterval('PT5M'))->format('Y-m-d H:i:s'));

        $activeSessions = $query->getResult();
        if ($activeSessions) {
            $collection->addDatapoint(new DataPoint('active_sessions', $activeSessions[0]['count']));
        }

        return $collection;
    }
}