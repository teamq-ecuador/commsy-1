<?php


namespace App\Metrics\Formatter;


use App\Metrics\DataCollection;
use App\Metrics\DataPoint;

interface OutputFormatterInterface
{
    public function format(DataCollection $dataCollection): string;
}