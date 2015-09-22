<?php

/**
 * AdvSearch - AdvSearch class
 *
 * @package 	AdvSearch
 * @author		Coroico - coroico@wangba.fr
 *              goldsky - goldsky@virtudraft.com
 * @copyright 	Copyright (c) 2012 - 2015 by Coroico <coroico@wangba.fr>
 *
 * @tutorial	Main class to get & display search results
 *
 */
include_once dirname(__FILE__) . "/advsearch.class.php";

class AdvSearchRequest extends AdvSearch {

    public $searchResults = null;
    /* deprecated since 2.0.0 */
    protected $offset = 0;
    protected $page = 1;
    protected $queryHook = null;
    protected $displayedFields = array();
    protected $extractFields = array();
    protected $nbExtracts;
    protected $asr = null;

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
            return false;
        }

        // The first time display or not results, duplicating snippet's validation
        $asId = filter_input(INPUT_GET, 'asId', FILTER_SANITIZE_SPECIAL_CHARS);
        $sub = filter_input(INPUT_GET, 'sub', FILTER_SANITIZE_SPECIAL_CHARS);
        $init = (!empty($asId) || !empty($sub)) ? 'all' : $this->config['init'];
        if ($init !== 'all') {
            return;
        }

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

        // &parents [ comma delimited integers ]
        $this->config['parents'] = $this->modx->getOption('parents', $this->config);

        // &ids [ comma delimited integers ]
        $this->config['ids'] = $this->modx->getOption('ids', $this->config);

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
        $this->config['clearDefault'] = (bool) $this->modx->getOption('clearDefault', $this->config, 0);

        $this->_initSearchString();

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
        if ($this->config['cacheQuery']
//                && $this->config['cacheType'] === 'html'
        ) {
            $key = serialize(array_merge($this->config, $asContext));
            $hash = md5($key);
            $cacheOptions = array(xPDO::OPT_CACHE_KEY => 'advsearch');
            $foundCached = $this->modx->cacheManager->get($hash, $cacheOptions);
            if ($foundCached) {
                $doQuery = false;
                $output = $foundCached['results'];
                $outputCount = $foundCached['resultsCount'];
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
            $outputCount = $this->searchResults->resultsCount;
            // searchResults resets "page" to 1 if the offset beyond the limit
            $this->page = $this->searchResults->getPage();
            $this->getHooks('postHook');

            // display results
            $outputType = array_map('trim', @explode(',', $this->config['output']));
            $out = array();
            foreach ($outputType as $to) {
                if ($to == 'html') {
                    if ($outputCount) {
                        $out[$to] = $this->searchResults->htmlResult;
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
                $out['nbr'] = $outputCount;
                $out['pgt'] = $this->config['pagingType'];
                $out['opc'] = $this->config['opacity'];
                $out['eff'] = $this->config['effect'];
                $out['cdf'] = $this->config['clearDefault'];

                $output = json_encode($out);
            } else {
                if (count($outputType) > 1) {
                    $output = json_encode($out);
                } else {
                    $output = $out[$outputType[0]];
                }
            }

            if ($this->config['cacheQuery'] && $outputCount > 0 && !empty($output)
//                    && $this->config['cacheType'] === 'html'
            ) {
                $cache = array(
                    'results' => $output,
                    'resultsCount' => $outputCount,
                );
                $this->modx->cacheManager->set($hash, $cache, $this->config['cacheTime'], $cacheOptions);
            }
        } // if ($doQuery)

        // log elapsed time
        $this->ifDebug('Elapsed time: ' . $this->getElapsedTime(), __METHOD__, __FILE__, __LINE__);

        // set global placeholders
        // query: search term entered by user, total: number of results, etime: elapsed time
        $placeholders = array(
            'resultInfo' => $this->_getResultInfo($outputCount),
            'moreResults' => $this->_getMoreLink(),
            'query' => $this->searchString,
            'total' => $outputCount,
            'etime' => $this->getElapsedTime(),
        );
        $this->modx->toPlaceholders($placeholders, $this->config['placeholderPrefix']);

        if (!empty($this->config['toPlaceholder'])) {
            $this->modx->setPlaceholder($this->config['toPlaceholder'], $output);
            return;
        }

        return $output;
    }

    /**
     * _initSearchString - initialize searchString && searchQuery
     * @access private
     * @param string $defaultString Default search string value
     * @return void
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

        $this->searchString = $searchString;
        $this->searchQuery = null;
        $matches = null;
        preg_match('/^(\'|")[\s\S](\'|")$/', $searchString, $matches);
        if ($matches) {
            $this->validTerm($searchString, 'phrase', true);
        } else {
            $this->validTerm($searchString, 'word', true);
        }

        return true;
    }

    /**
     * Get possible hooks
     *
     * @access private
     * @param string $type hook's type type
     * @return mixed boolean or AdvSearchHooks object for "queryHook" type
     */
    public function getHooks($type) {
        if (empty($this->config[$type])) {
            return false;
        }

        include_once dirname(__FILE__) . "/advsearchhooks.class.php";
        if (!class_exists('AdvSearchHooks')) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] Could not load AdvSearchHooks class.');
            return false;
        }

        $asHooks = new AdvSearchHooks($this);
        if (!empty($asHooks)) {
            if ($type === 'queryHook') {
                $asHooks->loadMultiple($this->config[$type], array(), array(
                    'hooks' => $this->config[$type],
                    'searchString' => $this->searchString
                ));
                $this->queryHook = $asHooks->queryHook;
                $this->ifDebug('QueryHook: ' . print_r($this->queryHook, true), __METHOD__, __FILE__, __LINE__);

                return $this->queryHook;
            } elseif ($type === 'postHook') {
                $asHooks->loadMultiple($this->config[$type], array(
                    'hooks' => $this->config[$type],
                    'offset' => $this->offset,
                    'page' => $this->page,
                    'perPage' => $this->config['perPage'],
                    'postHookTpls' => $this->config['postHookTpls']
                ));
                $this->searchResults = $asHooks->postHook;
                $this->ifDebug('PostHook: ' . print_r($asHooks->postHook, true), __METHOD__, __FILE__, __LINE__);

                return true;
            }
        }

        return false;
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
                'text' => !empty($this->config['highlightResults']) ? $this->addHighlighting($this->searchString, $this->searchTerms, $this->config['highlightClass'], $this->config['highlightTag']) : $this->searchString
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

}
