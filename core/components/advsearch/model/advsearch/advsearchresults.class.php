<?php

/**
 * AdvSearch - AdvSearchResults class
 *
 * @package 	AdvSearch
 * @author		Coroico
 * @copyright 	Copyright (c) 2012 by Coroico <coroico@wangba.fr>
 *
 * @tutorial	Class to get search results
 *
 */
class AdvSearchResults extends AdvSearchUtil {

    public $mainClass = 'modResource';
    public $primaryKey = 'id';
    public $mainFields = array();
    public $joinedFields = array();
    public $tvFields = array();
    public $resultsCount = 0;
    public $results = array();
    public $idResults = array();
    protected $offset = 0;
    protected $queryHook = null;
    protected $ids = array();
    protected $sortbyClass = array();
    protected $sortbyField = array();
    protected $sortbyDir = array();
    protected $mainWhereFields = array();
    protected $joinedWhereFields = array();
    protected $tvWhereFields = array();

    public function __construct(modX & $modx, array & $properties = array()) {
        parent::__construct($modx, $properties);
    }

    /**
     * Run the search
     */
    public function doSearch($asContext) {

        $this->searchString = $asContext['searchString'];
        $this->searchQuery = $asContext['searchQuery'];
        $this->offset = $asContext['offset'];
        $this->queryHook = $asContext['queryHook'];

        $this->_checkResultsParams();

        // get results
        if ($this->mainClass == 'modResource') {
            // default package (modResource + Tvs) and possibly joined packages
            $engine = $this->config['engine'];
            $results = ($engine == 'all') ? $this->_doAllSearch() : $this->doEngineSearch($engine);
        } else {
            // search in a different main package and possibly joined packages
            $results = $this->_customSearch();
        }

        return $results;
    }

    /**
     * Run the search for an engine (zend or mysql)
     *
     * @access private
     * @param string $engine zend or mysql engine
     * @return array Returns an array of results
     */
    public function doEngineSearch($engine) {
        // initialise the search - get relevant ids and hits
        $lstIds = '';
        $this->results = array();
        $hits = array();
        $c = $this->_initSearch($engine, $lstIds, $hits);

        if ($engine == 'mysql' || $lstIds) {
            //=============================  add selected modResource fields (docFields)
            $c->query['distinct'] = 'DISTINCT';
            $c->select($this->modx->getSelectColumns('modResource', 'modResource', '', $this->mainFields));

            //=============================  add TV where the search should occur (&withTVs parameter)
            foreach ($this->tvWhereFields as $tv) {
                $etv = $this->modx->escape($tv);
                $tvcv = $tv . '_cv';
                $etvcv = $this->modx->escape($tvcv);
                $c->leftJoin('modTemplateVar', $tv, array(
                    "{$etv}.`name` = '{$tv}'"
                ));
                $c->leftJoin('modTemplateVarResource', $tv . '_cv', array(
                    "{$etvcv}.`contentid` = `modResource`.`id`",
                    "{$etvcv}.`tmplvarid` = {$etv}.`id`"
                ));
                $c->select("IFNULL({$etvcv}.`value`, {$etv}.`default_text`) AS {$etv}");
            }

            // add joined resources
            $c = $this->_addJoinedResources($c);

            //============================= add pre-conditions (published, searchable, undeleted, lstIds, hideMenu, hideContainers)
            // restrict search to published, searcheable and undeleted modResource resources
            $c->andCondition(array('published' => '1', 'searchable' => '1', 'deleted' => '0'));

            // hideMenu
            if ($this->config['hideMenu'] == 0)
                $c->andCondition(array('hidemenu' => '0'));
            elseif ($this->config['hideMenu'] == 1)
                $c->andCondition(array('hidemenu' => '1'));

            // hideContainers
            if ($this->config['hideContainers'])
                $c->andCondition(array('isfolder' => '0'));

            // restrict search to $lstIds
            if (!empty($lstIds))
                $c->andCondition(array("modResource.id IN (" . $lstIds . ")"));

            // multiple context support
            if (!empty($this->config['contexts'])) {
                $contextArray = explode(',', $this->config['contexts']);
                $contexts = array();
                foreach ($contextArray as $ctx)
                    $contexts[] = $this->modx->quote($ctx);
                $context = implode(',', $contexts);
                unset($contexts, $ctx);
            } else {
                $context = $this->modx->quote($this->modx->context->get('key'));
            }
            $contextResourceTbl = $this->modx->getTableName('modContextResource');
            $c->andCondition(array(
                "(`modResource`.`context_key` IN ({$context}))"
            ));

            //============================= add searchString conditions on mainWhereFields, tvWhereFields and JoinedWhereFields
            if ($engine == 'mysql') {
                if (!empty($this->searchQuery)) {
                    $condition = $this->_getMysqlQuery($this->searchQuery);
                    $c->andCondition($condition);
                }
            }

            //============================= add query conditions
            if (!empty($this->queryHook['andConditions']))
                $c->andCondition($this->queryHook['andConditions']);


            //=============================  add an orderby clause for sorting fields of modResources (TVs are excluded from the list)
            $fields = array_intersect($this->sortbyField, $this->mainFields);
            if (count($fields)) {
                foreach ($fields as $field) {
                    $classfield = $this->sortbyClass["{$field}"] . $this->modx->escape($field);
                    $dir = $this->sortbyDir["{$field}"];
                    $c->sortby($classfield, $dir);
                }
            }

            if (empty($this->queryHook['stmt'])) {
                // debug mysql query
                if ($this->dbg)
                    $this->modx->log(modX::LOG_LEVEL_DEBUG, 'SearchString: ' . $this->searchString, '', 'doEngineSearch');
                if ($this->dbg)
                    $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Select before pagination: ' . $this->niceQuery($c), '', 'doEngineSearch');

                // get number of results before pagination
                $this->resultsCount = $this->modx->getCount('modResource', $c);
                if ($this->dbg)
                    $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Number of results before pagination: ' . $this->resultsCount, '', 'doEngineSearch');

                if ($this->resultsCount > 0) {
                    //============================= add query limits
                    $limit = $this->config['perPage'];
                    // offset,limit relevant only when results are sorted by fields from docFields (tvs and score excluded)
                    // If postHook is required we don't paginate the results
                    $offsetLimitSet = (count(array_intersect($this->sortbyField, $this->mainFields)) == count($this->sortbyField));
                    if ($offsetLimitSet && empty($this->config['postHook']))
                        $c->limit($limit, $this->offset);

                    // debug mysql query
                    if ($this->dbg)
                        $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Final select: ' . $this->niceQuery($c), '', 'doEngineSearch');

                    //============================= get results
                    $collection = $this->modx->getCollection('modResource', $c);

                    //============================= append & render tv fields (includeTVs, withTVs)
                    $this->results = $this->_appendTVsFields($collection);

                    //============================= add score field (sortby)
                    $this->results = $this->_addScoreField($engine, $this->results, $hits);

                    //============================= sort results (sortby)
                    $this->results = $this->_sortSearchResults($this->results);

                    //============================= set a subset (offset, perPage)
                    // offset,limit relevant only when results are sorted by fields different from docFields like tv and score.
                    $offsetLimitSet = (count(array_intersect($this->sortbyField, $this->mainFields)) != count($this->sortbyField));
                    if ($offsetLimitSet && empty($this->config['postHook']))
                        $this->results = array_slice($this->results, $this->offset, $this->config['perPage']);

                    //============================= prepare final results
                    $this->results = $this->_prepareResults($this->results);
                }
            }
            else {
                // run a new statement
                //============================= get results, append & render tv fields (includeTVs, withTVs)
                $this->results = $this->_runStmt($c);
                // get number of results before pagination
                $this->resultsCount = count($this->results);
                if ($this->dbg)
                    $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Number of results before pagination: ' . $this->resultsCount, '', 'doEngineSearch');

                //============================= set a subset (offset, perPage)
                if (empty($this->config['postHook']))
                    $this->results = array_slice($this->results, $this->offset, $this->config['perPage']);

                //============================= prepare final results
                $this->results = $this->_prepareResults($this->results);
            }
        }
        return $this->results;
    }

    /**
     *  Search inside zend lucene indexes and Mysql database both
     *
     * @access private
     */
    private function _doAllSearch() {
        // get results from zend Lucene indexes
        $zendResults = $this->doEngineSearch('zend');

        // get results from Mysql database
        $mysqlResults = $this->doEngineSearch('mysql');

        // merge results (zend lucene first then mysql), mysql override zend results
        $this->results = array_merge($zendResults, $mysqlResults);
        $this->resultsCount = count($this->results);
        return $this->results;
    }

    /**
     * Initialize search - Engine dependent
     *
     * @access private
     * @param string $engine zend or mysql engine
     * @param array $lstIds list of ids where to do the search
     * @param array $hit array of hit results after a zend search
     * @return xPDOQuery Returns the query
     */
    private function _initSearch($engine, & $lstIds, & $hits) {
        if ($engine == 'zend') {
            // open the index folder
            $index = Zend_Search_Lucene::open($this->config['docindexPath']);

            // increase the execution time of the script
            ini_set('max_execution_time', 0);

            // set up the zend query
            if ($this->searchString) {
                //change the length of non-wildcard prefix
                Zend_Search_Lucene_Search_Query_Wildcard::setMinPrefixLength(0);
                // do a search inside lucene index
                $zendQuery = $this->getZendQuery($this->searchQuery);
                try {
                    $hits = $index->find($zendQuery);
                } catch (Zend_Search_Lucene_Exception $ex) {
                    $hits = array();
                }
            }

            // pre-filtering hits with the ids parameter
            $lstIds = '';
            if (count($hits)) {
                $hitDocids = array();
                $fhits = array();
                foreach ($hits as $hit)
                    $hitDocids[] = $hit->docid;
                if ($this->config['ids']) {
                    $hitDocids = array_values(array_intersect($hitDocids, $this->ids)); // filtering by ids parameter
                    foreach ($hits as $hit) {
                        if (in_array($hit->id, $hitDocids))
                            $fhits[] = $hit;
                    }
                }
                $lstIds = implode(',', $hitDocids);
            }
        }
        else {
            $lstIds = $this->config['ids'];
            $hits = array();
        }
        if ($this->dbg)
            $this->modx->log(modX::LOG_LEVEL_DEBUG, 'lstIds: ' . $lstIds, '', '_initSearch');
        // set up a query to get results from database
        $c = $this->modx->newQuery('modResource');
        return $c;
    }

    /**
     * Get Zend query
     *
     * @access private
     * @param Zend_Search_Lucene_Search_Query $searchQuery The parsed query
     * @param boolean $process True if the condition should be processed
     * @return string Returns a condition
     */
    public function getZendQuery($query, $sign = true) {
        if ($query instanceOf Zend_Search_Lucene_Search_Query_Boolean) {
            $orCondition = array();
            $andCondition = array();
            $notCondition = array();
            $subqueries = $query->getSubqueries();
            $signs = $query->getSigns();
            $nbs = count($subqueries);
            for ($i = 0; $i < $nbs; $i++) {
                $condition = $this->getZendQuery($subqueries[$i], $signs[$i]);
                if (is_null($signs[$i]))
                    $orCondition[] = $condition;
                elseif ($signs[$i])
                    $andCondition[] = $condition;
                elseif (!$signs[$i])
                    $notCondition[] = $condition;
            }
            $conditions = array();
            $zendCondition = '';
            $nband = count($andCondition);
            if ($nband)
                $conditions[] = ($nband > 1) ? '(' . implode(' AND ', $andCondition) . ')' : $andCondition[0];
            $nbor = count($orCondition);
            if ($nbor)
                $conditions[] = ($nbor > 1) ? '(' . implode(' OR ', $orCondition) . ')' : $orCondition[0];
            $nbnot = count($notCondition);
            if ($nbnot)
                $conditions[] = ($nbnot > 1) ? 'NOT(' . implode(' AND ', $notCondition) . ')' : 'NOT ' . $notCondition[0];

            $nbc = count($conditions);
            if ($nbc)
                $zendCondition = ($nbc > 1) ? '(' . implode(' AND ', $conditions) . ')' : $conditions[0];
            return $zendCondition;
        }
        elseif ($query instanceOf Zend_Search_Lucene_Search_Query_Preprocessing_phrase) {
            $phrase = $query->__toString();
            return $phrase;
        } elseif ($query instanceOf Zend_Search_Lucene_Search_Query_Preprocessing_Term) {
            $term = $query->__toString();
            if ($sign || is_null($sign))
                $term = '*' . $term . '*'; // NOT excluded
            return $term;
        }
    }

    /**
     * Add search term conditions
     *
     * @access private
     * @param string $engine zend or mysql engine
     * @param xPDOQuery $c query in construction
     * @param Zend_Search_Lucene_Search_Query $searchQuery The parsed query
     * @return xPDOQuery Returns the modified query
     */
    private function _addQuerySearchConditions($engine, xPDOQuery $c, $searchQuery) {
        if ($engine == 'mysql') {
            if (!empty($searchQuery)) {
                $condition = $this->_getMysqlQuery($searchQuery);
                $c->andCondition($condition);
            }
        }
        return $c;
    }

    /**
     * Get MySQL condition from Zend_Search_Lucene_Search_Query
     *
     * @access private
     * @param Zend_Search_Lucene_Search_Query $searchQuery The parsed query
     * @param boolean $process True if the condition should be processed
     * @return string Returns a condition
     */
    private function _getMysqlQuery($query = null, $sign = true) {
        if ($query instanceOf Zend_Search_Lucene_Search_Query_Boolean) {
            $orCondition = array();
            $andCondition = array();
            $notCondition = array();
            $subqueries = $query->getSubqueries();
            $signs = $query->getSigns();
            $nbs = count($subqueries);
            for ($i = 0; $i < $nbs; $i++) {
                $condition = $this->_getMysqlQuery($subqueries[$i], $signs[$i]);
                if (is_null($signs[$i]))
                    $orCondition[] = $condition;
                elseif ($signs[$i])
                    $andCondition[] = $condition;
                elseif (!$signs[$i])
                    $notCondition[] = $condition;
            }
            $conditions = array();
            $mysqlCondition = '';
            $nband = count($andCondition);
            if ($nband)
                $conditions[] = ($nband > 1) ? '((' . implode(') AND (', $andCondition) . '))' : $andCondition[0];
            $nbor = count($orCondition);
            if ($nbor)
                $conditions[] = ($nbor > 1) ? '((' . implode(') OR (', $orCondition) . '))' : $orCondition[0];
            $nbnot = count($notCondition);
            if ($nbnot)
                $conditions[] = ($nbnot > 1) ? '((' . implode(') AND (', $notCondition) . '))' : $notCondition[0];

            $nbc = count($conditions);
            if ($nbc)
                $mysqlCondition = ($nbc > 1) ? '(' . implode(' AND ', $conditions) . ')' : $conditions[0];
            return $mysqlCondition;
        }
        else if ($query instanceOf Zend_Search_Lucene_Search_Query_Preprocessing_Phrase) {
            $phrase = substr($query->__toString(), 1, -1); // remove beginning and end quotes
            $condition = $this->_setCondition($phrase, $sign);
            return $condition;
        } else if ($query instanceOf Zend_Search_Lucene_Search_Query_Preprocessing_Term) {
            $term = $query->__toString();
            $condition = $this->_setCondition($term, $sign);
            return $condition;
        }
    }

    private function _setCondition($term, $sign) {
        $isLikeCondition = (($sign) || is_null($sign));
        if ($isLikeCondition)
            $like = 'LIKE';
        else
            $like = 'NOT LIKE';
        // replace lucene wildcards by MySql wildcards
        $pattern = array('#\*#', '#\?#');
        $replacement = array('%', '_');
        $term = preg_replace($pattern, $replacement, $term);
        $term = "%{$term}%";
        $orand = array();
        foreach ($this->mainWhereFields as $field)
            $orand[] = "`{$this->mainClass}`.`{$field}` {$like} '{$term}'";
        if (!empty($this->tvWhereFields))
            foreach ($this->tvWhereFields as $field)
                $orand[] = "`{$field}_cv`.`value` {$like} '{$term}'";
        if (!empty($this->joinedWhereFields))
            foreach ($this->joinedWhereFields as $field)
                $orand[] = "{$field} {$like} '{$term}'";
        $condition = '';
        if (count($orand)) {
            if ($isLikeCondition)
                $condition = '((' . implode(') OR (', $orand) . '))';
            else
                $condition = '((' . implode(') AND (', $orand) . '))';
        }
        return $condition;
    }

    /**
     * Run a statement & append rendered tv fields (includeTVs, withTVs)
     *
     * @access private
     * @return array Returns an array of results
     */
    private function _runStmt(xPDOQuery $c) {
        $results = array();
        $allowedTvNames = array_merge($this->tvWhereFields, $this->tvFields);
        $c->prepare();
        $sql = $c->toSQL();
        $patterns = array('{sql}');
        $replacements = array($sql);
        $sql = str_replace($patterns, $replacements, $this->queryHook['stmt']['execute']);
        if ($this->dbg)
            $this->modx->log(modX::LOG_LEVEL_DEBUG, 'sql: ' . $sql, '', '_runStmt');
        unset($c);
        $c = new xPDOCriteria($this->modx, $sql);
        if (!empty($this->queryHook['stmt']['prepare']))
            $c->bind($this->queryHook['stmt']['prepare']);
        $c->prepare();
        if ($c->stmt) {
            $exec = $c->stmt->execute();
            if ($exec) {
                if (count($allowedTvNames)) {
                    while ($row = $c->stmt->fetch(PDO::FETCH_ASSOC)) {
                        // Append & render tv fields (includeTVs, withTVs)
                        $tvs = array();
                        $templateVars = $this->modx->getCollection('modTemplateVar', array('name:IN' => $allowedTvNames));
                        foreach ($templateVars as $tv) {
                            $tvs[$tv->get('name')] = $tv->renderOutput($row['id']);
                        }
                        $results[] = array_merge($row, $tvs);
                    }
                } else {
                    $results = $c->stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                $c->stmt->closeCursor();
            }
        }
        unset($c);
        if ($this->dbg)
            $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Results: ' . print_r($results, true), '', '_runStmt');
        return $results;
    }

    /**
     * Append & render tv fields (includeTVs, withTVs)
     *
     * @access private
     * @param array of xPDOObjects $collection collection of search results
     * @return array Returns an array of results
     */
    private function _appendTVsFields($collection) {
        $results = array();
        $displayedFields = array_merge($this->mainFields, $this->joinedFields);
        $allowedTvNames = array_merge($this->tvWhereFields, $this->tvFields);

        if (count($allowedTvNames)) {
            foreach ($collection as $resourceId => $resource) {
                $result = $resource->get($displayedFields);
                $tvs = array();
                $templateVars = $this->modx->getCollection('modTemplateVar', array('name:IN' => $allowedTvNames));
                foreach ($templateVars as $tv) {
                    $tvs[$tv->get('name')] = $tv->renderOutput($result['id']);
                }
                $results[] = array_merge($result, $tvs);
            }
        } else {
            foreach ($collection as $resourceId => $resource) {
                $results[] = $resource->get($displayedFields);
            }
        }
        return $results;
    }

    /**
     * add search score as new field
     *
     * @access private
     * @param string $engine zend or mysql engine
     * @param array $results array of results
     * @param array $hits associative array of hits
     * @return array Returns a modified array of results
     */
    private function _addScoreField($engine, array $results, array $hits = null) {
        if ($this->config['fieldPotency'] != 'createdon:1') {
            $nbres = count($results);
            if ($engine == 'zend') {
                for ($i = 0; $i < $nbres; $i++) {
                    // add lucene score
                    $results[$i] = array_merge($results[$i], array("score" => $hits[$i]->score));
                }
            } else {
                // use fieldPotency parameter to calculate the score
                $fieldPotency = explode(',', $this->config['fieldPotency']);
                $fldps = array();
                foreach ($fieldPotency as $key => $value)
                    list($fldps[$key]['field'], $fldps[$key]['weight']) = explode(':', $value);
                for ($i = 0; $i < $nbres; $i++) {
                    $results[$i]['score'] = 0;
                    foreach ($fldps as $fldp) {
                        $field = strtolower($results[$i][$fldp['field']]);
                        $results[$i]['score'] += $this->_getScoreQuery($field, $fldp['weight'], $this->searchQuery);
                    }
                }
            }
        }
        return $results;
    }

    /**
     * Get score from Zend_Search_Lucene_Search_Query
     *
     * @access private
     * @param Zend_Search_Lucene_Search_Query $searchQuery The parsed query
     * @param boolean $process True if the condition should be processed
     * @return integer Returns a score
     */
    private function _getScoreQuery($field, $weight, $query = null, $sign = true) {
        if ($query instanceOf Zend_Search_Lucene_Search_Query_Boolean) {
            $orScore = array();
            $andScore = array();
            $subqueries = $query->getSubqueries();
            $signs = $query->getSigns();
            $nbs = count($subqueries);
            for ($i = 0; $i < $nbs; $i++) {
                $score = $this->_getScoreQuery($field, $weight, $subqueries[$i], $signs[$i]);
                if (is_null($signs[$i]))
                    $orScore[] = $score;
                elseif ($signs[$i])
                    $andScore[] = $score;
            }
            $scores = array();
            $score = 0;
            $nband = count($andScore);
            if ($nband)
                $scores[] = min($andScore);

            $nbor = count($orScore);
            if ($nbor)
                $scores[] = array_sum($orScore);

            $nbs = count($scores);
            if ($nbs)
                $score = min($scores);
            return $score;
        }
        else if ($query instanceOf Zend_Search_Lucene_Search_Query_Preprocessing_Phrase) {
            $phrase = strtolower(substr($query->__toString(), 1, -1)); // remove beginning and end quotes
            $score = $weight * mb_substr_count($field, $phrase);
            return $score;
        } else if ($query instanceOf Zend_Search_Lucene_Search_Query_Preprocessing_Term) {
            $term = strtolower($query->__toString());
            $score = $weight * mb_substr_count($field, $term);
            return $score;
        }
    }

    /**
     * Sort results by TVs or score
     *
     * @access private
     * @param array $results array of results
     * @return array Returns a modified array of results
     */
    private function _sortSearchResults($results) {
        // if needed finalize the sort with TVs (fields not included in mainFields) or score (fieldPotency)
        // results are already sorted by fields from modResource
        if ($this->config['fieldPotency'] != 'createdon:1') {
            foreach ($results as $key => $row) {
                $score[$key] = $row['score'];
				if (count($results) == count($score)) array_multisort($score, SORT_DESC, $results);
            }
        } else if ($this->config['sortby']) {
            foreach ($this->sortbyField as $field) {
                if (!in_array($field, $this->mainFields)) {
                    $col = array();
                    foreach ($results as $key => $row) {
                        $col[$key] = $row[$field];
                    }
                    $dir = ($this->sortbyDir["{$field}"] == 'DESC') ? SORT_DESC : SORT_ASC;
                    array_multisort($col, $dir, $results);
                }
            }
        }
        return $results;
    }

    /**
     * Prepare results
     *
     * @access private
     * @param array $results array of results
     * @param integer $offset offset of the result page
     * @return xPDOQuery Returns the modified query
     */
    private function _prepareResults($results) {
        // return search results as an associative array with id as key
        $searchResults = array();
        foreach ($results as $result) {
            $index = 'as' . $result['id'];
            $searchResults[$index] = $result;
            $this->idResults[] = $result['id'];
        }

        // set lstIdResults
        $lstIdResults = implode(',', $this->idResults);

        if ($this->dbg) {
            $this->modx->log(modX::LOG_LEVEL_DEBUG, 'lstIdsResults: ' . $lstIdResults, '', '_prepareResults');
        }

        return $searchResults;
    }

    /**
     * Check the parameters for results part
     *
     * @access private
     * @tutorial Whatever the main class (modResource or an other class) params run the same check process
     *           Some initial values could be overried by values from the query hook
     */
    private function _checkResultsParams() {

        if (!empty($this->queryHook['main'])) { // a new main package is declared in query hook
            $msg = '';
            if (empty($this->queryHook['main']['package']))
                $msg = 'Main - Package name should be declared in queryHook';
            elseif (empty($this->queryHook['main']['packagePath']))
                $msg = 'Main - Package path should be declared in queryHook';
            elseif (empty($this->queryHook['main']['class']))
                $msg = 'Main - Class name should be defined in queryHook';
            if (!empty($msg)) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] ' . $msg, '', '_checkResultsParams');
                return false;
            }
            $pattern = '{core_path}';   // wilcard used inside the pathname definition
            $replacement = $this->modx->getOption('core_path', null, MODX_CORE_PATH);
            $this->mainClass = $this->queryHook['main']['class'];  // main class
            $this->queryHook['main']['packagePath'] = str_replace($pattern, $replacement, $this->queryHook['main']['packagePath']);
			$tablePrefix = isset($this->queryHook['main']['tablePrefix']) ? $this->queryHook['main']['tablePrefix'] : '';
            $this->modx->addPackage($this->queryHook['main']['package'], $this->queryHook['main']['packagePath'], $tablePrefix); // add package
            $this->primaryKey = $this->modx->getPK($this->mainClass); // get primary key
        }

        // &contexts [ comma separated context names | $modx->context->get('key') ]
        $lstContexts = $this->modx->getOption('contexts', $this->config, $this->modx->context->get('key'));
        $this->config['contexts'] = implode(',', array_map('trim', explode(',', $lstContexts)));

        // &docindexPath [ path | 'assets/files/docindex/' ]
        $path = $this->modx->getOption('docindexPath', $this->config, 'docindex/');
        $this->config['docindexPath'] = $this->modx->getOption('assets_path') . 'files/' . $path;

        // &engine [ 'mysql' | 'zend' | 'all' ] - name of search engine to use
        $engine = strtolower(trim($this->modx->getOption('engine', $this->config, 'mysql')));
        $this->config['engine'] = (($engine == 'all') || ($engine == 'zend') || ($engine == 'mysql')) ? $engine : 'mysql';

        // &fields [csv list of fields | 'pagetitle,longtitle,alias,description,introtext,content' (modResource)  '' otherwise ]
        $lstFields = $this->modx->getOption('fields', $this->config, 'pagetitle,longtitle,alias,description,introtext,content');
        if (!empty($this->queryHook['main']['fields']))
            $lstFields = $this->queryHook['main']['fields'];
        $fields = array();
        if (!empty($lstFields)) {
            $fields = array_map('trim', explode(',', $lstFields));
        }
        $this->config['fields'] = implode(',', $fields);

        // initialise mainFields : 'id', 'template', 'context_key', 'createdon' + docFields for modResource
        if ($this->mainClass == 'modResource')
            $requiredFields = array('id', 'template', 'context_key', 'createdon');
        else
            $requiredFields = array($this->primaryKey);
        $this->mainFields = array_merge($requiredFields, $fields);

        // &fieldPotency - [ comma separated list of couple (field : potency) | 'createdon:1' (modResource) ]
        $lstFieldPotency = $this->modx->getOption('fieldPotency', $this->config, 'createdon:1');
        if (!empty($lstFieldPotency)) {
            $fieldPotency = array_map('trim', explode(',', $lstFieldPotency));
            $checkedFieldPotency = array();
            foreach ($fieldPotency as $fldp) {
                $fld = array_map('trim', explode(':', $fldp));
                $fld[1] = (isset($fld[1]) && intval($fld[1])) ? $fld[1] : 1;
                $checkedFieldPotency[] = implode(':', $fld);
            }
            $this->config['fieldPotency'] = implode(',', $checkedFieldPotency);
        }
        else
            $this->config['fieldPotency'] = $lstFieldPotency;

        // &withFields [csv list of fields | 'pagetitle,longtitle,alias,description,introtext,content' (modResource) '' (all fields) otherwise]
        $lstWithFields = $this->modx->getOption('withFields', $this->config, 'pagetitle,longtitle,alias,description,introtext,content');
        if (!empty($this->queryHook['main']['withFields']))
            $lstWithFields = $this->queryHook['main']['withFields'];
        if (!empty($lstWithFields)) {
            $this->mainWhereFields = array_map('trim', explode(',', $lstWithFields));
            $this->config['withFields'] = implode(',', $this->mainWhereFields);
        }
        else
            $this->config['withFields'] = $lstWithFields;

        if ($this->mainClass == 'modResource') {
            // &hideMenu [ 0 | 1 | 2 ]  Search in hidden documents from menu.
            $hideMenu = (int) $this->modx->getOption('hideMenu', $this->config, 2);
            $this->config['hideMenu'] = (($hideMenu < 3) && ($hideMenu >= 0)) ? $hideMenu : 2;

            // &includeTVs - [ comma separated tv names | '' ]
            $lstIncludeTVs = $this->modx->getOption('includeTVs', $this->config, '');
            if (!empty($lstIncludeTVs)) {
                $this->tvFields = array_map('trim', explode(',', $lstIncludeTVs));
                $this->config['includeTVs'] = implode(',', $this->tvFields);
            }
            else
                $this->config['includeTVs'] = $lstIncludeTVs;

            // &withTVs - [ a comma separated list of TV names | '' ]
            $lstWithTVs = $this->modx->getOption('withTVs', $this->config, '');
            if (!empty($lstWithTVs)) {
                $this->tvWhereFields = array_map('trim', explode(',', $lstWithTVs));
                $this->config['withTVs'] = implode(',', $this->tvWhereFields);
            }
            else
                $this->config['withTVs'] = $lstWithTVs;

            // remove doubles between withTVs and includeTVs parameters
            $this->tvFields = array_unique(array_merge($this->tvWhereFields, $this->tvFields));
        }

        // &ids [ comma separated list of Ids | '' ] - ids or primary keys for custom package
        $lstIds = $this->modx->getOption('ids', $this->config, '');
        if (!empty($this->queryHook['main']['lstIds']))
            $lstIds = $this->queryHook['main']['lstIds'];
        if (!empty($lstIds)) {
            $this->ids = array_map('trim', explode(',', $lstIds));
            $this->config['ids'] = implode(',', $this->ids);
        }
        else
            $this->config['ids'] = $lstIds;

        // &perPage [ int | 10 ] - Set to 0 if unlimited
        $perPage = (int) $this->modx->getOption('perPage', $this->config, 10);
        if ((!empty($this->queryHook)) && (!empty($this->queryHook['perPage'])))
            $perPage = $this->queryHook['perPage'];
        $this->config['perPage'] = (($perPage >= 0)) ? $perPage : 10;

        // &sortby - comma separated list of couple "field [ASC|DESC]" to sort by.
        // field from joined resource should be named resourceName_fieldName. e.g: quipComment_body
        $lstSortby = $this->modx->getOption('sortby', $this->config, 'createdon DESC');
        if (!empty($this->queryHook['main']['sortby']))
            $lstSortby = $this->queryHook['main']['sortby'];
        if (!empty($this->queryHook['sortby']))
            $lstSortby = $this->queryHook['sortby']; // override the custom declaration
        if (!empty($lstSortby)) {
            $sortCpls = array_map('trim', explode(',', $lstSortby));
            $sorts = array();
            $this->sortbyField = array();
            $this->sortbyDir = array();
            foreach ($sortCpls as $sortCpl) {
                $sortElts = array_map('trim', explode(' ', $sortCpl));
                $dir = (empty($sortElts[1])) ? 'DESC' : $sortElts[1];
                $dir = (($dir != 'DESC') && ($dir != 'ASC')) ? 'DESC' : $dir;
                $classFieldElts = array_map('trim', explode('.', $sortElts[0]));
                $class = (count($classFieldElts) == 2) ? $classFieldElts[0] : '';
                $field = (count($classFieldElts) == 2) ? $classFieldElts[1] : $classFieldElts[0];
                $this->sortbyField[] = $field;
                $this->sortbyClass["{$field}"] = (!empty($class)) ? $this->modx->escape($class) . '.' : '';
                $this->sortbyDir["{$field}"] = $dir;
                $sorts[] = "{$field} {$dir}";
            }
            $this->config['sortby'] = implode(',', $sorts);
        }
        else
            $this->config['sortby'] = $lstSortby;

        if ($this->dbg)
            $this->modx->log(modX::LOG_LEVEL_DEBUG, '[AdvSearch] Config parameters after checking: ' . print_r($this->config, true), '', '_checkResultsParams');

        return;
    }

    /**
     * Search in custom packages
     *
     * @access private
     * @return array $results array of results
     */
    private function _customSearch() {
        $results = array();
        $pattern = '{core_path}';
        $replacement = $this->modx->getOption('core_path', null, MODX_CORE_PATH);

        $main = $this->queryHook['main'];

        // set query from main package
        $c = $this->modx->newQuery($main['class']);

        // initialize and add main displayed fields
        $c->query['distinct'] = 'DISTINCT';
        $c->select($this->modx->getSelectColumns($main['class'], $main['class'], '', $this->mainFields));

        // restrict search to specific keys ($lstIds)
        if (!empty($this->config['ids']))
            $c->andCondition(array("{$this->mainClass}.{$this->primaryKey} IN (" . $this->config['ids'] . ")"));

        // restrict search with where condition
        if (!empty($main['where'])) {
            if (!is_array($main['where']))
                $c->andCondition(array($main['where']));
            else
                $c->andCondition($main['where']);
        }

        // add joined resources
        $c = $this->_addJoinedResources($c);

        //============================= add searchString conditions on mainWhereFields and joinedWhereFields
        if (!empty($this->searchQuery)) {
            $condition = $this->_getMysqlQuery($this->searchQuery);
            $c->andCondition($condition);
        }

        //============================= add query conditions
        if (!empty($this->queryHook['andConditions']))
            $c->andCondition($this->queryHook['andConditions']);

        //=============================  add an orderby clause for selected fields
        if (!empty($this->sortbyField)) {
            foreach ($this->sortbyField as $field) {
                $classfield = $this->sortbyClass["{$field}"] . $this->modx->escape($field);
                $dir = $this->sortbyDir["{$field}"];
                $c->sortby($classfield, $dir);
            }
        }

        // debug mysql query
        if ($this->dbg)
            $this->modx->log(modX::LOG_LEVEL_DEBUG, 'SearchString: ' . $this->searchString, '', '_customSearch');
        if ($this->dbg)
            $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Select before pagination: ' . $this->niceQuery($c), '', '_customSearch');

        // get number of results before pagination
        $this->resultsCount = $this->modx->getCount($main['class'], $c);
        if ($this->dbg)
            $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Number of results before pagination: ' . $this->resultsCount, '', '_customSearch');

        $idResults = array();  // list of primary keys
        if ($this->resultsCount > 0) {
            //============================= add query limits
            $limit = $this->config['perPage'];
            $c->limit($limit, $this->offset);

            // debug mysql query
            if ($this->dbg)
                $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Final select: ' . $this->niceQuery($c), '', '_customSearch');

            //============================= get results
            $collection = $this->modx->getCollection($main['class'], $c);
            if (!empty($collection)) {
                $fields = array_merge($this->mainFields, $this->joinedFields);
                foreach ($collection as $resource) {
                    $pkValue = $resource->get($this->primaryKey);
                    $this->results["{$pkValue}"] = $resource->get($fields);
                    $idResults[] = $pkValue;
                }
            }
        }
        // set lstIdResults
        $lstIdResults = implode(',', $idResults);

        if ($this->dbg) {
            $this->modx->log(modX::LOG_LEVEL_DEBUG, "lstIdsResults:" . $lstIdResults, '', '_customSearch');
        }
        return $this->results;
    }

    /**
     * Add joined resources to the main resource
     *
     * @access private
     * @param xPDOQuery $c query in construction
     * @return xPDOQuery $c updated query
     */
    private function _addJoinedResources(xPDOQuery $c) {
        if (!empty($this->queryHook['joined'])) {
            $pattern = '{core_path}';
            $replacement = $this->modx->getOption('core_path', null, MODX_CORE_PATH);
            $joineds = $this->queryHook['joined'];
            foreach ($joineds as $joined) {
                if (!empty($joined['joinCriteria'])) {
                    $joinedClass = $joined['class'];
                    // add package
                    $joined['packagePath'] = str_replace($pattern, $replacement, $joined['packagePath']);
					$tablePrefix = isset($joined['tablePrefix']) ? $joined['tablePrefix'] : '';
					$this->modx->addPackage($joined['package'],$joined['packagePath'], $tablePrefix);
                    // initialize and add joined displayed fields
                    if (!empty($joined['withFields'])) {
                        $joinedWhereFields = array_map('trim', explode(',', $joined['withFields']));    // fields of joined table where to do the search
                        if (!empty($joined['fields']))
                            $joinedFields = array_map('trim', explode(',', $joined['fields']));    // fields of joined table to display
                        else
                            $joinedFields = $joinedWhereFields;
                    }
                    else {
                        if (!empty($joined['fields'])) {
                            $joinedFields = array_map('trim', explode(',', $joined['fields']));
                            $joinedWhereFields = $joinedFields;
                        }
                    }

					$joinedAlias = isset($joined['alias']) ? $joined['alias'] : $joinedClass;
					foreach($joinedWhereFields as & $joinedWhereField) 
						$joinedWhereField = $this->modx->escape($joinedAlias) . '.' . $this->modx->escape($joinedWhereField);
                    $this->joinedWhereFields = array_merge($this->joinedWhereFields, $joinedWhereFields);
                    // add joined fields
					$c->select($this->modx->getSelectColumns($joinedClass,$joinedAlias,"{$joinedAlias}_",$joinedFields));
					foreach($joinedFields as & $joinedField) 
						$joinedField = "{$joinedAlias}_{$joinedField}"; // all the fields of joined class are prefixed by classname_  
                    $this->joinedFields = array_merge($this->joinedFields, $joinedFields);
                    // add left join
                    list($leftCriteria, $rightCriteria) = array_map('trim', explode('=', $joined['joinCriteria']));
                    $leftCriteriaElts = array_map('trim', explode('.', $leftCriteria));
					$leftCriteria = (count($leftCriteriaElts) == 1) ? "`{$joinedAlias}`.`{$leftCriteriaElts[0]}`" : "`{$leftCriteriaElts[0]}`.`{$leftCriteriaElts[1]}`";
                    $rightCriteriaElts = array_map('trim', explode('.', $rightCriteria));
					$rightCriteria = (count($rightCriteriaElts) == 1) ? "`{$joinedAlias}`.`{$rightCriteriaElts[0]}`" : "`{$rightCriteriaElts[0]}`.`{$rightCriteriaElts[1]}`";
                    $joined['joinCriteria'] = "{$leftCriteria} = {$rightCriteria}";
					$c->leftJoin($joinedClass,$joinedAlias,$joined['joinCriteria']);
                    // restrict search with a where condition on joined resource
                    if (!empty($joined['where'])) {
                        if (!is_array($joined['where']))
                            $c->andCondition(array($joined['where']));
                        else
                            $c->andCondition($joined['where']);
                    }
                }
            }
        }
        return $c;
    }

}