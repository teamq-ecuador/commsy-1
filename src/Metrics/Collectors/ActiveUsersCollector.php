<?php


namespace App\Metrics\Collectors;


use App\Metrics\DataCollection;
use App\Metrics\DataPoint;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;

class ActiveUsersCollector implements MetricCollectorInterface
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
        $rsm->addScalarResult('month', 'month');

        $query = $this->entityManager->createNativeQuery('
            SELECT
                COUNT(*) AS count,
                MONTH(user.lastlogin) AS month
            FROM
                user
            INNER JOIN
                portal
            ON
                user.context_id = portal.item_id
            WHERE
                user.deleter_id IS NULL AND
                user.deletion_date IS NULL AND
                portal.deleter_id IS NULL AND
                portal.deletion_date IS NULL AND
                user.lastlogin >= CONCAT(YEAR(CURDATE()), \'-01-01 00:00:00\')
            GROUP BY MONTH(user.lastlogin)
        ', $rsm);

        $activeUsers = $query->getResult();
        if ($activeUsers) {
            $countByMonth = [];
            foreach ($activeUsers as $activeUser) {
                $countByMonth[] = [
                    'month' => $activeUser['month'],
                    'count' => $activeUser['count'],
                ];
            }

            $collection->addDatapoint(new DataPoint('active_users_this_year', $countByMonth));
        }

        return $collection;
    }
}