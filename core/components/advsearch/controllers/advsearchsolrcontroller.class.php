<?php

if (!class_exists('AdvSearchEngineController')) {
    include_once dirname(__FILE__) . '/advsearchenginecontroller.class.php';
}

class AdvSearchSolrController extends AdvSearchEngineController {

    public function getResults($asContext) {
        ;
    }

}

return 'AdvSearchSolrController';