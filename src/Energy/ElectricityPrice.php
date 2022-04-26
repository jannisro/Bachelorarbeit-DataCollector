<?php

namespace DataCollector\Energy;

use DataCollector\EntsoEAdapter;

class ElectricityPrice extends EntsoEAdapter
{

    private bool $dryRun;

    /**
     * Requests and stores elctricity prices of all countries
     * @param \DateTimeImmutable $date Date for which data should be queried
     * @param bool $dryRun true=No data is stored and method is run for test purposes
     */
    public function dayAheadPrices(\DateTimeImmutable $date, bool $dryRun = false): void
    {
        $this->dryRun = $dryRun;
        foreach (parent::CURRENT_BIDDING_ZONES as $countryKey => $country) {
            if ($this->isDataNotPresent('electricity_prices', $countryKey, $date->format('Y-m-d')) || $dryRun) {
                // Fetch data of date
                $response = $this->makeGetRequest([
                    'documentType' => 'A44',
                    'in_Domain' => $country,
                    'out_Domain' => $country,
                    'periodStart' => \DateTime::createFromImmutable($date)->modify('-1 day')->format('Ymd2200'),
                    'periodEnd' => $date->format('Ymd2200')
                ]);
                if (!is_null($response)) {
                    $this->storeResultInDatabase($response, $countryKey, $date);
                }
            }
        }
        echo 'Done';
    }


    private function storeResultInDatabase(\SimpleXMLElement $response, string $countryKey, \DateTimeImmutable $date): void
    {
        // When TimeSeries is present and dry run is deactivated
        if ($response->TimeSeries && $this->dryRun === false) {
            // Iterate through hourly values and insert them into DB
            $time = 0;
            $rawValues = $this->xmlTimeSeriesToArray($response, 'price.amount');
            foreach ($this->aggregateHourlyValues($rawValues) as $hourlyValue) {
                $this->insertIntoDb('electricity_prices', [
                    'country' => $countryKey,
                    'datetime' => $date->format('Y-m-d') . "$time:00",
                    'value' => $hourlyValue,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                ++$time;
            }
        }
        elseif ($this->dryRun === true) {
            echo "<p>Electricity price data from " . $date->format('Y-m-d') . " for country '$countryKey' would have been inserted into database (DryRun is activated)</p>";
        }
        else {
            echo "<p>Failed to receive electricity price data for country '$countryKey'</p>";
        }
    }

}