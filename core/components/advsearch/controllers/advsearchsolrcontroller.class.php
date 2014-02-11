<?php

if (!class_exists('AdvSearchEngineController')) {
    include_once dirname(__FILE__) . '/advsearchenginecontroller.class.php';
}

class AdvSearchSolrController extends AdvSearchEngineController {

    /** @var array An array of connection properties for our SolrClient */
    private $_connectionOptions = array();

    /** @var A reference to the SolrClient object */
    public $client;

    public function __construct(modX $modx, $config) {
        parent::__construct($modx, $config);

        $this->_connectionOptions = array(
            'hostname' => $this->modx->getOption('advsearch.solr.hostname', null, '127.0.0.1'),
            'port' => $this->modx->getOption('advsearch.solr.port', null, '8983'),
            'path' => $this->modx->getOption('advsearch.solr.path', null, ''),
            'login' => $this->modx->getOption('advsearch.solr.username', null, ''),
            'password' => $this->modx->getOption('advsearch.solr.password', null, ''),
            'timeout' => $this->modx->getOption('advsearch.solr.timeout', null, 30),
            'secure' => $this->modx->getOption('advsearch.solr.ssl', null, false),
            'ssl_cert' => $this->modx->getOption('advsearch.solr.ssl_cert', null, ''),
            'ssl_key' => $this->modx->getOption('advsearch.solr.ssl_key', null, ''),
            'ssl_keypassword' => $this->modx->getOption('advsearch.solr.ssl_keypassword', null, ''),
            'ssl_cainfo' => $this->modx->getOption('advsearch.solr.ssl_cainfo', null, ''),
            'ssl_capath' => $this->modx->getOption('advsearch.solr.ssl_capath', null, ''),
            'proxy_host' => $this->modx->getOption('advsearch.solr.proxy_host', null, ''),
            'proxy_port' => $this->modx->getOption('advsearch.solr.proxy_port', null, ''),
            'proxy_login' => $this->modx->getOption('advsearch.solr.proxy_username', null, ''),
            'proxy_password' => $this->modx->getOption('advsearch.solr.proxy_password', null, ''),
        );

        try {
            $this->client = new SolrClient($this->_connectionOptions);
        } catch (Exception $e) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Error connecting to Solr server: ' . $e->getMessage());
        }
    }

    public function getResults($asContext) {
        ;
    }

}

return 'AdvSearchSolrController';
