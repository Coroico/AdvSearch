<?php

if (!class_exists('AdvSearchEngineController')) {
    include_once dirname(__FILE__) . '/advsearchenginecontroller.class.php';
}

class AdvSearchZendController extends AdvSearchEngineController {

    public function getResults($asContext) {
        ;
    }

    public function getResultsCount() {
        ;
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

}

return 'AdvSearchZendController';