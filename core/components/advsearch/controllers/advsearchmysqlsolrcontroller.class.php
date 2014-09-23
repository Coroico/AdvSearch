<?php

/**
 * @link http://wiki.apache.org/lucene-java/ImproveIndexingSpeed
 * @link http://wiki.apache.org/lucene-java/ImproveSearchingSpeed
 */

include_once dirname(__FILE__) . '/advsearchenginecontroller.class.php';

class AdvSearchMySQLSolrController extends AdvSearchEngineController {

    /** @var A reference to the Solarium\Client object */
    protected $client;
    /** @var A reference to the Solarium\Client::createSelect() object */
    protected $query;

    public function __construct(modX $modx, $config) {
        parent::__construct($modx, $config);

        include_once $config['libraryPath'] . 'solarium/vendor/autoload.php';
        include_once $config['libraryPath'] . 'solarium/library/Solarium/Autoloader.php';

        if (!isset($config['engineConfigFile']) || empty($config['engineConfigFile']) || !is_file($config['engineConfigFile'])) {
            $config['engineConfigFile'] = dirname(__FILE__) . '/configs/advsearchsolrconfig.php';
        }
        $engineConfig = include $config['engineConfigFile'];
        try {
            \Solarium\Autoloader::register();
            $this->client = new Solarium\Client($engineConfig);
        } catch (Exception $e) {
            $msg = 'Error connecting to Solr server: ' . $e->getMessage();
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, $msg);
            throw new Exception($msg);
        }
    }

    public function getResults($asContext) {
        $mySQLControllerClass = include_once 'advsearchmysqlcontroller.class.php';
        $mySQLController = new $mySQLControllerClass($this->modx, $this->config);
        $mySQLControllerContext = $asContext;
        $mySQLControllerContext['joinedFields'] = array();
        $mySQLControllerContext['tvFields'] = array();
        $mySQLControllerResults = $mySQLController->getResults($mySQLControllerContext);
        $ids = array();
        foreach ($mySQLControllerResults as $result) {
            $ids[] = $result['id'];
        }
        $asContext['ids'] = $ids;

        $this->query = $this->client->createSelect();
        $fields = array_merge($asContext['mainFields'], $asContext['tvFields']);
        $this->query->setFields($fields);
        $queriesString = 'id:(' . implode(' ', $asContext['ids']) . ')';
        $this->query->setQuery($queriesString);
        if (!empty($asContext['sortby'])) {
            foreach ($asContext['sortby'] as $classField => $dir) {
                $classFieldX = @explode('.', $classField);
                if (!isset($classFieldX[1])) {
                    $field = $classFieldX[0]; // modResource
                } elseif ($classFieldX[0] === 'modResource') {
                    $field = $classFieldX[1]; // modResource
                } else {
                    $field = rtrim($classFieldX[0], '_cv'); // Template Variable
                }
                $sortField = $field . '_s'; // to manipulate sorting on indexed="false" or multivalued="true"
                $this->query->addSort($sortField, $dir);
            }
        }

        try {
            $resultset = $this->client->select($this->query);
        } catch (Exception $e) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, __FILE__ . ' ');
            $this->modx->log(modX::LOG_LEVEL_ERROR, __METHOD__ . ' ');
            $error = $e->getMessage();
            $this->modx->log(modX::LOG_LEVEL_ERROR, __LINE__ . ': error getting result: ' . $error);
            return false;
        }

        if ($this->config['debug']) {
            $request = $this->client->createRequest($this->query);
            $debugInfo = (string)$request;
            $this->ifDebug('Solarium $debugInfo: ' . $debugInfo, __METHOD__, __FILE__, __LINE__);
        }

//        $this->resultsCount = $resultset->getNumFound();
        $this->resultsCount = $mySQLController->resultsCount;
        $results = array();
        foreach ($resultset as $document) {
            $result = array();
            foreach ($document as $field => $value) {
                $result[$field] = $value;
            }
            $results[] = $result;
        }

        $this->results = $results;
        $this->setPage($asContext['page']);
        return $results;
    }

}

return 'AdvSearchMySQLSolrController';
