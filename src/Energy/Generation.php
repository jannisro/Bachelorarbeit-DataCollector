<?php

namespace DataCollector\Energy;

use DataCollector\EntsoEAdapter;

class Generation extends EntsoEAdapter
{

    private bool $dryRun;

    /**
     * Requests and stores elctricity generation of all countries, identified by PSR type
     * @param \DateTimeImmutable $date Date for which data should be queried
     * @param bool $dryRun true=No data is stored and method is run for test purposes
     */
    public function actualGenerationPerType(\DateTimeImmutable $date, bool $dryRun = false): void
    {
        $this->dryRun = $dryRun;
        foreach (parent::COUNTRIES as $countryKey => $country) {
            if ($this->isDataNotPresent('generation', $countryKey, $date->format('Y-m-d'))) {
                // Fetch data of date
                $response = $this->makeGetRequest([
                    'documentType' => 'A75',
                    'processType' => 'A16',
                    'in_domain' => $country,
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
            // Iterate through hourly values of each PSR and insert them into DB
            foreach ($this->psrTypesWithHourlyValues($response) as $psrName => $hourlyValues) {
                foreach ($hourlyValues as $value) {
                    $this->insertIntoDb('electricity_generation', [
                        'country' => $countryKey,
                        'date' => $date->format('Y-m-d'),
                        'psr_type' => $psrName,
                        'value' => $value,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
        }
        elseif ($this->dryRun === true) {
            echo "<p>Generation data from " . $date->format('Y-m-d') . " for country '$countryKey' would have been inserted into database (DryRun is activated)</p>";
        }
    }


    /**
     * Returns hourly values of each PSR type ([psr1 => [hourlyValues], psr2 => [hourlyValues], ...])
     */
    private function psrTypesWithHourlyValues(\SimpleXMLElement $xml): array
    {
        $result = [];
        foreach ($this->psrTypesWithRawValues($xml) as $psrName => $rawValues) {
            // Raw values are hourly => No need for processing
            if (count($rawValues) === 24) {
                $result[$psrName] = $rawValues;
            }
            // Raw values are quarter hourly => Aggregate to hourly
            else if (count($rawValues) === 96) {
                $result[$psrName] = $this->aggregateValues($rawValues, 4);
            }
            // Raw values are half hourly => Aggregate to hourly
            else if (count($rawValues) === 48) {
                $result[$psrName] = $this->aggregateValues($rawValues, 2);
            }
        }
        return $result;
    }


    /**
     * Parses XML response to an array of format [psr1 => [values], psr2 => [values]]
     */
    private function psrTypesWithRawValues(\SimpleXMLElement $xml): array
    {
        $result = [];

        return $result;
    }


    /**
     * Sums up each n elements in an array
     */
    private function aggregateValues(array $values, int $elementsToUnite): array {
        $result = array_fill(0, 24, 0);
        $currentIndexInResult = $currentlyUnitedElements = 0;
        foreach ($values as $value) {
            $result[$currentIndexInResult] += $value;
            if (++$currentlyUnitedElements === $elementsToUnite) {
                ++$currentIndexInResult;
            }
        }
        return $result;
    }





    /**
     * Takes XML response from EntsoE and returns an array with the generated sum per PSR type
     * @param \SimpleXMLElement $xml Response from the EntsoE API
     * @return array Associative array of format ['psr_code' => total_generation_in_mw] 
     */
    private function getAllPsrValues(\SimpleXMLElement $xml): array
    {
        $result = [];
        // Iterate over TimeSeries
        $seriesIndex = 0;
        while ($series = $xml->TimeSeries[$seriesIndex]) {
            // Create PSR in array if not present
            $psr = $series->MktPSRType->psrType->__toString();
            if (!isset($result[$psr])) $result[$psr] = 0;
            // Iiterate over Points in TimeSeries
            $pointIndex = 0;
            while ($point = $series->Period->Point[$pointIndex]) {
                // Add Generation to sum
                $result[$psr] += intval($point->quantity->__toString());
                $pointIndex++;
            }
            $seriesIndex++;
        }
        return $result;
    }

}