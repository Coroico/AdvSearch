<?php

if (!class_exists('AdvSearchEngineController')) {
    include_once dirname(__FILE__) . '/advsearch.engine.controller.class.php';
}

class AdvSearchZendController extends AdvSearchEngineController {

    public function getResults($asContext) {

            // load the zend lucene library
            $file = $this->config['libraryPath'] . 'Zend/Search/Lucene.php';

            if (file_exists($file)) {
                require_once $file;
                // parse query
                $searchQuery = Zend_Search_Lucene_Search_QueryParser::parse($searchString, $this->config['charset']);

                // valid maxwords and minchars
                $valid = $this->_validQuery($searchQuery, true);
                if (!$valid) {
                    return false;
                }

                $this->searchString = $searchString;
                $this->searchQuery = $searchQuery;
            } else {
                $this->modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] Required library not found at ' . $file . '.');
                return false;
            }
    }

    private function _setCondition($term, $sign) {
        $isLikeCondition = (($sign) || is_null($sign));
        if ($isLikeCondition) {
            $like = 'LIKE';
        } else {
            $like = 'NOT LIKE';
        }
        // replace lucene wildcards by MySql wildcards
        $pattern = array('#\*#', '#\?#');
        $replacement = array('%', '_');
        $term = preg_replace($pattern, $replacement, $term);
        $term = "%{$term}%";
        $orand = array();
        foreach ($this->mainWhereFields as $field) {
            $orand[] = "`{$this->mainClass}`.`{$field}` {$like} '{$term}'";
        }
        if (!empty($this->tvWhereFields)) {
            foreach ($this->tvWhereFields as $field) {
                $orand[] = "`{$field}_cv`.`value` {$like} '{$term}'";
            }
        }
        if (!empty($this->joinedWhereFields)) {
            foreach ($this->joinedWhereFields as $field) {
                $orand[] = "{$field} {$like} '{$term}'";
            }
        }
        $condition = '';
        if (count($orand)) {
            if ($isLikeCondition) {
                $condition = '((' . implode(') OR (', $orand) . '))';
            } else {
                $condition = '((' . implode(') AND (', $orand) . '))';
            }
        }
        return $condition;
    }

    /**
     * valid a search query
     *
     * @access private
     * @param Zend_Search_Lucene_Search_Query $query The query to validate
     * @param boolean/null $sign true if mandatory, null if optional, false if excluded
     * @param integer $nbTerms Number of terms already processed
     * @return boolean Returns true if valid, otherwise false.
     */
    private function _validQuery($query = null, $sign = true, & $nbTerms = 0) {
        if ($query instanceOf Zend_Search_Lucene_Search_Query_Boolean) {
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
        } else if ($query instanceOf Zend_Search_Lucene_Search_Query_Preprocessing_Phrase) {
            $phrase = $query->__toString();
            $valid = $this->validTerm($phrase, 'phrase', $sign, $nbTerms);

            return $valid;
        } else if ($query instanceOf Zend_Search_Lucene_Search_Query_Preprocessing_Term) {
            $term = $query->__toString();
            $valid = $this->validTerm($term, 'word', $sign, $nbTerms);

            return $valid;
        } else {
            $msgerr = $this->modx->lexicon('advsearch.invalid_query');
            $this->setError($msgerr);

            return false;
        }
    }

}

return 'AdvSearchZendController';