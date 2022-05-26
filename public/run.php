<?php

ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';

use DataCollector\DatabaseAdapter;
use Symfony\Component\Dotenv\Dotenv;


// Parse .env file with configuration
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');


class Run extends DatabaseAdapter
{

    public function __construct()
    {
        parent::__construct();
        echo '<p>Start</p>';


        
        /*foreach ($this->runDbQuery("SELECT * FROM `electricity_load` WHERE `datetime` > '2022-05-24 23:00'") as $row) {
            $netPos = $this->runDbQuery("SELECT `value` FROM `electricity_net_positions` WHERE country='{$row['country']}' AND `datetime`='{$row['datetime']}'");
            $price = $this->runDbQuery("SELECT `value` FROM `electricity_prices` WHERE country='{$row['country']}' AND `datetime`='{$row['datetime']}'");
            $generation = $this->runDbQuery("SELECT SUM(`value`) AS `sum` FROM `electricity_generation` WHERE country='{$row['country']}' AND `datetime`='{$row['datetime']}'");
            $this->insertIntoDb('electricity_history_national', [
                'country' => $row['country'],
                'datetime' => $row['datetime'],
                'net_position' => ($netPos && count($netPos) > 0) ? $netPos[0]['value'] : 0,
                'price' => ($price && count($price) > 0) ? $price[0]['value'] : 0,
                'total_generation' => ($generation && count($generation) > 0) ? $generation[0]['sum'] : 0,
                'load' => $row['value'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }*/


        /*foreach ($this->runDbQuery("SELECT * FROM `electricity_flow_commercial` WHERE `datetime` > '2022-05-24 23:00'") as $row) {
            $physical = $this->runDbQuery("SELECT `value` FROM `electricity_flow_physical` WHERE country_start='{$row['country_start']}' AND country_end='{$row['country_end']}' AND `datetime`='{$row['datetime']}'");
            $ntc = $this->runDbQuery("SELECT `value` FROM `electricity_net_transfer_capacities` WHERE country_start='{$row['country_start']}' AND country_end='{$row['country_end']}' AND `datetime`='{$row['datetime']}'");
            $this->insertIntoDb('electricity_history_international', [
                'start_country' => $row['country_start'],
                'end_country' => $row['country_end'],
                'datetime' => $row['datetime'],
                'physical_flow' => ($physical && count($physical) > 0) ? $physical[0]['value'] : 0,
                'net_transfer_capacity' => ($ntc && count($ntc) > 0) ? $ntc[0]['value'] : 0,
                'commercial_flow' => $row['value'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }*/


        echo '<p>Finish</p>0';
    }

}

new Run;