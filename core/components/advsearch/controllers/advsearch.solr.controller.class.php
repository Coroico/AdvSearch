<?php

/**
 * @link http://wiki.apache.org/lucene-java/ImproveIndexingSpeed
 * @link http://wiki.apache.org/lucene-java/ImproveSearchingSpeed
 */
include_once dirname(__FILE__) . '/advsearch.engine.controller.class.php';

class AdvSearchSolrController extends AdvSearchEngineController {

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
        $this->query = $this->client->createSelect();
        $fields = array_merge($asContext['mainFields'], $asContext['tvFields']);
        $this->query->setFields($fields);
        $queriesString = '';

        // multiple parents support
        if (!empty($this->config['parents'])) {
            $queries = array();
            $parentArray = array_map('trim', @explode(',', $this->config['parents']));
            foreach ($parentArray as $parent) {
                $queries[] = 'parent:' . $parent;
            }
            $queriesString .= '(' . @implode(' AND ', $queries) . ')';
        }

        // multiple ids support
        if (!empty($this->config['ids'])) {
            $queries = array();
            $idsArray = array_map('trim', @explode(',', $this->config['ids']));
            foreach ($idsArray as $id) {
                $queries[] = 'id:' . $id;
            }
            $and = !empty($queriesString) ? ' AND ' : '';
            $queriesString .= $and . '(' . @implode(' AND ', $queries) . ')';
        }

        if (!empty($asContext['joinedWhereFields'])) {
            $queries = array();

            if (!empty($asContext['searchString'])) {
                $queries[] = 'text:' . $asContext['searchString'];              // copyField on solr's schema.xml
                $queries[] = 'text_rev:' . $asContext['searchString'];
                foreach ($asContext['joinedWhereFields'] as $v) {
                    $queries[] = $v . ':' . $asContext['searchString'];
                    $queries[] = $v . ':' . str_replace(' ', '* ', $asContext['searchString']) . '*';   // add * for LIKE query
                    $queries[] = $v . ':*' . str_replace(' ', ' *', $asContext['searchString']);        // front wildcard
                    $queries[] = $v . '_s:' . $asContext['searchString'];
                    $queries[] = $v . '_s:' . str_replace(' ', '* ', $asContext['searchString']) . '*';
                    $queries[] = $v . '_s:*' . str_replace(' ', ' *', $asContext['searchString']);
                }
            } elseif (empty($queriesString)) {
                $queries[] = '*:*';
            }
            if (!empty($queries)) {
                $and = !empty($queriesString) ? ' AND ' : '';
                $queriesString .= $and . '(' . @implode(' OR ', $queries) . ')';
            }
        }

        if (isset($asContext['queryHook']) &&
                !empty($asContext['queryHook']) &&
                isset($asContext['queryHook']['andConditionsRaw']) &&
                !empty($asContext['queryHook']['andConditionsRaw'])) {

            if ($asContext['queryHook']['version'] == '1.3') {
                $conditions = $this->processHookConditions($asContext);
            } else {
                $conditions = $this->processHookConditionsDeprecated($asContext);
            }

            if (!empty($conditions)) {
                $conditionsString = @implode(' AND ', $conditions);
                if (!empty($queriesString)) {
                    $queriesString .= ' AND (' . $conditionsString . ')';
                } else {
                    $queriesString = $conditionsString;
                }
            }
        }

        if (!empty($this->config['fieldPotency'])) {
            $edismax = $this->query->getEDisMax();
            $fieldPotency = array_map('trim', explode(',', $this->config['fieldPotency']));
            $queryFields = array();
            foreach ($fieldPotency as $fldp) {
                $fld = array_map('trim', explode(':', $fldp));
                $fld[1] = (isset($fld[1]) && floatval($fld[1])) ? number_format($fld[1], 1) : 1;
                $queryFields[] = $fld[0] . '^' . $fld[1];
            }
            $queryFields = implode(' ', $queryFields);
            $edismax->setQueryFields($queryFields);
        }

        $this->query->setQuery($queriesString);
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
            $debugInfo = (string) $request;
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
        $this->setPage($asContext['page']);
        return $results;
    }

    public function processHookConditions($asContext) {
        $conditions = array();

        foreach ($asContext['queryHook']['andConditionsRaw'] as $keyCondition => $valueCondition) {
            $keyElts = array_map("trim", explode(':', $keyCondition));
            if (count($keyElts) == 1) {
                $keyElts[1] = '=';
            }

            $keyCondition = implode(':', $keyElts);
            $oper = strtoupper($keyElts[1]); // operator
            // $ptrn = strtolower($keyElts[2]); // pattern
            // pattern
            $ptrn = !empty($valueCondition['pattern']) ?
                    strtolower($valueCondition['pattern']) :
                    ($oper == 'REGEXP' ? '%s' : '');

            $classFieldElts = array_map("trim", explode('.', $keyElts[0]));
            $class = (count($classFieldElts) == 2) ? $classFieldElts[0] : '';
            $class = trim($class, '`');
            $field = (count($classFieldElts) == 2) ? $classFieldElts[1] : $classFieldElts[0];
            $field = trim($field, '`');

            // $valueElts = array_map("trim", @explode(':', $valueCondition));
            $tag = isset($valueCondition['key']) && !empty($valueCondition['key']) ? $valueCondition['key'] : '';
            $typeValue = (!empty($valueCondition['method'])) ? strtolower($valueCondition['method']) : 'request';
            $filtered = (!empty($valueCondition['ignoredValue'])) ? array_map("trim", @explode(',', $valueCondition['ignoredValue'])) : array();

            if ($typeValue == 'request' && !empty($tag)) { // the value is provided par an http variable
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

    /**
     * @deprecated since version 1.3
     * @param array $asContext
     * @return array
     */
    public function processHookConditionsDeprecated($asContext) {
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
                /* @since 1.3 */
                case 'MATCH':
                    $val = addslashes($val);
                    $condition = "{$field}:/.*{$val}.*/ OR ";
                    $condition .= "{$field}_s:/.*{$val}.*/"; // run regex on "string" fieldtype of field's clone instead
                    break;
                case 'REGEXP': // operator with exact pattern matching. eg: ptrn= '%s[0-9]*'
                    $val = addslashes($val);
                    $ptrn = str_replace('%s', $val, $ptrn);
                    $condition = "{$field}:/.*{$ptrn}.*/ OR ";
                    $condition .= "{$field}_s:/.*{$ptrn}.*/"; // run regex on "string" fieldtype of field's clone instead
                    break;
                case 'QUERY':
                default:
                    $val = addslashes($val);
                    $ptrn = str_replace('%s', $val, $ptrn);
                    $condition = "{$field}:{$ptrn} OR ";
                    $condition .= "{$field}_s:{$ptrn}";
                    break;
            }
        }

        return $condition;
    }

}

return 'AdvSearchSolrController';
