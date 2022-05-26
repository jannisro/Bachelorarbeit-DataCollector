<?php

namespace DataCollector\Energy;

use DataCollector\DatabaseAdapter;

class ResultStoreHelper extends DatabaseAdapter
{

    private array $nationalResult = [];
    private array $internationalResult = [];

    public function __construct()
    {
        parent::__construct();
    }


    public function addNationalValue(\DateTimeImmutable $datetime, string $country, array $value): void
    {
        $this->nationalResult[$country][$datetime->format('Y-m-d H:i')][$value[0]] = $value[1];
    }


    public function addInternationalValue(\DateTimeImmutable $datetime, array $countries, array $value): void
    {
        $this->internationalResult[$countries[0]][$countries[1]][$datetime->format('Y-m-d H:i')][$value[0]] = $value[1];
    }


    public function storeValues(): void
    {
        /*if (count($this->nationalResult) > 0) {
            $this->storeNationalResult();
        }*/
        if (count($this->internationalResult) > 0) {
            $this->storeInternationalResult();
        }
    }


    private function storeNationalResult(): void
    {
        $baseQuery = "INSERT INTO `electricity_history_national` 
            (`datetime`, `country`, `net_position`, `price`, `total_generation`, `load`, `load_forecast`, `created_at`)
            VALUES ";
        $valueQueries = [];
        foreach ($this->nationalResult as $country => $datetimes) {
            foreach ($datetimes as $datetime => $values) {
                $netPos = floatval($values['total_generation']) - floatval($values['load']);
                $valueQueries[] = "(
                    '$datetime', 
                    '$country', 
                    '$netPos', 
                    '{$values['price']}', 
                    '{$values['total_generation']}', 
                    '{$values['load']}', 
                    '{$values['load_forecast']}', 
                    '".date('Y-m-d H:i:s')."'
                )";
            }
        }
        $this->runDbMultiQuery($baseQuery . implode(', ', $valueQueries));
    }


    private function storeInternationalResult(): void
    {
        $baseQuery = "INSERT INTO `electricity_history_international` 
            (`datetime`, `start_country`, `end_country`, `commercial_flow`, `physical_flow`, `net_transfer_capacity`, `created_at`)
            VALUES ";
        $valueQueries = [];
        foreach ($this->internationalResult as $startCountry => $endCountries) {
            foreach ($endCountries as $endCountry => $datetimes) {
                foreach ($datetimes as $datetime => $values) {
                    $valueQueries[] = "(
                        '$datetime', 
                        '$startCountry', 
                        '$endCountry', 
                        '{$values['commercial_flow']}', 
                        '{$values['physical_flow']}', 
                        '{$values['net_transfer_capacity']}',
                        '".date('Y-m-d H:i:s')."'
                    )";
                }
            }
        }
        $this->runDbMultiQuery($baseQuery . implode(', ', $valueQueries));
    }


}