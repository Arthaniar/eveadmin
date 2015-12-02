<?php 

/**
 * Class to integrate zKillboard to EveAdmin Auth System
 * @author Josh Grancell <josh@joshgrancell.com>
 * @copyright (c) 2015 Josh Grancell
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2.0
 */

class zKillboard {
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
        // Here we will save the $db variable we have passed into the custructor as a Class Attribute.
        // The $this-> portion refers to the properties listed before the constructor. So we're saving $db to our 'private $db' by calling it as $this->db
        $this->db = $db; 
        $this->baseURL = 'https://zkillboard.com/api/';

        // By default we set the connection status to false. Only once it has been set to true will we actually attempt a lookup

        // Our testing URL is going to pull the most recent kill from zKillboard. To do this, we take the baseURL string and concatenate another string onto it using a period.
        $url = $this->baseURL.'kills/limit/1/';

        // Now we will pull the HTTP header information from the CURL and check for a status code
        $requestHeaders = get_headers($url);

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
     * @return json object
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

        // Decoding the raw json into something readable
        $response = json_decode($rawResponse, TRUE);

        // Outputting the decoded json information back to the calling method
        return $response;
    }

    /** 
     * handles the lookup for zKillboard. Processes for the type of check before handing off to the connect method
     * @param $lookup array
     * @param $type string
     * @return json object 
     */

    public function fetchKillmails($lookup, $type, $last_id) {
        // This conditional checks the connectionStatus property for truth. If it is true, it will continue on. If it is false, it will gracefully error out for us.
        if($this->connectionStatus) {           

            $url = $this->baseURL.$type."/";

            foreach($lookup as $scope => $id ) {
                $url .= $scope."/".$id."/";
            }

            $url .= 'afterKillID/'.$last_id.'./';


            // Concatenating everything into a properly formatted zKillboard API URL.
            $url .= 'limit/2000/orderDirection/asc/';

            // Here we are calling our previously created connect method and feeding it our URL. It will give us decoded json in return.
            $response = $this->connect($url);

            // And finally, we are outputting our decoded json to the calling script.
            return $response;

        } else {
            // The check has failed, so we will now give the user a short error message and stop any further communication attempts.
            echo 'The zKillboard API is not currently available. Please try your request at a later time.';
        }
    }
}