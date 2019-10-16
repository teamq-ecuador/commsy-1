<?php

namespace App\Command;

use App\Metrics\Collectors\ActiveSessionsCollector;
use App\Metrics\MetricBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CommsyMetricsCommand extends Command
{
    protected static $defaultName = 'commsy:metrics';

    private $metricBuilder;

    public function __construct(MetricBuilder $metricBuilder)
    {
        parent::__construct();

        $this->metricBuilder = $metricBuilder;
    }

    protected function configure()
    {
        $this
            ->setDescription('Outputs metrics for commsy')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Output format', 'json')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $this->metricBuilder->collectMetrics();
        $output->write($this->metricBuilder->getFormattedOutput($input->getOption('format')));
    }
}
