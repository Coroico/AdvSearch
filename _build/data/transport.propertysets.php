<?php
/**
 * AdvSearch transport property sets
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
 * Description:  Array of property set objects for AdvSearch package
 * @package advsearch
 * @subpackage build
 */


$propertySets = array();

$propertySets[1]= $modx->newObject('modPropertySet');
$propertySets[1]->fromArray(array(
    'id' => 1,
    'name' => 'PropertySet',
    'description' => 'PropertySet for AdvSearch.',
),'',true,true);
$properties = include $sources['data'].'/properties/properties.advsearchpropertyset.php';
$propertySets[1]->setProperties($properties);
unset($properties);

return $propertySets;