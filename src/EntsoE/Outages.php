<?php

namespace DataCollector\EntsoE;

use DateTime;
use DateTimeImmutable;

class Outages extends EntsoeAdapter
{

    private bool $dryRun;
    private string $queryDate;


    /**
     * Requests and stores total grid load of all countries on a certain date
     * @param \DateTimeImmutable $date Date for which data should be queried
     * @param bool $dryRun true=No data is stored and method is run for test purposes
     */
    public function load(\DateTimeImmutable $date, bool $dryRun = false): void
    {
        $this->dryRun = $dryRun;
        $this->queryDate = $date->format('Y-m-d');
        foreach (parent::OUTAGE_ZONES as $countryKey => $country) {
            // Fetch outages of generation units
            $this->fetchData($date, $countryKey, $country, 'A80');
            // Fetch outages of production units
            $this->fetchData($date, $countryKey, $country, 'A77');
        }
        $this->emptyTmpDir();
        echo 'Done';
    }


    private function fetchData(\DateTimeImmutable $date, string $countryKey, string $country, string $documentType): void
    {
        $zipFile = $this->makeGetRequestWithZipResponse([
            'documentType' => $documentType,
            'businessType' => 'A53',
            'processType' => 'A16',
            'biddingZone_Domain' => $country,
            'periodStart' => \DateTime::createFromImmutable($date)->modify('-1 day')->format('Ymd2300'),
            'periodEnd' => $date->format('Ymd2300')
        ]);
        if (!is_null($zipFile)) {
            $xmlDirIterator = $this->zipToXml($zipFile);
            if (!is_null($xmlDirIterator)) {
                $this->handleXmlFiles($xmlDirIterator, $countryKey);
            }
        }
    }


    private function zipToXml(string $zipFilePath): ?\DirectoryIterator
    {
        $zip = new \ZipArchive;
        $zip->open($zipFilePath);
        $folderName = __DIR__ . '/../../tmp/' . mt_rand(100, 999) . time();
        if (mkDir($folderName) && $zip->extractTo($folderName)) {
            return new \DirectoryIterator($folderName);
        }
        return null;
    }


    private function handleXmlFiles(\DirectoryIterator $dirIterator, string $countryKey): void
    {
        foreach ($dirIterator as $fileInfo) {
            if (!$fileInfo->isDot()) {
                $content = file_get_contents($fileInfo->getPathname());
                if (false !== $xml = simplexml_load_string($content)) {
                    $this->saveOutage($xml, $countryKey);
                }
            }
        }
    }


    private function saveOutage(\SimpleXMLElement $xml, string $countryKey): void
    {
        if ($series = $xml->TimeSeries) {
            $period = $xml->{'unavailability_Time_Period.timeInterval'};
            $data = [
                'country' => $countryKey,
                'unit_name' => $series->{'production_RegisteredResource.pSRType.powerSystemResources.name'}->__toString(),
                'start' => (new DateTime($period->start->__toString()))->format('Y-m-d H:i'),
                'end' => (new DateTime($period->end->__toString()))->format('Y-m-d H:i'),
                'installed_capacity' => $series->{'production_RegisteredResource.pSRType.powerSystemResources.nominalP'}->__toString(),
                'available_capacity' => $series->Available_Period->Point->quantity->__toString(),
                'psr_type' => $series->{'production_RegisteredResource.pSRType.psrType'}->__toString(),
                'reason' => $xml->Reason->text->__toString()
            ];
            if (!$this->doesOutageExists($data)) {
                $this->storeResultInDatabase($data);
            }
        }
    }


    private function storeResultInDatabase(array $data): void
    {
        if ($this->dryRun === false) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['query_date'] = $this->queryDate;
            $this->insertIntoDb('outages', $data);
        }
        elseif ($this->dryRun === true) {
            echo "<p>Outage data for country '{$data['country']}' and unit '{$data['unit_name']}' ({$data['available_capacity']}MW/{$data['installed_capacity']}MW) would have been inserted into database (DryRun)</p>";
        }
    }


    private function doesOutageExists(array $data): bool
    {
        $res = $this->getDb()->query(
            "SELECT * FROM `outages` 
            WHERE `country` = '{$data['country']}' 
                AND `unit_name` = '{$data['unit_name']}'
                AND `start` = '{$data['start']}'
                AND `end` = '{$data['end']}'
                AND `available_capacity` = '{$data['available_capacity']}'"
        );
        return $res && $res->num_rows > 0;
    }


    private function emptyTmpDir(): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(__DIR__ . '/../../tmp', \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileInfo) {
            $todo = ($fileInfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileInfo->getRealPath());
        }
    }

}