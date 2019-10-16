<?php


namespace App\Metrics;


use App\Metrics\Collectors\ActiveSessionsCollector;
use App\Metrics\Collectors\ActiveUsersCollector;
use App\Metrics\Collectors\MetricCollectorInterface;
use App\Metrics\Collectors\RoomCollector;
use App\Metrics\Formatter\JsonOutputFormatter;
use App\Metrics\Formatter\OutputFormatterInterface;

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

    public function getFormattedOutput(string $format)
    {
        /** @var OutputFormatterInterface $formatter */
        $formatter = null;
        switch ($format) {
            case 'json':
                $formatter = new JsonOutputFormatter();
                return $formatter->format($this->metrics);
                break;

            default:
                throw new \Exception('No formatter for format ' . $format . ' found.');
        }
    }
}