<?php
/**
 * AdvSearch - AdvSearchForm class
 *
 * @package 	AdvSearch
 * @author		Coroico
 * @copyright 	Copyright (c) 2011 by Coroico <coroico@wangba.fr>
 *
 * @tutorial	Main class to display the search form
 *
 */

include_once dirname(__FILE__)."/advsearchutil.class.php";

class AdvSearchForm extends AdvSearchUtil{

    function __construct(modX & $modx, array $properties = array()) {
        parent::__construct($modx, $properties);
    }

	/**
     * Output the newSearch form
	 *
     * @access public
     * @return string output as string
     */
    public function output() {

        $jsHeaderArray = array();
        $msg = '';

        // check parameters
        $valid = $this->checkParams($msg);
		if (!$valid) return '';

        // initialize searchString
        $defaultString = $this->modx->lexicon('advsearch.box_text');
        if (!empty($this->config['searchString'])) $defaultString = $this->config['searchString'];
        $this->searchString = $this->initSearchString($defaultString);

        // set up the search form
        // add the help link
        if ($this->config['help']) {
            $helpHandler = $this->config['help'];
            if ($helpHandler != 1) {
                // specific help handler provided
                $resource = $this->modx->getObject('modResource',array(
                    'id' => $helpHandler,
                    'published' => 1
                ));
            }
            else {
                $resource = $this->modx->getObject('modResource',array(
                    'published' => 1,
                    'pagetitle' => 'AdvSearch help'
                ));
            }
            if ($resource) {   // newSearchHelp handler exists
                $helpHandler = $resource->get('id');
                $placeholders = array(
                    'asId' => $this->config['asId'],
                    'helpId' => '[[~' . $helpHandler . ']]'
                );
				$this->getChunk('HelpLink');
                $helpLink = $this->processChunk('HelpLink',$placeholders);
            }
            else {
                $this->config['help'] = 0;
                $helpLink = '';
            }
        }
        else $helpLink = '';

        // add the <div></div> section to set the results window throught jscript
        if ($this->config['withAjax']) {
            $placeholders = array('asId' => $this->config['asId']);
			$this->getChunk('ResultsWindow');
            $resultsWindow = $this->processChunk('ResultsWindow',$placeholders);
        }
        else $resultsWindow = '';

        // display search form
        $placeholders = array(
            'method' => $this->config['method'],
            'landing' => $this->config['landing'],
            'asId' => $this->config['asId'],
            'searchValue' => $this->searchString,
            'searchIndex' => $this->config['searchIndex'],
            'helpLink' => $helpLink,
            'resultsWindow' => $resultsWindow
        );

        // set the form into a placeholder if requested
        $output = $this->processChunk($this->config['tpl'],$placeholders);
        if (!empty($this->config['toPlaceholder'])) {
            $this->modx->setPlaceholder($this->config['toPlaceholder'],$output);
            $output = '';
        }

        // add the external css and js files
        // add newSearch css file
        $this->modx->regClientCss($this->modx->getOption('assets_url')."components/advsearch/css/advsearch.css");

        // include or not the jQuery library (required for help, clear default text, ajax mode)
        if ($this->config['help'] || $this->config['clearDefault'] || $this->config['withAjax']) {
            if ($this->config['addJQuery']) $this->modx->regClientStartupScript($this->config['jsJQuery']);
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

        // add inputForm js script linked to the form
        if ($this->config['jsSearchForm']) $this->modx->regClientStartupScript($this->config['jsSearchForm']);

        if ($this->config['withAjax']) {
            // include the advsearch js file in the header
            $this->modx->regClientStartupScript($this->modx->getOption('assets_url').'components/advsearch/js/advsearch.min.js');

            // add ajaxResultsId and liveSearch mode in js header
            $jsHeaderArray['asid'] = $this->config['asId'];
            if ($this->config['liveSearch']) $jsHeaderArray['ls'] = $this->config['liveSearch'];
            if ($this->config['opacity'] < 1.) $jsHeaderArray['op'] = $this->config['opacity'];
            $jsHeaderArray['arid'] = $this->config['ajaxResultsId'];
        }

        // set up of js header for the current instance
        $jsHeaderArray = array_unique($jsHeaderArray);
        $jshCount = count($jsHeaderArray);
        if ($jshCount) {
            $jsonPair = array();
            foreach($jsHeaderArray as $key => $value) $jsonPair[] = '"' . $key . '":"' . $value . '"';
            $json = '{' . implode(',',$jsonPair) . '}';
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
            $this->modx->regClientStartupScript($jsHeader);

        }

        // log elapsed time
        $this->modx->log(modX::LOG_LEVEL_DEBUG, "Elapsed time:".$this->getElapsedTime());

        return $output;
    }

    /**
     * Check params
	 *
     * @access private
     * @param string $msgerr The error message
	 * @return boolean true if valid otherwise false + msgerr
     */
    private function checkParams(& $msgerr = '') {

        // check the common parameters with AdvSearch class
        $valid = $this->checkCommonParams();
		if (!$valid) return false;

        // &clearDefault - [ 1 | 0 ]
        $clearDefault = (int) $this->modx->getOption('clearDefault',$this->config,0);
        $this->config['clearDefault'] = ($clearDefault == 1 || $clearDefault == 0) ? $clearDefault : 0;

        // &help - [ 1 | 0 ] - to add a help link near the search form
        $help = (int) $this->modx->getOption('help',$this->config,1);
        $this->config['help'] = ($help >=0) ? $help : 1;

        // &jsSearchForm - [ url | $assetsUrl . 'js/newSearchForm.min.js' ]
        $this->config['jsSearchForm'] = $this->modx->getOption('jsSearchForm',$this->config,$this->config['assetsUrl'].'js/advsearchform.min.js');

        // &landing  [ int id of a document | 0 ]
        $landing = (int) $this->modx->getOption('landing',$this->config,0);
        $this->config['landing'] = ($landing > 0) ? $landing : $this->modx->resource->get('id');

        // &tpl [ chunk name | 'AdvSearchForm' ]
        $tpl = $this->modx->getOption('tpl',$this->config,'AdvSearchForm');
        $chunk = $this->getChunk($tpl);
        $this->config['tpl'] = (empty($chunk)) ? 'AdvSearchForm' : $tpl;

        //jQuery used by the help and by ajax mode
        if ($this->config['help'] || $this->config['withAjax']) {
            // &addJQuery - [1 | 0]
            $addJQuery = (int) $this->modx->getOption('addJQuery',$this->config,1);
            $this->config['addJQuery'] = ($addJQuery == 0 || $addJQuery == 1) ? $addJQuery : 1;

            // &jsJQuery - [ Location of the jQuery javascript library ]
            $this->config['jsJQuery'] = $this->modx->getOption('jsJQuery',$this->config,$this->config['assetsUrl'].'js/jquery-1.5.1.min.js');
        }

        // ajax mode parameters
        if ($this->config['withAjax']) {
            // $liveSearch - [ 1 | 0 ]
            $liveSearch = floatval($this->modx->getOption('liveSearch',$this->config,0));
            $this->config['liveSearch'] = ($liveSearch == 0 || $liveSearch == 1) ? $liveSearch : 0;

            // &opacity - [ 0. < float <= 1. ]  Should be a float value
            $opacity = floatval($this->modx->getOption('opacity',$this->config,1.));
            $this->config['opacity'] = ($opacity > 0. && $opacity <= 1.) ? $opacity : 1.0;
        }

        if ($this->dbg) $this->modx->log(modX::LOG_LEVEL_DEBUG, '[AdvSearch] Config parameters after checking: '.print_r($this->config, true),'','checkParams');

        return true;
    }

    /**
     * initSearchString - initialize searchString
	 *
     * @access private
     * @param string $defaultString The default search string value to use
	 * @return string the search string
	 */
    private function initSearchString($defaultString) {
        $searchString = $defaultString;
        if ((!empty($_REQUEST[$this->config['searchIndex']])) && ($this->forThisInstance())) {
            $searchString = $this->sanitizeSearchString($_REQUEST[$this->config['searchIndex']]);
        }
		return $searchString;
    }
}