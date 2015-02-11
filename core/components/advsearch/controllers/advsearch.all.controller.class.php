<?php

if (!class_exists('AdvSearchEngineController')) {
    include_once dirname(__FILE__) . '/advsearch.engine.controller.class.php';
}

class AdvSearchAllEnginesController extends AdvSearchEngineController {

    public function getResults($asContext) {
        ;
    }

}

return 'AdvSearchAllEnginesController';