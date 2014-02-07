<?php

if (!class_exists('AdvSearchEngineController')) {
    include_once dirname(__FILE__) . '/advsearchenginecontroller.class.php';
}

class AdvSearchCustomController extends AdvSearchEngineController {

    public function getResults($asContext) {
        ;
    }

    public function getResultsCount() {
        ;
    }

}

return 'AdvSearchCustomController';