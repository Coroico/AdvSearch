<?php

include_once dirname(dirname(__FILE__)) . "/model/advsearch/advsearch.class.php";

abstract class AdvSearchEngineController extends AdvSearch {

    public $modx;
    public $config;
    public $mainClass = 'modResource';
    public $primaryKey = 'id';
    public $mainFields = array();
    public $joinedFields = array();
    public $tvFields = array();
    public $resultsCount = 0;
    public $results = array();
    public $idResults = array();
    public $searchString;
    public $searchTerms = array();
    public $limit;
    protected $page = 1;
    protected $queryHook = null;
    protected $ids = array();
    protected $sortbyClass = array();
    protected $sortbyField = array();
    protected $sortbyDir = array();
    protected $mainWhereFields = array();
    protected $joinedWhereFields = array();
    protected $tvWhereFields = array();

    public function __construct(modX $modx, $config) {
        $this->modx = & $modx;
        $this->config = $config;

        // increase the execution time of the script
        if (function_exists('ini_get') && !ini_get('safe_mode')) {
            if (function_exists('set_time_limit')) {
                set_time_limit(1000);
            }
            if (function_exists('ini_set')) {
                if (ini_get('max_execution_time') !== 1000) {
                    ini_set('max_execution_time', 1000);
                }
            }
        }
    }

    abstract function getResults($asContext);

    public function getResultsCount() {
        return $this->resultsCount;
    }

    public function getSearchString() {
        return $this->searchString;
    }

    public function getSearchTerms() {
        return $this->searchTerms;
    }

    public function getPage(){
        return $this->page;
    }

    protected function setPage($page){
        $this->page = $page;
    }

    /**
     * Add joined resources to the main resource
     *
     * @access protected
     * @param xPDOQuery $c query in construction
     * @return xPDOQuery $c updated query
     */
    protected function addJoinedResources(xPDOQuery & $c, $asContext) {
        if (empty($asContext['queryHook']['joined'])) {
            return $c;
        }
        $pattern = '{core_path}';
        $replacement = $this->modx->getOption('core_path', null, MODX_CORE_PATH);
        $joineds = $asContext['queryHook']['joined'];
        foreach ($joineds as $joined) {
            if (!empty($joined['joinCriteria'])) {
                $joinedClass = $joined['class'];
                // add package
                $joined['packagePath'] = str_replace($pattern, $replacement, $joined['packagePath']);
                $tablePrefix = isset($joined['tablePrefix']) ? $joined['tablePrefix'] : '';
                $this->modx->addPackage($joined['package'], $joined['packagePath'], $tablePrefix);
                // initialize and add joined displayed fields
                if (!empty($joined['withFields'])) {
                    $joinedWhereFields = array_map('trim', explode(',', $joined['withFields']));    // fields of joined table where to do the search
                    if (!empty($joined['fields'])) {
                        $joinedFields = array_map('trim', explode(',', $joined['fields']));    // fields of joined table to display
                    } else {
                        $joinedFields = $joinedWhereFields;
                    }
                } else {
                    if (!empty($joined['fields'])) {
                        $joinedFields = array_map('trim', explode(',', $joined['fields']));
                        $joinedWhereFields = $joinedFields;
                    }
                }

                $joinedAlias = isset($joined['alias']) ? $joined['alias'] : $joinedClass;
                foreach ($joinedWhereFields as & $joinedWhereField) {
                    $joinedWhereField = $this->modx->escape($joinedAlias) . '.' . $this->modx->escape($joinedWhereField);
                }
                $this->joinedWhereFields = array_merge($this->joinedWhereFields, $joinedWhereFields);
                // add joined fields
                $c->select($this->modx->getSelectColumns($joinedClass, $joinedAlias, "{$joinedAlias}_", $joinedFields));
                foreach ($joinedFields as & $joinedField) {
                    $joinedField = "{$joinedAlias}_{$joinedField}"; // all the fields of joined class are prefixed by classname_
                }
                $this->joinedFields = array_merge($this->joinedFields, $joinedFields);
                // add left join
                list($leftCriteria, $rightCriteria) = array_map('trim', explode('=', $joined['joinCriteria']));
                $leftCriteriaElts = array_map('trim', explode('.', $leftCriteria));
                $leftCriteria = (count($leftCriteriaElts) == 1) ? "`{$joinedAlias}`.`{$leftCriteriaElts[0]}`" : "`{$leftCriteriaElts[0]}`.`{$leftCriteriaElts[1]}`";
                $rightCriteriaElts = array_map('trim', explode('.', $rightCriteria));
                $rightCriteria = (count($rightCriteriaElts) == 1) ? "`{$joinedAlias}`.`{$rightCriteriaElts[0]}`" : "`{$rightCriteriaElts[0]}`.`{$rightCriteriaElts[1]}`";
                $joined['joinCriteria'] = "{$leftCriteria} = {$rightCriteria}";
                $c->leftJoin($joinedClass, $joinedAlias, $joined['joinCriteria']);
                // restrict search with a where condition on joined resource
                if (!empty($joined['where'])) {
                    if (!is_array($joined['where'])) {
                        $c->andCondition(array($joined['where']));
                    } else {
                        $c->andCondition($joined['where']);
                    }
                }
            }
        }

        return $c;
    }
}
