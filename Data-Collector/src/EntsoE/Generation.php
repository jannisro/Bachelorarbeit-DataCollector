<?php

namespace DataCollector\EntsoE;

use Exception;

class Generation extends EntsoeAdapter
{

    private bool $dryRun;

    /**
     * Requests and stores elctricity generation of all countries, identified by PSR type
     * @param \DateTime $date Date for which data should be queried
     */
    public function actualGenerationPerType(\DateTimeImmutable $date, bool $dryRun = false): void
    {
        $this->dryRun = $dryRun;
        foreach (parent::COUNTRIES as $countryKey => $country) {
            if ($this->isDataNotPresent($countryKey, $date->format('Y-m-d'))) {
                // Fetch data from yesterday
                $response = $this->makeGetRequest([
                    'documentType' => 'A75',
                    'processType' => 'A16',
                    'in_domain' => $country,
                    'periodStart' => \DateTime::createFromImmutable($date)->modify('-1 day')->format('Ymd2300'),
                    'periodEnd' => $date->format('Ymd2300')
                ]);
                if (!is_null($response)) {
                    $this->storeResultInDatabase($response, $countryKey, $date);
                }
                else {
                    print_r("No valid response received for country $countryKey and date " . $date->format('d.m.Y'));
                }
            }
        }
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


    private function storeResultInDatabase(\SimpleXMLElement $response, string $countryKey, \DateTimeImmutable $date): void
    {
        // When TimeSeries is present and dry run is deactivated
        if ($response->TimeSeries && $this->dryRun === false) {
            // Add all PSR generation values to the database
            foreach ($this->getAllPsrValues($response) as $psrType => $generationAmount) {
                $this->insertIntoDb('generation', [
                    'country' => $countryKey,
                    'date' => $date->format('Y-m-d'),
                    'psr_type' => $psrType,
                    'amount' => $generationAmount,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
            echo "<p>Generation data from " . $date->format('Y-m-d') . " for country '$countryKey' have been inserted into database</p>";
        }
        elseif ($this->dryRun === true) {
            echo "<p>Generation data from " . $date->format('Y-m-d') . " for country '$countryKey' would have been inserted into database (DryRun is activated)</p>";
        }
    }


    /**
     * Checks whether the datbase already contains data for a given country and date
     */
    private function isDataNotPresent(string $countryKey, string $date): bool
    {
        $res = $this->getDb()->query("SELECT * FROM `generation` WHERE `country` = '$countryKey' AND `date` = '$date'");
        return $res->num_rows === 0;
    }

}