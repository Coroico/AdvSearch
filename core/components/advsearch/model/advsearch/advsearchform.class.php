<?php

/**
 * AdvSearch - AdvSearchForm class
 *
 * @package 	AdvSearch
 * @author		Coroico
 * @copyright 	Copyright (c) 2012 by Coroico <coroico@wangba.fr>
 *
 * @tutorial	Main class to display the search form
 *
 */
include_once dirname(__FILE__) . "/advsearchutil.class.php";

class AdvSearchForm extends AdvSearchUtil {

    public function __construct(modX & $modx, array $config = array()) {
        // &clearDefault - [ 1 | 0 ]
        $config['clearDefault'] = (bool)(int) $modx->getOption('clearDefault', $config, 0);

        // &help - [ 1 | 0 ] - to add a help link near the search form
        $config['help'] = (bool)(int) $modx->getOption('help', $config, 1);

        // &keyval
        $config['keyval'] = $modx->getOption('keyval', $config, '');

        // &jsSearchForm - [ url | $assetsUrl . 'js/advsearchform.min.js' ]
        $config['jsSearchForm'] = $modx->getOption('jsSearchForm', $config, $config['assetsUrl'] . 'js/advsearchform.min.js');

        // &jsSearch - [ url | $assetsUrl . 'js/advsearch.min.js' ]
        $config['jsSearch'] = $modx->getOption('jsSearch', $config, $config['assetsUrl'] . 'js/advsearch.min.js');

        // &landing  [ int id of a document | 0 ]
        $landing = (int) $modx->getOption('landing', $config, 0);
        $config['landing'] = ($landing > 0) ? $landing : $modx->resource->get('id');

        // &tpl [ chunk name | 'AdvSearchForm' ]
        $config['tpl'] = $modx->getOption('tpl', $config, 'AdvSearchForm');

        //jQuery used by the help and by ajax mode
        if ($config['help'] || $config['withAjax']) {
            // &addJQuery - [ 0 | 1 | 2 ]
            $addJQuery = (int) $modx->getOption('addJQuery', $config, 1);
            $config['addJQuery'] = ($addJQuery == 0 || $addJQuery == 1 || $addJQuery == 2) ? $addJQuery : 1;

            // &jsJQuery - [ Location of the jQuery javascript library ]
            $config['jsJQuery'] = $modx->getOption('jsJQuery', $config, $config['assetsUrl'] . 'js/jquery-1.10.2.min.js');
        }

        // ajax mode parameters
        if ($config['withAjax']) {
            // &ajaxResultsId - [ resource id | 0]
            $ajaxResultsId = (int) $modx->getOption('ajaxResultsId', $config, 0);
            $config['ajaxResultsId'] = ($ajaxResultsId > 0) ? $ajaxResultsId : 0;
            if (!$config['ajaxResultsId']) {
                $msg = '[AdvSearch] &ajaxResultsId property is required and can not be zero!';
                $modx->log(modX::LOG_LEVEL_ERROR, $msg);
                throw new Exception($msg);
            }

            // &liveSearch - [ 1 | 0 ]
            $config['liveSearch'] = (bool)(int)$modx->getOption('liveSearch', $config, 0);
        }

        return parent::__construct($modx, $config);
    }

    /**
     * Output the advSearch form
     *
     * @access public
     * @return string output as string
     */
    public function output() {

        $jsHeaderArray = array();
        $msg = '';

        // initialize searchString
        $this->searchString = $this->_initSearchString();

        // set up the search form
        // add the help link
        if ($this->config['help']) {
            $helpHandler = $this->config['help'];
            if ($helpHandler != 1) {
                // specific help handler provided
                $resource = $this->modx->getObject('modResource', array(
                    'id' => $helpHandler,
                    'published' => 1
                        ));
            } else {
                $resource = $this->modx->getObject('modResource', array(
                    'published' => 1,
                    'pagetitle' => 'AdvSearch help'
                        ));
            }
            if ($resource) {   // advSearchHelp handler exists
                $helpHandler = $resource->get('id');
                $placeholders = array(
                    'asId' => $this->config['asId'],
                    'helpId' => $this->modx->makeUrl($helpHandler)
                );
                $helpLink = $this->parseTpl('HelpLink', $placeholders);
            } else {
                $this->config['help'] = 0;
                $helpLink = '';
            }
        } else {
            $helpLink = '';
        }

        // add the <div></div> section to set the results window throught jscript
        if ($this->config['withAjax']) {
            $placeholders = array('asId' => $this->config['asId']);
            $resultsWindow = $this->parseTpl('ResultsWindow', $placeholders);
        } else {
            $resultsWindow = '';
        }

        // display search form
        $placeholders = array(
            'method' => $this->config['method'],
            'landing' => $this->config['landing'],
            'asId' => $this->config['asId'],
            'searchValue' => $this->searchString,
            'searchIndex' => $this->config['searchIndex'],
            'helpLink' => $helpLink,
            'liveSearch' => $this->config['liveSearch'],
            'resultsWindow' => $resultsWindow
        );

		if ($this->config['liveSearch']) {
            $placeholders['liveSearch'] = 1;
        } else {
            $placeholders['liveSearch'] = 0;
        }

        // set the form into a placeholder if requested
        $output = $this->processElementTags($this->parseTpl($this->config['tpl'], $placeholders));
        if (!empty($this->config['toPlaceholder'])) {
            $this->modx->setPlaceholder($this->config['toPlaceholder'], $output);
            $output = '';
        }

        // add the external css and js files
        // add advSearch css file
        if ($this->config['addCss'] == 1) {
            $this->modx->regClientCss($this->config['assetsUrl'] . 'css/advsearch.css');
        }

        // include or not the jQuery library (required for help, clear default text, ajax mode)
        if ($this->config['help'] || $this->config['clearDefault'] || $this->config['withAjax']) {
            if ($this->config['addJQuery'] == 1) {
                //regClientStartupHTMLBlock
                $this->modx->regClientStartupHTMLBlock('<script>window.jQuery || document.write(\'<script src="' . $this->config['jsJQuery'] . '"><\/script>\');</script>');
            } elseif ($this->config['addJQuery'] == 2) {
                $this->modx->regClientHTMLBlock('<script>window.jQuery || document.write(\'<script src="' . $this->config['jsJQuery'] . '"><\/script>\');</script>');
            }
        }

        if ($this->config['help']) {
            // add help handler id in js header
            $jsHeaderArray['asid'] = $this->config['asId'];
            $jsHeaderArray['hid'] = $helpHandler; // add the help handler id as js variable
        }

        if ($this->config['clearDefault']) {
            $jsHeaderArray['asid'] = $this->config['asId'];
            $jsHeaderArray['cdt'] = $this->modx->lexicon('advsearch.box_text');
        }

        // include or not the inputForm js script linked to the form
        if ($this->config['addJs'] == 1) {
            $this->modx->regClientStartupScript($this->config['jsSearchForm']);
        } elseif ($this->config['addJs'] == 2) {
            $this->modx->regClientScript($this->config['jsSearchForm']);
        }

        if ($this->config['withAjax']) {
            // include the advsearch js file in the header
            if ($this->config['addJs'] == 1) {
                $this->modx->regClientStartupScript($this->config['jsSearch']);
            } elseif ($this->config['addJs'] == 2) {
                $this->modx->regClientScript($this->config['jsSearch']);
            }

            // add ajaxResultsId, liveSearch mode and some other parameters in js header
            $jsHeaderArray['asid'] = $this->config['asId'];
            if ($this->config['liveSearch']) {
                $jsHeaderArray['ls'] = $this->config['liveSearch'];
            }
            if ($this->config['searchIndex'] != 'search') {
                $jsHeaderArray['sx'] = $this->config['searchIndex'];
            }
            if ($this->config['offsetIndex'] != 'offset') {
                $jsHeaderArray['ox'] = $this->config['offsetIndex'];
            }
            if ($this->config['init'] != 'none') {
                $jsHeaderArray['ii'] = $this->config['init'];
            }
            if ($this->config['keyval']) {
                $keyvals = array_map("trim", explode(',', $this->config['keyval']));
                foreach ($keyvals as $keyval) {
                    list($key, $val) = array_map("trim", explode(':', $keyval));
                    $jsHeaderArray[$key] = $val;
                }
            }
            $jsHeaderArray['arh'] = $this->modx->makeUrl($this->config['ajaxResultsId'], '', array(), $this->config['urlScheme']);
        }

        // set up of js header for the current instance
        $jsHeaderArray = array_unique($jsHeaderArray);
        $jshCount = count($jsHeaderArray);
        if ($jshCount) {
            $jsonPair = array();
            foreach ($jsHeaderArray as $key => $value) {
                $jsonPair[] = '"' . $key . '":"' . $value . '"';
            }
            $json = '{' . implode(',', $jsonPair) . '}';
            $jsline = "advsea[advsea.length]='{$json}';";
            $jsHeader = <<<EOD
<!-- start AdvSearch header -->
<script type="text/javascript">
//<![CDATA[
{$jsline}
//]]>
</script>
<!-- end AdvSearch header -->
EOD;
            if ($this->config['addJs'] == 1) {
                $this->modx->regClientStartupScript($jsHeader);
            } else {
                $this->modx->regClientScript($jsHeader);
            }
        }

        // log elapsed time
        $this->ifDebug("Elapsed time:" . $this->getElapsedTime(), __METHOD__, __FILE__, __LINE__);

        return $output;
    }

    /**
     * _initSearchString - initialize searchString
     *
     * @access private
     * @return string the search string
     */
    private function _initSearchString() {
        $searchString = '';
        if (isset($_REQUEST[$this->config['searchIndex']]) && (!empty($_REQUEST[$this->config['searchIndex']])) && ($this->forThisInstance())) {
            $searchString = $this->sanitizeSearchString($_REQUEST[$this->config['searchIndex']]);
        }
        return $searchString;
    }

}