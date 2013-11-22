<?php

/**
 * AdvSearch - AdvSearch class
 *
 * @package 	AdvSearch
 * @author		Coroico
 * @copyright 	Copyright (c) 2012 by Coroico <coroico@wangba.fr>
 *
 * @tutorial	Main class to get & display search results
 *
 */
include_once dirname(__FILE__) . "/advsearchutil.class.php";

class AdvSearch extends AdvSearchUtil {

    public $searchResults = null;
    protected $offset = 0;
    protected $queryHook = null;
    protected $extractFields = array();
    protected $nbExtracts;
    protected $asr = null;

    public function __construct(modX & $modx, array $properties = array()) {
        parent::__construct($modx, $properties);
    }

    /**
     * output the serch results
     *
     * @access public
     * @return string returns the search results
     */
    public function output() {
        $output = '';
        $msg = '';
        // check parameters
        $valid = $this->_checkParams($msg);
        if (!$valid)
            return '';

        if ($this->forThisInstance()) {

            // initialize searchString
            if ($this->_initSearchString('', $msg)) {

                // initialise offset of results
                if (isset($_REQUEST[$this->config['offsetIndex']]))
                    $this->offset = $this->sanitize($_REQUEST[$this->config['offsetIndex']]);
                else
                    $this->offset = 0;

                // The first time display or not results
                $init = (isset($_REQUEST['asId']) || isset($_REQUEST['sub'])) ? 'all' : $this->config['init'];
                if ($init == 'all') {
                    $asContext = array(
                        'searchString' => $this->searchString,
                        'searchQuery' => $this->searchQuery,
                        'offset' => $this->offset,
                        'queryHook' => $this->getHooks('queryHook')
                    );
                    include_once dirname(__FILE__) . "/advsearchresults.class.php";
                    if (!class_exists('AdvSearchResults')) {
                        $this->modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] Could not load AdvSearchResults class.');
                        return false;
                    }
                    $this->searchResults = new AdvSearchResults($this->modx, $this->config);
                    $this->searchResults->doSearch($asContext);
                    $this->getHooks('postHook');

                    // display results
                    $typout = explode(',', $this->config['output']);
                    $out = array();
                    foreach ($typout as $to) {
                        if ($to == 'html') {
                            if ($this->searchResults->resultsCount)
                                $out[$to] = $this->renderOutput($this->searchResults, $asContext);
                            else
                                $out[$to] = $this->modx->lexicon('advsearch.no_results');
                        }
                        elseif ($to == 'ids')
                            $out[$to] = json_encode($this->searchResults->idResults);
                        elseif ($to == 'json')
                            $out[$to] = json_encode($this->searchResults->results);
                    }

                    if ($this->config['withAjax']) {
                        $out['ofs'] = $this->offset;
                        $out['ppg'] = $this->config['perPage'];
                        $out['nbr'] = $this->searchResults->resultsCount;
                        $out['pgt'] = $this->config['pagingType'];
                        $out['opc'] = $this->config['opacity'];
                        $out['eff'] = $this->config['effect'];
                        $output = json_encode($out);
                    } else {
                        if (count($typout) > 1)
                            $output = json_encode($out);
                        else
                            $output = $out[$typout[0]];
                    }
                }
            }
            else {
                $output = $msg;
            }

            if (!empty($this->config['toPlaceholder'])) {
                $this->modx->setPlaceholder($this->config['toPlaceholder'], $output);
                $output = '';
            }

            // log elapsed time
            if ($this->dbg)
                $this->modx->log(modX::LOG_LEVEL_DEBUG, '[AdvSearch] Elapsed time: ' . $this->getElapsedTime(), '', 'output');

            return $output;
        }
    }

    /**
     * Check parameters
     *
     * @access private
     */
    private function _checkParams(& $msgerr = '') {
        // check the common parameters with AdvSearchForm class
        $valid = $this->checkCommonParams($msgerr);
        if (!$valid)
            return false;

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

        if ($this->dbg)
            $this->modx->log(modX::LOG_LEVEL_DEBUG, '[AdvSearch] Config parameters after checking: ' . print_r($this->config, true), '', '_checkParams');

        return true;
    }

    /**
     * _initSearchString - initialize searchString && searchQuery
     *
     * @access private
     * @param string $defaultString Default search string value
     * @param string $msgerr Error message
     * @return boolean Returns true if correctly initialized, otherwise false.
     */
    private function _initSearchString($defaultString = '', & $msgerr = '') {

        if (isset($this->config['searchString']))
            $defaultString = $this->config['searchString'];

        if (isset($_REQUEST[$this->config['searchIndex']])) {
            $request = $this->sanitizeSearchString($_REQUEST[$this->config['searchIndex']]);
            if ($request == $this->modx->lexicon('advsearch.box_text'))
                $searchString = '';
            else
                $searchString = $request;
        }
        else
            $searchString = $defaultString;

        if (!empty($searchString)) {
            // load the zend lucene library
            require_once $this->config['libraryPath'] . 'Zend/Search/Lucene.php';
            // parse query
            $searchQuery = Zend_Search_Lucene_Search_QueryParser::parse($searchString, $this->config['charset']);
            // valid maxwords and minchars
            $valid = $this->_validQuery($searchQuery, true, $msgerr);
            if (!$valid)
                return false;

            $this->searchString = $searchString;
            $this->searchQuery = $searchQuery;
        }
        else {
            // set the default value
            $this->searchString = $defaultString;
            $this->searchQuery = null;
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
                if ($type == 'queryHook') {
                    $ashooks->loadMultiple($this->config[$type], array(), array(
                        'hooks' => $this->config[$type],
                        'searchString' => $this->searchString
                    ));
                    $this->queryHook = $ashooks->queryHook;
                    if ($this->dbg)
                        $this->modx->log(modX::LOG_LEVEL_DEBUG, '[AdvSearch] QueryHook: ' . print_r($this->queryHook, true), '', 'getHooks');
                    return $this->queryHook;
                }
                elseif ($type == 'postHook') {
                    $ashooks->loadMultiple($this->config[$type], array(
                        'hooks' => $this->config[$type],
                        'offset' => $this->offset,
                        'perPage' => $this->config['perPage'],
                        'postHookTpls' => $this->config['postHookTpls']
                            )
                    );
                    $this->searchResults = $ashooks->postHook;
                    if ($this->dbg)
                        $this->modx->log(modX::LOG_LEVEL_DEBUG, '[AdvSearch] PostHook: ' . print_r($ashooks->postHook, true), '', 'getHooks');
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
     * @param string $msgerr Error message
     * @param integer $nbTerms Number of terms already processed
     * @return boolean Returns true if valid, otherwise false.
     */
    private function _validQuery($query = null, $sign = true, & $msgerr = '', & $nbTerms = 0) {
        if ($query instanceOf Zend_Search_Lucene_Search_Query_Boolean) {
            $subqueries = $query->getSubqueries();
            $signs = $query->getSigns();
            $nbs = count($subqueries);
            for ($i = 0; $i < $nbs; $i++) {
                $valid = $this->_validQuery($subqueries[$i], $signs[$i], $msgerr, $nbTerms);
                if (!$valid)
                    return false;
            }
            return true;
        } else if ($query instanceOf Zend_Search_Lucene_Search_Query_Preprocessing_Phrase) {
            $phrase = $query->__toString();
            $valid = $this->validTerm($phrase, 'phrase', $sign, $msgerr, $nbTerms);
            return $valid;
        } else if ($query instanceOf Zend_Search_Lucene_Search_Query_Preprocessing_Term) {
            $term = $query->__toString();
            $valid = $this->validTerm($term, 'word', $sign, $msgerr, $nbTerms);
            return $valid;
        } else {
            $msgerr = $this->modx->lexicon('advsearch.invalid_query');
            return false;
        }
    }

    /*
     * Valid a term as search term
     *
     * @access private
     * @param string or array $term The term(s) to validate
     * @param boolean/null $sign true if mandatory, null if optional, false if excluded
     * @param string $msgerr error message
     * @param integer $nbTerms Number of terms already processed
     * @return boolean Returns true if valid, otherwise false.
     */
    public function validTerm($term, $type, $sign, & $msgerr, & $nbTerms = 0, $record = true) {
        if ($type == 'phrase') {
            $phrase = substr($term, 1, -1); // remove beginning and end quotes
            $phraseArray = explode(' ', $phrase);
            foreach ($phraseArray as $word) {
                $valid = $this->validTerm($word, 'word', $sign, $msgerr, $nbTerms, false);
                if (!$valid)
                    return false;
            }
            $this->searchTerms[] = $phrase;
            return true;
        }
        else {
            if (strlen($term) < $this->config['minChars']) {
                $msgerr = $this->modx->lexicon('advsearch.minchars', array(
                    'minterm' => $term,
                    'minchars' => $this->config['minChars']
                        ));
                return false;
            }
            $nbTerms++;
            if ($nbTerms > $this->config['maxWords']) {
                $msgerr = $this->modx->lexicon('advsearch.maxwords', array(
                    'maxwords' => $this->config['maxwords']
                        ));
                return false;
            }
            // record the valid search terms for futher highlighting
            if ($record && ($sign || is_null($sign))) {
                $this->searchTerms[] = $term;
            }
            return true;
        }
    }

    /**
     * If the chunk is called by AJAX processor, it needs to be parsed for the
     * other elements to work, like snippet and output filters.
     *
     * Example:
     * <pre><code>
     * <?php
     * $content = $myObject->parseTpl('tplName', $placeholders);
     * $content = $myObject->processElementTags($content);
     * </code></pre>
     *
     * @param   string  $content    the chunk output
     * @param   array   $options    option for iteration
     * @return  string  parsed content
     */
    public function processElementTags($content, array $options = array()) {
        $maxIterations = intval($this->modx->getOption('parser_max_iterations', $options, 10));
        if (!$this->modx->parser) {
            $this->modx->getParser();
        }
        $this->modx->parser->processElementTags('', $content, true, false, '[[', ']]', array(), $maxIterations);
        $this->modx->parser->processElementTags('', $content, true, true, '[[', ']]', array(), $maxIterations);
        return $content;
    }

    /*
     * Returns search results output
     *
     * @access public
     * @param AdvSearchResults $asr a AdvSearchResult object
     * @return string Returns search results output
     */
    public function renderOutput(AdvSearchResults $asr = null) {
        $placeholders = array();
        $output = '';

        if (is_null($asr))
            return $output;
        $results = $asr->results;
        $pageResultsCount = count($results);
        $resultsCount = $asr->resultsCount;
        $this->searchTerms = array_unique($this->searchTerms);

        $displayedFields = array_merge($asr->mainFields, $asr->tvFields, $asr->joinedFields);
        $this->_checkDisplayParams($displayedFields);

        // add advSearch css file
        if ($this->config['addCss'] == 1) $this->modx->regClientCss($this->config['assetsUrl'] . 'css/advsearch.css');

        // results header
        $infoOutput = $this->_getResultInfo($this->searchString, $resultsCount);

        // pagination
        $pagingOutput = $this->_getPaging($this->searchString, $resultsCount, $this->offset, $pageResultsCount);

        // moreResults link
        $moreLinkOutput = $this->_getMoreLink($this->searchString, $resultsCount, $this->offset, $pageResultsCount);

        // results
        $resultsOutput = '';
        $idx = $this->offset + 1;

        foreach ($results as $result) {

            if ($this->nbExtracts && count($this->extractFields)) {
                $text = '';
                foreach ($this->extractFields as $extractField) {
                    $text .= "{$this->processElementTags($result[$extractField])}";
                }

                $extracts = $this->_getExtracts(
                        $text, $this->nbExtracts, $this->config['extractLength'], $this->searchTerms, $this->config['extractTpl'], $ellipsis = '...'
                );
            }
            else
                $extracts = '';

            $result['idx'] = $idx;
            $result['extracts'] = $extracts;

            if (empty($result['link'])) {
                $ctx = (!empty($result['context_key'])) ? $result['context_key'] : $this->modx->context->get('key');
                if ((int) $result[$asr->primaryKey])
                    $result['link'] = $this->modx->makeUrl($result[$asr->primaryKey], $ctx, '', $this->config['urlScheme']);
            }

            $resultsOutput .= $this->processChunk($this->config['tpl'], $result);
            $idx++;
        }

        $resultsPh = array(
            'resultInfo' => $infoOutput,
            'paging' => $pagingOutput,
            'pagingType' => $this->config['pagingType'],
            'results' => $resultsOutput,
            'moreResults' => $moreLinkOutput
        );
        $output = $this->processChunk($this->config['containerTpl'], $resultsPh);

        // set global placeholders
        // query: search term entered by user, total: number of results, etime: elapsed time
        $placeholders = array(
            'query' => $this->searchString,
            'total' => $resultsCount,
            'etime' => $this->getElapsedTime()
        );
        $this->modx->setPlaceholders($placeholders, $this->config['placeholderPrefix']);

        return $output;
    }

    /**
     * Check parameters for the displaying of results
     *
     * @access private
     * @param array $displayedFields Fields to display
     */
    private function _checkDisplayParams($displayedFields) {

        // &output
        $outputLst = $this->modx->getOption('output', $this->config, 'output');
        $output = array_map('trim', explode(',', $outputLst));
        $output = array_intersect($output, array('html', 'rows', 'ids'));
        if (!count($output))
            $output = array('html');
        $this->config['output'] = implode(',', $output);

        // &containerTpl [ chunk name | 'AdvSearchResults' ]
        $containerTpl = $this->modx->getOption('containerTpl', $this->config, 'AdvSearchResults');
        $chunk = $this->getChunk($containerTpl);
        $this->config['containerTpl'] = (empty($chunk)) ? 'AdvSearchResults' : $containerTpl;

        // &tpl [ chunk name | 'AdvSearchResult' ]
        $tpl = $this->modx->getOption('tpl', $this->config, 'AdvSearchResult');
        $chunk = $this->getChunk($tpl);
        $this->config['tpl'] = (empty($chunk)) ? 'AdvSearchResult' : $tpl;

        // &showExtract [ string | '1:content' ]
        $showExtractArray = explode(':', $this->modx->getOption('showExtract', $this->config, '1:content'));
        if ((int) $showExtractArray[0] < 0)
            $showExtractArray[0] = 0;
        if ($showExtractArray[0]) {
            if (!isset($showExtractArray[1]))
                $showExtractArray[1] = 'content';
            // check that all the fields selected for extract exists in mainFields, tvFields or joinedFields
            $extractFields = explode(',', $showExtractArray[1]);
            foreach ($extractFields as $key => $field) {
                if (!in_array($field, $displayedFields))
                    unset($extractFields[$key]);
            }
            $this->extractFields = array_values($extractFields);
            $this->nbExtracts = $showExtractArray[0];
            $this->config['showExtract'] = $showExtractArray[0] . ':' . implode(',', $this->extractFields);
        }
        else {
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
            $chunk = $this->getChunk($extractTpl);
            $this->config['extractTpl'] = (empty($chunk)) ? 'Extract' : $extractTpl;

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

        // &pagingType[ 0 | 1 | 2 ]
        $pagingType = (int) $this->modx->getOption('pagingType', $this->config, 1);
        $this->config['pagingType'] = (($pagingType <= 2) && ($pagingType >= 0)) ? $pagingType : 1;

        if ($this->config['pagingType'] == 1) {
            // &paging1Tpl [ chunk name | 'Paging1' ]
            $paging1Tpl = $this->modx->getOption('paging1Tpl', $this->config, 'Paging1');
            $chunk = $this->getChunk($paging1Tpl);
            $this->config['paging1Tpl'] = (empty($chunk)) ? 'Paging1' : $paging1Tpl;
        } elseif ($this->config['pagingType'] == 2) {
            // &paging2Tpl [ chunk name | 'Paging2' ]
            $paging2Tpl = $this->modx->getOption('paging2Tpl', $this->config, 'Paging2');
            $chunk = $this->getChunk($paging2Tpl);
            $this->config['paging2Tpl'] = (empty($chunk)) ? 'Paging2' : $paging2Tpl;

            // &currentPageTpl [ chunk name | 'CurrentPageLink' ]
            $currentPageTpl = $this->modx->getOption('currentPageTpl', $this->config, 'CurrentPageLink');
            $chunk = $this->getChunk($currentPageTpl);
            $this->config['currentPageTpl'] = (empty($chunk)) ? 'CurrentPageLink' : $currentPageTpl;

            // &pageTpl [ chunk name | 'PageLink' ]
            $pageTpl = $this->modx->getOption('pageTpl', $this->config, 'PageLink');
            $chunk = $this->getChunk($pageTpl);
            $this->config['pageTpl'] = (empty($chunk)) ? 'PageLink' : $pageTpl;

            // &pagingSeparator
            $this->config['pagingSeparator'] = $this->modx->getOption('pagingSeparator', $this->config, ' | ');
        }

        if ($this->config['withAjax']) {
            // &moreResults - [ int id of a document | 0 ]
            $moreResults = (int) $this->modx->getOption('moreResults', $this->config, 0);
            $this->config['moreResults'] = ($moreResults > 0) ? $moreResults : 0;

            if ($this->config['moreResults']) {
                // &moreResultsTpl [ chunk name | 'MoreResults' ]
                $moreResultsTpl = $this->modx->getOption('moreResultsTpl', $this->config, 'MoreResults');
                $chunk = $this->getChunk($moreResultsTpl);
                $this->config['moreResultsTpl'] = (empty($chunk)) ? 'MoreResults' : $moreResultsTpl;
            }
        }

        if ($this->dbg)
            $this->modx->log(modX::LOG_LEVEL_DEBUG, '[AdvSearch] Config parameters after checking: ' . print_r($this->config, true), '', '_checkDisplayParams');

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
    private function _getResultInfo($searchString, $resultsCount) {
        $output = '';
        if (!empty($searchString)) {
            if ($resultsCount > 1)
                $lexicon = 'advsearch.results_text_found_plural';
            else
                $lexicon = 'advsearch.results_text_found_singular';
            $output = $this->modx->lexicon($lexicon, array(
                'count' => $resultsCount,
                'text' => !empty($this->config['highlightResults']) ? $this->_addHighlighting($searchString, $this->searchTerms, $this->config['highlightClass'], $this->config['highlightTag']) : $searchString
                    ));
        }
        else {
            if ($resultsCount > 1)
                $lexicon = 'advsearch.results_found_plural';
            else
                $lexicon = 'advsearch.results_found_singular';
            $output = $this->modx->lexicon($lexicon, array(
                'count' => $resultsCount
                    ));
        }
        return $output;
    }

    /*
     * Returns paging (type 0 or 1)
     *
     * @access private
     * @param string $searchString The search string
     * @param integer $resultsCount The number of results found
     * @param integer $offset The offset of the result page
     * @param integer $pageResultsCount The number of results for the current page
     * @return string Returns search results output header info
     */
    private function _getPaging($searchString, $resultsCount, $offset, $pageResultsCount) {
        $output = '';
        if ($this->config['perPage'] && $this->config['pagingType']) {
            $id = $this->modx->resource->get('id');
            $idParameters = $this->modx->request->getParameters();

            // first: number of the first result of the current page, last: number of the last result of current page,
            // page: number of the current page, nbpages: total number of pages
            $currentPage = ceil($offset / $this->config['perPage']) + 1;
            $nbPages = ceil($resultsCount / $this->config['perPage']);
            $pagePh = array(
                'first' => $offset + 1,
                'last' => $offset + $pageResultsCount,
                'total' => $resultsCount,
                'currentpage' => $currentPage,
                'nbpages' => $nbPages
            );
            $this->modx->setPlaceholders($pagePh, $this->config['placeholderPrefix']);

            $qParameters = array();
            if (!empty($this->queryHook['requests']))
                $qParameters = $this->queryHook['requests'];

            if ($this->config['pagingType'] == 1) {
                // pagination type 1
                $pagePh = array();
                $nextOffset = $offset + $this->config['perPage'];
                if ($nextOffset < $resultsCount) {
                    $parameters = array_merge($idParameters, $qParameters, array(
                        $this->config['offsetIndex'] => $nextOffset
                            ));
                    $pagePh['nextlink'] = $this->modx->makeUrl($id, '', $parameters, $this->config['urlScheme']);
                }

                $previousOffset = $offset - $this->config['perPage'];
                if ($previousOffset >= 0) {
                    $parameters = array_merge($idParameters, $qParameters, array(
                        $this->config['offsetIndex'] => $previousOffset
                            ));
                    $pagePh['previouslink'] = $this->modx->makeUrl($id, '', $parameters, $this->config['urlScheme']);
                }

                $output = $this->processChunk($this->config['paging1Tpl'], $pagePh);
            } elseif ($this->config['pagingType'] == 2) {
                // pagination type 2
                $paging2 = '';
                for ($i = 0; $i < $nbPages; ++$i) {
                    $pagePh = array();
                    $pagePh['text'] = $i + 1;
                    $pagePh['separator'] = $this->config['pagingSeparator'];
                    $pagePh['offset'] = $i * $this->config['perPage'];
                    if ($currentPage == $i + 1) {
                        $pagePh['link'] = $i + 1;
                        $paging2 .= $this->processChunk($this->config['currentPageTpl'], $pagePh);
                    } else {
                        $parameters = array_merge($idParameters, $qParameters, array(
                            $this->config['offsetIndex'] => $pagePh['offset']
                                ));
                        $pagePh['link'] = $this->modx->makeUrl($id, '', $parameters, $this->config['urlScheme']);
                        $paging2 .= $this->processChunk($this->config['pageTpl'], $pagePh);
                    }
                    if ($i < $nbPages) {
                        $paging2 .= $this->config['pagingSeparator'];
                    }
                }
                $paging2 = trim($paging2, $this->config['pagingSeparator']);
                $output = $this->processChunk($this->config['paging2Tpl'], array('paging2' => $paging2));
            }
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
    private function _getMoreLink($searchString, $resultsCount, $offset, $pageResultsCount) {
        $output = '';
        if ($this->config['moreResults']) {
            $idParameters = $this->modx->request->getParameters();
            $qParameters = array();
            if (!empty($this->queryHook['requests']))
                $qParameters = $this->queryHook['requests'];
            $parameters = array_merge($idParameters, $qParameters);
            $id = $this->config['moreResults'];
            $linkPh = array(
                'asId' => $this->config['asId'],
                'moreLink' => $this->modx->makeUrl($id, '', $parameters, $this->config['urlScheme'])
            );
            $output = $this->processChunk($this->config['moreResultsTpl'], $linkPh);
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
        if (empty($text)) return '';

        $trimchars = "\t\r\n -_()!~?=+/*\\,.:;\"'[]{}`&";
        $nbTerms = count($searchTerms);
        if (!$nbTerms) {
            // with an empty searchString - show as introduction the first characters of the text
            if (($extractLength > 0) && !empty($text)) {
				$offset = ($extractLength  <  $textLength) ? $extractLength - 1 : $textLength - 1;
                $pos = min(mb_strpos($text, ' ', $offset), mb_strpos($text, '.', $offset));
                if ($pos)
                    $intro = rtrim(mb_substr($text, 0, $pos), $trimchars) . $ellipsis;
                else
                    $intro = $text;
            }
            else
                $intro = '';
            return($this->processChunk($tpl, array('extract' => $intro)));
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
                if ($left < 0)
                    $left = 0;
                if ($right > $textLength)
                    $right = $textLength;
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
                if ($begin != '')
                    $extracts[$i]['left'] = (int) mb_strrpos($begin, ' ');

                $end = mb_substr($text, $extracts[$i]['right'] + 1, $textLength - $extracts[$i]['right']);
                if ($end != '')
                    $dr = (int) mb_strpos($end, ' ');
                if (is_int($dr))
                    $extracts[$i]['right']+= $dr + 1;
            }

            if ($extracts[0]['left'] == 0)
                $extracts[0]['etcLeft'] = '';
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
            $output .= $this->processChunk($tpl, $extractPh);
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