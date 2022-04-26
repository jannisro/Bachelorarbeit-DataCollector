<?php

namespace DataCollector\Energy;

use DataCollector\EntsoeAdapter;

class InstalledCapacities extends EntsoEAdapter
{

    private bool $dryRun;

    /**
     * Requests and stores installed capacites of all countries, identified by PSR type
     * @param \DateTimeImmutable $date Date of the year for which data should be queried
     * @param bool $dryRun true=No data is stored and method is run for test purposes
     */
    public function __invoke(\DateTimeImmutable $date, bool $dryRun = false): void
    {
        $this->dryRun = $dryRun;
        foreach (parent::COUNTRIES as $countryKey => $country) {
            if ($this->isDataNotPresent('electricity_installed_capacities', $countryKey, $date->format('Y'))) {
                $response = $this->makeGetRequest([
                    'documentType' => 'A68',
                    'processType' => 'A33',
                    'in_domain' => $country,
                    'periodStart' => \DateTime::createFromImmutable($date)->modify('-1 year')->format('Y12312300'),
                    'periodEnd' => $date->format('Y12312300')
                ]);
                if (!is_null($response)) {
                    $this->storeResultInDatabase($response, $countryKey, $date);
                }
            }
        }
        echo 'Done';
    }


    protected function isDataNotPresent(string $tableName, string $countryKey, string $date): bool
    {
        $res = $this->getDb()->query("SELECT * FROM `$tableName` WHERE `country` = '$countryKey' AND `year` = '$date'");
        return $res && $res->num_rows === 0;
    }


    private function storeResultInDatabase(\SimpleXMLElement $response, string $countryKey, \DateTimeImmutable $date): void
    {
        // When TimeSeries is present and dry run is deactivated
        if ($response->TimeSeries && $this->dryRun === false) {
            // Add all PSR capacity values to the database
            foreach ($this->getAllPsrValues($response) as $psrType => $capacityAmount) {
                $this->insertIntoDb('electricity_installed_capacities', [
                    'country' => $countryKey,
                    'year' => $date->format('Y'),
                    'psr_type' => $psrType,
                    'value' => $capacityAmount,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
        elseif ($this->dryRun === true) {
            echo "<p>Capacity data from " . $date->format('Y-m-d') . " for country '$countryKey' would have been inserted into database (DryRun is activated)</p>";
        }
    }


    /**
     * Takes XML response from EntsoE and returns an array with the generated sum per PSR type
     * @param \SimpleXMLElement $xml Response from the EntsoE API
     * @return array Associative array of format ['psr_code' => total_capacity_in_mw] 
     */
    private function getAllPsrValues(\SimpleXMLElement $xml): array
    {
        $result = [];
        // Iterate over TimeSeries
        $seriesIndex = 0;
        while ($series = $xml->TimeSeries[$seriesIndex]) {
            $psr = $series->MktPSRType->psrType->__toString();
            $result[$psr] = intval($series->Period->Point->quantity->__toString());
            $seriesIndex++;
        }
        return $result;
    }

}