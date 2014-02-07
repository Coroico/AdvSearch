<?php

include_once dirname(__FILE__) . '/advsearchenginecontroller.class.php';

class AdvSearchMysqlController extends AdvSearchEngineController {

    public function getResults($asContext, $fulltext = true) {
        $c = $this->modx->newQuery('modResource');
        //=============================  add selected modResource fields (docFields)
        $c->query['distinct'] = 'DISTINCT';
        $c->select($this->modx->getSelectColumns('modResource', 'modResource', '', $asContext['mainFields']));

        // multiple parents support
        if (!empty($this->config['parents'])) {
            $parentArray = explode(',', $this->config['parents']);
            $parents = array();
            foreach ($parentArray as $parent) {
                $parents[] = $parent;
            }
            $c->where(array(
                'modResource.parent:IN' => $parents
            ));
        }
        // multiple ids support
        if (!empty($this->config['ids'])) {
            $ids = array_map('trim', explode(',', $this->config['ids']));
            $c->where(array(
                'modResource.id:IN' => $ids
            ));
        }
        // hideLinks
        if ($this->config['hideLinks']) {
            $c->where(array(
                'class_key:!=' => 'modSymLink',
                'class_key:!=' => 'modWebLink',
                    ), 'OR');
        }
//$fulltext = false;
        if (!empty($asContext['searchString'])) {
            if ($fulltext) {
                // fulltext searching
//                $mainFields = array();
//                foreach ($asContext['mainFields'] as $field) {
//                    $mainFields[] = 'modResource.' . $field;
//                }
//                $mainFields = @implode(',', $mainFields);
//                $c->select("MATCH ($mainFields) AGAINST ('{$asContext['searchString']}' IN BOOLEAN MODE) AS mainFields_score");
//                $c->where("MATCH ($mainFields) AGAINST ('{$asContext['searchString']}' IN BOOLEAN MODE)");
//
//                //=============================  add TV where the search should occur (&withTVs parameter)
//                if (!empty($asContext['tvWhereFields']) && !empty($asContext['searchString'])) {
//                    $tvWhereFields = array();
//                    foreach ($asContext['tvWhereFields'] as $tv) {
//                        $tvWhereFields[] = '`'.$tv.'_cv`.`value`';
//                    }
//                    $tvWhereFields = @implode(',', $tvWhereFields);
//                    $c->select("MATCH ($tvWhereFields) AGAINST ('{$asContext['searchString']}' IN BOOLEAN MODE) AS tvWhereFields_score");
//                    $c->orCondition("MATCH ($tvWhereFields) AGAINST ('{$asContext['searchString']}' IN BOOLEAN MODE)");
//                }

                // fulltext searching
                $fields = array();
                foreach ($asContext['mainFields'] as $field) {
                    $fields[] = 'modResource.' . $field;
                }
                //=============================  add TV where the search should occur (&withTVs parameter)
                if (!empty($asContext['tvWhereFields']) && !empty($asContext['searchString'])) {
                    foreach ($asContext['tvWhereFields'] as $tv) {
                        $fields[] = '`'.$tv.'_cv`.`value`';
                    }
                }
                $fields = @implode(',', $fields);
                $c->select("MATCH ($fields) AGAINST ('{$asContext['searchString']}' IN BOOLEAN MODE) AS score");
                $c->where("MATCH ($fields) AGAINST ('{$asContext['searchString']}' IN BOOLEAN MODE)");
            } else {
                // textlike searching
                $searchStrings = array_map('trim', @explode(' ', $asContext['searchString']));
                $conditions = array();
                foreach ($asContext['mainFields'] as $field) {
                    if ($field === 'id' || $field === 'template') {
                        continue;
                    }
                    foreach ($searchStrings as $string) {
                        $conditions[] = array(
                            'modResource.' . $field . ':LIKE' => "%$string%"
                        );
                    }
                }

                //=============================  add TV where the search should occur (&withTVs parameter)
                if (!empty($asContext['tvWhereFields']) && !empty($asContext['searchString'])) {
                    foreach ($asContext['tvWhereFields'] as $tv) {
                        if (!empty($asContext['searchString'])) {
                            $searchStrings = array_map('trim', @explode(' ', $asContext['searchString']));
                            foreach ($searchStrings as $string) {
                                $conditions[] = array(
                                    $this->modx->escape($tv . '_cv.value') . ':LIKE' => "%$string%",
                                );
                            }
                        }
                    }
                }

                $c->orCondition(array($conditions));
            }
        }

        if (!empty($asContext['tvWhereFields'])) {
            foreach ($asContext['tvWhereFields'] as $tv) {
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
        }

        // add joined resources
//        $c = $this->_addJoinedResources($c);

        //============================= add pre-conditions (published, searchable, undeleted, lstIds, hideMenu, hideContainers)
        // restrict search to published, searcheable and undeleted modResource resources
        $c->andCondition(array('published' => '1', 'searchable' => '1', 'deleted' => '0'));

        // hideMenu
        if ($this->config['hideMenu'] == 0) {
            $c->andCondition(array('hidemenu' => '0'));
        } elseif ($this->config['hideMenu'] == 1) {
            $c->andCondition(array('hidemenu' => '1'));
        } else {
            // if &hideMenu=2 or anything else, show all
        }

        // hideContainers
        if ($this->config['hideContainers']) {
            $c->andCondition(array('isfolder' => '0'));
        }

        // multiple context support
        $contexts = array();
        if (!empty($this->config['contexts'])) {
            $contextArray = explode(',', $this->config['contexts']);
            foreach ($contextArray as $ctx) {
                $contexts[] = $ctx;
            }
        } else {
            $contexts[] = $this->modx->context->get('key');
        }
        $c->where(array(
            'modResource.context_key:IN' => $contexts
        ));

        //============================= add query conditions
        if (!empty($asContext['queryHook']['andConditions'])) {
            $c->andCondition($asContext['queryHook']['andConditions']);
        }

        if (!empty($asContext['queryHook']['orConditions'])) {
            $c->orCondition($asContext['queryHook']['orConditions']);
        }

        //=============================  add an orderby clause for selected fields
        if (!empty($asContext['sortbyField'])) {
            foreach ($asContext['sortbyField'] as $field) {
                $classfield = $asContext['sortbyClass']["{$field}"] . $this->modx->escape($field);
                $dir = $asContext['sortbyDir']["{$field}"];
                $c->sortby($classfield, $dir);
            }
        }

        if (empty($asContext['queryHook']['stmt'])) {
            // debug mysql query
            $this->ifDebug('SearchString: ' . $asContext['searchString'], __METHOD__, __FILE__, __LINE__);
            if ($fulltext) {
                $this->ifDebug('FULLTEXT Query : ');
            } else {
                $this->ifDebug('LIKE Query : ');
            }
            $this->ifDebug('Select before pagination: ' . $this->niceQuery($c), __METHOD__, __FILE__, __LINE__);

            // get number of results before pagination
            $this->resultsCount = $this->modx->getCount('modResource', $c);
            $this->ifDebug('Number of results before pagination ' . ($fulltext ? '(FULLTEXT) : ' : '(LIKE) : '). $this->resultsCount, __METHOD__, __FILE__, __LINE__);

            if ($this->resultsCount > 0) {
                $c->limit($this->config['perPage'], ($asContext['page'] - 1) * $this->config['perPage']);

                // debug mysql query
                if ($fulltext) {
                    $this->ifDebug('FULLTEXT Query : ');
                } else {
                    $this->ifDebug('LIKE Query : ');
                }
                $this->ifDebug('Final select after pagination: ' . $this->niceQuery($c), __METHOD__, __FILE__, __LINE__);

//$c->prepare();
//$sql = $c->toSQL();
//
//$this->modx->log(modX::LOG_LEVEL_ERROR, __FILE__ . ' ');
//$this->modx->log(modX::LOG_LEVEL_ERROR, __METHOD__ . ' ');
//$this->modx->log(modX::LOG_LEVEL_ERROR, __LINE__ . ': $this->niceQuery($c) ' . $this->niceQuery($c));

                //============================= get results
                $collection = $this->modx->getCollection('modResource', $c);

                //============================= append & render tv fields (includeTVs, withTVs)
                $this->results = $this->appendTVsFields($collection, $asContext);
            }
        } else {
            // run a new statement
            //============================= get results, append & render tv fields (includeTVs, withTVs)
            $this->results = $this->_runStmt($c, $asContext);
            // get number of results before pagination
            $this->resultsCount = count($this->results);
            $this->ifDebug('Number of results before pagination: ' . $this->resultsCount, __METHOD__, __FILE__, __LINE__);

            //============================= set a subset (offset, perPage)
            if (empty($this->config['postHook'])) {
                $this->results = array_slice($this->results, ($asContext['page'] - 1) * $this->config['perPage'], $this->config['perPage']);
            }

            //============================= prepare final results
            $this->results = $this->prepareResults($this->results);
        }

        $countCheck = count($this->results);
        if ($countCheck === 0) {
            $this->resultsCount = 0;
        }

        return $this->results;
    }

    public function getCount() {
        return $this->resultsCount;
    }

    /**
     * Run a statement & append rendered tv fields (includeTVs, withTVs)
     *
     * @access private
     * @return array Returns an array of results
     */
    private function _runStmt(xPDOQuery $c, $asContext) {
        $results = array();
        $allowedTvNames = array_merge($asContext['tvWhereFields'], $asContext['tvFields']);
        $c->prepare();
        $sql = $c->toSQL();
        $patterns = array('{sql}');
        $replacements = array($sql);
        $sql = str_replace($patterns, $replacements, $asContext['queryHook']['stmt']['execute']);
        $this->ifDebug('sql: ' . $sql, __METHOD__, __FILE__, __LINE__);
        unset($c);
        $c = new xPDOCriteria($this->modx, $sql);
        if (!empty($asContext['queryHook']['stmt']['prepare'])) {
            $c->bind($asContext['queryHook']['stmt']['prepare']);
        }
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
        $this->ifDebug('Results of _runStmt: ' . print_r($results, true), __METHOD__, __FILE__, __LINE__);

        return $results;
    }

}

return 'AdvSearchMysqlController';