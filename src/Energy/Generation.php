<?php

namespace DataCollector\Energy;

use DataCollector\EnergyAdapter;

class Generation extends EnergyAdapter
{

    /**
     * Requests and stores elctricity generation of all countries, identified by PSR type
     * @param \DateTimeImmutable $date Date for which data should be queried
     */
    public function __invoke(\DateTimeImmutable $date): void
    {
        foreach (parent::COUNTRIES as $countryKey => $country) {
            foreach (parent::PSR_TYPES as $psrCode) {
                // Fetch data of date
                $response = $this->makeGetRequest([
                    'documentType' => 'A75',
                    'processType' => 'A16',
                    'psrType' => $psrCode,
                    'in_domain' => $country,
                    'periodStart' => \DateTime::createFromImmutable($date)->modify('-1 day')->format('Ymd0000'),
                    'periodEnd' => $date->format('Ymd0000')
                ]);
                if (!is_null($response)) {
                    $this->storeResultInDatabase($psrCode, $response, $countryKey, $date);
                }
                sleep(2); # Prevent too many requests to the EntsoE
            }
            $this->sumGeneration($countryKey, $date);
        }
        $this->runDbMultiQuery("DELETE FROM `electricity_generation` WHERE `datetime` LIKE '0000-00-00%'");
    }


    private function storeResultInDatabase(string $psrCode, \SimpleXMLElement $response, string $countryKey, \DateTimeImmutable $date): void
    {
        if ($response->TimeSeries) {
            // Iterate through hourly values of each PSR and insert them into DB
            $time = 0;
            foreach ($this->xmlTimeSeriesToHourlyValues($response, 'quantity', 0) as $hourlyValue) {
                $dt = $date->format('Y-m-d') . " $time:00";
                $created = date('Y-m-d H:i:s');
                if (!str_starts_with($dt, '0000') && intval($hourlyValue) != 0) {
                    $this->runDbMultiQuery(
                        "INSERT INTO `electricity_generation` 
                        (`id`, `datetime`, `country`, `psr_type`, `value`, `created_at`)
                        VALUES ('', '$dt', '$countryKey', '$psrCode', '$hourlyValue', '$created')
                        ON DUPLICATE KEY UPDATE `value`='{$hourlyValue}'"
                    );
                }
                ++$time;
            }
        }
    }


    private function sumGeneration(string $country, \DateTimeImmutable $date): void
    {
        $dateItems = $this->runDbQuery("SELECT `datetime`, SUM(`value`) AS `sum` 
            FROM `electricity_generation` 
            WHERE `country` = '{$country}'
            AND `datetime` LIKE '{$date->format('Y-m-d')}%'
            GROUP BY `datetime`");
        foreach ($dateItems as $item) {
            $created = date('Y-m-d H:i:s');
            $this->runDbMultiQuery(
                "INSERT INTO `electricity_history_national` 
                (`id`, `datetime`, `country`, `total_generation`, `created_at`)
                VALUES ('', '{$item['datetime']}', '$country', '{$item['sum']}', '$created')
                ON DUPLICATE KEY UPDATE `total_generation`='{$item['sum']}'"
            );
        }
    }

}