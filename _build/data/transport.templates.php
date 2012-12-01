<?php
/**
 * AdvSearch transport templates
 * Copyright 2012 Coroico <coroico@wangba.fr>
 * @author Coroico <coroico@wangba.fr>
 * 28/11/2012
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
 * Description: Array of template objects for AdvSearch package
 * @package advsearch
 * @subpackage build
 */

$templates = array();

$templates[1]= $modx->newObject('modTemplate');
$templates[1]->fromArray(array(
    'id' => 1,
    'templatename' => 'myTemplate1',
    'description' => 'Template One for AdvSearch package',
    'content' => file_get_contents($sources['source_core'].'/elements/templates/mytemplate1.tpl'),
    'properties' => '',
),'',true,true);

$templates[2]= $modx->newObject('modTemplate');
$templates[2]->fromArray(array(
    'id' => 2,
    'templatename' => 'myTemplate2',
    'description' => 'Template Two for AdvSearch Package',
    'content' => file_get_contents($sources['source_core'].'/elements/templates/mytemplate2.tpl'),
    'properties' => '',
),'',true,true);

return $templates;