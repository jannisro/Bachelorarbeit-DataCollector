<?php

require __DIR__ . '/../../vendor/autoload.php';

use DataCollector\DatabaseAdapter;
use Symfony\Component\Dotenv\Dotenv;


// Parse .env file with configuration
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../../.env');

class HistoryFetch extends DatabaseAdapter
{

    public function __invoke()
    {
        $minDate = $this->runDbQuery(
            "SELECT `datetime` 
            FROM `electricity_generation` 
            ORDER BY `datetime` ASC 
            LIMIT 1"
        )[0]['datetime'];
        $dt = (new \DateTime($minDate))->modify('-1 day');
        if ($dt) {
            echo 'Collect data for ' . $dt->format('Y-m-d');
            $classes = [
                "ElectricityPrice",
                "CommercialFlow",
                "PhysicalFlow",
                "Generation",
                "Load",
                "ForecastedLoad",
                "NetTransferCapacity"
            ];
            foreach ($classes as $class) {
                $call = "DataCollector\\Energy\\$class";
                (new $call)(\DateTimeImmutable::createFromMutable($dt));
                sleep(5);
            }
            (new DataCollector\Energy\NetPosition)(\DateTimeImmutable::createFromMutable($dt));
        }
    }

}

(new HistoryFetch)();