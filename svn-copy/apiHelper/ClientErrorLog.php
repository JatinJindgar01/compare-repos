<?php
/**
 * User: pankaj.gupta@capillarytech.com
 */


/**
 * @package Logger
 * Used as a logger, can turn on or off file level and view level logging.
 */

define('LOG4PHP_DIR', $_ENV['DH_LIB']."/php/log4php");

if (!defined('LOG4PHP_CONFIGURATION')) {
    define('LOG4PHP_CONFIGURATION', "log4php.properties");
}


require_once(LOG4PHP_DIR . '/LoggerManager.php');
require_once(LOG4PHP_DIR . '/LoggerNDC.php');

class ClientErrorLog {

    private $enabled = false;

    private $printed = false;

    private $level = self::NONE;

    private $l4plogger;

    private $entries;

    private static $ndc_pushed = false;

    const ALL = 100;
    const DEBUG = 50;
    const SQL = 35;
    const INFO = 25;
    const WARN = 10;
    const ERROR = 5;
    const NONE = 0;

    static public $levels = array ( self::ALL =>'ALL', self::DEBUG => 'DEBUG', self::SQL => 'SQL', self::INFO => 'INFO', self::WARN => 'WARN', self::ERROR => 'ERROR', self::NONE => 'NONE');

    function __construct() {
        //read enabled and level from config file
        $this->enabled = false;
        $this->level = self::ALL;
        $this->entries = array();
        $this->l4plogger = LoggerManager::getLogger('clienterrorlog');

        global $currentorg;

        if(is_object($currentorg) && isset($currentorg->org_id) && !self::$ndc_pushed)
        {
            LoggerNDC::push($currentorg->org_id);
            self::$ndc_pushed = true;
        }
    }

    function __destruct() {

        if ($this->enabled) {

        }

        LoggerNDC::pop();
    }


    function debug($message) {

        $message = str_replace("\n", chr(248), $message);
        if ($this->l4plogger){
            $this->l4plogger->debug($message);
        }
    }


    function info($message) {

        $message = str_replace("\n", chr(248), $message);
        if ($this->l4plogger){
            $this->l4plogger->info($message);
        }
    }


    function error($message) {

        $message = str_replace("\n", chr(248), $message);
        if ($this->l4plogger){
            $this->l4plogger->error($message);
        }
    }

    function getLevel() {

        return ClientErrorLog::$levels[$this->level];

    }


    /**
     * Loads the appropriate function for loading log4php subclasses. If the
     * classname matches the key, then do a preg_replace with the value. Otherwise
     * return the default result (i.e. at root dir)
     */
    public static function Log4phpLoader($classname) {
        static $l4phpmap = array(
            "/^LoggerDOMConfigurator$/"=>"/xml/LoggerDOMConfigurator",
            "/^LoggerAppender([\w]+)$/"=>"/appenders/LoggerAppender\${1}",
            "/^LoggerLayout([\w]+)$/"=>"/layouts/LoggerLayout\${1}"
        ); // todo make this a class static instead of function static

        foreach ($l4phpmap as $key=>$value) {
            if (preg_match($key, $classname) != 0)
                return (LOG4PHP_DIR . preg_replace($key, $value, $classname) . ".php");
        }
        return (LOG4PHP_DIR . "/$classname.php");
    }

    public function fetchLog(){

    }

}


?>
