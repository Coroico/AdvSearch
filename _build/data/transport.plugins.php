<?php

/**
 * AdvSearch transport plugins
 * Copyright 2012 Coroico <coroico@wangba.fr>
 * @author Coroico <coroico@wangba.fr>
 * @author goldsky <goldsky@virtudraft.com>
 * 07/1/2016
 *
 * AdvSearch is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option) any
 * later version.
 *
 * AdvSearch is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * AdvSearch; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package advsearch
 */
/**
 * Description:  Array of plugin objects for AdvSearch package
 * @package advsearch
 * @subpackage build
 */
if (!function_exists('getPluginContent')) {

    function getpluginContent($filename) {
        $o = file_get_contents($filename);
        $o = str_replace('<?php', '', $o);
        $o = str_replace('?>', '', $o);
        $o = trim($o);
        return $o;
    }

}
$plugins = array();

$plugins['AdvSearch'] = $modx->newObject('modPlugin');
$plugins['AdvSearch']->fromArray(array(
    'name' => 'AdvSearch',
    'description' => 'AdvSearch to clear cache on its partition.',
    'plugincode' => getPluginContent($sources['source_core'] . '/elements/plugins/advsearch.plugin.php'),
        ), '', true, true);
//$properties = include $sources['data'] . 'properties/properties.advsearch.plugin.php';
//$plugins['AdvSearch']->setProperties($properties);
//unset($properties);

/* add plugin events */
$events = include $sources['data'] . 'transport.advsearch.plugin.events.php';
if (is_array($events) && !empty($events)) {
    $plugins['AdvSearch']->addMany($events);
    $modx->log(xPDO::LOG_LEVEL_INFO, 'Packaged in ' . count($events) . ' AdvSearch Plugin Events.');
    flush();
} else {
    $modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not find plugin AdvSearch Plugin Events!');
}

$plugins['AdvSearchSolr'] = $modx->newObject('modPlugin');
$plugins['AdvSearchSolr']->fromArray(array(
    'name' => 'AdvSearchSolr',
    'description' => 'AdvSearch\'s plugin to handle Solr engine\'s indexer when changing the documents',
    'plugincode' => getPluginContent($sources['source_core'] . '/elements/plugins/advsearch.solr.plugin.php'),
        ), '', true, true);
//$properties = include $sources['data'] . 'properties/properties.advsearchsolr.plugin.php';
//$plugins['AdvSearchSolr']->setProperties($properties);
//unset($properties);

$events = include $sources['data'] . 'transport.advsearch.solr.plugin.events.php';
if (is_array($events) && !empty($events)) {
    $plugins['AdvSearchSolr']->addMany($events);
    $modx->log(xPDO::LOG_LEVEL_INFO, 'Packaged in ' . count($events) . ' AdvSearchSolr Plugin Events.');
    flush();
} else {
    $modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not find plugin AdvSearchSolr Plugin Events!');
}

return $plugins;
