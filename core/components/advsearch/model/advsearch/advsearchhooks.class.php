<?php

/**
 * AdvSearch - AdvSearchHooks
 *
 * @package 	AdvSearch
 * @author		Coroico
 * @copyright 	Copyright (c) 2012 by Coroico <coroico@wangba.fr>
 *
 * @tutorial	Class to handle hooks for AdvSearch classes
 *
 */
class AdvSearchHooks {

    /**
     * @var array $errors A collection of all the processed errors so far.
     * @access public
     */
    public $errors = array();

    /**
     * @var array $hooks A collection of all the processed hooks so far.
     * @access public
     */
    public $hooks = array();

    /**
     * @var modX $modx A reference to the modX instance.
     * @access public
     */
    public $modx = null;

    /**
     * @var AdvSearchResults $search A reference to the AdvSearchResults instance.
     * @access public
     */
    public $results = null;
    public $search = null;
    public $queryHook = null;
    public $postHook = null;

    /**
     * The constructor for the advSearchHooks class
     *
     * @param AdvSearchResults &$search A reference to the AdvSearchResults class instance.
     * @param array $config Optional. An array of configuration parameters.
     * @return asHooks
     */
    public function __construct(AdvSearch &$search, array $config = array()) {
        $this->search = & $search;
        $this->modx = & $search->modx;
        $this->config = array_merge(array(
                ), $config);
    }

    /**
     * Loads an array of hooks. If one fails, will not proceed.
     *
     * @access public
     * @param array $hooks The csv list of hooks to run.
     * @param array $commonProperties An array of extra properties common to all hooks
     * @param array $hookProperties An array of extra properties for each hook
     * @return
     */
    public function loadMultiple($hooks, array $commonProperties = array(), array $hookProperties = array()) {
        if (empty($hooks))
            return array();
        if (is_string($hooks))
            $hooks = explode(',', $hooks);

        $this->hooks = array();
        $ih = 0;
        foreach ($hooks as $hook) {
            $hook = trim($hook);
            $properties = $commonProperties;
            foreach ($hookProperties as $propertyName => $propertyVal) {
                $propertyArrayVal = array_map('trim', explode(',', $propertyVal));
                $properties = array_merge($properties, array($propertyName => $propertyArrayVal[$ih]));
            }
            $success = $this->load($hook, $properties);
            if (!$success)
                return $this->hooks; /* dont proceed if hook fails */
            $ih++;
        }
        return $this->hooks;
    }

    /**
     * Load a hook. Stores any errors for the hook to $this->errors.
     *
     * @access public
     * @param string $hook The name of the hook. May be a Snippet name.
     * @param array $results The keys and values of the results.
     * @param array $customProperties Any other custom properties to load into a custom hook.
     * @return boolean True if hook was successful.
     */
    public function load($hook, array $customProperties = array()) {
        $success = false;
        $this->hooks[] = $hook;

        $reserved = array('load', 'process', '__construct', 'getErrorMessage');
        if (method_exists($this, $hook) && !in_array($hook, $reserved)) {
            /* built-in hooks */
            $success = $this->$hook();
        } else if ($snippet = $this->modx->getObject('modSnippet', array('name' => $hook))) {
            /* custom snippet hook */
            $properties = array_merge($this->search->config, $customProperties);
            $properties['advsearch'] = & $this->search;
            $properties['hook'] = & $this;
            $properties['errors'] = & $this->errors;
            $success = $snippet->process($properties);
        } else {
            /* no hook found */
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] Could not find hook "' . $hook . '".');
            $success = true;
        }

        if (is_array($success) && !empty($success)) {
            $this->errors = array_merge($this->errors, $success);
            $success = false;
        } else if ($success != true) {
            $this->errors[$hook] .= ' ' . $success;
            $success = false;
        }
        return $success;
    }

    /**
     * Gets the error messages compiled into a single string.
     *
     * @access public
     * @param string $delim The delimiter between each message.
     * @return string The concatenated error message
     */
    public function getErrorMessage($delim = "\n") {
        return implode($delim, $this->errors);
    }

    /**
     * Adds an error to the stack.
     *
     * @access private
     * @param string $key The field to add the error to.
     * @param string $value The error message.
     * @return string The added error message with the error wrapper.
     */
    public function addError($key, $value) {
        $this->errors[$key] .= $value;
        return $this->errors[$key];
    }

    public function getVarRequest() {
        if (isset($_REQUEST['asform'])) {
            $asform = strip_tags($_REQUEST['asform']);
            $formParams = json_decode($asform);
            $excluded = array('id', 'asid', 'sub');
            $excluded[] = $this->search->config['searchIndex'];
            $excluded[] = $this->search->config['offsetIndex'];
            foreach ($formParams as $key => $val) {
                $key = trim($key, "[]");
                if (!(in_array($key, $excluded))) {
                    if (is_array($val)) {
                        if (!isset($_REQUEST[$key])) {
                            $_REQUEST[$key] = array();
                            foreach ($val as $v)
                                $_REQUEST[$key][] = $v;
                        }
                    } else {
                        if (!isset($_REQUEST[$key]))
                            $_REQUEST[$key] = $val;
                    }
                }
            }
        }
    }

    public function processValue($class, $classField, $oper, $ptrn, $val) {
        $condition = '';
        $val = addslashes($val);
        if ($this->queryHook['version'] == '1.2') {
            switch ($oper) {
                case 'IN':
                case 'NOT IN':  // operator with a list of values wrapped by parenthesis
                    $condition = "({$classField} {$oper}({$val}))";
                    break;
                case 'FIND':
                    if (empty($ptrn))
                        $condition = "(FIND_IN_SET( {$val}, {$classField} ))"; // csv list by default
                    else
                        $condition = "(FIND_IN_SET( '{$val}', REPLACE( {$classField}, '{$ptrn}', ',' ) ))";
                    break;
                case 'MATCH':  // operator with exact matching between word1||word2||word3
                    $condition = "({$classField} REGEXP '(^|\\\|)+{$val}(\\\||$)+' )";
                    break;
                case 'REGEXP': // operator with exact pattern matching. eg: ptrn= '%s[0-9]*'
                    // MATCH is equivalent to ptrn =  '(^|\\\|)+%s(\\\||$)+'
                    $ptrn = sprintf($ptrn, $val);
                    $condition = "({$classField} REGEXP '{$ptrn}' )";
                    break;
                default:    // >,<,>=,<=,LIKE  (unary operator)
                    $val = (!is_numeric($val)) ? "'{$val}'" : $val;
                    $condition = "({$classField} {$oper} {$val})";
            }
        }
        else { // QueryHook version 1.1
            if ($oper == 'FIND')
                $oper = 'REGEXP';
            if ($oper == 'MATCH')
                $oper = 'REGEXP';
            switch ($oper) {
                case 'IN':
                case 'NOT IN':  // unary operator with a list of values wrapped by parenthesis
                    $condition = "({$classField} {$oper}({$val}))";
                    break;
                default:    // >,<,>=,<=,LIKE,REGEXP  (unary operator)
                    $condition = "({$classField} {$oper} {$val})";
            }
        }
        return $condition;
    }

    public function processTvCondition($cvTbl, $tvTbl, $tvName, $condition) {
        $tvCondition = " EXISTS( SELECT 1 FROM {$cvTbl} `tvcv` JOIN {$tvTbl} tv ON `tv`.`name` = '{$tvName}' ";
        $tvCondition .= "AND `tv`.`id` = `tvcv`.`tmplvarid` AND {$condition} WHERE `tvcv`.`contentid` = `modResource`.`id` ) ";
        return $tvCondition;
    }

    public function processConditions($andConditions, & $requests) {
        $conditions = array();
        foreach ($andConditions as $keyCondition => $valueCondition) {

            $keyElts = array_map("trim", explode(':', $keyCondition));
            if (count($keyElts) == 1)
                $keyElts[1] = '=';
            elseif (count($keyElts) == 2) {
                if ($keyElts[1] == 'REGEXP')
                    $keyElts[2] = '%s';
                else
                    $keyElts[2] = '';
            }

            $keyCondition = implode(':', $keyElts);
            $oper = strtoupper($keyElts[1]); // operator
            $ptrn = strtolower($keyElts[2]); // pattern

            $classFieldElts = array_map("trim", explode('.', $keyElts[0]));
            $class = (count($classFieldElts) == 2) ? $classFieldElts[0] : '';
            $class = trim($class, '`');
            $field = (count($classFieldElts) == 2) ? $classFieldElts[1] : $classFieldElts[0];
            $field = trim($field, '`');

            if (empty($class))
                $classField = $this->modx->escape($field);
            elseif ($class == 'tv') {
                $classField = "`tvcv`.{$this->modx->escape('value')}";
                $cvTbl = $this->modx->getTableName('modTemplateVarResource'); // site_tmplvar_contentvalues
                $tvTbl = $this->modx->getTableName('modTemplateVar'); // site_tmplvars
            }
            else
                $classField = "{$this->modx->escape($class)}.{$this->modx->escape($field)}";

            $valueElts = array_map("trim", explode(':', $valueCondition));
            $tag = $valueElts[0];
            $typeValue = (!empty($valueElts[1])) ? strtolower($valueElts[1]) : 'request';
            $filtered = (!empty($valueElts[2])) ? array_map("trim", explode(',', $valueElts[2])) : array();

            if ($typeValue == 'request') { // the value is provided par an http variable
                if (isset($_REQUEST[$tag])) {
                    if (is_array($_REQUEST[$tag])) {
                        // multiple list
                        $values = $_REQUEST[$tag];
                        $orConditions = array();
                        foreach ($values as $val) {
                            $val = strip_tags($val);
                            if (($val != '') && !in_array($val, $filtered)) {
                                $requests[$tag][] = $val;
                                $orConditions[] = $this->processValue($class, $classField, $oper, $ptrn, $val);
                            }
                        }
                        if (count($orConditions)) {
                            $orCondition = '(' . implode(' OR ', $orConditions) . ')';
                            if ($class != 'tv')
                                $conditions[] = $orCondition;
                            else
                                $conditions[] = $this->processTvCondition($cvTbl, $tvTbl, $field, $orCondition);
                        }
                    }
                    else {
                        // single value
                        $val = strip_tags($_REQUEST[$tag]);
                        if (($val != '') && !in_array($val, $filtered)) {
                            $requests[$tag] = $val;
                            $orCondition = $this->processValue($class, $classField, $oper, $ptrn, $val);
                            if ($class != 'tv')
                                $conditions[] = $orCondition;
                            else
                                $conditions[] = $this->processTvCondition($cvTbl, $tvTbl, $field, $orCondition);
                        }
                    }
                }
            }
            else {
                // field:oper => CONST  (where CONST is a numeric or a string)
                $orCondition = $this->processValue($class, $classField, $oper, $ptrn, $tag);
                if ($class != 'tv')
                    $conditions[] = $orCondition;
                else
                    $conditions[] = $this->processTvCondition($cvTbl, $tvTbl, $field, $orCondition);
            }
        }
        return $conditions;
    }

    // ============================================================== function available from queryHook

    public function setQueryHook(array $qhDeclaration = array()) {
        $requests = null;
        if (!empty($qhDeclaration)) {

            // queryHook version
            $this->queryHook['version'] = '1.1';
            if (!empty($qhDeclaration['qhVersion']))
                $this->queryHook['version'] = $qhDeclaration['qhVersion'];

            // requests
            if (!empty($qhDeclaration['requests']))
                $requests = $qhDeclaration['requests'];

            // sortby
            if (!empty($qhDeclaration['sortby'])) {
                $tag = $qhDeclaration['sortby'];
                if (is_array($_REQUEST[$tag])) {
                    // multiple list
                    $values = $_REQUEST[$tag];
                    $vals = array();
                    foreach ($values as $val) {
                        if (!empty($val))
                            $vals[] = strip_tags($val);
                    }
                    if (count($vals)) {
                        $val = implode(',', $vals);
                        $this->queryHook['sortby'] = $val;
                        $requests[$tag] = $val;
                    }
                } else {
                    $val = strip_tags($_REQUEST[$tag]);
                    if (!empty($val)) {
                        $this->queryHook['sortby'] = $val;
                        $requests[$tag] = $val;
                    }
                }
            }

            // perPage
            if (!empty($qhDeclaration['perPage'])) {
                $tag = $qhDeclaration['perPage'];
                $val = strip_tags($_REQUEST[$tag]);
                if (!empty($val)) {
                    $this->queryHook['perPage'] = $val;
                    $requests[$tag] = $val;
                }
            }

            // main
            if (!empty($qhDeclaration['main']))
                $this->queryHook['main'] = $qhDeclaration['main'];

            // joined
            if (!empty($qhDeclaration['joined']))
                $this->queryHook['joined'] = $qhDeclaration['joined'];

            // andConditions
            if (!empty($qhDeclaration['andConditions'])) {
                if ($this->search->config['withAjax'])
                    $this->getVarRequest();
                $andConditions = $this->processConditions($qhDeclaration['andConditions'], $requests);
                if (!empty($andConditions))
                    $this->queryHook['andConditions'] = $andConditions;
            }

            // stmt
            if (!empty($qhDeclaration['stmt']))
                $this->queryHook['stmt'] = $qhDeclaration['stmt'];

            if (!empty($requests))
                $this->queryHook['requests'] = $requests;
        }
        return $this->queryHook;
    }

    /**
     * Processes string and sets placeholders
     *
     * @param string $str The string to process
     * @param array $placeholders An array of placeholders to replace with values
     * @return string The parsed string
     */
    public function process($str, array $placeholders = array()) {
        foreach ($placeholders as $k => $v) {
            $str = str_replace('[[+' . $k . ']]', $v, $str);
        }
        return $str;
    }

    // ============================================================== function available from postHook

    /**
     * Update results
     *
     * @param array $searchResults The updated AdvSearchResults
     * @return array $postHook
     */
    public function updateResults($searchResults) {
        $this->postHook = $searchResults;
        return $this->postHook;
    }

}