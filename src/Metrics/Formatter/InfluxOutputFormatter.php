<?php


namespace App\Metrics\Formatter;


use App\Metrics\DataCollection;
use App\Metrics\DataPoint;
use InfluxDB\Client;
use InfluxDB\Database;
use InfluxDB\Point;

class InfluxOutputFormatter implements OutputFormatterInterface
{
    /**
     * @var Database
     */
    private $database;

    public function __construct(string $host, string $port, string $database, string $user, string $password)
    {
        $this->database = Client::fromDSN(sprintf('influxdb://%s:%s@%s:%s/%s', $user, $password, $host, $port, $database));
    }

    public function format(DataCollection $dataCollection): string
    {
        $point = new Point($dataPoint->getName());
        $point->setTimestamp(microtime());

        foreach ($dataCollection as $dataPoint) {
            /** @var DataPoint $dataPoint */

            $point->setTags(['portal' => 'some portal']);
            $point->setFields();

        }

        $this->database->writePoints([$point], Database::PRECISION_MILLISECONDS);

        return '';
    }

}