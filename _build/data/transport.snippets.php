<?php
/**
 * AdvSearch transport snippets
 * Copyright 2011 Coroico <coroico@wangba.fr>
 * @author Coroico <coroico@wangba.fr>
 * 14/08/2011
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
 * Description:  Array of snippet objects for AdvSearch package
 * @package advsearch
 * @subpackage build
 */

if (! function_exists('getSnippetContent')) {
    function getSnippetContent($filename) {
        $o = file_get_contents($filename);
        $o = str_replace('<?php','',$o);
        $o = str_replace('?>','',$o);
        $o = trim($o);
        return $o;
    }
}
$snippets = array();

$snippets[1]= $modx->newObject('modSnippet');
$snippets[1]->fromArray(array(
    'id' => 1,
    'name' => 'AdvSearchForm',
    'description' => 'AdvSearchForm snippet for AdvSearch.',
    'snippet' => getSnippetContent($sources['source_core'].'/elements/snippets/advsearchform.snippet.php'),
),'',true,true);
$properties = include $sources['data'].'/properties/properties.advsearchform.php';
$snippets[1]->setProperties($properties);
unset($properties);


$snippets[2]= $modx->newObject('modSnippet');
$snippets[2]->fromArray(array(
    'id' => 2,
    'name' => 'AdvSearch',
    'description' => 'AdvSearch snippet for AdvSearch.',
    'snippet' => getSnippetContent($sources['source_core'].'/elements/snippets/advsearch.snippet.php'),
),'',true,true);
$properties = include $sources['data'].'/properties/properties.advsearch.php';
$snippets[2]->setProperties($properties);
unset($properties);


$snippets[3]= $modx->newObject('modSnippet');
$snippets[3]->fromArray(array(
    'id' => 3,
    'name' => 'AdvSearchHelp',
    'description' => 'AdvSearchHelp snippet for AdvSearch.',
    'snippet' => getSnippetContent($sources['source_core'].'/elements/snippets/advsearchhelp.snippet.php'),
),'',true,true);

return $snippets;