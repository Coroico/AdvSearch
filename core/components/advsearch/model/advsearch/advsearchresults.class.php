<?php

/**
 * AdvSearch - AdvSearchResults class
 *
 * @package 	AdvSearch
 * @author		Coroico - coroico@wangba.fr
 *              goldsky - goldsky@virtudraft.com
 * @copyright 	Copyright (c) 2012 - 2015 by Coroico <coroico@wangba.fr>
 *
 * @tutorial	Class to get search results
 *
 */
include_once dirname(__FILE__) . "/advsearch.class.php";

class AdvSearchResults extends AdvSearch {

    public $mainClass = 'modResource';
    public $primaryKey = 'id';
    public $mainFields = array();
    public $joinedFields = array();
    public $tvFields = array();
    public $resultsCount = 0;
    public $results = array();
    public $idResults = array();
    public $htmlResult = '';
    protected $page = 1;
    protected $queryHook = null;
    protected $ids = array();
    protected $sortby = array();
    protected $mainWhereFields = array();
    protected $joinedWhereFields = array();
    protected $tvWhereFields = array();
    protected $controller;

    public function __construct(modX & $modx, array & $config = array()) {
        parent::__construct($modx, $config);
        parent::loadDefaultConfigs();
    }

    /**
     * Run the search
     */
    public function doSearch($asContext) {
        $this->searchString = $asContext['searchString'];
        $this->searchQuery = $asContext['searchQuery'];
        $this->searchTerms = $asContext['searchTerms'];
        $this->offset = $asContext['offset'];
        $this->page = $asContext['page'];
        $this->queryHook = $asContext['queryHook'];

        $this->_loadResultsProperties();
        $asContext['mainFields'] = $this->mainFields;
        $asContext['tvFields'] = $this->tvFields;
        $asContext['joinedFields'] = $this->joinedFields;
        $asContext['mainWhereFields'] = $this->mainWhereFields;
        $asContext['tvWhereFields'] = $this->tvWhereFields;
        $asContext['joinedWhereFields'] = $this->joinedWhereFields;
        $asContext['sortby'] = $this->sortby;

        $engine = trim(strtolower($this->config['engine']));
        if (empty($engine)) {
            $msg = 'Engine was not defined';
            $this->setError($msg);
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] ' . $msg, '', __METHOD__, __FILE__, __LINE__);
            return false;
        }

        // get results
        if (!$this->controller) {
            if ($this->mainClass === 'modResource') {
                // default package (modResource + Tvs) and possibly joined packages
                try {
                    $this->controller = $this->loadController($engine);
                } catch (Exception $ex) {
                    $msg = 'Could not load controller for engine: "' . $engine . '". Exception: ' . $ex->getMessage();
                    $this->setError($msg);
                    $this->modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] ' . $msg, '', __METHOD__, __FILE__, __LINE__);
                    return false;
                }
            } else {
                // search in a different main package and possibly joined packages
                try {
                    $this->controller = $this->loadController('custom');
                } catch (Exception $ex) {
                    $msg = 'Could not load controller for engine: "' . $engine . '" Exception: ' . $ex->getMessage();
                    $this->setError($msg);
                    $this->modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] ' . $msg, '', __METHOD__, __FILE__, __LINE__);
                    return false;
                }
            }
        }
        if ($this->controller) {
            $this->results = $this->controller->getResults($asContext);
            $this->resultsCount = $this->controller->getResultsCount();
            $this->page = $this->controller->getPage();
        } else {
            $msg = 'Controller could not generate the result';
            $this->setError($msg);
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] ' . $msg, '', __METHOD__, __FILE__, __LINE__);
            return false;
        }

        // reset pagination if the output empty while the counter shows more
        if ($this->resultsCount > 0 && count($this->results) === 0) {
            $asContext['page'] = 1;
            $this->page = 1;
            return $this->doSearch($asContext);
        }

        if (empty($this->results)) {
            $this->page = 1;
        } else {
            $outputType = array_map('trim', @explode(',', $this->config['output']));
            if (in_array('html', $outputType)) {
                $this->htmlResult = $this->renderOutput($this->results);
            }
            if (in_array('ids', $outputType)) {
                $this->idResults = $this->controller->idResults;
            }
        }

        return $this->results;
    }

    public function getPage() {
        return $this->page;
    }

    public function loadController($name) {
        if (!empty($this->config['engineControllerPath'])) {
            $filename = $this->replacePropPhs($this->config['engineControllerPath']);
        } else {
            $filename = dirname(dirname(dirname(__FILE__))) . '/controllers/advsearch.' . strtolower($name) . '.controller.class.php';
        }
        if (!file_exists($filename)) {
            $msg = 'Missing Controller file: ' . $filename;
            $this->setError($msg);
            throw new Exception($msg);
        }
        $className = include $filename;
        $controller = new $className($this->modx, $this->config);

        return $controller;
    }

    /**
     * Check the parameters for results part
     *
     * @access private
     * @tutorial Whatever the main class (modResource or an other class) params run the same check process
     *           Some initial values could be overried by values from the query hook
     */
    private function _loadResultsProperties() {
        if (!empty($this->queryHook['main'])) { // a new main package is declared in query hook
            $msg = '';
            if (empty($this->queryHook['main']['package'])) {
                $msg = 'Main - Package name should be declared in queryHook';
            } elseif (empty($this->queryHook['main']['packagePath'])) {
                $msg = 'Main - Package path should be declared in queryHook';
            } elseif (empty($this->queryHook['main']['class'])) {
                $msg = 'Main - Class name should be defined in queryHook';
            }
            if (!empty($msg)) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] ' . $msg, '', __METHOD__, __FILE__, __LINE__);
                return false;
            }
            $this->mainClass = $this->queryHook['main']['class'];  // main class
            $this->queryHook['main']['packagePath'] = $this->replacePropPhs($this->queryHook['main']['packagePath']);

            $tablePrefix = isset($this->queryHook['main']['tablePrefix']) ? $this->queryHook['main']['tablePrefix'] : $this->modx->config[modX::OPT_TABLE_PREFIX];
            $added = $this->modx->addPackage($this->queryHook['main']['package'], $this->queryHook['main']['packagePath'], $tablePrefix); // add package
            if (!$added) {
                return false;
            }

            $this->primaryKey = $this->modx->getPK($this->mainClass); // get primary key
        }

        // &contexts [ comma separated context names | $modx->context->get('key') ]
        $lstContexts = $this->modx->getOption('contexts', $this->config, $this->modx->context->get('key'));
        $this->config['contexts'] = implode(',', array_map('trim', explode(',', $lstContexts)));

        /**
         * @deprecated
         */
        // &docindexPath [ path | 'assets/files/docindex/' ]
        $path = $this->modx->getOption('docindexPath', $this->config, 'docindex/');
        $this->config['docindexPath'] = $this->modx->getOption('assets_path') . 'files/' . $path;

        /**
         * &docindexRoot [ path | '[[++core_path]]docindex/' ]
         * eg: will be appended by engine's name
         *     [[++core_path]]docindex/zend/ for zend engine
         *     [[++core_path]]docindex/solr/ for solr engine
         */
        $this->config['docindexRoot'] = $this->modx->getOption('docindexRoot', $this->config, '[[++core_path]]docindex/');

        // &engine [ 'mysql' | 'zend' | 'solr' | 'all' | ... ] - name of search engine to use
        $engine = strtolower(trim($this->modx->getOption('engine', $this->config, 'mysql')));
        $this->config['engine'] = !empty($engine) ? $engine : 'mysql';
        $this->config['engineConfigFile'] = $this->modx->getOption('engineConfigFile', $this->config);
        $this->config['engineControllerPath'] = $this->modx->getOption('engineControllerPath', $this->config);

        // &fields [csv list of fields | 'pagetitle,longtitle,alias,description,introtext,content' (modResource)  '' otherwise ]
        $lstFields = $this->config['fields'];
        if (!empty($this->queryHook['main']['fields'])) {
            $lstFields = $this->queryHook['main']['fields'];
        }
        $fields = array();
        if (!empty($lstFields)) {
            $fields = array_map('trim', explode(',', $lstFields));
        }
        $this->config['fields'] = implode(',', $fields);

        // initialise mainFields : 'id', 'template', 'context_key', 'createdon' + docFields for modResource
        if ($this->mainClass == 'modResource') {
            $requiredFields = array('id', 'template', 'context_key', 'createdon');
        } else {
            $requiredFields = array($this->primaryKey);
        }
        $this->mainFields = array_merge($requiredFields, $fields);

        // &fieldPotency - [ comma separated list of couple (field : potency) | 'createdon:1' (modResource) ]
        $lstFieldPotency = $this->modx->getOption('fieldPotency', $this->config, 'createdon:1');
        if (!empty($lstFieldPotency)) {
            $fieldPotency = array_map('trim', explode(',', $lstFieldPotency));
            $checkedFieldPotency = array();
            foreach ($fieldPotency as $fldp) {
                $fld = array_map('trim', explode(':', $fldp));
                $fld[1] = (isset($fld[1]) && floatval($fld[1])) ? floatval($fld[1]) : 1;
                $checkedFieldPotency[] = implode(':', $fld);
            }
            $this->config['fieldPotency'] = implode(',', $checkedFieldPotency);
        } else {
            $this->config['fieldPotency'] = $lstFieldPotency;
        }

        if (!empty($this->queryHook['main']['withFields'])) {
            $lstWithFields = $this->queryHook['main']['withFields'];
        } else {
            // &withFields [csv list of fields | 'pagetitle,longtitle,alias,description,introtext,content' (modResource) '' (all fields) otherwise]
            $lstWithFields = $this->modx->getOption('withFields', $this->config, 'pagetitle,longtitle,alias,description,introtext,content');
        }
        if (!empty($lstWithFields)) {
            $this->mainWhereFields = array_map('trim', explode(',', $lstWithFields));
            $this->config['withFields'] = implode(',', $this->mainWhereFields);
        } else {
            $this->config['withFields'] = $lstWithFields;
        }

        if ($this->mainClass == 'modResource') {
            // &hideMenu [ 0 | 1 | 2 ]  Search in hidden documents from menu.
            $hideMenu = (int) $this->modx->getOption('hideMenu', $this->config, 2);
            $this->config['hideMenu'] = (($hideMenu < 3) && ($hideMenu >= 0)) ? $hideMenu : 2;

            // &includeTVs - [ comma separated tv names | '' ]
            $lstIncludeTVs = $this->modx->getOption('includeTVs', $this->config, '');
            if (!empty($lstIncludeTVs)) {
                $this->tvFields = array_map('trim', explode(',', $lstIncludeTVs));
                $this->config['includeTVs'] = implode(',', $this->tvFields);
            } else {
                $this->config['includeTVs'] = $lstIncludeTVs;
            }

            // &withTVs - [ a comma separated list of TV names | '' ]
            $lstWithTVs = $this->modx->getOption('withTVs', $this->config, '');
            if (!empty($lstWithTVs)) {
                $this->tvWhereFields = array_map('trim', explode(',', $lstWithTVs));
                $this->config['withTVs'] = implode(',', $this->tvWhereFields);
            } else {
                $this->config['withTVs'] = $lstWithTVs;
            }

            // remove duplicates between withTVs and includeTVs parameters
            $this->tvFields = array_unique(array_merge($this->tvWhereFields, $this->tvFields));
        }

        $this->joinedFields = array_merge($this->mainFields, $this->tvFields);
        $this->joinedWhereFields = array_merge($this->mainWhereFields, $this->tvWhereFields);

        if (!empty($this->queryHook['main']['lstIds'])) {
            $lstIds = $this->queryHook['main']['lstIds'];
        } else {
            // &ids [ comma separated list of Ids | '' ] - ids or primary keys for custom package
            $lstIds = $this->modx->getOption('ids', $this->config, '');
        }

        if (!empty($lstIds)) {
            $this->ids = array_map('trim', explode(',', $lstIds));
            $this->config['ids'] = implode(',', $this->ids);
        } else {
            $this->config['ids'] = $lstIds;
        }

        if ((!empty($this->queryHook)) && (!empty($this->queryHook['perPage']))) {
            $perPage = $this->queryHook['perPage'];
        } else {
            // &perPage [ int | 10 ] - Set to 0 if unlimited
            $perPage = (int) $this->modx->getOption('perPage', $this->config, 10);
        }
        $this->config['perPage'] = (($perPage >= 0)) ? $perPage : 10;

        if (!empty($this->queryHook['sortby'])) {
            $lstSortby = $this->queryHook['sortby'];
        } else if (!empty($this->queryHook['main']['sortby'])) {
            $lstSortby = $this->queryHook['main']['sortby'];
        } else {
            // &sortby - comma separated list of couple "field [ASC|DESC]" to sort by.
            // field from joined resource should be named resourceName_fieldName. e.g: quipComment_body
            $lstSortby = $this->modx->getOption('sortby', $this->config, 'id DESC');
        }

        if (!empty($lstSortby)) {
            $this->sortby = array();
            $sortCpls = array_map('trim', explode(',', $lstSortby));
            $sorts = array();
            foreach ($sortCpls as $sortCpl) {
                $sortElts = array_map('trim', explode(' ', $sortCpl));
                $classField = !empty($sortElts[0]) ? $sortElts[0] : 'modResource.id';
                $dir = strtolower((empty($sortElts[1])) ? 'desc' : $sortElts[1]);
                $dir = in_array($dir, array('asc', 'desc')) ? $dir : 'desc';
                $this->sortby[$classField] = $dir;
            }
        }

        $this->ifDebug('Config parameters after checking in class ' . __CLASS__ . ': ' . print_r($this->config, true), __METHOD__, __FILE__, __LINE__);

        return;
    }

    /*
     * Returns search results output
     *
     * @access public
     * @param AdvSearchResults $asr a AdvSearchResult object
     * @return string Returns search results output
     */

    public function renderOutput($results = array()) {
        if (empty($results)) {
            return false;
        }

        $this->searchTerms = array_unique($this->searchTerms);
        $this->displayedFields = array_merge($this->mainFields, $this->tvFields, $this->joinedFields);
        $this->_loadOutputProperties();

        // pagination
        $pagingOutput = $this->_getPaging($this->resultsCount);

        // results
        $resultsOutput = '';
        $resultsArray = array();
        $idx = ($this->page - 1) * $this->config['perPage'] + 1;
        foreach ($results as $result) {
            if ($this->nbExtracts && count($this->extractFields)) {
                $text = '';
                foreach ($this->extractFields as $extractField) {
                    $text .= "{$this->processElementTags($result[$extractField])}";
                }

                $extracts = $this->_getExtracts(
                    $text, $this->nbExtracts, $this->config['extractLength'], $this->searchTerms, $this->config['extractTpl'], $ellipsis = '...'
                );
            } else {
                $extracts = '';
            }

            $result['idx'] = $idx;
            $result['extracts'] = $extracts;
            if (empty($result['link'])) {
                $ctx = (!empty($result['context_key'])) ? $result['context_key'] : $this->modx->context->get('key');
                if ((int) $result[$this->primaryKey]) {
                    $result['link'] = $this->modx->makeUrl($result[$this->primaryKey], $ctx, '', $this->config['urlScheme']);
                }
            }

            if ($this->config['toArray']) {
                $resultsArray[] = $result;
            } else {
                $result = $this->setPlaceholders($result, $this->config['placeholderPrefix']);
                $resultsOutput .= $this->processElementTags($this->parseTpl($this->config['tpl'], $result));
            }
            $idx++;
        }

        $resultsPh = array(
            'paging' => $pagingOutput,
            'pagingType' => $this->config['pagingType'],
        );
        if ($this->config['toArray']) {
            $resultsPh['properties'] = $this->config;
            $resultsPh['results'] = $resultsArray;
            $output = '<pre class="advsea-code">' . print_r($resultsPh, 1) . '</pre>';
        } else {
            $resultsPh['results'] = $resultsOutput;
            $resultsPh = $this->setPlaceholders($resultsPh, $this->config['placeholderPrefix']);
            $output = $this->processElementTags($this->parseTpl($this->config['containerTpl'], $resultsPh));
        }

        return $output;
    }

    /**
     * Check parameters for the displaying of results
     *
     * @access private
     * @param array $displayedFields Fields to display
     */
    private function _loadOutputProperties() {

        // &output
        $outputLst = $this->modx->getOption('output', $this->config, 'output');
        $output = array_map('trim', explode(',', $outputLst));
        $output = array_intersect($output, array('html', 'rows', 'ids'));
        if (!count($output)) {
            $output = array('html');
        }
        $this->config['output'] = implode(',', $output);

        // &containerTpl [ chunk name | 'AdvSearchResults' ]
        $containerTpl = $this->modx->getOption('containerTpl', $this->config, 'AdvSearchResults');
        $chunk = $this->modx->getObject('modChunk', array('name' => $containerTpl));
        $this->config['containerTpl'] = (empty($chunk)) ? 'searchresults' : $containerTpl;

        // &tpl [ chunk name | 'AdvSearchResult' ]
        $tpl = $this->modx->getOption('tpl', $this->config, 'AdvSearchResult');
        $chunk = $this->modx->getObject('modChunk', array('name' => $tpl));
        $this->config['tpl'] = (empty($chunk)) ? 'searchresult' : $tpl;

        // &showExtract [ string | '1:content' ]
        $showExtractArray = explode(':', $this->modx->getOption('showExtract', $this->config, '1:content'));
        if ((int) $showExtractArray[0] < 0) {
            $showExtractArray[0] = 0;
        }
        if ($showExtractArray[0]) {
            if (!isset($showExtractArray[1])) {
                $showExtractArray[1] = 'content';
            }
            // check that all the fields selected for extract exists in mainFields, tvFields or joinedFields
            $extractFields = explode(',', $showExtractArray[1]);
            foreach ($extractFields as $key => $field) {
                if (!in_array($field, $this->displayedFields)) {
                    unset($extractFields[$key]);
                }
            }
            $this->extractFields = array_values($extractFields);
            $this->nbExtracts = $showExtractArray[0];
            $this->config['showExtract'] = $showExtractArray[0] . ':' . implode(',', $this->extractFields);
        } else {
            $this->nbExtracts = 0;
            $this->config['showExtract'] = '0';
        }

        if ($this->nbExtracts && count($this->extractFields)) {
            // &extractEllipsis [ string | '...' ]
            $this->config['extractEllipsis'] = $this->modx->getOption('extractEllipsis', $this->config, '...');

            // &extractLength [ 50 < int < 800 | 200 ]
            $extractLength = (int) $this->modx->getOption('extractLength', $this->config, 200);
            $this->config['extractLength'] = (($extractLength < 800) && ($extractLength >= 50)) ? $extractLength : 200;

            // &extractTpl [ chunk name | 'Extract' ]
            $extractTpl = $this->modx->getOption('extractTpl', $this->config, 'Extract');
            $chunk = $this->modx->getObject('modChunk', array('name' => $extractTpl));
            $this->config['extractTpl'] = (empty($chunk)) ? 'extract' : $extractTpl;

            // &highlightResults [ 0 | 1 ]
            $highlightResults = (int) $this->modx->getOption('highlightResults', $this->config, 1);
            $this->config['highlightResults'] = (($highlightResults == 0 || $highlightResults == 1)) ? $highlightResults : 1;

            if ($this->config['highlightResults']) {
                // &highlightClass [ string | 'advsea-highlight']
                $this->config['highlightClass'] = $this->modx->getOption('highlightClass', $this->config, 'advsea-highlight');

                // &highlightTag [ tag name | 'span' ]
                $this->config['highlightTag'] = $this->modx->getOption('highlightTag', $this->config, 'span');
            }
        }

        // &pagingType[ 0 | 1 | 2 | 3 ]
        $pagingType = (int) $this->modx->getOption('pagingType', $this->config, 1);
        $this->config['pagingType'] = (($pagingType <= 3) && ($pagingType >= 0)) ? $pagingType : 1;

        if ($this->config['pagingType'] == 1) {
            // &paging1Tpl [ chunk name | 'Paging1' ]
            $paging1Tpl = $this->modx->getOption('paging1Tpl', $this->config, 'Paging1');
            $chunk = $this->modx->getObject('modChunk', array('name' => $paging1Tpl));
            $this->config['paging1Tpl'] = (empty($chunk)) ? 'paging1' : $paging1Tpl;
        } elseif ($this->config['pagingType'] == 2) {
            // &paging2Tpl [ chunk name | 'Paging2' ]
            $paging2Tpl = $this->modx->getOption('paging2Tpl', $this->config, 'Paging2');
            $chunk = $this->modx->getObject('modChunk', array('name' => $paging2Tpl));
            $this->config['paging2Tpl'] = (empty($chunk)) ? 'paging2' : $paging2Tpl;

            // &currentPageTpl [ chunk name | 'CurrentPageLink' ]
            $currentPageTpl = $this->modx->getOption('currentPageTpl', $this->config, 'CurrentPageLink');
            $chunk = $this->modx->getObject('modChunk', array('name' => $currentPageTpl));
            $this->config['currentPageTpl'] = (empty($chunk)) ? 'currentpagelink' : $currentPageTpl;

            // &pageTpl [ chunk name | 'PageLink' ]
            $pageTpl = $this->modx->getOption('pageTpl', $this->config, 'PageLink');
            $chunk = $this->modx->getObject('modChunk', array('name' => $pageTpl));
            $this->config['pageTpl'] = (empty($chunk)) ? 'pagelink' : $pageTpl;

            // &pagingSeparator
            $this->config['pagingSeparator'] = $this->modx->getOption('pagingSeparator', $this->config, ' | ');
        } elseif ($this->config['pagingType'] == 3) {
            // &paging3Tpl [ chunk name | 'Paging3' ]
            $paging3Tpl = $this->modx->getOption('paging3Tpl', $this->config, 'Paging3');
            $chunk = $this->modx->getObject('modChunk', array('name' => $paging3Tpl));
            $this->config['paging3Tpl'] = (empty($chunk)) ? 'paging3' : $paging3Tpl;

            // &currentPageTpl [ chunk name | 'CurrentPageLink' ]
            $currentPageTpl = $this->modx->getOption('paging3CurrentPageTpl', $this->config, 'CurrentPageLink');
            $chunk = $this->modx->getObject('modChunk', array('name' => $currentPageTpl));
            $this->config['paging3CurrentPageTpl'] = (empty($chunk)) ? 'currentpagelink' : $currentPageTpl;

            // &pageTpl [ chunk name | 'PageLink' ]
            $pageTpl = $this->modx->getOption('paging3PageLinkTpl', $this->config, 'PageLink');
            $chunk = $this->modx->getObject('modChunk', array('name' => $pageTpl));
            $this->config['paging3PageLinkTpl'] = (empty($chunk)) ? 'pagelink' : $pageTpl;

            // &pagingSeparator
            $this->config['paging3Separator'] = $this->modx->getOption('paging3Separator', $this->config, ' | ');

            // &pagingOuterRange
            $this->config['paging3OuterRange'] = $this->modx->getOption('paging3OuterRange', $this->config, 2);

            // &pagingMiddleRange
            $this->config['paging3MiddleRange'] = $this->modx->getOption('paging3MiddleRange', $this->config, 3);

            // &pagingRangeSplitter

            $paging3RangeSplitter = $this->modx->getOption('paging3RangeSplitterTpl', $this->config, 'Paging3RangeSplitter');
            $chunk = $this->modx->getObject('modChunk', array('name' => $paging3RangeSplitter));
            $this->config['paging3RangeSplitterTpl'] = (empty($chunk)) ? 'paging3rangesplitter' : $paging3RangeSplitter;
        }

        if ($this->config['withAjax']) {
            // &moreResults - [ int id of a document | 0 ]
            $moreResults = (int) $this->modx->getOption('moreResults', $this->config, 0);
            $this->config['moreResults'] = ($moreResults > 0) ? $moreResults : 0;

            if ($this->config['moreResults']) {
                // &moreResultsTpl [ chunk name | 'MoreResults' ]
                $moreResultsTpl = $this->modx->getOption('moreResultsTpl', $this->config, 'MoreResults');
                $chunk = $this->modx->getObject('modChunk', array('name' => $moreResultsTpl));
                $this->config['moreResultsTpl'] = (empty($chunk)) ? 'moreresults' : $moreResultsTpl;
            }
        }

        // &toArray [ 0| 1 ]
        $this->config['toArray'] = (bool) $this->modx->getOption('toArray', $this->config, 0);

        $this->ifDebug('Config parameters after checking in class ' . __CLASS__ . ': ' . print_r($this->config, true), __METHOD__, __FILE__, __LINE__);

        return true;
    }

    /*
     * Format pagination
     *
     * @access private
     * @param integer $resultsCount The number of results found
     * @param integer $pageResultsCount The number of results for the current page
     * @return string Returns search results output header info
     */

    private function _getPaging($resultsCount) {
        if (!$this->config['perPage'] || !$this->config['pagingType']) {
            return;
        }
        $id = $this->modx->resource->get('id');
        $idParameters = $this->modx->request->getParameters();
        $this->page = intval($this->page);

        // first: number of the first result of the current page, last: number of the last result of current page,
        // page: number of the current page, nbpages: total number of pages
        $nbPages = (int) ceil($resultsCount / $this->config['perPage']);
        $flatCount = $this->page * $this->config['perPage'];
        $last = $flatCount <= $resultsCount ? $flatCount : $resultsCount;
        $pagePh = array(
            'first' => ($this->page - 1) * $this->config['perPage'] + 1,
            'last' => $last,
            'total' => $resultsCount,
            'currentpage' => $this->page,
            'page' => $this->page, // by convention
            'nbpages' => $nbPages,
            'totalPage' => $nbPages, // by convention
        );

//        $this->modx->setPlaceholders($pagePh, $this->config['placeholderPrefix']);

        $qParameters = array();
        if (!empty($this->queryHook['requests'])) {
            $qParameters = $this->queryHook['requests'];
        }

        if ($this->config['pagingType'] == 1) {
            // pagination type 1
            $previousCount = ($this->page - 1) * $this->config['perPage'];
            $pagePh['previouslink'] = '';
            if ($previousCount > 0) {
                $parameters = array_merge($idParameters, $qParameters, array(
                    $this->config['pageIndex'] => $this->page - 1
                ));
                $pagePh['previouslink'] = $this->modx->makeUrl($id, '', $parameters, $this->config['urlScheme']);
            }

            $nextPage = ($this->page + 1);
            $pagePh['nextlink'] = '';
            if ($nextPage <= $nbPages) {
                $parameters = array_merge($idParameters, $qParameters, array(
                    $this->config['pageIndex'] => $this->page + 1
                ));
                $pagePh['nextlink'] = $this->modx->makeUrl($id, '', $parameters, $this->config['urlScheme']);
            }

            $pagePh = $this->setPlaceholders($pagePh, $this->config['placeholderPrefix']);
            $output = $this->processElementTags($this->parseTpl($this->config['paging1Tpl'], $pagePh));
        } elseif ($this->config['pagingType'] == 2) {
            // pagination type 2
            $paging2 = array();
            for ($i = 0; $i < $nbPages; ++$i) {
                $pagePh['text'] = $i + 1;
                $pagePh['separator'] = $this->config['pagingSeparator'];
                $pagePh['page'] = $i + 1;
                if ($this->page == $i + 1) {
                    $pagePh['link'] = $i + 1;
                    $pagePh = $this->setPlaceholders($pagePh, $this->config['placeholderPrefix']);
                    $paging2[] = $this->processElementTags($this->parseTpl($this->config['currentPageTpl'], $pagePh));
                } else {
                    $parameters = array_merge($idParameters, $qParameters, array(
                        $this->config['pageIndex'] => $pagePh['page']
                    ));
                    $pagePh['link'] = $this->modx->makeUrl($id, '', $parameters, $this->config['urlScheme']);
                    $pagePh = $this->setPlaceholders($pagePh, $this->config['placeholderPrefix']);
                    $paging2[] = $this->processElementTags($this->parseTpl($this->config['pageTpl'], $pagePh));
                }
            }
            $paging2 = @implode($this->config['pagingSeparator'], $paging2);
            $phs = $this->setPlaceholders(array('paging2' => $paging2), $this->config['placeholderPrefix']);
            $output = $this->processElementTags($this->parseTpl($this->config['paging2Tpl'], $phs));
        } elseif ($this->config['pagingType'] == 3) {
            // pagination type 3
            $paging3 = array();

            $previousCount = ($this->page - 1) * $this->config['perPage'];
            $previouslink = '';
            if ($previousCount > 0) {
                $parameters = array_merge($idParameters, $qParameters, array(
                    $this->config['pageIndex'] => $this->page - 1
                ));
                $previouslink = $this->modx->makeUrl($id, '', $parameters, $this->config['urlScheme']);
            }

            $nextPage = ($this->page + 1);
            $nextlink = '';
            if ($nextPage <= $nbPages) {
                $parameters = array_merge($idParameters, $qParameters, array(
                    $this->config['pageIndex'] => $this->page + 1
                ));
                $nextlink = $this->modx->makeUrl($id, '', $parameters, $this->config['urlScheme']);
            }

            $maxOuterRange = $this->config['paging3OuterRange'] + $this->config['paging3MiddleRange'];
            $middleWingRange = (int) ceil(($this->config['paging3MiddleRange'] - 1) / 2);
            $middleWingRange = $middleWingRange > 0 ? $middleWingRange : 1;

            for ($i = 1; $i <= $nbPages; ++$i) {
                $parameters = array_merge($idParameters, $qParameters);

                if ($i <= $this->config['paging3OuterRange'] ||
                    $i > ($nbPages - $this->config['paging3OuterRange'])
                ) {
                    $paging3[] = $this->_formatPaging3($i, $id, $parameters);
                } else {
                    if ($nbPages <= ($this->config['paging3OuterRange'] * 2)) {
                        continue;
                    }
                    // left splitter
                    if ($i === ($this->config['paging3OuterRange'] + 1) &&
                        $this->page >= $maxOuterRange) {
                        $paging3[] = $this->processElementTags($this->parseTpl($this->config['paging3RangeSplitterTpl']));
                    }

                    if ($i <= ($this->page + $middleWingRange) &&
                        $i >= ($this->page - $middleWingRange)) {
                        $paging3[] = $this->_formatPaging3($i, $id, $parameters);
                    }

                    // right splitter
                    if ($i === ($nbPages - $this->config['paging3OuterRange']) &&
                        $this->page <= ($nbPages - $maxOuterRange) + 1) {
                        $paging3[] = $this->processElementTags($this->parseTpl($this->config['paging3RangeSplitterTpl']));
                    }
                }
            } // for ($i = 1; $i <= $nbPages; ++$i)

            $paging3 = @implode($this->config['paging3Separator'], $paging3);
            $phs = $this->setPlaceholders(array(
                'previouslink' => $previouslink,
                'paging3' => $paging3,
                'nextlink' => $nextlink,
                ), $this->config['placeholderPrefix']);
            $output = $this->processElementTags($this->parseTpl($this->config['paging3Tpl'], $phs));
        }
        return $output;
    }

    private function _formatPaging3($idx, $docId, $parameters = array()) {
        $pagePh = array();
        $pagePh['text'] = $idx;
        $pagePh['separator'] = $this->config['paging3Separator'];
        $pagePh['page'] = $idx;

        if ($this->page == $idx) {
            $pagePh['link'] = $idx;
            $pagePh = $this->setPlaceholders($pagePh, $this->config['placeholderPrefix']);
            $output = $this->processElementTags($this->parseTpl($this->config['paging3CurrentPageTpl'], $pagePh));
        } else {
            $parameters = array_merge($parameters, array(
                $this->config['pageIndex'] => $idx
            ));
            $pagePh['link'] = $this->modx->makeUrl($docId, '', $parameters, $this->config['urlScheme']);
            $pagePh = $this->setPlaceholders($pagePh, $this->config['placeholderPrefix']);
            $output = $this->processElementTags($this->parseTpl($this->config['paging3PageLinkTpl'], $pagePh));
        }

        return $output;
    }

    /*
     * Returns extracts with highlighted searchterms
     *
     * @access private
     * @param string $text The text from where to extract extracts
     * @param integer $nbext The number of extracts required / found
     * @param integer $extractLength The extract lenght wished
     * @param array $searchTerms The searched terms
     * @param string $tpl The template name for extract
     * @param string $ellipsis The string to use as ellipsis
     * @return string Returns extracts output
     * @tutorial this algorithm search several extracts for several search terms
     * 		if some extracts intersect then they are merged. Searchterm could be
     *      a lucene regexp expression using ? or *
     */

    private function _getExtracts($text, $nbext = 1, $extractLength = 200, $searchTerms = array(), $tpl = '', $ellipsis = '...') {

        mb_internal_encoding($this->config['charset']); // set internal encoding to UTF-8 for multi-bytes functions

        $text = trim(preg_replace('/\s+/', ' ', $this->sanitize($text)));
        $textLength = mb_strlen($text);
        if (empty($text)) {
            return '';
        }

        $trimchars = "\t\r\n -_()!~?=+/*\\,.:;\"'[]{}`&";
        $nbTerms = count($searchTerms);
        if (!$nbTerms) {
            // with an empty searchString - show as introduction the first characters of the text
            if (($extractLength > 0) && !empty($text)) {
                $offset = ($extractLength < $textLength) ? $extractLength - 1 : $textLength - 1;
                $pos = min(mb_strpos($text, ' ', $offset), mb_strpos($text, '.', $offset));
                if ($pos) {
                    $intro = rtrim(mb_substr($text, 0, $pos), $trimchars) . $ellipsis;
                } else {
                    $intro = $text;
                }
            } else {
                $intro = '';
            }
            $phs = $this->setPlaceholders(array('extract' => $intro), $this->config['placeholderPrefix']);

            return $this->processElementTags($this->parseTpl($tpl, $phs));
        }

        // get extracts
        $extracts = array();
        $extractLength2 = $extractLength / 2;
        $rank = 0;

        foreach ($searchTerms as $s) {
            $s = trim($s);
            $x = preg_split('/\s/', $s);
            $searchTerms = array_merge($x);
        }

        // search the position of all search terms
        foreach ($searchTerms as $searchTerm) {
            $rank++;
            // replace lucene wildcards by regexp wildcards
            $pattern = array('#\*#', '#\?#');
            $replacement = array('\w*', '\w');
            $searchTerm = preg_replace($pattern, $replacement, $searchTerm);
            $pattern = '#' . $searchTerm . '#i';
            $matches = array();
            $nbr = preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);

            for ($i = 0; $i < $nbr && $i < $nbext; $i++) {
                $term = $matches[0][$i][0]; // term found even with wildcard
                $wordLength = mb_strlen($term);
                $wordLength2 = $wordLength / 2;
                $wordLeft = mb_strlen(mb_substr($text, 0, $matches[0][$i][1]));
                $wordRight = $wordLeft + $wordLength - 1;
                $left = (int) ($wordLeft - $extractLength2 + $wordLength2);
                $right = $left + $extractLength - 1;
                if ($left < 0) {
                    $left = 0;
                }
                if ($right > $textLength) {
                    $right = $textLength;
                }
                $extracts[] = array(
                    'searchTerm' => $term,
                    'wordLeft' => $wordLeft,
                    'wordRight' => $wordRight,
                    'rank' => $rank,
                    'left' => $left,
                    'right' => $right,
                    'etcLeft' => $ellipsis,
                    'etcRight' => $ellipsis
                );
            }
        }

        $nbext = count($extracts);
        if ($nbext > 1) {
            for ($i = 0; $i < $nbext; $i++) {
                $lft[$i] = $extracts[$i]['left'];
                $rght[$i] = $extracts[$i]['right'];
            }
            array_multisort($lft, SORT_ASC, $rght, SORT_ASC, $extracts);

            for ($i = 0; $i < $nbext; $i++) {
                $begin = mb_substr($text, 0, $extracts[$i]['left']);
                if ($begin != '') {
                    $extracts[$i]['left'] = (int) mb_strrpos($begin, ' ');
                }

                $end = mb_substr($text, $extracts[$i]['right'] + 1, $textLength - $extracts[$i]['right']);
                if ($end != '') {
                    $dr = (int) mb_strpos($end, ' ');
                }
                if (is_int($dr)) {
                    $extracts[$i]['right']+= $dr + 1;
                }
            }

            if ($extracts[0]['left'] == 0) {
                $extracts[0]['etcLeft'] = '';
            }
            for ($i = 1; $i < $nbext; $i++) {
                if ($extracts[$i]['left'] < $extracts[$i - 1]['wordRight']) {
                    $extracts[$i - 1]['right'] = $extracts[$i - 1]['wordRight'];
                    $extracts[$i]['left'] = $extracts[$i - 1]['right'] + 1;
                    $extracts[$i - 1]['etcRight'] = $extracts[$i]['etcLeft'] = '';
                } else if ($extracts[$i]['left'] < $extracts[$i - 1]['right']) {
                    $extracts[$i - 1]['right'] = $extracts[$i]['left'];
                    $extracts[$i - 1]['etcRight'] = $extracts[$i]['etcLeft'] = '';
                }
            }
        }

        $output = '';
        $highlightTag = $this->config['highlightTag'];
        $highlightClass = $this->config['highlightClass'];

        for ($i = 0; $i < $nbext; $i++) {
            $extract = mb_substr($text, $extracts[$i]['left'], $extracts[$i]['right'] - $extracts[$i]['left'] + 1);
            if ($this->config['highlightResults']) {
                $rank = $extracts[$i]['rank'];
                $searchTerm = $extracts[$i]['searchTerm'];
                $extract = $this->addHighlighting($extract, (array) $searchTerm, $highlightClass, $highlightTag, $rank);
            }
            $extractPh = array(
                'extract' => $extracts[$i]['etcLeft'] . $extract . $extracts[$i]['etcRight']
            );
            $extractPh = $this->setPlaceholders($extractPh, $this->config['placeholderPrefix']);
            $output .= $this->processElementTags($this->parseTpl($tpl, $extractPh));
        }

        return $output;
    }

}
