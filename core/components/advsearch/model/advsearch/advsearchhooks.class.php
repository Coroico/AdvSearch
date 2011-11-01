<?php
/**
 * AdvSearch - AdvSearchHooks
 *
 * @package 	AdvSearch
 * @author		Coroico
 * @copyright 	Copyright (c) 2011 by Coroico <coroico@wangba.fr>
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
    public $search = null;

    public $queryHook;

    /**
     * The constructor for the advSearchHooks class
     *
     * @param AdvSearchResults &$search A reference to the AdvSearchResults class instance.
     * @param array $config Optional. An array of configuration parameters.
     * @return asHooks
     */
    function __construct(AdvSearch &$search, array $config = array()) {
        $this->search =& $search;
        $this->modx =& $search->modx;
        $this->config = array_merge(array(
        ),$config);
    }

    /**
     * Loads an array of hooks. If one fails, will not proceed.
     *
     * @access public
     * @param array $hooks The hooks to run.
     * @param array $fields The fields and values of the form
     * @param array $customProperties An array of extra properties to send to the hook
     * @return array An array of field name => value pairs.
     */
    public function loadMultiple($hooks, array $fields = array(), array $customProperties = array()) {
        if (empty($hooks)) return array();
        if (is_string($hooks)) $hooks = explode(',',$hooks);

        $this->hooks = array();
        $this->fields =& $fields;
        foreach ($hooks as $hook) {
            $hook = trim($hook);
            $success = $this->load($hook,$this->fields,$customProperties);
            if (!$success) return $this->hooks;
            /* dont proceed if hook fails */
        }
        return $this->hooks;
    }

    /**
     * Load a hook. Stores any errors for the hook to $this->errors.
     *
     * @access public
     * @param string $hook The name of the hook. May be a Snippet name.
     * @param array $fields The fields and values of the form.
     * @param array $customProperties Any other custom properties to load into a custom hook.
     * @return boolean True if hook was successful.
     */
    public function load($hook, array $fields = array(), array $customProperties = array()) {
        $success = false;
        if (!empty($fields)) $this->fields =& $fields;
        $this->hooks[] = $hook;

        $reserved = array('load','__construct','getErrorMessage');
        if (method_exists($this,$hook) && !in_array($hook,$reserved)) {
            /* built-in hooks */
            $success = $this->$hook($this->fields);

        } else if ($snippet = $this->modx->getObject('modSnippet',array('name' => $hook))) {
            /* custom snippet hook */
            $properties = array_merge($this->search->config, $customProperties);
            $properties['advsearch'] =& $this->search;
            $properties['hook'] =& $this;
            $properties['fields'] = $this->fields;
            $properties['errors'] =& $this->errors;
            $success = $snippet->process($properties);

        } else {
            /* no hook found */
            $this->modx->log(modX::LOG_LEVEL_ERROR,'[AdvSearch] Could not find hook "'.$hook.'".');
            $success = true;
        }

        if (is_array($success) && !empty($success)) {
            $this->errors = array_merge($this->errors,$success);
            $success = false;
        } else if ($success != true) {
            $this->errors[$hook] .= ' '.$success;
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
        return implode($delim,$this->errors);
    }

    /**
     * Adds an error to the stack.
     *
     * @access private
     * @param string $key The field to add the error to.
     * @param string $value The error message.
     * @return string The added error message with the error wrapper.
     */
    public function addError($key,$value) {
        $this->errors[$key] .= $value;
        return $this->errors[$key];
    }

	function processValue($class,$classField,$oper,$val) {
		$condition = '';
		if (!is_numeric($val)) $val = "'{$val}'";
		switch($oper) {
			case 'IN':
			case 'NOT IN':  // unary operator with a list of values wrapped by parenthesis
				$condition = "({$classField} {$oper}({$val}))";
				break;
			default:    // >,<,>=,<=,LIKE,REGEXP  (unary operator)
				$condition = "({$classField} {$oper} {$val})";
		}
		return $condition;
	}

    function processTvCondition($cvTbl,$tvTbl,$tvName,$condition) {
		$tvCondition = " EXISTS( SELECT 1 FROM {$cvTbl} `tvcv` JOIN {$tvTbl} tv ON `tv`.`name` = '{$tvName}' ";
		$tvCondition .= "AND `tv`.`id` = `tvcv`.`tmplvarid` AND {$condition} WHERE `tvcv`.`contentid` = `modResource`.`id` ) ";
        return $tvCondition;
    }

	function processConditions($andConditions, & $requests) {
		$conditions = array();
		foreach($andConditions as $keyCondition => $valueCondition) {

			$keyElts = array_map("trim",explode(':',$keyCondition));
			if (count($keyElts) == 1) $keyElts[1] = '=';
			$keyCondition = implode(':',$keyElts);
			$classFieldElts = array_map("trim",explode('.',$keyElts[0]));
			$class = (count($classFieldElts) == 2) ? $classFieldElts[0] : '';
            $class = trim($class,'`');
            $field = (count($classFieldElts) == 2) ? $classFieldElts[1] : $classFieldElts[0];
            $field = trim($field,'`');

            if (empty($class)) $classField = $this->modx->escape($field);
            elseif ($class == 'tv') {
                $classField = "`tvcv`.{$this->modx->escape('value')}";
				$cvTbl = $this->modx->getTableName('modTemplateVarResource'); // site_tmplvar_contentvalues
				$tvTbl = $this->modx->getTableName('modTemplateVar');	// site_tmplvars
            }
            else $classField = "{$this->modx->escape($class)}.{$this->modx->escape($field)}";
			$oper = strtoupper($keyElts[1]);	// operator

			$valueElts = array_map("trim",explode(':',$valueCondition));
			$tag = $valueElts[0];
			$typeValue = (!empty($valueElts[1])) ? strtolower($valueElts[1]) : 'request';
			$filtered = (!empty($valueElts[2])) ? array_map("trim",explode(',',$valueElts[2])) : array();

			if ($typeValue == 'request') { // the value is provided par an http variable
				if (isset($_POST[$tag]) || isset($_GET[$tag])){
					if (is_array($_POST[$tag]) || is_array($_GET[$tag])) {
						// multiple list
						$values = (isset($_POST[$tag])) ? $_POST[$tag] : $_GET[$tag];
						$orConditions = array();
						foreach($values as $val) {
							$val = strip_tags($val);
							if (($val != '') && !in_array($val, $filtered)) {
								$requests[$tag][] = $val;
								$orConditions[] = $this->processValue($class,$classField,$oper,$val);
							}
						}
						if (count($orConditions)) {
							$orCondition = '(' . implode(' OR ', $orConditions) . ')';
							if ($class != 'tv') $conditions[] = $orCondition;
							else $conditions[] = $this->processTvCondition($cvTbl,$tvTbl,$field,$orCondition);
						}
					}
					else {
						// single value
						$val = (isset($_POST[$tag])) ? strip_tags($_POST[$tag]) : strip_tags($_GET[$tag]);
						if (($val != '') && !in_array($val, $filtered)) {
							$requests[$tag] = $val;
							$orCondition = $this->processValue($class,$classField,$oper,$val);
							if ($class != 'tv') $conditions[] = $orCondition;
							else $conditions[] = $this->processTvCondition($cvTbl,$tvTbl,$field,$orCondition);
						}
					}
				}
			}
			else {
				// field:oper => CONST  (where CONST is a numeric or a string)
				$orCondition = $this->processValue($class,$classField,$oper,$tag);
				if ($class != 'tv') $conditions[] = $orCondition;
				else $conditions[] = $this->processTvCondition($cvTbl,$tvTbl,$field,$orCondition);
			}
		}
		return $conditions;
	}

    public function setQueryHook(array $qhDeclaration = array() ) {
		$requests = null;
		if (!empty($qhDeclaration)) {
			if ($this->config['debug']) $this->modx->log(modX::LOG_LEVEL_DEBUG, '[AdvSearch] qhDeclaration: '.print_r($qhDeclaration,true),'','setQueryHook');

			// requests
			if (!empty($qhDeclaration['requests'])) $requests = $qhDeclaration['requests'];

			// sortby
			if (!empty($qhDeclaration['sortby'])) {
				$tag = $qhDeclaration['sortby'];
				if (is_array($_POST[$tag]) || is_array($_GET[$tag])) {
					// multiple list
					$values = (isset($_POST[$tag])) ? $_POST[$tag] : $_GET[$tag];
					$vals = array();
					foreach($values as $val) {
						if (!empty($val)) $vals[] = strip_tags($val);
					}
					if (count($vals)) {
						$val = implode(',',$vals);
						$this->queryHook['sortby'] = $val;
						$requests[$tag] = $val;
					}
				}
				else {
					$val = (isset($_POST[$tag])) ? strip_tags($_POST[$tag]) : strip_tags($_GET[$tag]);
					if (!empty($val)) {
						$this->queryHook['sortby'] = $val;
						$requests[$tag] = $val;
					}
				}
			}

			// perPage
			if (!empty($qhDeclaration['perPage'])) {
				$tag = $qhDeclaration['perPage'];
				$val = (isset($_POST[$tag])) ? strip_tags($_POST[$tag]) : strip_tags($_GET[$tag]);
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
				$andConditions = $this->processConditions($qhDeclaration['andConditions'], $requests);
				if (!empty($andConditions)) $this->queryHook['andConditions'] = $andConditions;
			}

			// stmt
			if (!empty($qhDeclaration['stmt']))
				$this->queryHook['stmt'] = $qhDeclaration['stmt'];

			if (!empty($requests)) $this->queryHook['requests'] = $requests;
		}
        return $this->queryHook;
    }
}