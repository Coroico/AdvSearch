<?php

if (!class_exists('AdvSearchEngineController')) {
    include_once dirname(__FILE__) . '/advsearch.engine.controller.class.php';
}

class AdvSearchCustomController extends AdvSearchEngineController {

    public function getResults($asContext) {
        if (empty($asContext['queryHook'])) {
            $msg = 'Missing query hook for engine: "custom"';
            $this->setError($msg);
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] ' . $msg, '', __METHOD__, __FILE__, __LINE__);
            return false;
        }
        $main = $asContext['queryHook']['main'];
        $this->mainClass = $main['class'];  // main class
        $main['packagePath'] = $this->replacePropPhs($main['packagePath']);
        $tablePrefix = isset($main['tablePrefix']) ? $main['tablePrefix'] : '';
        $this->modx->addPackage($main['package'], $main['packagePath'], $tablePrefix); // add package
        $this->primaryKey = $this->modx->getPK($this->mainClass); // get primary key

        // set query from main package
        $c = $this->modx->newQuery($main['class']);
        // add joined resources
        $c = $this->addJoinedResources($c, $asContext);
        $fields = array_merge((array) $main['mainFields'], (array)$main['joinedFields']);
        if (!in_array('id', $fields)) {
            $fields = array_merge(array('id'), $fields);
        }
        // initialize and add main displayed fields
        $c->distinct();
        $c->select($this->modx->getSelectColumns($main['class'], $main['class'], '', $fields));

        // restrict search to specific keys ($lstIds)
        if (!empty($this->config['ids'])) {
            $c->andCondition(array("{$this->mainClass}.{$this->primaryKey} IN (" . $this->config['ids'] . ")"));
        }

        // restrict search with where condition
        if (!empty($main['where'])) {
            if (!is_array($main['where'])) {
                $c->andCondition(array($main['where']));
            } else {
                $c->andCondition($main['where']);
            }
        }

        //============================= add query conditions
        if (!empty($asContext['queryHook']['andConditions'])) {
            $c->andCondition($asContext['queryHook']['andConditions']);
        }

        //=============================  add an orderby clause for selected fields
        if (!empty($asContext['sortby'])) {
            foreach ($asContext['sortby'] as $field => $dir) {
                $classFieldX = array_map('trim', explode('.', $field));
                foreach ($classFieldX as $k => $v) {
                    $classFieldX[$k] = $this->modx->escape($v);
                }
                $field = @implode('.', $classFieldX);
                $c->sortby($field, $dir);
            }
        }

        // debug mysql query
        if ($this->dbg) {
            $this->modx->log(modX::LOG_LEVEL_DEBUG, 'SearchString: ' . $this->searchString, '', '_customSearch');
            $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Select before pagination: ' . $this->niceQuery($c), '', '_customSearch');
        }

        // get number of results before pagination
        $this->resultsCount = $this->getQueryCount($main['class'], $c);
        if ($this->dbg) {
            $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Number of results before pagination: ' . $this->resultsCount, '', '_customSearch');
        }

        if ($this->resultsCount > 0) {
            //============================= add query limits
            $limit = $this->config['perPage'];
            $c->limit($limit, $this->offset);

            // debug mysql query
            if ($this->dbg) {
                $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Final select: ' . $this->niceQuery($c), '', '_customSearch');
            }

            //============================= get results
            $collection = $this->modx->getCollection($main['class'], $c);
            if (!empty($collection)) {
                foreach ($collection as $resource) {
                    $pkValue = $resource->get($this->primaryKey);
                    $this->results["{$pkValue}"] = $resource->toArray('', false, true);
                    $this->idResults[] = $pkValue;
                }
            }
        }

        if ($this->dbg) {
            $this->modx->log(modX::LOG_LEVEL_DEBUG, "lstIdsResults:" . @implode(',', $this->idResults), '', '_customSearch');
        }

        return $this->results;
    }

}

return 'AdvSearchCustomController';