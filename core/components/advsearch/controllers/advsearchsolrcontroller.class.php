<?php

/**
 * @link http://wiki.apache.org/lucene-java/ImproveIndexingSpeed
 * @link http://wiki.apache.org/lucene-java/ImproveSearchingSpeed
 */

include_once dirname(__FILE__) . '/advsearchenginecontroller.class.php';
include_once dirname(dirname(__FILE__)) . '/vendors/solarium/vendor/autoload.php';
include_once dirname(dirname(__FILE__)) . '/vendors/solarium/library/Solarium/Autoloader.php';

class AdvSearchSolrController extends AdvSearchEngineController {

    /** @var A reference to the Solarium\Client object */
    protected $client;
    /** @var A reference to the Solarium\Client::createSelect() object */
    protected $query;

    public function __construct(modX $modx, $config) {
        parent::__construct($modx, $config);

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
        $this->query = $this->client->createSelect();
        $fields = array_merge($asContext['mainFields'], $asContext['tvFields']);
        $this->query->setFields($fields);
        if (!empty($asContext['joinedWhereFields']) && !empty($asContext['searchString'])) {
            $queries = array();
            $queries[] = 'text:' . $asContext['searchString'] . '*';      // copyField on solr's schema.xml
            $queries[] = 'text_rev:' . $asContext['searchString'] . '*';  // copyField on solr's schema.xml
            foreach ($asContext['joinedWhereFields'] as $v) {
                $queries[] = $v . ':' . $asContext['searchString'] . '*'; // add * for LIKE query
            }
            $queriesString = @implode(' ', $queries);
            $this->query->setQuery($queriesString);
        }

        if (isset($asContext['queryHook']) &&
                !empty($asContext['queryHook']) &&
                isset($asContext['queryHook']['andConditionsRaw']) &&
                !empty($asContext['queryHook']['andConditionsRaw'])) {

            $conditions = $this->processHookConditions($asContext);

            if (!empty($conditions)) {
                $conditionsString = @implode(' ', $conditions);
                $this->query->setQuery($conditionsString);
            }

        }

        $this->query->setStart(($asContext['page'] - 1) * $this->config['perPage'])->setRows($this->config['perPage']);
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
                $sortField = $field . '_sort'; // to manipulate sorting on indexed="false" or multivalued="true"
                $this->query->addSort($sortField, $dir);
            }
        }

        try {
            $resultset = $this->client->select($this->query);
        } catch (Exception $e) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, __FILE__ . ' ');
            $this->modx->log(modX::LOG_LEVEL_ERROR, __METHOD__ . ' ');
            $this->modx->log(modX::LOG_LEVEL_ERROR, __LINE__ . ': error getting result ' . $e->getMessage());
            return false;
        }

        if ($this->config['debug']) {
            $request = $this->client->createRequest($this->query);
            $debugInfo = (string)$request;
            $this->ifDebug('Solarium $debugInfo: ' . $debugInfo, __METHOD__, __FILE__, __LINE__);
        }

        $this->resultsCount = $resultset->getNumFound();
        $results = array();
        foreach ($resultset as $document) {
            $result = array();
            foreach ($document as $field => $value) {
                $result[$field] = $value;
            }
            $results[] = $result;
        }

        $this->results = $results;

        $countCheck = count($this->results);
        if ($countCheck === 0) {
            $this->resultsCount = 0;
        }

        $minOffset = ($asContext['page'] - 2) * $this->config['perPage'];
        if ($this->resultsCount < $minOffset) {
            $asContext['page'] = 1;
            $this->setPage(1);
        } else {
            $this->setPage($asContext['page']);
        }

        return $this->results;
    }

    public function processHookConditions($asContext) {
            $conditions = array();
            foreach ($asContext['queryHook']['andConditionsRaw'] as $keyCondition => $valueCondition) {
                $keyElts = array_map("trim", explode(':', $keyCondition));
                if (count($keyElts) == 1) {
                    $keyElts[1] = '=';
                } elseif (count($keyElts) == 2) {
                    if ($keyElts[1] == 'REGEXP') {
                        $keyElts[2] = '%s';
                    } else {
                        $keyElts[2] = '';
                    }
                }

                $keyCondition = implode(':', $keyElts);
                $oper = strtoupper($keyElts[1]); // operator
                $ptrn = strtolower($keyElts[2]); // pattern

                $classFieldElts = array_map("trim", explode('.', $keyElts[0]));
                $class = (count($classFieldElts) == 2) ? $classFieldElts[0] : '';
                $class = trim($class, '`');
                $field = (count($classFieldElts) == 2) ? $classFieldElts[1] : $classFieldElts[0];
                $field = trim($field, '`');

                $valueElts = array_map("trim", explode(':', $valueCondition));
                $tag = $valueElts[0];
                $typeValue = (!empty($valueElts[1])) ? strtolower($valueElts[1]) : 'request';
                $filtered = (!empty($valueElts[2])) ? array_map("trim", explode(',', $valueElts[2])) : array();

                if ($typeValue == 'request') { // the value is provided par an http variable
                    if (isset($_REQUEST[$tag])) {
                        if (is_array($_REQUEST[$tag])) {
                            // multiple list
                            $values = $_REQUEST[$tag];
                            $orConditions = array();
                            foreach ($values as $val) {
                                $val = strip_tags($val);
                                if (($val != '') && !in_array($val, $filtered)) {
                                    $orConditions[] = $this->processHookValue($asContext['queryHook'], $field, $oper, $ptrn, $val);
                                }
                            }
                            if (count($orConditions)) {
                                $conditions[] = '(' . implode(' OR ', $orConditions) . ')';
                            }
                        } else {
                            // single value
                            $val = strip_tags($_REQUEST[$tag]);
                            if (($val != '') && !in_array($val, $filtered)) {
                                $conditions[] = $this->processHookValue($asContext['queryHook'], $field, $oper, $ptrn, $val);
                            }
                        }
                    }
                }
            } // foreach

        return $conditions;
    }

    public function processHookValue($queryHook, $field, $oper, $ptrn, $val) {
        $condition = '';
        if ($queryHook['version'] == '1.3') {
            switch ($oper) {
                case 'IN':
                case 'NOT IN':  // operator with a list of values wrapped by parenthesis
                    $lstval = explode(',', $val);
                    $arrayVal = array();
                    foreach ($lstval as $v) {
                        if (is_numeric($val)) {
                            $arrayVal[] = $v;
                        } else {
                            $v = addslashes($v);
                            $arrayVal[] = "'" . $v . "'";
                        }
                    }
                    $val = implode(',', $arrayVal);
                    $condition = "({$field} {$oper}({$val}))";
                    break;
                case 'FIND':
                    $val = addslashes($val);
                    if (empty($ptrn)) {
                        $condition = "(FIND_IN_SET( {$val}, {$field} ))"; // csv list by default
                    } else {
                        $condition = "(FIND_IN_SET( '{$val}', REPLACE( {$field}, '{$ptrn}', ',' ) ))";
                    }
                    break;
                    /* @since 1.3 */
                case 'MATCH':  // operator with exact matching between word1||word2||word3
                    $val = addslashes($val);
                    $condition = "({$field} REGEXP '{$val}' )";
                    break;
                    /* @since 1.3 */
                case 'REGEXP': // operator with exact pattern matching. eg: ptrn= '%s[0-9]*'
                    $val = addslashes($val);
                    $ptrn = str_replace('%s', $val, $ptrn);
                    // http://lucene.apache.org/core/4_4_0/queryparser/org/apache/lucene/queryparser/classic/package-summary.html#Escaping_Special_Characters
                    $helper = $this->query->getHelper();
                    $condition = "$field:/*{$helper->escapeTerm($ptrn)}*/";
                    break;
                default:    // >,<,>=,<=,LIKE  (unary operator)
                    $val = addslashes($val);
                    $val = (!is_numeric($val)) ? "'{$val}'" : $val;
                    $condition = "({$field} {$oper} {$val})";
            }
        }

        return $condition;
    }

}

return 'AdvSearchSolrController';
