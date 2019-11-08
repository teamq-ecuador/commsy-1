<?php


namespace App\Metrics;


class DataCollection implements \IteratorAggregate
{
    /**
     * @var DataPoint[]
     */
    private $datapoints = [];

    public function addDatapoint(DataPoint $dataPoint)
    {
        $this->datapoints[] = $dataPoint;
    }

    public function addCollection(DataCollection $dataCollection)
    {
        foreach ($dataCollection as $dataPoint) {
            $this->addDatapoint($dataPoint);
        }
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->datapoints);
    }
}