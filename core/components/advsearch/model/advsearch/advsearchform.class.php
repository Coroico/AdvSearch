<?php

/**
 * AdvSearch - AdvSearchForm class
 *
 * @package 	AdvSearch
 * @author		Coroico
 *              goldsky - goldsky@virtudraft.com
 * @copyright 	Copyright (c) 2012 by Coroico <coroico@wangba.fr>
 *
 * @tutorial	Main class to display the search form
 *
 */
include_once dirname(__FILE__) . "/advsearchutil.class.php";

class AdvSearchForm extends AdvSearchUtil {

    public function __construct(modX & $modx, array $config = array()) {
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
        }

        parent::__construct($modx, $config);
        parent::loadDefaultConfigs();
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

        // &help - [ 1 | 0 ] - to add a help link near the search form
        $this->config['help'] = (bool) (int) $this->modx->getOption('help', $this->config, 1);

        //jQuery used by the help and by ajax mode
        if ($this->config['help'] || $this->config['withAjax']) {
            // &addJQuery - [ 0 | 1 | 2 ]
            $addJQuery = (int) $this->modx->getOption('addJQuery', $this->config, 1);
            $this->config['addJQuery'] = ($addJQuery == 0 || $addJQuery == 1 || $addJQuery == 2) ? $addJQuery : 1;

            // &jsJQuery - [ Location of the jQuery javascript library ]
            $this->config['jsJQuery'] = $this->modx->getOption('jsJQuery', $this->config, $this->config['assetsUrl'] . 'js/jquery-1.10.2.min.js');
        }

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

        // &resultsWindowTpl [ chunk name | 'ResultsWindow' ]
        $this->config['resultsWindowTpl'] = $this->modx->getOption('resultsWindowTpl', $this->config, 'ResultsWindow');

        // add the <div></div> section to set the results window throught jscript
        if ($this->config['withAjax']) {
            $placeholders = array('asId' => $this->config['asId']);
            $resultsWindow = $this->processElementTags($this->parseTpl($this->config['resultsWindowTpl'], $placeholders));
        } else {
            $resultsWindow = '';
        }

        // &method - [ post | get ]
        $this->config['method'] = strtolower($this->modx->getOption('method', $this->config, 'get'));

        // &landing  [ int id of a document | 0 ]
        $landing = (int) $this->modx->getOption('landing', $this->config, 0);
        $this->config['landing'] = ($landing > 0) ? $landing : $this->modx->resource->get('id');

        // &liveSearch - [ 1 | 0 ]
        $this->config['liveSearch'] = (bool) (int) $this->modx->getOption('liveSearch', $this->config, 0);

        // &searchIndex - [ search | any string ]
        $this->config['searchIndex'] = trim($this->modx->getOption('searchIndex', $this->config, 'search'));

        // &uncacheScripts - [ 1 | 0 ]
        $uncacheScripts = (bool) (int) $this->modx->getOption('uncacheScripts', $this->config, 1);
        $this->config['uncacheScripts'] = $uncacheScripts ? '?_=' . time() : '';

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

        // &tpl [ chunk name | 'AdvSearchForm' ]
        $this->config['tpl'] = $this->modx->getOption('tpl', $this->config, 'AdvSearchForm');

        // set the form into a placeholder if requested
        $output = $this->processElementTags($this->parseTpl($this->config['tpl'], $placeholders));
        if (!empty($this->config['toPlaceholder'])) {
            $this->modx->setPlaceholder($this->config['toPlaceholder'], $output);
            $output = '';
        }

        // add the external css and js files
        // add advSearch css file
        if ($this->config['addCss'] == 1) {
            $this->modx->regClientCss($this->config['assetsUrl'] . 'css/advsearch.css' . $this->config['uncacheScripts']);
        }

        // &clearDefault - [ 1 | 0 ]
        $this->config['clearDefault'] = (bool) (int) $this->modx->getOption('clearDefault', $this->config, 0);

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

        // &jsSearchForm - [ url | $assetsUrl . 'js/advsearchform.min.js' ]
        $this->config['jsSearchForm'] = $this->modx->getOption('jsSearchForm', $this->config, $this->config['assetsUrl'] . 'js/advsearchform.min.js');

        // include or not the inputForm js script linked to the form
        if ($this->config['addJs'] == 1) {
            $this->modx->regClientStartupScript($this->config['jsSearchForm'] . $this->config['uncacheScripts']);
        } elseif ($this->config['addJs'] == 2) {
            $this->modx->regClientScript($this->config['jsSearchForm'] . $this->config['uncacheScripts']);
        }

        if ($this->config['withAjax']) {
            // &jsSearch - [ url | $assetsUrl . 'js/advsearch.min.js' ]
            $this->config['jsSearch'] = $this->modx->getOption('jsSearch', $this->config, $this->config['assetsUrl'] . 'js/advsearch.min.js');

            // &jsPopulateForm - [ js populate form library ]
            $this->config['jsPopulateForm'] = $this->modx->getOption('jsPopulateForm', $this->config, $this->config['assetsUrl'] . 'vendors/populate/jquery.populate.pack.js');

            // &useHistory - [ 0 | 1 ]
            $this->config['useHistory'] = $this->modx->getOption('useHistory', $this->config, 0);

            if ($this->config['useHistory']) {
                // &jsURI - [ URI.js library ]
                $this->config['jsURI'] = $this->modx->getOption('jsURI', $this->config, $this->config['assetsUrl'] . 'vendors/urijs/src/URI.min.js');
                // &jsHistory - [ History.js library ]
                $this->config['jsHistory'] = $this->modx->getOption('jsHistory', $this->config, $this->config['assetsUrl'] . 'vendors/historyjs/scripts/bundled-uncompressed/html5/jquery.history.js');
            }

            // include the advsearch js file in the header
            if ($this->config['addJs'] == 1) {
                $addJs = 'regClientStartupScript';
            } elseif ($this->config['addJs'] == 2) { // if ($this->config['addJs'] == 2)
                $addJs = 'regClientScript';
            }

            if ($this->config['addJs'] != 0) {
                $this->modx->$addJs($this->config['jsSearch'] . $this->config['uncacheScripts']);
                if ($this->config['useHistory']) {
                    $this->modx->$addJs($this->config['jsURI']);
                    $this->modx->$addJs($this->config['jsHistory']);
                    $this->modx->$addJs($this->config['jsPopulateForm']);
                }
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
            if ($this->config['pageIndex'] != 'page') {
                $jsHeaderArray['pax'] = $this->config['pageIndex'];
            }
            if ($this->config['init'] != 'none') {
                $jsHeaderArray['ii'] = $this->config['init'];
            }

            $jsHeaderArray['hst'] = $this->config['useHistory'];

            // ajax connector
            $jsHeaderArray['arh'] = $this->modx->makeUrl($this->config['ajaxResultsId'], '', array(), $this->config['urlScheme']);

            // &ajaxLoaderImageTpl - [ the chunk of spinning loader image. @FILE/@CODE/@INLINE[/@CHUNK] ]
            $ajaxLoaderImageTpl = $this->modx->getOption('ajaxLoaderImageTpl', $this->config, '@CODE <img src="' . $this->config['assetsUrl'] . 'js/images/indicator.white.gif' . '" alt="loading" />');

            // &ajaxCloseImageTpl - [ the chunk of close image. @FILE/@CODE/@INLINE[/@CHUNK] ]
            $ajaxCloseImageTpl = $this->modx->getOption('ajaxCloseImageTpl', $this->config, '@CODE <img src="' . $this->config['assetsUrl'] . 'js/images/close2.png' . '" alt="close search" />');

            // loader image
            $ajaxLoaderImage = $this->processElementTags($this->parseTpl($ajaxLoaderImageTpl));
            if (!empty($ajaxLoaderImage)) {
                $jsHeaderArray['ali'] = addslashes(trim($ajaxLoaderImage));
                // DOM ID that holds the loader image
                $jsHeaderArray['alii'] = $this->modx->getOption('ajaxLoaderImageDOMId', $this->config);
            }
            // close image
            $ajaxCloseImage = $this->processElementTags($this->parseTpl($ajaxCloseImageTpl));
            if (!empty($ajaxCloseImage)) {
                $jsHeaderArray['aci'] = addslashes(trim($ajaxCloseImage));
                // DOM ID that holds the loader image
                $jsHeaderArray['acii'] = $this->modx->getOption('ajaxCloseImageDOMId', $this->config);
            }

            /**
             * Google Map
             */
            $jsHeaderArray['gmp'] = $this->modx->getOption('googleMapDomId', $this->config);
            $jsHeaderArray['gmpLt'] = $this->modx->getOption('googleMapLatTv', $this->config);
            $jsHeaderArray['gmpLn'] = $this->modx->getOption('googleMapLonTv', $this->config);
            $jsHeaderArray['gmpTtl'] = $this->modx->getOption('googleMapMarkerTitleField', $this->config);
            $googleMapMarkerWindowId  = intval($this->modx->getOption('googleMapMarkerWindowId', $this->config));
            if (!empty($googleMapMarkerWindowId)) {
                $jsHeaderArray['gmpWin'] = $this->modx->makeUrl($googleMapMarkerWindowId);
            }
            $jsHeaderArray['gmpZoom'] = (int) $this->modx->getOption('googleMapZoom', $this->config, 5);
            $jsHeaderArray['gmpCenterLat'] = $this->modx->getOption('googleMapCenterLat', $this->config);
            $jsHeaderArray['gmpCenterLong'] = $this->modx->getOption('googleMapCenterLong', $this->config);

            // &keyval
            $this->config['keyval'] = $this->modx->getOption('keyval', $this->config, '');

            if ($this->config['keyval']) {
                $keyvals = array_map("trim", explode(',', $this->config['keyval']));
                foreach ($keyvals as $keyval) {
                    list($key, $val) = array_map("trim", explode(':', $keyval));
                    $jsHeaderArray[$key] = $val;
                }
            }
        }

        // set up of js header for the current instance
        $jsHeaderArray = array_unique($jsHeaderArray);
        $jshCount = count($jsHeaderArray);
        if ($jshCount) {
            $json = json_encode($jsHeaderArray);
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
            } elseif ($this->config['addJs'] == 2)  {
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
