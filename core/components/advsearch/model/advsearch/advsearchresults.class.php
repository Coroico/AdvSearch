<?php

/**
 * AdvSearch - AdvSearchResults class
 *
 * @package 	AdvSearch
 * @author		Coroico
 *              goldsky - goldsky@virtudraft.com
 * @copyright 	Copyright (c) 2012 by Coroico <coroico@wangba.fr>
 *
 * @tutorial	Class to get search results
 *
 */
include_once dirname(__FILE__) . "/advsearchutil.class.php";

class AdvSearchResults extends AdvSearchUtil {

    public $mainClass = 'modResource';
    public $primaryKey = 'id';
    public $mainFields = array();
    public $joinedFields = array();
    public $tvFields = array();
    public $resultsCount = 0;
    public $results = array();
    public $idResults = array();
    protected $page = 1;
    protected $queryHook = null;
    protected $ids = array();
    protected $sortbyClass = array();
    protected $sortbyField = array();
    protected $sortbyDir = array();
    protected $mainWhereFields = array();
    protected $joinedWhereFields = array();
    protected $tvWhereFields = array();

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
        $asContext['joinedFields'] = $this->joinedFields;
        $asContext['tvWhereFields'] = $this->tvWhereFields;
        $asContext['tvFields'] = $this->tvFields;
        $asContext['sortbyClass'] = $this->sortbyClass;
        $asContext['sortbyField'] = $this->sortbyField;
        $asContext['sortbyDir'] = $this->sortbyDir;

        $doQuery = TRUE;
        if ($this->config['cacheQuery']) {
            $key = serialize(array_merge($this->config, $asContext));
            $hash = md5($key);
            $cacheOptions = array(xPDO::OPT_CACHE_KEY => 'advsearch');
            $foundCached = $this->modx->cacheManager->get($hash, $cacheOptions);
            if ($foundCached) {
                $doQuery = FALSE;
                $this->results = $foundCached['results'];
                $this->resultsCount = $foundCached['resultsCount'];
            }
        }

        if ($doQuery) {
            // get results
            if ($this->mainClass == 'modResource') {
                // default package (modResource + Tvs) and possibly joined packages
                $engine = trim(strtolower($this->config['engine']));
                switch ($engine) {
                    case 'all':
//                        $controller = $this->loadController('all');
//                        $this->results = $controller->getResults($asContext);
//
//                        break;
                    case 'zend':
//                        $controller = $this->loadController('zend');
//                        $this->results = $controller->getResults($asContext);
//
//                        break;
                    case 'mysql':
                    default:
                        $controller = $this->loadController('mysql');
                        break;
                }
            } else {
                // search in a different main package and possibly joined packages
                $controller = $this->loadController('custom');
            }
            if ($controller) {
                $this->results = $controller->getResults($asContext);
                if (count($this->results) === 0) {
                    // disable fulltext
                    $this->results = $controller->getResults($asContext, false);
                }
                $this->resultsCount = $controller->getResultsCount();
                $this->page = $controller->getPage();
            }

            if ($this->config['cacheQuery']) {
                $cache = array(
                    'results' => $this->results,
                    'resultsCount' => $this->resultsCount
                );
                $this->modx->cacheManager->set($hash, $cache, $this->config['cacheTime'], $cacheOptions);
            }
        }

        return $this->results;
    }

    public function getPage() {
        return $this->page;
    }

    public function loadController($name) {
        $filename = dirname(dirname(dirname(__FILE__))) . '/controllers/advsearch' . strtolower($name) . 'controller.class.php';
        if (!file_exists($filename)) {
            $msg = 'Missing Controller file: ' . $filename;
            $this->setError($msg);
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] ' . $msg, '', __METHOD__, __FILE__, __LINE__);
            return FALSE;
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
            $pattern = '{core_path}';   // wilcard used inside the pathname definition
            $replacement = $this->modx->getOption('core_path', null, MODX_CORE_PATH);
            $this->mainClass = $this->queryHook['main']['class'];  // main class
            $this->queryHook['main']['packagePath'] = str_replace($pattern, $replacement, $this->queryHook['main']['packagePath']);

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

        // &engine [ 'mysql' | 'zend' | 'all' ] - name of search engine to use
        $engine = strtolower(trim($this->modx->getOption('engine', $this->config, 'mysql')));
        $this->config['engine'] = in_array(strtolower($engine), array('all', 'zend', 'mysql')) ? strtolower($engine) : 'mysql';

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
                $fld[1] = (isset($fld[1]) && intval($fld[1])) ? $fld[1] : 1;
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
            $lstSortby = $this->modx->getOption('sortby', $this->config, 'createdon DESC');
        }

        if (!empty($lstSortby)) {
            $sortCpls = array_map('trim', explode(',', $lstSortby));
            $sorts = array();
            $this->sortbyField = array();
            $this->sortbyDir = array();
            foreach ($sortCpls as $sortCpl) {
                $sortElts = array_map('trim', explode(' ', $sortCpl));
                $dir = (empty($sortElts[1])) ? 'DESC' : $sortElts[1];
                $dir = (($dir != 'DESC') && ($dir != 'ASC')) ? 'DESC' : $dir;
                $classFieldElts = array_map('trim', explode('.', $sortElts[0]));
                $class = (count($classFieldElts) == 2) ? $classFieldElts[0] : '';
                $field = (count($classFieldElts) == 2) ? $classFieldElts[1] : $classFieldElts[0];
                $this->sortbyField[] = $field;
                $this->sortbyClass["{$field}"] = (!empty($class)) ? $this->modx->escape($class) . '.' : '';
                $this->sortbyDir["{$field}"] = $dir;
                $sorts[] = "{$field} {$dir}";
            }
            $this->config['sortby'] = implode(',', $sorts);
        } else {
            $this->config['sortby'] = $lstSortby;
        }

        $this->ifDebug('Config parameters after checking in class ' . __CLASS__ . ': ' . print_r($this->config, true), __METHOD__, __FILE__, __LINE__);

        return;
    }
}