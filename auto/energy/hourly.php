<?php

require __DIR__ . '/../../vendor/autoload.php';

use DataCollector\DatabaseAdapter;
use Symfony\Component\Dotenv\Dotenv;


// Parse .env file with configuration
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../../.env');

class CurrentFetch extends DatabaseAdapter
{

    public function __invoke()
    {
        $this->runDbMultiQuery("DELETE FROM `electricity_generation` WHERE `datetime` LIKE '0000-%'");
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
            (new $call)(new \DateTimeImmutable());
            sleep(5);
        }
        (new DataCollector\Energy\NetPosition)(new \DateTimeImmutable());
    }

}

(new CurrentFetch)();