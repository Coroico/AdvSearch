<?php
/**
 * AdvSearch - AdvSearchUtil class
 *
 * @package 	AdvSearch
 * @author		Coroico
 * @copyright 	Copyright (c) 2012 by Coroico <coroico@wangba.fr>
 *
 * @tutorial	Some useful methods shared by advSearch classes
 *
 */
// AdvSearch version
define('PKG_VERSION','1.0.0');
define('PKG_RELEASE','pl');

abstract class AdvSearchUtil {

    public $modx;
    public $config = array();

	protected $searchString = '';
	protected $searchQuery = null;
	protected $searchTerms = array();

    protected $chunks = array();
    protected $tstart;
    protected $dbg = false;

    public function __construct(modX & $modx, array & $config = array()) {

        // get time of starting
        $mtime = explode(" ", microtime());
        $this->tstart = $mtime[1] + $mtime[0];

    	$this->modx =& $modx;
        $this->config =& $config;

        // path and url
        $corePath = $this->modx->getOption('advSearch.core_path',null,$this->modx->getOption('core_path').'components/advsearch/');
        $assetsUrl = $this->modx->getOption('advSearch.assets_url',null,'assets/components/advsearch/');
        $this->config = array_merge(array(
            'corePath' => $corePath,
            'assetsUrl' => $assetsUrl,
            'chunksPath' => $corePath.'elements/chunks/',
            'modelPath' => $corePath.'model/',
        ),$config);

        // &debug = [ 0 | 1 ]
        if ($this->modx->getOption('debug',$this->config,0)) {
            // error_reporting(E_ALL & ~E_NOTICE); // sets error_reporting to everything except NOTICE remarks
            error_reporting(E_ALL);
            ini_set('display_errors',true);
            $this->modx->setLogTarget('HTML');
            $this->modx->setLogLevel(modX::LOG_LEVEL_DEBUG);
        }
		$this->dbg = ($this->config['debug'] > 0);

        // load default lexicon
        $this->modx->lexicon->load('advsearch:default');
    }

    /**
     * Check common params between AdvSearch and AdvSearchForm classes
	 *
     * @access public
     * @param string $msgerr The error message
	 * @return boolean true if valid otherwise false + msgerr
     */
    public function checkCommonParams(& $msgerr = '') {

        $this->config = array_map("trim",$this->config);

        $revoVersion = $this->modx->getVersionData();
        $systemInfo = array(
			"MODx version" => $revoVersion['full_version'],
			"Php version" => phpversion(),
			"MySql version" => $this->getMysqlVersion(),
			"AdvSearch version" => PKG_VERSION . ' '. PKG_RELEASE,
		);
        if ($this->dbg) $this->modx->log(modX::LOG_LEVEL_DEBUG, '[AdvSearch] System environment: '.print_r($systemInfo, true),'','checkCommonParams');

        if ($this->dbg) $this->modx->log(modX::LOG_LEVEL_DEBUG, '[AdvSearch] Config parameters before checking: '.print_r($this->config, true),'','checkCommonParams');

		// charset [ charset | 'UTF-8' ]
		$charset = $this->modx->config['modx_charset'];
		if ($charset != 'UTF-8') {
			$msgerr = '[AdvSearch] AdvSearch runs only with charset UTF-8. The current charset is '.$charset;
			$this->modx->log(modX::LOG_LEVEL_ERROR,$msgerr);
			return false;
		}
        $this->config['charset'] = $charset;

		// check that multibyte string option is on
		$usemb = $this->modx->config['use_multibyte'];
		if (!$usemb) {
			$msgerr = '[AdvSearch] AdvSearch runs only with the multibyte extension on. See Lexicon and language system settings.';
			$this->modx->log(modX::LOG_LEVEL_ERROR,$msgerr);
			return false;
		}

        // &asId - [Unique id for advSearch instance | 'as0' ]
        $this->config['asId'] = $this->modx->getOption('asId',$this->config,'as0');

        // &method [ 'POST' | 'GET' ]
        $this->config['method'] = strtoupper($this->modx->getOption('method',$this->config,'GET'));

        // &init  [ 'none' | 'all' ]
        $init = $this->modx->getOption('init',$this->config,'none');
        $this->config['init'] = (($init == 'all') || ($init == 'none')) ? $init : 'none';

        // &libraryPath under assets [ path | 'libraries/' ]
        $path = $this->modx->getOption('libraryPath',$this->config,'libraries/');
        $path = $this->modx->getOption('assets_path') . $path;
        $this->config['libraryPath'] = is_dir($path) ? $path : $this->modx->getOption('assets_path') . 'libraries/' ;
		// First make sure the Zend library is in the include path:
		ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $this->config['libraryPath']);

        // &offsetIndex [ string | 'offset' ]
        $this->config['offsetIndex'] = $this->modx->getOption('offsetIndex',$this->config,'offset');

        // &searchIndex [ string | 'search' ]
        $this->config['searchIndex'] = $this->modx->getOption('searchIndex',$this->config,'search');

		// searchString [ string | '' ]
        $this->config['searchString'] = $this->modx->getOption('searchString',$this->config,'');

        // &toPlaceholder [ string | '' ]
        $this->config['toPlaceholder'] = $this->modx->getOption('toPlaceholder',$this->config,'');

        // &withAjax [ 1 | 0 ]
        $withAjax = (int) $this->modx->getOption('withAjax',$this->config,0);
        $this->config['withAjax'] = (($withAjax == 0 || $withAjax == 1)) ? $withAjax : 0;

		// &urlScheme
		$this->config['urlScheme'] = $this->modx->getOption('urlScheme',$this->config,-1);

		if ($this->dbg) $this->modx->log(modX::LOG_LEVEL_DEBUG, '[AdvSearch] Config parameters after checking: '.print_r($this->config, true),'','checkCommonParams');

        return true;
    }

    /**
     * Get mysql version
	 *
     * @access private
	 * @return string $mysqlVersion mysql server version as "5.5.8-log"
	 */
    public function getMysqlVersion() {
        $c = new xPDOCriteria($this->modx,"SELECT VERSION() AS mysql_version;");
		$c->stmt->execute();
		$result = $c->stmt->fetchAll(PDO::FETCH_ASSOC);
		$c->stmt->closeCursor();
		$mysqlVersion = $result[0]['mysql_version'];
        return $mysqlVersion;
    }

    public function forThisInstance() {
        $asId = (isset($_REQUEST['asId'])) ? $this->sanitize($_REQUEST['asId']) : 'as0';
        $forThisInstance = ($asId == $this->config['asId']);
        return $forThisInstance;
    }
    /**
     * Returns the elapsed time between the current time and tstart
     *
     * @access public
     * @param string $start starting time
     * @return string Elapsed time since start
     */
    public function getElapsedTime($start=0) {
        $tend= $this->modx->getMicroTime();
        if ($start) $eTime= ($tend - $start);
        else $eTime= ($tend - $this->tstart);
        $etime = sprintf("%.4fs",$eTime);
        return $etime;
    }

	/**
     * Gets unprocessed chunk, set cacheable
     *
     * @access public
     * @param string $name The name of the Chunk
     * @return string The unprocessed chunk
     */
    public function getChunk($name) {
        $chunk = null;
        if (!isset($this->chunks[$name])) {
			$chunk = $this->modx->getObject('modChunk',array('name' => $name),true); // from chunk
			if (empty($chunk)) {
				$f = $this->config['chunksPath'].strtolower($name).'.chunk.tpl';
				if (file_exists($f)) {
					$fhdl = fopen($f,'r');
                    $o = fread($fhdl, filesize($f));
					$chunk = $this->modx->newObject('modChunk');
					$chunk->set('name',$name);
					$chunk->setContent($o);
				}
				if (empty($chunk)) return false;
			}
			$chunk->setCacheable(false);
			// record chunk object
            $this->chunks[$name] = $chunk;
        }
        return $chunk;
    }

    /**
     * Process chunk with properties.
     *
     * @access public
     * @param string $name The name of the Chunk
     * @param array $properties The properties for the Chunk
     * @return string The processed content of the Chunk
     */
    public function processChunk($name, $properties = array()) {
        $chunk = $this->chunks[$name];
		if (!empty($chunk)) {
			$chunk->_processed = false;
			return $chunk->process($properties);
		}
		else return '';
    }

    /**
     * Sanitize a searchString
     *
	 * @access public
     * @param string $searchString The search string
     * @return string The sanitized search string
     */
	 public function sanitizeSearchString($searchString) {
		if (!empty($searchString)) {
			$searchStringArray = explode(' ',$searchString);
			$searchStringArray = array_map("strip_tags",$this->modx->sanitize($searchStringArray, $this->modx->sanitizePatterns));
			$searchString = implode(' ', $searchStringArray);
		}
		return $searchString;
    }

    /**
     * Sanitize a text
	 *
	 * @access public
     * @param string $text The text to sanitize
     * @return string The sanitized text
     */
    public function sanitize($text) {
        $text = strip_tags($text);
        $text = preg_replace('/(\[\[\+.*?\]\])/i', '', $text);
        return $this->modx->stripTags($text);
    }

	/*
     *  Returns select statement for printing
	 *
	 * @access public
     * @param xPDOQuery $query The query to print
     * @return string The select statement
     */
    public function niceQuery(xPDOQuery $query = null) {
        $searched = array("SELECT", "GROUP_CONCAT", "LEFT JOIN", "INNER JOIN", "EXISTS", "LIMIT", "FROM", "WHERE", "GROUP BY", "HAVING", "ORDER BY", "OR", "AND", "IFNULL");
        $replace = array(" \r\nSELECT", " \r\nGROUP_CONCAT", " \r\nLEFT JOIN", " \r\nINNER JOIN", " \r\nEXISTS"," \r\nLIMIT", " \r\nFROM", " \r\nWHERE", " \r\nGROUP BY", " \r\nHAVING", " ORDER BY", " \r\nOR", " \r\nAND", " \r\nIFNULL");
		$output = '';
		if (isset($query)) {
			$query->prepare();
			$output = str_replace($searched, $replace, " " . $query->toSQL());
		}
        return $output;
    }
}