<?php 

/**
 * Class to integrate EveCentral to EveAdmin Auth System
 * @author Josh Grancell <josh@joshgrancell.com>
 * @copyright (c) 2015 Josh Grancell
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2.0
 */

class EveCentral {
    /**
     * @param $db PDO object
     * @param $baseURL string
     * @param $connectionStatus bool
     */

    private $db;
    private $baseURL;
    private $connectionStatus = FALSE;
    
    /**
     * All zKillboard actions must be called as public methods from a class object. Nothing is static.
     * @param $db PDO object
     */

    public function __construct($db) {
        $this->db = $db; 
        $this->baseURL = 'https://api.eve-central.com/api/marketstat/';


        $requestHeaders = get_headers($this->baseURL);

        /**
         * We are looping through all of the provided headers looking for the status code 200 OK. That means the server is responding.
         * If we find it, we can proceed with our lookups. Otherwise, we will fail all further lookups.
         */
        
        foreach($requestHeaders as $header) {
            if(strpos($header, '200 OK')) {
                $this->connectionStatus = TRUE;
            }
        }
    }


    /**
     * This function handles all connections to the zKillboard servers.
     * It does not include any of its own error checking, since it should never be called directly.
     * @param $url string
     * @return xml object
     */

    private function connect($url) {
        // Initializing curl request and setting options
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        // We are setting the below HTTP headers at zKillboard's request
        curl_setopt($ch, CURLOPT_USERAGENT, 'https://eveadmin.com, Maintaner: Ashkrall, Contact: ashkrall@dogft.com');
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');

        // Executing the curl transfer
        $rawResponse = curl_exec($ch);

        // Decoding the raw XML into something readable
        $response = new SimpleXMLElement($rawResponse);

        // Outputting the decoded json information back to the calling method
        return $response;
    }

    /** 
     * handles the lookup for an Item on EveCentral. Processes for the type of check before handing off to the connect method
     * @param $id string
     * @param $sale_type string the type of sale we're looking for. choices are: buy, sell
     * @param $location string the system or region we are limiting this to. currently only supports Jita
     * @return int requested cost of an object 
     */

    public function lookupItem($typeID, $sale_type, $location) {
        // Confirming we have an live connection to the Eve Central API
        if($this->connectionStatus) {
            // We're going to add in 
            
            $lookup_location = $this->getLocation($location);

            $url = $this->baseURL.'?typeid='.$typeID.$lookup_location;

            $xmlObject = $this->connect($url);

            $value = $this->getValue($sale_type, $xmlObject);

            return $value;
        } else {
            // The check has failed, so we will now give the user a short error message and stop any further communication attempts.
            echo 'The Eve Central API is not currently available. Please try your request at a later time.';
        }
    }

    /** 
     * handles parsing the XML from Eve Central to get our value
     * @param $type sting either buy or sell
     * @param $xml object xml object provided by the connect function of this classooking for. choices are: buy, sell
     * @return int requested cost of an object 
     */
    private function getValue($type, $xml) {
        return $xml->marketstat->type->$type->min;
    }

    private function getLocation($location) {
        if($location == "Jita") {
            return '&regionlimit=10000002';
        } else {
            // Doing stuff here
        }
    }
}