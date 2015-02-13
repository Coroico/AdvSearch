<?php

if (!class_exists('AdvSearchEngineController')) {
    include_once dirname(__FILE__) . '/advsearch.engine.controller.class.php';
}

class AdvSearchZendController extends AdvSearchEngineController {

    /** @var A reference to the Solarium\Client::createSelect() object */
    protected $query;

    public function __construct(modX $modx, $config) {
        parent::__construct($modx, $config);

        // load the zend lucene library
        $file = $config['libraryPath'] . 'ZendSearch/vendor/autoload.php';
        if (!file_exists($file)) {
            $msg = '[AdvSearch] Required library was not found at ' . $file . '.';
            $this->modx->log(modX::LOG_LEVEL_ERROR, $msg);
            throw new Exception($msg);
        }

        require_once $file;

    }

    public function getResults($asContext) {
        if (empty($asContext['searchString'])) {
            return false;
        }

        try {
            $this->query = \ZendSearch\Lucene\Search\QueryParser::parse($asContext['searchString'], $this->config['charset']);
        } catch (Exception $e) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, __FILE__ . ' ');
            $this->modx->log(modX::LOG_LEVEL_ERROR, __METHOD__ . ' ');
            $error = $e->getMessage() . "\n" . $e->getTraceAsString();
            $this->modx->log(modX::LOG_LEVEL_ERROR, __LINE__ . ': error getting result: ' . "\n" . $error);
            return false;
        }
        // valid maxwords and minchars
        $valid = $this->_validQuery($this->query, true);
        if (!$valid) {
            return false;
        }

        $fields = array_merge($asContext['mainFields'], $asContext['tvFields']);
        $dataPath = dirname(MODX_CORE_PATH) . '/zendresources/_files';

        try {
            $index = \ZendSearch\Lucene\Lucene::open($dataPath);
            //change the length of non-wildcard prefix
            \ZendSearch\Lucene\Search\Query\Wildcard::setMinPrefixLength(0);
            // do a search inside lucene index
            $zendQuery = $this->_getZendQuery($this->query);
            $hits = $index->find($zendQuery);
        } catch (Exception $e) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, __FILE__ . ' ');
            $this->modx->log(modX::LOG_LEVEL_ERROR, __METHOD__ . ' ');
            $error = $e->getMessage() . "\n" . $e->getTraceAsString();
            $this->modx->log(modX::LOG_LEVEL_ERROR, __LINE__ . ': error getting result: ' . $error);
            return false;
        }

        $this->resultsCount = count($hits);
        $results = array();
        $perPage = 0;
        $page = 1;
        foreach ($hits as $hit) {
            $hitResult = array();
            if (!isset($results[$page])) {
                $results[$page] = array();
            }
            foreach ($fields as $field) {
                try {
                    if ($field === 'id') {
                        $hitResult[$field] = $hit->docid;
                    } else {
                        $hitResult[$field] = $hit->$field;
                    }
                } catch (Exception $e) {
                    $hitResult[$field] = null;
                }
            }
            $results[$page][] = $hitResult;
            if ($perPage % $this->config['perPage'] === 0) {
                $page++;
            }
            $perPage++;
        }

        $this->results = $results[$asContext['page']];
        $this->setPage($asContext['page']);

        return $this->results;
    }

    /**
     * valid a search query
     *
     * @access private
     * @param \ZendSearch\Lucene\Search\Query $query The query to validate
     * @param boolean/null $sign true if mandatory, null if optional, false if excluded
     * @param integer $nbTerms Number of terms already processed
     * @return boolean Returns true if valid, otherwise false.
     */
    private function _validQuery($query = null, $sign = true, & $nbTerms = 0) {
        if ($query instanceOf \ZendSearch\Lucene\Search\Query\Boolean) {
            $subqueries = $query->getSubqueries();
            $signs = $query->getSigns();
            $nbs = count($subqueries);
            for ($i = 0; $i < $nbs; $i++) {
                $valid = $this->_validQuery($subqueries[$i], $signs[$i], $nbTerms);
                if (!$valid) {
                    return false;
                }
            }

            return true;
        } else if ($query instanceOf \ZendSearch\Lucene\Search\Query\Preprocessing\Phrase) {
            $phrase = $query->__toString();
            $valid = $this->validTerm($phrase, 'phrase', $sign, $nbTerms);

            return $valid;
        } else if ($query instanceOf \ZendSearch\Lucene\Search\Query\Preprocessing\Term) {
            $term = $query->__toString();
            $valid = $this->validTerm($term, 'word', $sign, $nbTerms);

            return $valid;
        } else {
            $msgerr = $this->modx->lexicon('advsearch.invalid_query');
            $this->setError($msgerr);
            $this->modx->setDebug();
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'error getting result: "' . $msgerr . '"', '', __METHOD__, __FILE__, __LINE__);
            $this->modx->setDebug(false);
            return false;
        }
    }

    /**
     * Get Zend query
     *
     * @access private
     * @param \ZendSearch\Lucene\Search\Query $searchQuery The parsed query
     * @param boolean $sign True if the condition should be processed
     * @return string Returns a condition
     */
    private function _getZendQuery($query, $sign = true) {
        if ($query instanceOf \ZendSearch\Lucene\Search\Query\Boolean) {
            $orCondition = array();
            $andCondition = array();
            $notCondition = array();
            $subqueries = $query->getSubqueries();
            $signs = $query->getSigns();
            $nbs = count($subqueries);
            for ($i = 0; $i < $nbs; $i++) {
                $condition = $this->_getZendQuery($subqueries[$i], $signs[$i]);
                if (is_null($signs[$i])) {
                    $orCondition[] = $condition;
                } elseif ($signs[$i]) {
                    $andCondition[] = $condition;
                } elseif (!$signs[$i]) {
                    $notCondition[] = $condition;
                }
            }
            $conditions = array();
            $zendCondition = '';
            $nband = count($andCondition);
            if ($nband) {
                $conditions[] = ($nband > 1) ? '(' . implode(' AND ', $andCondition) . ')' : $andCondition[0];
            }
            $nbor = count($orCondition);
            if ($nbor) {
                $conditions[] = ($nbor > 1) ? '(' . implode(' OR ', $orCondition) . ')' : $orCondition[0];
            }
            $nbnot = count($notCondition);
            if ($nbnot) {
                $conditions[] = ($nbnot > 1) ? 'NOT(' . implode(' AND ', $notCondition) . ')' : 'NOT ' . $notCondition[0];
            }

            $nbc = count($conditions);
            if ($nbc) {
                $zendCondition = ($nbc > 1) ? '(' . implode(' AND ', $conditions) . ')' : $conditions[0];
            }

            return $zendCondition;
        } elseif ($query instanceOf \ZendSearch\Lucene\Search\Query\Preprocessing\Phrase) {
            $phrase = $query->__toString();

            return $phrase;
        } elseif ($query instanceOf \ZendSearch\Lucene\Search\Query\Preprocessing\Term) {
            $term = $query->__toString();
            if ($sign || is_null($sign)) {
                $term = '*' . $term . '*'; // NOT excluded
            }

            return $term;
        }
    }

}

return 'AdvSearchZendController';