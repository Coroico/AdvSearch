<?php

/**
 * AdvSearch transport plugins
 * Copyright 2012 Coroico <coroico@wangba.fr>
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
 * Description:  Array of plugin events for AdvSearch package
 * @package advsearch
 * @subpackage build
 */
$events = array();

$events['OnDocFormSave'] = $modx->newObject('modPluginEvent');
$events['OnDocFormSave']->fromArray(array(
    'event' => 'OnDocFormSave',
    'priority' => 0,
    'propertyset' => 0,
        ), '', true, true);

$events['OnTemplateSave'] = $modx->newObject('modPluginEvent');
$events['OnTemplateSave']->fromArray(array(
    'event' => 'OnTemplateSave',
    'priority' => 0,
    'propertyset' => 0,
        ), '', true, true);

$events['OnTempFormSave'] = $modx->newObject('modPluginEvent');
$events['OnTempFormSave']->fromArray(array(
    'event' => 'OnTempFormSave',
    'priority' => 0,
    'propertyset' => 0,
        ), '', true, true);

$events['OnTVFormSave'] = $modx->newObject('modPluginEvent');
$events['OnTVFormSave']->fromArray(array(
    'event' => 'OnTVFormSave',
    'priority' => 0,
    'propertyset' => 0,
        ), '', true, true);

$events['OnSnipFormSave'] = $modx->newObject('modPluginEvent');
$events['OnSnipFormSave']->fromArray(array(
    'event' => 'OnSnipFormSave',
    'priority' => 0,
    'propertyset' => 0,
        ), '', true, true);

$events['OnPluginFormSave'] = $modx->newObject('modPluginEvent');
$events['OnPluginFormSave']->fromArray(array(
    'event' => 'OnPluginFormSave',
    'priority' => 0,
    'propertyset' => 0,
        ), '', true, true);

$events['OnMediaSourceFormSave'] = $modx->newObject('modPluginEvent');
$events['OnMediaSourceFormSave']->fromArray(array(
    'event' => 'OnMediaSourceFormSave',
    'priority' => 0,
    'propertyset' => 0,
        ), '', true, true);

$events['OnChunkFormSave'] = $modx->newObject('modPluginEvent');
$events['OnChunkFormSave']->fromArray(array(
    'event' => 'OnChunkFormSave',
    'priority' => 0,
    'propertyset' => 0,
        ), '', true, true);

$events['OnSiteRefresh'] = $modx->newObject('modPluginEvent');
$events['OnSiteRefresh']->fromArray(array(
    'event' => 'OnSiteRefresh',
    'priority' => 0,
    'propertyset' => 0,
        ), '', true, true);

return $events;
