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
    private $envnvironment;

    /**
     *  Build a new instance using default and null values.
     */
        function __constructor() {
            $this->setAPIHost(
            );
            $this->intToken = null;
            $this->devToken = null;
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
     * @param  string $filename  YAML file to parse
     * @throws (File exception class) File access exceptions
     * @access public
     */
    public function loadYAML($filename) {
         $env = Yaml::parseFile($filename);

         printf("\nYAML contents\n");
         var_dump($env);

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
     * Return a GuzzleHTTPClient for the specified token.
     * Automatically creates the headers.
     * @param string $token     API token
     * @return object           GuzzleHTTPClient object
     */
     public function getClient($token) {
         $base_uri = $this->getAPIHost();
         $headers = $this->getHeaders($token);
         $client =  new \GuzzleHttp\Client([
                        'base_uri' => $base_uri,
                        'headers'  => $headers
        ]);
        return $client;
     }

 /**
  * Convenience method to return a GuzzleHTTPClient for the
  * Integration API. Calls `getClient()`.
  * @return object           GuzzleHTTPClient object
  */
  public function getIntClient() {
      $token = $this->getIntToken();
      return $this->getClient($token);
  }

  /**
   * Convenience method to return  a GuzzleHTTPClient for the
   * Web Development API.  Calls `getClient()`.
   * @return object           GuzzleHTTPClient object
   */
   public function getWebDevClient() {
       $token = $this->getWebDevToken();
       return $this->getClient($token);
   }

  /**
   * Convenience method to retrieve the YAML filename from the command
   * line and create a new DemoUtils instance with it.  Call this first
   * thing when you're writing a demo app.
   *
   * Usage:
   *
   * php your_app.php --login tokens_file.yaml
   *
   * Errors are fatal and noisy.
   * @return object  DemoUtils nstance loaded from the contents of
   *                 `tokens_file.yaml`.
   */
  public function start() {
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
      return $this;
  }
}

?>