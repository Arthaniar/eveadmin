<?php 

/**
 * Class to integrate zKillboard to EveAdmin Auth System
 * @author Josh Grancell <josh@joshgrancell.com>
 * @copyright (c) 2015 Josh Grancell
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2.0
 */

class Crest {
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
        $this->baseURL = 'https://public-crest.eveonline.com/';

        // Getting the readers for the Crest API
        //$requestHeaders = get_headers($this->baseURL);

        /**
         * We are looping through all of the provided headers looking for the status code 200 OK. That means the server is responding.
         * If we find it, we can proceed with our lookups. Otherwise, we will fail all further lookups.
         */
        
        //foreach($requestHeaders as $header) {
            //if(strpos($header, '200 OK')) {
                $this->connectionStatus = TRUE;
           // }
       // }
    }


    /**
     * This function handles all connections to the zKillboard servers.
     * It does not include any of its own error checking, since it should never be called directly.
     * @param $url string
     * @return json object
     */

    private function connect($url) {
        // Initializing curl request and setting options
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        // We are setting the below HTTP headers at CCP's request
        curl_setopt($ch, CURLOPT_USERAGENT, 'https://eveadmin.com, Maintaner: Ashkrall, Contact: ashkrall@dogft.com');
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');

        // Executing the curl transfer
        $rawResponse = curl_exec($ch);

        // Decoding the raw json into something readable
        $response = json_decode($rawResponse, TRUE);

        // Outputting the decoded json information back to the calling method
        return $response;
    }

    /** 
     * Fetches data from the requested CREST endpoint
     * @param $endpoint string the requested endpoint for the crest api
     * @param $argument string any argument needed by the crest api
     * @access private
     * @return json object containing requested CREST data
     */

    private function fetchData($endpoint, $argument) {
        if($this->connectionStatus) {
            $url = $this->baseURL.$endpoint.$argument.'/';

            $response = $this->connect($url);
        } else {
            echo "The CREST API is not currently available";
        }
    }

    /**
     * Lookup method for Alliance CREST Endpoint
     * @param $typeID int ccp-provided typeID integer
     * @access public
     * @return array
     */
    public function Alliance($typeID) {
        if(isset($typeID)) {
            $raw_data = $this->fetchData('alliances/', '150097440');
        } else {
            // Error ID# 1001 - Blank or null $typeID
            setAlert('danger', 'Internal Server Error', 'Please contact your administrator and reference Error ID# 1001');
        }
    }
}