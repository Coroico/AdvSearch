<?php

/**
 * AdvSearch - AdvSearch class
 *
 * @package 	AdvSearch
 * @author		Coroico
 *              goldsky - goldsky@virtudraft.com
 * @copyright 	Copyright (c) 2012 by Coroico <coroico@wangba.fr>
 *
 * @tutorial	Main class to get & display search results
 *
 */
include_once dirname(__FILE__) . "/advsearchutil.class.php";

class AdvSearch extends AdvSearchUtil {

    public $searchResults = null;
    /* deprecated since 2.0.0 */
    protected $offset = 0;
    protected $page = 1;
    protected $queryHook = null;
    protected $displayedFields = array();
    protected $extractFields = array();
    protected $nbExtracts;
    protected $asr = null;
    protected $qtime = 0;

    public function __construct(modX & $modx, array $config = array()) {

        parent::__construct($modx, $config);
        parent::loadDefaultConfigs();
    }

    /**
     * output the serch results
     *
     * @access public
     * @return string returns the search results
     */
    public function output() {
        if (!$this->forThisInstance()) {
            return FALSE;
        }

        // The first time display or not results
        $asId = filter_input(INPUT_GET, 'asId', FILTER_SANITIZE_SPECIAL_CHARS);
        $sub = filter_input(INPUT_GET, 'sub', FILTER_SANITIZE_SPECIAL_CHARS);
        $init = (!empty($asId) || !empty($sub)) ? 'all' : $this->config['init'];
        if ($init !== 'all') {
            return;
        }

        // &toArray [ 0| 1 ]
        $this->config['toArray'] = (bool)$this->modx->getOption('toArray', $this->config, 0);

        // &cacheQuery [ 0 | 1 ]
        $this->config['cacheQuery'] = (bool) $this->modx->getOption('cacheQuery', $this->config, 0);

        // &cacheTime [ 0 | 1 ]
        $this->config['cacheTime'] = (int) $this->modx->getOption('cacheTime', $this->config, 7200);

        // &cacheType [ output | values ]
        $cacheType = $this->modx->getOption('cacheType', $this->config);
        if ($cacheType === 'html') {
            $this->config['cacheType'] = 'html';
        } else {
            // default
            $this->config['cacheType'] = 'array';
        }

        // &maxWords [ 1 < int < 30 ]
        $maxWords = (int) $this->modx->getOption('maxWords', $this->config, 20);
        $this->config['maxWords'] = (($maxWords <= 30) && ($maxWords >= 1)) ? $maxWords : 20;

        // &minChars [  2 <= int <= 10 ]
        $minChars = (int) $this->modx->getOption('minChars', $this->config, 3);
        $this->config['minChars'] = (($minChars <= 10) && ($minChars >= 2)) ? $minChars : 3;

        // &queryHook [ snippet name | '' ]
        $this->config['queryHook'] = trim($this->modx->getOption('queryHook', $this->config, ''));

        // &postHook [ a snippet name | '' ]
        $this->config['postHook'] = trim($this->modx->getOption('postHook', $this->config, ''));

        // &postHookTpls [ A comma-separated list of templates for the postHook | '' ]
        // a list of templates used by the postHook to style the postHooked result
        $postHookTplsLst = $this->modx->getOption('postHookTpls', $this->config, '');
        $postHookTpls = (!empty($postHookTplsLst)) ? array_map('trim', explode(',', $postHookTplsLst)) : array();
        $this->config['postHookTpls'] = implode(',', $postHookTpls);

        // ajax mode parameters
        if ($this->config['withAjax']) {
            // &opacity - [ 0. < float <= 1. ]  Should be a float value
            $opacity = floatval($this->modx->getOption('opacity', $this->config, 1.));
            $this->config['opacity'] = ($opacity > 0. && $opacity <= 1.) ? $opacity : 1.0;

            // &effect - [ 'basic' | 'showfade' | 'slidefade' ]
            $this->config['effect'] = $this->modx->getOption('effect', $this->config, 'basic');
        }

        // &clearDefault [ 0| 1 ]
        $this->config['clearDefault'] = (bool)$this->modx->getOption('clearDefault', $this->config, 0);

        // initialize searchString
        if (!$this->_initSearchString()) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] Could not load _initSearchString: ' . $this->getError());
            return;
        }

        // initialise page of results
        $page = filter_input(INPUT_GET, $this->config['pageIndex'], FILTER_SANITIZE_NUMBER_INT);
        if (!empty($page)) {
            $this->page = intval($page) > 0 ? intval($page) : 1;
        } else {
            $this->page = 1;
        }

        $queryHook = $this->getHooks('queryHook');
        if (isset($queryHook['perPage']) && !empty($queryHook['perPage'])) {
            $this->config['perPage'] = $queryHook['perPage'];
        }
        $asContext = array(
            'searchString' => $this->searchString,
            'searchQuery' => $this->searchQuery,
            'searchTerms' => $this->searchTerms,
            'offset' => $this->offset,
            'page' => $this->page,
            'queryHook' => $queryHook
        );

        /**
         * Start the searching from here
         */
        $doQuery = true;
        if ($this->config['cacheQuery'] && $this->config['cacheType'] === 'html') {
            $key = serialize(array_merge($this->config, $asContext));
            $hash = md5($key);
            $cacheOptions = array(xPDO::OPT_CACHE_KEY => 'advsearch');
            $foundCached = $this->modx->cacheManager->get($hash, $cacheOptions);
            if ($foundCached) {
                $doQuery = FALSE;
                $output = $foundCached['results'];
            }
        }
        if ($doQuery) {
            $defaultAdvSearchCorePath = $this->modx->getOption('core_path') . 'components/advsearch/';
            $advSearchCorePath = $this->modx->getOption('advsearch.core_path', null, $defaultAdvSearchCorePath);
            $this->searchResults = $this->modx->getService('advsearchresults', 'AdvSearchResults', $advSearchCorePath . 'model/advsearch/', $this->config);
            if (!($this->searchResults instanceof AdvSearchResults)) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] Could not load AdvSearchResults class.');
                return false;
            }
            $this->searchResults->doSearch($asContext);
            $this->qtime = $this->getElapsedTime();
            $this->modx->setPlaceholder('qtime', $this->qtime);
            // searchResults resets "page" to 1 if the offset beyond the limit
            $this->page = $this->searchResults->getPage();
            $this->getHooks('postHook');

            // display results
            $typout = explode(',', $this->config['output']);
            $out = array();
            foreach ($typout as $to) {
                if ($to == 'html') {
                    if ($this->searchResults->resultsCount) {
                        $out[$to] = $this->renderOutput($this->searchResults);
                    } else {
                        $out[$to] = $this->modx->lexicon('advsearch.no_results');
                    }
                } elseif ($to == 'ids') {
                    $out[$to] = json_encode($this->searchResults->idResults);
                } elseif ($to == 'json') {
                    $out[$to] = json_encode($this->searchResults->results);
                }
            }

            if ($this->config['withAjax']) {
                $out['pag'] = $this->page;
                $out['ppg'] = $this->config['perPage'];
                $out['nbr'] = $this->searchResults->resultsCount;
                $out['pgt'] = $this->config['pagingType'];
                $out['opc'] = $this->config['opacity'];
                $out['eff'] = $this->config['effect'];
                $out['cdf'] = $this->config['clearDefault'];

                $output = json_encode($out);
            } else {
                if (count($typout) > 1) {
                    $output = json_encode($out);
                } else {
                    $output = $out[$typout[0]];
                }
            }

            if ($this->config['cacheQuery'] && $this->config['cacheType'] === 'html') {
                $cache = array(
                    'results' => $output,
                );
                $this->modx->cacheManager->set($hash, $cache, $this->config['cacheTime'], $cacheOptions);
            }
        }

        // log elapsed time
        $this->ifDebug('Elapsed time: ' . $this->getElapsedTime(), __METHOD__, __FILE__, __LINE__);

        if (!empty($this->config['toPlaceholder'])) {
            $this->modx->setPlaceholder($this->config['toPlaceholder'], $output);
            return;
        }

        return $output;
    }

    /**
     * _initSearchString - initialize searchString && searchQuery
     * @deprecated  should be moved to the controllers
     * @access private
     * @param string $defaultString Default search string value
     * @return boolean Returns true if correctly initialized, otherwise false.
     */
    private function _initSearchString($defaultString = '') {
        if (isset($this->config['searchString'])) {
            $defaultString = $this->config['searchString'];
        }

        $searchString = '';
        $filteredString = filter_input(INPUT_GET, $this->config['searchIndex'], FILTER_SANITIZE_SPECIAL_CHARS);
        if (!empty($filteredString)) {
            $searchString = $this->sanitizeSearchString($filteredString);
        } else {
            $searchString = $defaultString;
        }

        if (in_array($this->config['engine'], array('all', 'zend'))) {
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
        } else {
            $this->searchString = $searchString;
            $this->searchQuery = null;
            $matches = null;
            preg_match('/^(\'|")[\s\S](\'|")$/', $searchString, $matches);
            if ($matches) {
                $this->validTerm($searchString, 'phrase', TRUE);
            } else {
                $this->validTerm($searchString, 'word', TRUE);
            }
        }

        return true;
    }

    /**
     * Get possible hooks
     *
     * @access private
     * @param string $type Hook type
     * @return AdvSearchHooks Returns advSearchHooks object
     */
    public function getHooks($type) {
        if (!empty($this->config[$type])) {
            include_once dirname(__FILE__) . "/advsearchhooks.class.php";
            if (!class_exists('AdvSearchHooks')) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] Could not load AdvSearchHooks class.');
                return false;
            }

            $ashooks = new AdvSearchHooks($this);
            if (!empty($ashooks)) {
                if ($type === 'queryHook') {
                    $ashooks->loadMultiple($this->config[$type], array(), array(
                        'hooks' => $this->config[$type],
                        'searchString' => $this->searchString
                    ));
                    $this->queryHook = $ashooks->queryHook;
                    $this->ifDebug('QueryHook: ' . print_r($this->queryHook, true), __METHOD__, __FILE__, __LINE__);

                    return $this->queryHook;
                } elseif ($type === 'postHook') {
                    $ashooks->loadMultiple($this->config[$type], array(
                        'hooks' => $this->config[$type],
                        'offset' => $this->offset,
                        'page' => $this->page,
                        'perPage' => $this->config['perPage'],
                        'postHookTpls' => $this->config['postHookTpls']
                    ));
                    $this->searchResults = $ashooks->postHook;
                    $this->ifDebug('PostHook: ' . print_r($ashooks->postHook, true), __METHOD__, __FILE__, __LINE__);

                    return true;
                }
            }
        }

        return false;
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

    /*
     * Valid a term as search term
     *
     * @access private
     * @param string or array $term The term(s) to validate
     * @param boolean/null $sign true if mandatory, null if optional, false if excluded
     * @param integer $nbTerms Number of terms already processed
     * @return boolean Returns true if valid, otherwise false.
     */

    public function validTerm($term, $type, $sign, & $nbTerms = 0, $record = true) {
        if ($type == 'phrase') {
            $phrase = substr($term, 1, -1); // remove beginning and end quotes
            $phraseArray = explode(' ', $phrase);
            foreach ($phraseArray as $word) {
                $valid = $this->validTerm($word, 'word', $sign, $nbTerms, false);
                if (!$valid) {
                    return false;
                }
            }
            $this->searchTerms[] = $phrase;

            return true;
        } else {
            if (strlen($term) < $this->config['minChars']) {
                $msgerr = $this->modx->lexicon('advsearch.minchars', array(
                    'minterm' => $term,
                    'minchars' => $this->config['minChars']
                ));
                $this->setError($msgerr);

                return false;
            }
            $nbTerms++;
            if ($nbTerms > $this->config['maxWords']) {
                $msgerr = $this->modx->lexicon('advsearch.maxwords', array(
                    'maxwords' => $this->config['maxwords']
                ));
                $this->setError($msgerr);

                return false;
            }
            // record the valid search terms for futher highlighting
            if ($record && ($sign || is_null($sign))) {
                $this->searchTerms[] = $term;
            }

            return true;
        }
    }

    /*
     * Returns search results output
     *
     * @access public
     * @param AdvSearchResults $asr a AdvSearchResult object
     * @return string Returns search results output
     */

    public function renderOutput(AdvSearchResults $asr = null) {
        if (is_null($asr)) {
            return FALSE;
        }

        $results = $asr->results;
        $pageResultsCount = count($results);
        $resultsCount = $asr->resultsCount;
        $this->searchTerms = array_unique($this->searchTerms);
        $this->displayedFields = array_merge($asr->mainFields, $asr->tvFields, $asr->joinedFields);
        $this->_loadOutputProperties();

        // add advSearch css file
        if ($this->config['addCss'] == 1) {
            $this->modx->regClientCss($this->config['assetsUrl'] . 'css/advsearch.css');
        }

        // results header
        $infoOutput = $this->_getResultInfo($resultsCount);

        // pagination
        $pagingOutput = $this->_getPaging($resultsCount);

        // moreResults link
        $moreLinkOutput = $this->_getMoreLink();

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
                if ((int) $result[$asr->primaryKey]) {
                    $result['link'] = $this->modx->makeUrl($result[$asr->primaryKey], $ctx, '', $this->config['urlScheme']);
                }
            }
            if ($this->config['toArray']) {
                $resultsArray[] = $result;
            } else {
                $resultsOutput .= $this->processElementTags($this->parseTpl($this->config['tpl'], $result));
            }
            $idx++;
        }

        // set global placeholders
        // query: search term entered by user, total: number of results, etime: elapsed time
        $placeholders = array(
            'query' => $this->searchString,
            'total' => $resultsCount,
            'etime' => $this->getElapsedTime(),
        );

        $this->modx->setPlaceholders($placeholders, $this->config['placeholderPrefix']);

        $resultsPh = array(
            'resultInfo' => $infoOutput,
            'paging' => $pagingOutput,
            'pagingType' => $this->config['pagingType'],
            'moreResults' => $moreLinkOutput
        );
        if ($this->config['toArray']) {
            $resultsPh = array_merge($placeholders, array(
                'qtime' => $this->qtime,
            ),$resultsPh, array(
                'properties' => $this->config,
                'results' => $resultsArray,
            ));
            $output = '<pre class="advsea-code">' . print_r($resultsPh, 1) . '</pre>';
        } else {
            $resultsPh['results'] = $resultsOutput;
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
            $pageTpl = $this->modx->getOption('page3PageLinkTpl', $this->config, 'PageLink');
            $chunk = $this->modx->getObject('modChunk', array('name' => $pageTpl));
            $this->config['page3PageLinkTpl'] = (empty($chunk)) ? 'pagelink' : $pageTpl;

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

        $this->ifDebug('Config parameters after checking in class ' . __CLASS__ . ': ' . print_r($this->config, true), __METHOD__, __FILE__, __LINE__);

        return true;
    }

    /*
     * Returns Result info header
     *
     * @access private
     * @param string $searchString The search string
     * @param integer $resultsCount The number of results found
     * @return string Returns search results output header info
     */

    private function _getResultInfo($resultsCount) {
        $output = '';
        if (!empty($this->searchString)) {
            if ($resultsCount > 1) {
                $lexicon = 'advsearch.results_text_found_plural';
            } else {
                $lexicon = 'advsearch.results_text_found_singular';
            }
            $output = $this->modx->lexicon($lexicon, array(
                'count' => $resultsCount,
                'text' => !empty($this->config['highlightResults']) ? $this->_addHighlighting($this->searchString, $this->searchTerms, $this->config['highlightClass'], $this->config['highlightTag']) : $searchString
            ));
        } else {
            if ($resultsCount > 1) {
                $lexicon = 'advsearch.results_found_plural';
            } else {
                $lexicon = 'advsearch.results_found_singular';
            }
            $output = $this->modx->lexicon($lexicon, array(
                'count' => $resultsCount
            ));
        }

        return $output;
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
            'nbpages' => $nbPages
        );

        $this->modx->setPlaceholders($pagePh, $this->config['placeholderPrefix']);

        $qParameters = array();
        if (!empty($this->queryHook['requests'])) {
            $qParameters = $this->queryHook['requests'];
        }

        if ($this->config['pagingType'] == 1) {
            // pagination type 1
            $pagePh = array();

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

            $output = $this->processElementTags($this->parseTpl($this->config['paging1Tpl'], $pagePh));
        } elseif ($this->config['pagingType'] == 2) {
            // pagination type 2
            $paging2 = array();
            for ($i = 0; $i < $nbPages; ++$i) {
                $pagePh = array();
                $pagePh['text'] = $i + 1;
                $pagePh['separator'] = $this->config['pagingSeparator'];
                $pagePh['page'] = $i + 1;
                if ($this->page == $i + 1) {
                    $pagePh['link'] = $i + 1;
                    $paging2[] = $this->processElementTags($this->parseTpl($this->config['currentPageTpl'], $pagePh));
                } else {
                    $parameters = array_merge($idParameters, $qParameters, array(
                        $this->config['pageIndex'] => $pagePh['page']
                    ));
                    $pagePh['link'] = $this->modx->makeUrl($id, '', $parameters, $this->config['urlScheme']);
                    $paging2[] = $this->processElementTags($this->parseTpl($this->config['pageTpl'], $pagePh));
                }
            }
            $paging2 = @implode($this->config['pagingSeparator'], $paging2);
            $output = $this->processElementTags($this->parseTpl($this->config['paging2Tpl'], array('paging2' => $paging2)));
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
            $output = $this->processElementTags($this->parseTpl($this->config['paging3Tpl'], array(
                        'previouslink' => $previouslink,
                        'paging3' => $paging3,
                        'nextlink' => $nextlink,
            )));
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
            $output = $this->processElementTags($this->parseTpl($this->config['currentPageTpl'], $pagePh));
        } else {
            $parameters = array_merge($parameters, array(
                $this->config['pageIndex'] => $idx
            ));
            $pagePh['link'] = $this->modx->makeUrl($docId, '', $parameters, $this->config['urlScheme']);
            $output = $this->processElementTags($this->parseTpl($this->config['page3PageLinkTpl'], $pagePh));
        }

        return $output;
    }

    /*
     * Returns "More results" link
     *
     * @access private
     * @param string $searchString The search string
     * @param integer $resultsCount The number of results found
     * @param integer $offset The offset of the result page
     * @param integer $pageResultsCount The number of results for the current page
     * @return string Returns "More results" link
     */

    private function _getMoreLink() {
        $output = '';
        if ($this->config['moreResults']) {
            $idParameters = $this->modx->request->getParameters();
            $qParameters = array();
            if (!empty($this->queryHook['requests'])) {
                $qParameters = $this->queryHook['requests'];
            }
            $parameters = array_merge($idParameters, $qParameters);
            $id = $this->config['moreResults'];
            $linkPh = array(
                'asId' => $this->config['asId'],
                'moreLink' => $this->modx->makeUrl($id, '', $parameters, $this->config['urlScheme'])
            );
            $output = $this->processElementTags($this->parseTpl($this->config['moreResultsTpl'], $linkPh));
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

            return $this->processElementTags($this->parseTpl($tpl, array('extract' => $intro)));
        }

        // get extracts
        $extracts = array();
        $extractLength2 = $extractLength / 2;
        $rank = 0;

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
                $pattern = '#' . preg_quote($searchTerm, '/') . '#i';
                $subject = '<' . $highlightTag . ' class="' . $highlightClass . ' ' . $highlightClass . '-' . $rank . '">\0</' . $highlightTag . '>';
                $extract = preg_replace($pattern, $subject, $extract);
            }
            $extractPh = array(
                'extract' => $extracts[$i]['etcLeft'] . $extract . $extracts[$i]['etcRight']
            );
            $output .= $this->processElementTags($this->parseTpl($tpl, $extractPh));
        }
        return $output;
    }

    /**
     * Adds highlighting to the passed string
     *
     * @access private
     * @param string $searchString The search string
     * @param array $searchTerms The searched terms
     * @param string $class The class name to use for highlight the terms found
     * @param string $tag The html tag name to use to wrap the term found
     * @return string Returns highlighted search string
     */
    private function _addHighlighting($string, array $searchTerms = array(), $class = 'advsea-highlight', $tag = 'span') {
        foreach ($searchTerms as $key => $value) {
            $pattern = preg_quote($value);
            $string = preg_replace('/' . $pattern . '/i', '<' . $tag . ' class="' . $class . ' ' . $class . '-' . ($key + 1) . '">$0</' . $tag . '>', $string);
        }
        return $string;
    }

}
