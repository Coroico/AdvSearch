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

$events['OnDocPublished'] = $modx->newObject('modPluginEvent');
$events['OnDocPublished']->fromArray(array(
    'event' => 'OnDocPublished',
    'priority' => 0,
    'propertyset' => 0,
        ), '', true, true);

$events['OnDocUnpublished'] = $modx->newObject('modPluginEvent');
$events['OnDocUnpublished']->fromArray(array(
    'event' => 'OnDocUnpublished',
    'priority' => 0,
    'propertyset' => 0,
        ), '', true, true);

$events['OnDocUnPublished'] = $modx->newObject('modPluginEvent');
$events['OnDocUnPublished']->fromArray(array(
    'event' => 'OnDocUnPublished',
    'priority' => 0,
    'propertyset' => 0,
        ), '', true, true);

$events['OnResourceDuplicate'] = $modx->newObject('modPluginEvent');
$events['OnResourceDuplicate']->fromArray(array(
    'event' => 'OnResourceDuplicate',
    'priority' => 0,
    'propertyset' => 0,
        ), '', true, true);

$events['OnResourceDelete'] = $modx->newObject('modPluginEvent');
$events['OnResourceDelete']->fromArray(array(
    'event' => 'OnResourceDelete',
    'priority' => 0,
    'propertyset' => 0,
        ), '', true, true);

$events['OnResourceUndelete'] = $modx->newObject('modPluginEvent');
$events['OnResourceUndelete']->fromArray(array(
    'event' => 'OnResourceUndelete',
    'priority' => 0,
    'propertyset' => 0,
        ), '', true, true);

return $events;
