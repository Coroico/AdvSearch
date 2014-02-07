<?php

/**
 * AdvSearch - AdvSearchUtil class
 *
 * @package 	AdvSearch
 * @author      Coroico
 *              goldsky - goldsky@virtudraft.com
 * @copyright 	Copyright (c) 2012 by Coroico <coroico@wangba.fr>
 *
 * @tutorial	Some useful methods shared by advSearch classes
 *
 */
// AdvSearch version
define('PKG_VERSION', '1.0.1');
define('PKG_RELEASE', 'dev');

class AdvSearchUtil {

    public $modx;
    public $config = array();
    protected $searchString = '';
    protected $searchQuery = null;
    protected $searchTerms = array();
    protected $chunks = array();
    protected $tstart;
    protected $dbg = false;
    /**
     * To hold error message
     * @var string
     */
    private $_error = '';

    /**
     * To hold placeholder array, flatten array with prefix
     * @var array
     */
    private $_placeholders = array();

    public function __construct(modX & $modx, array & $config = array()) {

        // get time of starting
        $mtime = explode(" ", microtime());
        $this->tstart = $mtime[1] + $mtime[0];

        $this->modx = & $modx;

        // &debug = [ 0 | 1 ]
        $config['debug'] = $this->modx->getOption('debug', $config, 0);
        if ($config['debug']) {
            // error_reporting(E_ALL & ~E_NOTICE); // sets error_reporting to everything except NOTICE remarks
            error_reporting(E_ALL);
            ini_set('display_errors', true);
            if ($config['withAjax']) {
                $this->_placeholders['debug'] = array();
                $this->modx->setLogTarget('FILE');
            } else {
                $this->modx->setLogTarget('HTML');
            }
            $this->modx->setLogLevel(modX::LOG_LEVEL_DEBUG);
        }
        $this->dbg = ($config['debug'] > 0);

        // charset [ charset | 'UTF-8' ]
        $config['charset'] = $this->modx->config['modx_charset'];
        if (strtolower(trim($config['charset'])) !== 'utf-8') {
            $msg = '[AdvSearch] AdvSearch runs only with charset UTF-8. The current charset is ' . $config['charset'];
            $this->modx->log(modX::LOG_LEVEL_ERROR, $msg);
            throw new Exception($msg);
        }

        // check that multibyte string option is on
        $usemb = $this->modx->config['use_multibyte'];
        if (!$usemb) {
            $msg = '[AdvSearch] AdvSearch runs only with the multibyte extension on. See Lexicon and language system settings.';
            $this->modx->log(modX::LOG_LEVEL_ERROR, $msg);
            throw new Exception($msg);
        }

        //===============================================================================================================================
        // path and url
        $corePath = $this->modx->getOption('advSearch.core_path', null, $this->modx->getOption('core_path') . 'components/advsearch/');
        $assetsUrl = $this->modx->getOption('advSearch.assets_url', null, 'assets/components/advsearch/');
        $this->config = array_merge(array(
            'corePath' => $corePath,
            'assetsUrl' => $assetsUrl,
            'chunksPath' => $corePath . 'elements/chunks/',
            'modelPath' => $corePath . 'model/',
                ), $config);

        $this->config = array_map("trim", array_merge($this->config, $config));

        // load default lexicon
        $this->modx->lexicon->load('advsearch:default');
    }

    protected function loadDefaultConfigs() {
        $this->config = array_map("trim", $this->config);

        $revoVersion = $this->modx->getVersionData();
        $systemInfo = array(
            "MODx version" => $revoVersion['full_version'],
            "Php version" => phpversion(),
            "MySql version" => $this->getMysqlVersion(),
            "AdvSearch version" => PKG_VERSION . ' ' . PKG_RELEASE,
        );
        if ($this->dbg) {
            $this->modx->log(modX::LOG_LEVEL_DEBUG, '[AdvSearch] System environment: ' . print_r($systemInfo, true), '', __METHOD__);
            $this->modx->log(modX::LOG_LEVEL_DEBUG, '[AdvSearch] Config parameters before checking: ' . print_r($this->config, true), '', __METHOD__);
        }

        // &asId - [Unique id for advSearch instance | 'as0' ]
        $this->config['asId'] = $this->modx->getOption('asId', $this->config, 'as0');

        // &method [ 'post' | 'get' ]
        $this->config['method'] = strtolower($this->modx->getOption('method', $this->config, 'get'));

        // &init  [ 'none' | 'all' ]
        $init = $this->modx->getOption('init', $this->config, 'none');
        $this->config['init'] = (($init == 'all') || ($init == 'none')) ? $init : 'none';

        // &libraryPath under assets [ path | 'libraries/' ]
        $path = $this->modx->getOption('libraryPath', $this->config, 'libraries/');
        $path = $this->modx->getOption('assets_path') . $path;
        $this->config['libraryPath'] = is_dir($path) ? $path : $this->modx->getOption('assets_path') . 'libraries/';
        // First make sure the Zend library is in the include path:
        ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $this->config['libraryPath']);

        /* deprecated @2.0.0 */
        // &offsetIndex [ string | 'offset' ] : The name of the REQUEST parameter to use for the pagination offset
        $this->config['offsetIndex'] = $this->modx->getOption('offsetIndex', $this->config, 'offset');

        // &pageIndex [ string | 'page' ] : The name of the REQUEST parameter to use for the pagination page
        $this->config['pageIndex'] = $this->modx->getOption('pageIndex', $this->config, 'page');

        // &searchIndex [ string | 'search' ]
        $this->config['searchIndex'] = $this->modx->getOption('searchIndex', $this->config, 'search');

        // searchString [ string | '' ]
        $this->config['searchString'] = $this->modx->getOption('searchString', $this->config, '');

        // &toPlaceholder [ string | '' ]
        $this->config['toPlaceholder'] = $this->modx->getOption('toPlaceholder', $this->config, '');

        // &addCss - [ 0 | 1 ]
        $this->config['addCss'] = (bool)(int) $this->modx->getOption('addCss', $this->config, 1);

        // &addJs - [ 0 | 1 | 2 ]
        $addJs = (int) $this->modx->getOption('addJs', $this->config, 1);
        $this->config['addJs'] = ($addJs == 0 || $addJs == 1 || $addJs == 2) ? $addJs : 1;

        // &withAjax [ 1 | 0 ]
        $withAjax = (int) $this->modx->getOption('withAjax', $this->config, 0);
        $this->config['withAjax'] = (($withAjax == 0 || $withAjax == 1)) ? $withAjax : 0;

        // &urlScheme
        $this->config['urlScheme'] = $this->modx->getOption('urlScheme', $this->config, -1);

        // &hideLinks
        $this->config['hideLinks'] = $this->modx->getOption('hideLinks', $this->config);

        return $this->config;

    }

    /**
     * Set class configuration exclusively for multiple snippet calls
     * @param   array   $config     snippet's parameters
     */
    public function setConfigs(array $config = array()) {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Get configs
     * @return array configurations
     */
    public function getConfigs() {
        return $this->config;
    }

    /**
     * Define individual config for the class
     * @param   string  $key    array's key
     * @param   string  $val    array's value
     */
    public function setConfig($key, $val) {
        $this->config[$key] = $val;
    }

    /**
     * Set string error for boolean returned methods
     * @return  void
     */
    public function setError($msg) {
        $this->_error = $msg;
    }

    /**
     * Get string error for boolean returned methods
     * @return  string  output
     */
    public function getError() {
        return $this->_error;
    }

    /**
     * Set internal placeholder
     * @param   string  $key    key
     * @param   string  $value  value
     * @param   string  $prefix add prefix if it's required
     */
    public function setPlaceholder($key, $value, $prefix = '') {
        $prefix = !empty($prefix) ? $prefix : (isset($this->config['phsPrefix']) ? $this->config['phsPrefix'] : '');
        $this->_placeholders[$prefix . $key] = $this->trimString($value);
    }

    /**
     * Set internal placeholders
     * @param   array   $placeholders   placeholders in an associative array
     * @param   string  $prefix         add prefix if it's required
     * @return  mixed   boolean|array of placeholders
     */
    public function setPlaceholders($placeholders, $prefix = '') {
        if (empty($placeholders)) {
            return FALSE;
        }
        $prefix = !empty($prefix) ? $prefix : (isset($this->config['phsPrefix']) ? $this->config['phsPrefix'] : '');
        $placeholders = $this->trimArray($placeholders);
        $placeholders = $this->implodePhs($placeholders, rtrim($prefix, '.'));
        // enclosed private scope
        $this->_placeholders = array_merge($this->_placeholders, $placeholders);
        // return only for this scope
        return $placeholders;
    }

    /**
     * Get internal placeholders in an associative array
     * @return array
     */
    public function getPlaceholders() {
        return $this->_placeholders;
    }

    /**
     * Get an internal placeholder
     * @param   string  $key    key
     * @return  string  value
     */
    public function getPlaceholder($key) {
        return $this->_placeholders[$key];
    }

    /**
     * Merge multi dimensional associative arrays with separator
     * @param   array   $array      raw associative array
     * @param   string  $keyName    parent key of this array
     * @param   string  $separator  separator between the merged keys
     * @param   array   $holder     to hold temporary array results
     * @return  array   one level array
     */
    public function implodePhs(array $array, $keyName = null, $separator = '.', array $holder = array()) {
        $phs = !empty($holder) ? $holder : array();
        foreach ($array as $k => $v) {
            $key = !empty($keyName) ? $keyName . $separator . $k : $k;
            if (is_array($v)) {
                $phs = $this->implodePhs($v, $key, $separator, $phs);
            } else {
                $phs[$key] = $v;
            }
        }
        return $phs;
    }

    /**
     * Trim string value
     * @param   string  $string     source text
     * @param   string  $charlist   defined characters to be trimmed
     * @link http://php.net/manual/en/function.trim.php
     * @return  string  trimmed text
     */
    public function trimString($string, $charlist = null) {
        if (empty($string) && !is_numeric($string)) {
            return '';
        }
        $string = htmlentities($string);
        // blame TinyMCE!
        $string = preg_replace('/(&Acirc;|&nbsp;)+/i', '', $string);
        $string = trim($string, $charlist);
        $string = trim(preg_replace('/\s+^(\r|\n|\r\n)/', ' ', $string));
        $string = html_entity_decode($string);
        return $string;
    }

    /**
     * Trim array values
     * @param   array   $array          array contents
     * @param   string  $charlist       [default: null] defined characters to be trimmed
     * @link http://php.net/manual/en/function.trim.php
     * @return  array   trimmed array
     */
    public function trimArray($input, $charlist = null) {
        if (is_array($input)) {
            $output = array_map(array($this, 'trimArray'), $input);
        } else {
            $output = $this->trimString($input, $charlist);
        }

        return $output;
    }

    /**
     * Parsing template
     * @param   string  $tpl    @BINDINGs options
     * @param   array   $phs    placeholders
     * @return  string  parsed output
     * @link    http://forums.modx.com/thread/74071/help-with-getchunk-and-modx-speed-please?page=2#dis-post-413789
     */
    public function parseTpl($tpl, array $phs = array()) {
        $output = '';
        if (preg_match('/^(@CODE|@INLINE)/i', $tpl)) {
            $tplString = preg_replace('/^(@CODE|@INLINE)/i', '', $tpl);
            // tricks @CODE: / @INLINE:
            $tplString = ltrim($tplString, ':');
            $tplString = trim($tplString);
            $output = $this->parseTplCode($tplString, $phs);
        } elseif (preg_match('/^@FILE/i', $tpl)) {
            $tplFile = preg_replace('/^@FILE/i', '', $tpl);
            // tricks @FILE:
            $tplFile = ltrim($tplFile, ':');
            $tplFile = trim($tplFile);
            try {
                $output = $this->parseTplFile($tplFile, $phs);
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }
        // ignore @CHUNK / @CHUNK: / empty @BINDING
        else {
            $tplChunk = preg_replace('/^@CHUNK/i', '', $tpl);
            // tricks @CHUNK:
            $tplChunk = ltrim($tpl, ':');
            $tplChunk = trim($tpl);

            $chunk = $this->modx->getObject('modChunk', array('name' => $tplChunk), true);
            if (empty($chunk)) {
                // try to use @splittingred's fallback
                $f = $this->config['chunksPath'] . strtolower($tplChunk) . '.chunk.tpl';
                try {
                    $output = $this->parseTplFile($f, $phs);
                } catch (Exception $e) {
                    $output = $e->getMessage();
                    $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Chunk: ' . $tplChunk . ' is not found, neither the file ' . $output);
                    return;
                }
            } else {
//                $output = $this->modx->getChunk($tplChunk, $phs);
                /**
                 * @link    http://forums.modx.com/thread/74071/help-with-getchunk-and-modx-speed-please?page=4#dis-post-464137
                 */
                $chunk = $this->modx->getParser()->getElement('modChunk', $tplChunk);
                $chunk->setCacheable(false);
                $chunk->_processed = false;
                $output = $chunk->process($phs);
            }
        }

        return $output;
    }

    /**
     * Parsing inline template code
     * @param   string  $code   HTML with tags
     * @param   array   $phs    placeholders
     * @return  string  parsed output
     */
    public function parseTplCode($code, array $phs = array()) {
        $chunk = $this->modx->newObject('modChunk');
        $chunk->setContent($code);
        $chunk->setCacheable(false);
        $chunk->_processed = false;
        return $chunk->process($phs);
    }

    /**
     * Parsing file based template
     * @param   string  $file   file path
     * @param   array   $phs    placeholders
     * @return  string  parsed output
     * @throws  Exception if file is not found
     */
    public function parseTplFile($file, array $phs = array()) {
        if (!file_exists($file)) {
            throw new Exception('File: ' . $file . ' is not found.');
        }
        $o = file_get_contents($file);
        $chunk = $this->modx->newObject('modChunk');

        // just to create a name for the modChunk object.
        $name = strtolower(basename($file));
        $name = rtrim($name, '.tpl');
        $name = rtrim($name, '.chunk');
        $chunk->set('name', $name);

        $chunk->setCacheable(false);
        $chunk->setContent($o);
        $chunk->_processed = false;
        $output = $chunk->process($phs);

        return $output;
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

    /**
     * Get mysql version
     *
     * @access private
     * @return string $mysqlVersion mysql server version as "5.5.8-log"
     */
    public function getMysqlVersion() {
        $c = new xPDOCriteria($this->modx, "SELECT VERSION() AS mysql_version;");
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
    public function getElapsedTime($start = 0) {
        $tend = $this->modx->getMicroTime();
        if ($start) {
            $eTime = ($tend - $start);
        } else {
            $eTime = ($tend - $this->tstart);
        }
        $etime = sprintf("%.4fs", $eTime);
        return $etime;
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
            $searchStringArray = explode(' ', $searchString);
            $searchStringArray = array_map("strip_tags", $this->modx->sanitize($searchStringArray, $this->modx->sanitizePatterns));
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
        $searched = array("SELECT", "GROUP_CONCAT", "LEFT JOIN", "INNER JOIN", "EXISTS", "LIMIT", "FROM",
            "WHERE", "GROUP BY", "HAVING", "ORDER BY", "OR", "AND", "IFNULL", "ON", "MATCH", "AGAINST",
            "COUNT");
        $replace = array(" \r\nSELECT", " \r\nGROUP_CONCAT", " \r\nLEFT JOIN", " \r\nINNER JOIN", " \r\nEXISTS", " \r\nLIMIT", " \r\nFROM",
            " \r\nWHERE", " \r\nGROUP BY", " \r\nHAVING", " ORDER BY", " \r\nOR", " \r\nAND", " \r\nIFNULL", " \r\nON", " \r\nMATCH", " \r\nAGAINST",
            " \r\nCOUNT");
        $output = '';
        if (isset($query)) {
            $query->prepare();
            $output = str_replace($searched, $replace, " " . $query->toSQL());
        }
        return $output;
    }

    /**
     * Log a message with details about where and when an event occurs.
     * @param string $msg The message to log.
     * @param string $def The name of a defining structure (such as a class) to
     * help identify the message source.
     * @param string $file A filename in which the log event occured.
     * @param string $line A line number to help locate the source of the event
     * within the indicated file.
     */
    public function ifDebug($msg, $def= '', $file= '', $line= '') {
        if ($this->config['debug']) {
            if ($this->config['withAjax']) {
                $target = array(
                    'target' => 'FILE',
                );
            } else {
                $target = 'HTML';
            }
            $this->modx->log(modX::LOG_LEVEL_DEBUG, '[AdvSearch] ' . $msg, $target, $def, $file, $line);
        }
    }

    /**
     * Replacing MODX's getCount(), because it has bug on counting SQL with function.<br>
     * Retrieves a count of xPDOObjects by the specified xPDOCriteria.
     *
     * @param string $className Class of xPDOObject to count instances of.
     * @param mixed $criteria Any valid xPDOCriteria object or expression.
     * @return integer The number of instances found by the criteria.
     * @see xPDO::getCount()
     * @link http://forums.modx.com/thread/88619/getcount-fails-if-the-query-has-aggregate-leaving-having-039-s-field-undefined The discussion for this
     */
    public function getQueryCount($className, $criteria= null) {
        $count= 0;
        if ($query= $this->modx->newQuery($className, $criteria)) {
            $expr= '*';
            if ($pk= $this->modx->getPK($className)) {
                if (!is_array($pk)) {
                    $pk= array ($pk);
                }
                $expr= $this->modx->getSelectColumns($className, 'alias', '', $pk);
            }
            $query->prepare();
            $sql = $query->toSQL();
            $stmt= $this->modx->query("SELECT COUNT($expr) FROM ($sql) alias");
            if ($stmt) {
                $tstart = microtime(true);
                if ($stmt->execute()) {
                    $this->modx->queryTime += microtime(true) - $tstart;
                    $this->modx->executedQueries++;
                    if ($results= $stmt->fetchAll(PDO::FETCH_COLUMN)) {
                        $count= reset($results);
                        $count= intval($count);
                    }
                } else {
                    $this->modx->queryTime += microtime(true) - $tstart;
                    $this->modx->executedQueries++;
                    $this->modx->log(modX::LOG_LEVEL_ERROR, "[AdvSearch] Error " . $stmt->errorCode() . " executing statement: \n" . print_r($stmt->errorInfo(), true), '', __METHOD__, __FILE__, __LINE__);
                }
            }
        }
        return $count;
    }

}