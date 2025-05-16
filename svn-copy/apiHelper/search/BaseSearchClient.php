<?php

/**
 * Base Solr Search Client
 *
 * Wraps the Solr Client by Donovan Jimenez
 * available at http://code.google.com/p/solr-php-client/
 *
 * @author pigol
 *
 */

define('PRODUCT', 'product');
define('CUSTOMER', 'customer');

global $cfg;
require_once('solr/Apache/Solr/Service.php');
require_once('svc_mgrs/SolrServiceManager.php');

include_once('apiHelper/search/FacetResult.php');
abstract class BaseSearchClient
{

    private $host;

    private $port;

    /**
     * Path is separate because we may go for
     * multi-core solr deployment
     **/
    private $path;

    private $client;

    protected $logger;

    protected static $null_words;

    private $cb_client;

    protected $org_id;

    protected function __construct($type = PRODUCT)
    {

        global $cfg, $logger, $currentorg;

        $this->logger = $logger;
        $search_cfg = $cfg['search'][$type];

        $this->host = $search_cfg['host'];
        $this->port = $search_cfg['port'];
        $this->path = $search_cfg['path'];

        if ($GLOBALS['cfg']['mock_mode'] !== true) { 
            $this->client = new Apache_Solr_Service($this->host, $this->port, $this->path);
            $this->client->setDefaultTimeout(5);
        }
        BaseSearchClient::$null_words = array('null', 'none');
        $this->org_id = $currentorg->org_id;

        $this->cb_client = new SolrServiceManager();
    }

    /**
     * Make the search query call and return the result set.
     * start and rows params are for pagination etc.
     * We may want to put the results of the Solr into memcache later on
     * for faster pagination etc.. adding is_cacheable for that
     *
     * @param $query
     * @param $start
     * @param $rows
     */

    private function makeSearchCall($query = "*:*", $start = 0, $rows = 10, $is_cacheable = false, $distinct_field = null)
    {
        $this->logger->info("Searching with query: $query, start: $start, rows:$rows, distinct: $distinct_field");
        try {
            if ($GLOBALS['cfg']['mock_mode'] === true) {
                $response = $this->cb_client->handleMock("search", array($query, $start, $rows));
            } else {
                $additional_params = array('fq' => "org_id:$this->org_id");
                if($distinct_field) {
                    $additional_params["facet"] = "on";
                    $additional_params["facet.field"] = $distinct_field;
                    $additional_params["facet.offset"] = $start;
                    $additional_params["facet.limit"] = $rows;
                    $additional_params["facet.mincount"] = 1;
                    $rows = 0;
                    $start = 0;
                }
                $response = $this->client->search($query, $start, $rows, $additional_params);
            }
            return $response;
        } catch (Exception $e) {
            $this->logger->error("Error occured in querying: " + print_r($e, true));
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * The actual function which needs to be implemented by the clients
     * subclassing this BaseClient.
     *
     * @param $query : Solr Query
     * @param $start : id to start from
     * @param $rows : number of rows to fetch
     *
     * @return SearchResult
     */

    protected function search($query, $start, $rows, $distinct_field = false)
    {
         $this->logger->debug("Searching distinct $distinct_field");
        if ($start < 0 || !isset($start)) {
            $start = 0;
        }

        if ($rows <= 0 || !isset($rows)) {
            $rows = 10;
        }

        try {
            $solr_response = $this->makeSearchCall($query, $start, $rows, false, $distinct_field);
            
            $result = $distinct_field ? new FacetResult($solr_response) : new SearchResult($solr_response);

            //$this->logger->debug("solr resposne: " . print_r($result, true));

            
            $documents = $distinct_field ? $this->createFacetDocuments($result->getDocuments()) : $this->cleanDocuments($result->getDocuments());
            return array(
                'count' => $result->getCount(),
                'start' => $start,
                'rows' => $rows,
                'results' => array('item' => $documents)
            );

        } catch (Exception $e) {

        }
    }


    protected abstract function cleanDocuments(&$documents);

    /**
     * Removes the suffixes which are added to the dynamic fields
     */
    protected function cleanAttribute($key)
    {

        if (preg_match('/_t$|_tf$|_cf$/', $key)) {
            $key = preg_replace('/_t$|_tf$|_cf$/', '', $key);
        }
        return $key;
    }

    /**
     * @param $key_val_map - array of documents.
     * eg: documents {'name' => 'Pankaj', 'points' => 10}
     */
    public function addDocument($key_val_map)
    {
        $input = array();
        foreach ($key_val_map as $key => $val) {
            if ($this->validateKey($key)) {
                $input[$key] = $val;
            } else {
                $this->logger->debug("BaseSearchClient : Invalid attribute : " . $key);
            }
        }

        include_once('solr/Apache/Solr/Document.php');
        $doc = new Apache_Solr_Document();

        foreach ($input as $key => $value) {
            $doc->$key = $value;
        }

        try {
            if ($GLOBALS['cfg']['mock_mode'] == true) {
                return $this->cb_client->handleMock("addDocument", array('doc' =>$doc, 'shouldThrowException' => false));
            } else {
                return $this->client->addDocument($doc, false, true, true, 10000);
            }
        } catch (Exception $e) {
            $this->logger->error("Error occured in addDocument: " + print_r($e, true));
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

}


?>
