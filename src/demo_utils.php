<?php
namespace DemoUtils;
require 'vendor/autoload.php';
use GuzzleHttp\Client;
use Symfony\Component\Yaml\Yaml;

/** DemoUtils is used to store and retrieve the settings and functions
 * used in Salsa's Github repository o' Engage samples in PHP.  The class
 * provides easy ways to
 *    - retrieve and park Engage API tokens
 *    - reuse a single Guzzle client
 *    - hold app-specific stuff for the demos.
 */

/**
 *  Demo utility class.
 */

class DemoUtils {
    private $apiHost;
    private $intToken;
    private $devToken;
    private $environment;
    private $metrics;

    /**
     *  Build a new instance using default and null values.
     */
        function __construct() {
            $this->apiHost = 'https://api.salsalabs.org/';
            $this->intToken = null;
            $this->devToken = null;
            $this->metrics = null;
        }

    /**
     *  Get the apiHost.
     * @return string  Engage API host or the default.
     * @access public
     */
        public function getAPIHost() {
            return $this->apiHost;
        }
    /**
     *  Set the value of the apiHost.
     * @param string $value  New value for the Engage API host.
     * @access public
     *
     * Note that no attempt is made to validate the provided value.
     */
        public function setAPIHost($value) {
            $this->apiHost = $value;
        }

    /**
     *  Get the Integration API token.
     * @return string  Engage Integration API token or null.
     * @access public
     */
        public function getIntToken() {
            return $this->intToken;
        }
    /**
     *  Set the value of the Integration API Token.
     * @param string $value  New value for the Engage API token.
     * @access public
     */
        public function setIntToken($value) {
            $this->intToken = $value;
        }

    /**
     *  Get value of the Web Developer API token.
     * @return string  Web Developer API token or null.
     * @access public
     */
        public function getWebDevToken() {
            return $this->devToken;
        }
    /**
     *  Set the value of the devToken.
     * @param string $value  New value for the Web Developer API token.
     */
    public function setWebDevToken($value) {
        $this->devToken = $value;
    }

    /**
     *  Return the contents of the parsed YAML file provided in
     * loadYAML.
     * @return object  Contents of the parsed YAML file or none.
     * @access public
     */
    public function getEnvironment() {
        return $this->environment;
    }

    /**
     *  Load a YAML file and set the standard fields.  Errors are noisy
     *  and fatal. For your convenience, the parsed contents of the YAML
     * file can be retrieved using `getEnvironment()`.
     *
     * Side effect: Automatically retrieves the metrics.  Useful for apps
     * that need to paginate.
     *
     * @param  string $filename  YAML file to parse
     * @throws Exceptions        File access exceptions
     * @access public
     */
    public function loadYAML($filename) {
         $env = Yaml::parseFile($filename);
         $fields = [ "apiHost",
                     "intToken",
                     "devToken"];
         foreach ($fields as $f) {
             if (array_key_exists($f, $env)) {
                switch ($f) {
                    case "apiHost":
                        $this->apiHost = $env[$f];
                        break;
                    case "intToken":
                        $this->intToken = $env[$f];
                        break;
                    case "devToken":
                        $this->devToken = $env[$f];
                        break;
                    default:
                        printf("Warning: unknown YAML parameter '%s'\n", $f);
                        break;
                }
            }
            $this->environment = $env;
            $this->getMetrics();
        }
     }

    /**
     * Return the headers that Engage needs: authToken and Content-Type
     * in an object ready to send.
     * @param  string  token  API token
     * @param  boolean integration True if the headers need the integration
     *                            API Token.
     * @return object  Object containing the required tokens
     */
     public function getHeaders($token) {
         $headers = [
             'authToken' => $token,
             'Content-Type' => 'application/json'
         ];
         return $headers;
        }

    /**
     * Return a GuzzleHTTPClient for the specified endpoint.
     * endpoints contain the token type as part of the endpoint URL.
     * Throws an exception if the endpoint is egregiously malformed.
     * (Bet you never got a change to put "egrigiously" in to a comment...)
     * @param string $token     API token
     * @return object           GuzzleHTTPClient object
     * @throws Exception        Throws an exception for a malformed endpoint
     *                          Throws an exception if the Integration Token is null
     *                          Throws an exception if the Web Development Token is null
     * @access public
     */
     public function getClient($endpoint) {
         if(strpos($endpoint, "integration") !== false) {
             $token = $this->getIntToken();
             if (is_null($token)) {
                 throw new \Exception("Integration API Token is null");
             }
         } else {
             if (strpos($endpoint, "developer") !== false) {
                     $token = $this->getWebDevToken();
                     if (is_null($token)) {
                         throw new Exception("Web Developer API Token is null");
                     }
            } else {
                throw new \Exception("Malformed endpoint, ''" . $endpoint . "'");
             }
         }
         $client = new \GuzzleHttp\Client([
                        'base_uri' => $this->getAPIHost(),
                        'headers'  => $this->getHeaders($token)
        ]);
        return $client;
     }

   /* Returns the stashed metrics.  If the metrics have not been retrieved
    * yet, then calls updateMetrics().  Raises an exception if the metrics
    * can't be retrieved.
    *
    * Metrics change with every API call.  Monitoring the metrics can help
    * you avoid unwanted terminations and slowdowns.
    *
    * See
    *
    * https://help.salsalabs.com/hc/en-us/articles/224531208-General-Use
    *
    * Note:
    *
    * $currentRateLimit = $du->getMetrics()->currentRateLimit
    #
    # Because metrics are a object(stdClass).
    *
    * @return object(stdClass)                      Stashed metrics object.
    * @raises GuzzleHttp\Exception\ClientException  Read failures
    * @scope public
    */
   function getMetrics()
   {
       if (!isset($this->metrics)) {
           $this->updateMetrics();
       }
       return $this->metrics;
   }

   /**
    * Updates stashed metrics.  Does nothing if the API Host or Integration
    * token is not initialized.  Useful for apps that want to see where
    * they are in the call limits.
    * @return object                                Updated metrics value.
    * @raises GuzzleHttp\Exception\ClientException  Read failures
    * @access public
    */
    public function updateMetrics() {
        if (isset($this->apiHost) && (isset($this->intToken))) {
            $method = 'GET';
            $endpoint = '/api/integration/ext/v1/metrics';
            $client = $this->getClient($endpoint);
            $response = $client->request($method, $endpoint);
            $data = json_decode($response -> getBody());
            $this->metrics = $data->payload;
        }
        return $this->metrics;
    }

  /**
   * Convenience method to retrieve the YAML filename from the command
   * line and create a new DemoUtils instance with it.  Call this first
   * thing when you're writing a demo app. Errors are noisy and fatal.
   *
   * Usage:
   *
   * php your_app.php --login tokens_file.yaml
   *
   * @return object  DemoUtils nstance loaded from the contents of
   *                 `tokens_file.yaml`.
   * @access public
   */
  public function appInit() {
      $shortopts = "";
      $longopts = array(
          "login:"
      );
      $options = getopt($shortopts, $longopts);
      if (false == array_key_exists('login', $options)) {
          exit("\nYou must provide a parameter file with --login!\n");
      }
      $filename = $options['login'];
      $this->loadYAML($filename);
      $this->updateMetrics();
      return $this;
  }
}

?>
