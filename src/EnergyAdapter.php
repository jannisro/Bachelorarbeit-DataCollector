<?php

namespace DataCollector;

class EnergyAdapter extends DatabaseAdapter
{

    protected string $apiUrl;

    const COUNTRIES = [
        'AL' => '10YAL-KESH-----5',
        'AT' => '10YAT-APG------L',
        'BA' => '10YBA-JPCC-----D',
        'BE' => '10YBE----------2',
        'BG' => '10YCA-BULGARIA-R',
        'CH' => '10YCH-SWISSGRIDZ',
        'CZ' => '10YCZ-CEPS-----N',
        'DE' => '10Y1001A1001A83F',
        'DK' => '10Y1001A1001A65H',
        'EE' => '10Y1001A1001A39I',
        'ES' => '10YES-REE------0',
        'FI' => '10YFI-1--------U',
        'FR' => '10YFR-RTE------C',
        'GR' => '10YGR-HTSO-----Y',
        'HR' => '10YHR-HEP------M',
        'HU' => '10YHU-MAVIR----U',
        'IT' => '10YIT-GRTN-----B',
        'LT' => '10YLT-1001A0008Q',
        'LU' => '10YLU-CEGEDEL-NQ',
        'LV' => '10YLV-1001A00074',
        'ME' => '10YCS-CG-TSO---S',
        'MK' => '10YMK-MEPSO----8',
        'NL' => '10YNL----------L',
        'NO' => '10YNO-0--------C',
        'PL' => '10YPL-AREA-----S',
        'PT' => '10YPT-REN------W',
        'RO' => '10YRO-TEL------P',
        'RS' => '10YCS-SERBIATSOV',
        'SE' => '10YSE-1--------K',
        'SI' => '10YSI-ELES-----O',
        'SK' => '10YSK-SEPS-----K'
    ];


    const BIDDING_ZONES = [
        'AL' => ['10YAL-KESH-----5'],
        'AT' => ['10YAT-APG------L'],
        'BA' => ['10YBA-JPCC-----D'],
        'BE' => ['10YBE----------2'],
        'BG' => ['10YCA-BULGARIA-R'],
        'CH' => ['10YCH-SWISSGRIDZ'],
        'CZ' => ['10YCZ-CEPS-----N'],
        'DE' => ['10Y1001A1001A82H'],
        'DK' => ['10YDK-1--------W', '10YDK-2--------M'],
        'EE' => ['10Y1001A1001A39I'],
        'ES' => ['10YES-REE------0'],
        'FI' => ['10YFI-1--------U'],
        'FR' => ['10YFR-RTE------C'],
        'GR' => ['10YGR-HTSO-----Y'],
        'HR' => ['10YHR-HEP------M'],
        'HU' => ['10YHU-MAVIR----U'],
        'IT' => ['10YIT-GRTN-----B'],
        'LT' => ['10YLT-1001A0008Q'],
        'LU' => ['10Y1001A1001A82H'],
        'LV' => ['10YLV-1001A00074'],
        'ME' => ['10YCS-CG-TSO---S'],
        'MK' => ['10YMK-MEPSO----8'],
        'NL' => ['10YNL----------L'],
        'NO' => ['10YNO-2--------T', '10YNO-1--------2', '50Y0JVU59B4JWQCU', '10YNO-3--------J', '10YNO-4--------9', '10Y1001A1001A48H'],
        'PL' => ['10YPL-AREA-----S'],
        'PT' => ['10YPT-REN------W'],
        'RO' => ['10YRO-TEL------P'],
        'RS' => ['10YCS-SERBIATSOV'],
        'SE' => ['10Y1001A1001A44P', '10Y1001A1001A45N', '10Y1001A1001A46L', '10Y1001A1001A47J'],
        'SI' => ['10YSI-ELES-----O'],
        'SK' => ['10YSK-SEPS-----K']
    ];

    const BIDDING_ZONES_CHANGES_PRE_2018 = [
        'AT' => ['10Y1001A1001A63L'],
        'DE' => ['10Y1001A1001A63L'],
        'LU' => ['10Y1001A1001A63L'],
    ];


    const BORDER_RELATIONS = [
        'AL' => ['GR', 'ME', 'RS'],
        'AT' => ['CZ', 'DE', 'HU', 'IT', 'SI', 'CH'],
        'BA' => ['HR', 'ME', 'RS'],
        'BE' => ['FR', 'DE', 'NL', 'LU'],
        'BG' => ['GR', 'RO', 'RS'],
        'CH' => ['AT', 'FR', 'DE', 'IT'],
        'CZ' => ['AT', 'DE', 'PL', 'SK'],
        'DE' => ['NL', 'BE', 'FR', 'CH', 'AT', 'CZ', 'PL', 'DK', 'NO', 'SE', 'LU'],
        'DK' => ['DE', 'NL', 'NO', 'SE'],
        'EE' => ['FI', 'LV'],
        'ES' => ['FR', 'PT'],
        'FI' => ['EE', 'NO', 'SE'],
        'FR' => ['BE', 'DE', 'IT', 'ES', 'CH'],
        'GR' => ['AL', 'BG', 'IT'],
        'HR' => ['BA', 'HU', 'RS', 'SI'],
        'HU' => ['AT', 'HR', 'RO', 'RS', 'SK'],
        'IT' => ['AT', 'FR', 'GR', 'ME', 'SI', 'CH'],
        'LT' => ['LV', 'PL', 'SE'],
        'LU' => ['DE', 'BE'],
        'LV' => ['EE', 'LT'],
        'ME' => ['AL', 'IT', 'RS'],
        'MK' => ['BG', 'GR', 'RS'],
        'NL' => ['BE', 'DK', 'DE', 'NO'],
        'NO' => ['DK', 'FI', 'DE', 'NL', 'SE'],
        'PL' => ['CZ', 'DE', 'LT', 'SK', 'SE'],
        'PT' => ['ES'],
        'RO' => ['BG', 'HU', 'RS'],
        'RS' => ['AL', 'BA', 'BG', 'HR', 'HU', 'ME', 'RO', 'MK'],
        'SE' => ['DK', 'FI', 'DE', 'LT', 'NO', 'PL'],
        'SI' => ['AT', 'HR', 'IT'],
        'SK' => ['CZ', 'HU', 'PL']
    ];


    const PSR_TYPES = [
        'B01', 'B02', 'B03', 'B04', 'B05', 'B05', 'B06', 'B07', 'B08', 'B09', 
        'B10', 'B11', 'B12', 'B13', 'B14', 'B15', 'B16', 'B17', 'B18', 'B19', 'B20'
    ];


    public function __construct()
    {
        parent::__construct();
        $this->apiUrl = "{$_ENV['ENTSOE_API_URL']}?securityToken={$_ENV['ENTSOE_API_TOKEN']}";
    }


    /**
     * Performs GET request to the EntsoE API
     * @param array $params All needed parameters [name=>value]
     * @return \SimpleXMLElement Parsed XML or null at error
     */
    protected function makeGetRequest(array $params): ?\SimpleXMLElement
    {
        $url = $this->apiUrl;
        foreach ($params as $key => $value) {
            $url .= "&$key=$value";
        }
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $xml = simplexml_load_string(curl_exec($curl));
        curl_close($curl);
        return $xml ? $xml : null;
    }


    /**
     * Performs GET request to an endpoint with a ZIP file attached to the response
     * @param array $params All needed parameters [name=>value]
     * @return string|null Path to the zip file or null at failure
     */
    /*protected function makeGetRequestWithZipResponse(array $params): ?string
    {
        $url = $this->apiUrl;
        foreach ($params as $key => $value) {
            $url .= "&$key=$value";
        }
        if(!is_dir(__DIR__ . '/../../tmp')) {
            mkdir(__DIR__ . '/../../tmp');
        }
        $fileName = __DIR__ . '/../../tmp/' . mt_rand(100, 999) . time() . '.zip';
        $response = file_get_contents($url);
        // When response is binary (<=> ZIP returned)
        if (!preg_match('//u', $response)) {
            file_put_contents($fileName, $response);
            return $fileName;
        }
        return null;
    }*/


    /**
     * Returns current bidding zones based on given date by applying necessary changes
     */
    protected function getBiddingZones(\DateTimeImmutable $date): array
    {
        if ($date->getTimestamp() < strtotime('2018-01-01')) { 
            $result = self::BIDDING_ZONES;
            foreach (self::BIDDING_ZONES_CHANGES_PRE_2018 as $key => $value) {
                $result[$key] = $value;
            }
            return $result;
        }
        return self::BIDDING_ZONES;
    }


    /**
     * Transforms XML time series to aggregated hourly array
     */
    protected function xmlTimeSeriesToHourlyValues(\SimpleXMLElement $xml, string $dataElementName): array
    {
        $processedData = [];
        // Response contains more than 1 TimeSeries
        if ($xml->TimeSeries->count() > 1) {
            $pointInFirstSeries = $xml->TimeSeries[0]->Period->Point->count();
            // First TimeSeries does not include whole day
            if ($pointInFirstSeries !== 24 && $pointInFirstSeries !== 48 && $pointInFirstSeries !== 96) {
                // Merge all TimeSeries to get complete day data
                $timeSeriesIndex = 0;
                while ($timeSeries = $xml->TimeSeries[$timeSeriesIndex]) {
                    $processedData = array_merge($processedData, $this->xmlTimeSeriesPeriodsToArray($timeSeries, $dataElementName));
                    ++$timeSeriesIndex;
                }
            }
            // First TimeSeries includes whole day => Ignore second TimeSeries
            else {
                $processedData = $this->xmlTimeSeriesPeriodsToArray($xml->TimeSeries[0], $dataElementName);
            }
        }
        // Handle only first TimeSeries
        else {
            $processedData = $this->xmlTimeSeriesPeriodsToArray($xml->TimeSeries[0], $dataElementName);
        }
        return $this->aggregateHourlyValues($processedData);
    }


    /**
     * Transforms XML time series data to array
     */
    private function xmlTimeSeriesPeriodsToArray(\SimpleXMLElement $xml, string $dataElementName): array
    {
        $result = [];
        // Iiterate over Points in TimeSeries
        $pointIndex = 0;
        while ($point = $xml->Period->Point[$pointIndex]) {
            $result[] = floatval($point->{$dataElementName}->__toString());
            ++$pointIndex;
        }
        return $result;
    }


    /**
     * Transforms raw time series data to hourly aggregated
     */
    private function aggregateHourlyValues(array $rawValues): array
    {
        // Raw values are hourly => No need for processing
        if (count($rawValues) === 24) {
            return $rawValues;
        }
        // Raw values are quarter hourly => Aggregate to hourly
        else if (count($rawValues) === 96) {
            return $this->aggregateValues($rawValues, 4);
        }
        // Raw values are half hourly => Aggregate to hourly
        else if (count($rawValues) === 48) {
            return $this->aggregateValues($rawValues, 2);
        }
        // Incomplete dataset => Process anyway to keep existing data
        else {
            return $this->aggregateValues($rawValues, 1);
        }
    }


    /**
     * Sums up each n elements of an array and put them in an array of 24 items
     */
    protected function aggregateValues(array $values, int $elementsToUnite): array {
        $result = array_fill(0, 24, 0);
        foreach (array_chunk($values, $elementsToUnite) as $index => $chunk) {
            $result[$index] = array_sum($chunk)/$elementsToUnite;
        }
        return $result;
    }

}