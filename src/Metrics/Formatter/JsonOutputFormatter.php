<?php


namespace App\Metrics\Formatter;


use App\Metrics\DataCollection;
use App\Metrics\DataPoint;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class JsonOutputFormatter implements OutputFormatterInterface
{
    public function format(DataCollection $dataCollection): string
    {
        $data = [];
        foreach ($dataCollection as $dataPoint) {
            /** @var DataPoint $dataPoint */
            $value = $dataPoint->getValue();

            $data[$dataPoint->getName()] = $dataPoint->getValue();
        }

        $encoder = new JsonEncoder();
        return $encoder->encode($data, JsonEncoder::FORMAT);
    }
}