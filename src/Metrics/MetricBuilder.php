<?php


namespace App\Metrics;


use App\Metrics\Collectors\ActiveSessionsCollector;
use App\Metrics\Collectors\ActiveUsersCollector;
use App\Metrics\Collectors\MetricCollectorInterface;
use App\Metrics\Collectors\RoomCollector;
use App\Metrics\Formatter\InfluxOutputFormatter;
use App\Metrics\Formatter\JsonOutputFormatter;
use App\Metrics\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Input\InputInterface;

class MetricBuilder
{
    /**
     * @var DataCollection
     */
    private $metrics;

    /**
     * @var MetricCollectorInterface[]
     */
    private $collectors;

    public function __construct(
        ActiveSessionsCollector $activeSessionsCollector,
        ActiveUsersCollector $activeUsersCollector,
        RoomCollector $roomCollector
    ) {
        $this->collectors[] = $activeSessionsCollector;
        $this->collectors[] = $activeUsersCollector;
        $this->collectors[] = $roomCollector;
    }

    public function collectMetrics()
    {
        $this->metrics = new DataCollection();
        foreach ($this->collectors as $collector) {
            $this->metrics->addCollection($collector->getDataCollection());
        }
    }

    public function getFormatter(InputInterface $input): OutputFormatterInterface
    {
        $format = $input->getOption('format');

        /** @var OutputFormatterInterface $formatter */
        $formatter = null;
        switch ($format) {
            case 'json':
                return new JsonOutputFormatter();

            case 'influxdb':
                if (empty($input->getOption('influx-host'))) {
                    throw new \Exception('"influx-host" option is missing');
                }
                if (empty($input->getOption('influx-port'))) {
                    throw new \Exception('"influx-port" option is missing');
                }
                if (empty($input->getOption('influx-database'))) {
                    throw new \Exception('"influx-database" option is missing');
                }
                if (empty($input->getOption('influx-user'))) {
                    throw new \Exception('"influx-user" option is missing');
                }
                if (empty($input->getOption('influx-password'))) {
                    throw new \Exception('"influx-password" option is missing');
                }

                return new InfluxOutputFormatter(
                    $input->getOption('influx-host'),
                    $input->getOption('influx-port'),
                    $input->getOption('influx-database'),
                    urlencode($input->getOption('influx-user')),
                    urlencode($input->getOption('influx-password'))
                );

            default:
                throw new \Exception('No formatter for format ' . $format . ' found.');
        }
    }

    public function getMetrics(): DataCollection
    {
        return $this->metrics;
    }
}