<?php


namespace App\Metrics\Collectors;


use App\Metrics\DataCollection;

interface MetricCollectorInterface
{
    public function getDataCollection(): DataCollection;
}