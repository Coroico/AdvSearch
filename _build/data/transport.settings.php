<?php
/** Array of system settings for Mycomponent package
 * @package advsearch
 * @subpackage build
 */


/* This section is ONLY for new System Settings to be added to
 * The System Settings grid. If you include existing settings,
 * they will be removed on uninstall. Existing setting can be
 * set in a script resolver (see install.script.php).
 */
$settings = array();

/* The first three are new settings */
$settings['advsearch_setting1']= $modx->newObject('modSystemSetting');
$settings['advsearch_setting1']->fromArray(array (
    'key' => 'advsearch_setting1',
    'value' => 'Value for setting 1',
    'namespace' => 'advsearch',
    'area' => 'advsearch',
), '', true, true);

$settings['advsearch_setting2']= $modx->newObject('modSystemSetting');
$settings['advsearch_setting2']->fromArray(array (
    'key' => 'advsearch_setting2',
    'value' => '1',
    'xtype' => 'combo-boolean',
    'namespace' => 'advsearch',
    'area' => 'advsearch',
), '', true, true);

$settings['advsearch_setting3']= $modx->newObject('modSystemSetting');
$settings['advsearch_setting3']->fromArray(array (
    'key' => 'advsearch_setting3',
    'value' => '0',
    'xtype' => 'combo-boolean',
    'namespace' => 'advsearch',
    'area' => 'advsearch',
), '', true, true);

return $settings;