<?php

namespace DataCollector\Energy;

use DataCollector\EntsoEAdapter;

class Load extends EntsoEAdapter
{

    private bool $dryRun;

    /**
     * Requests and stores elctricity load of all countrie
     * @param \DateTimeImmutable $date Date for which data should be queried
     * @param bool $dryRun true=No data is stored and method is run for test purposes
     */
    public function actualLoad(\DateTimeImmutable $date, bool $dryRun = false): void
    {
        $this->dryRun = $dryRun;
        foreach (parent::COUNTRIES as $countryKey => $country) {
            if ($this->isDataNotPresent('electricity_load', $countryKey, $date->format('Y-m-d')) || $dryRun) {
                // Fetch data of date
                $response = $this->makeGetRequest([
                    'documentType' => 'A65',
                    'processType' => 'A16',
                    'outBiddingZone_Domain' => $country,
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


    /**
     * Requests and stores elctricity load forecast of all countrie
     * @param \DateTimeImmutable $date Date for which data should be queried
     * @param bool $dryRun true=No data is stored and method is run for test purposes
     */
    public function forecastedLoad(\DateTimeImmutable $date, bool $dryRun = false): void
    {
        $this->dryRun = $dryRun;
        foreach (parent::COUNTRIES as $countryKey => $country) {
            if ($this->isDataNotPresent('electricity_load_forecast', $countryKey, $date->format('Y-m-d')) || $dryRun) {
                // Fetch data of date
                $response = $this->makeGetRequest([
                    'documentType' => 'A65',
                    'processType' => 'A01',
                    'outBiddingZone_Domain' => $country,
                    'periodStart' => \DateTime::createFromImmutable($date)->modify('-1 day')->format('Ymd2200'),
                    'periodEnd' => $date->format('Ymd2200')
                ]);
                if (!is_null($response)) {
                    $this->storeResultInDatabase($response, $countryKey, $date, '_forecast');
                }
            }
        }
        echo 'Done';
    }


    private function storeResultInDatabase(\SimpleXMLElement $response, string $countryKey, \DateTimeImmutable $date, string $tableSuffix = ""): void
    {
        // When TimeSeries is present and dry run is deactivated
        if ($response->TimeSeries && $this->dryRun === false) {
            // Iterate through hourly values of each PSR and insert them into DB
            foreach ($this->hourlyValues($response) as $hourlyValue) {
                $time = 0;
                $this->insertIntoDb("electricity_load$tableSuffix", [
                    'country' => $countryKey,
                    'datetime' => $date->format('Y-m-d') . "$time:00",
                    'value' => $hourlyValue,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                ++$time;
            }
        }
        elseif ($this->dryRun === true) {
            print_r($this->hourlyValues($response));
            echo "<p>Load data from " . $date->format('Y-m-d') . " for country '$countryKey' would have been inserted into database (DryRun is activated)</p>";
        }
        else {
            echo "<p>Failed to receive load data for country '$countryKey'</p>";
        }
    }


    /**
     * Returns hourly values of each PSR type ([psr1 => [hourlyValues], psr2 => [hourlyValues], ...])
     */
    private function hourlyValues(\SimpleXMLElement $xml): array
    {
        $result = [];
        $rawValues = $this->rawValues($xml);
        // Raw values are hourly => No need for processing
        if (count($rawValues) === 24) {
            $result = $rawValues;
        }
        // Raw values are quarter hourly => Aggregate to hourly
        else if (count($rawValues) === 96) {
            $result = $this->aggregateValues($rawValues, 4);
        }
        // Raw values are half hourly => Aggregate to hourly
        else if (count($rawValues) === 48) {
            $result = $this->aggregateValues($rawValues, 2);
        }
        // Incomplete dataset => Process anyway to keep existing data
        else if (count($rawValues) < 24) {
            $result = $this->aggregateValues($rawValues, 1);
        }
        return $result;
    }


    /**
     * Parses XML response to an array of format [psr1 => [values], psr2 => [values]]
     */
    private function rawValues(\SimpleXMLElement $xml): array
    {
        $result = [];
        // Iterate over TimeSeries
        $seriesIndex = 0;
        while ($series = $xml->TimeSeries[$seriesIndex]) {
            // Iiterate over Points in TimeSeries
            $pointIndex = 0;
            while ($point = $series->Period->Point[$pointIndex]) {
                $result[] = floatval($point->quantity->__toString());
                $pointIndex++;
            }
            $seriesIndex++;
        }
        return $result;
    }

}