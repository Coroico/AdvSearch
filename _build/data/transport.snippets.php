<?php

/**
 * AdvSearch transport snippets
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
 * Description:  Array of snippet objects for AdvSearch package
 * @package advsearch
 * @subpackage build
 */
if (!function_exists('getSnippetContent')) {

    function getSnippetContent($filename) {
        $o = file_get_contents($filename);
        $o = str_replace('<?php', '', $o);
        $o = str_replace('?>', '', $o);
        $o = trim($o);
        return $o;
    }

}
$snippets = array();

$snippets['AdvSearchForm'] = $modx->newObject('modSnippet');
$snippets['AdvSearchForm']->fromArray(array(
    'name' => 'AdvSearchForm',
    'description' => 'AdvSearchForm snippet to render search form.',
    'snippet' => getSnippetContent($sources['source_core'] . '/elements/snippets/advsearchform.snippet.php'),
        ), '', true, true);
$properties = include $sources['data'] . '/properties/properties.advsearchform.php';
$snippets['AdvSearchForm']->setProperties($properties);
unset($properties);


$snippets['AdvSearch'] = $modx->newObject('modSnippet');
$snippets['AdvSearch']->fromArray(array(
    'name' => 'AdvSearch',
    'description' => 'AdvSearch snippet to get the output.',
    'snippet' => getSnippetContent($sources['source_core'] . '/elements/snippets/advsearch.snippet.php'),
        ), '', true, true);
$properties = include $sources['data'] . '/properties/properties.advsearch.php';
$snippets['AdvSearch']->setProperties($properties);
unset($properties);


$snippets['AdvSearchGmapInfoWindow'] = $modx->newObject('modSnippet');
$snippets['AdvSearchGmapInfoWindow']->fromArray(array(
    'name' => 'AdvSearchGmapInfoWindow',
    'description' => 'AdvSearch snippet for AdvSearch\'s googlemap infobox.',
    'snippet' => getSnippetContent($sources['source_core'] . '/elements/snippets/advsearch.gmapinfowindow.snippet.php'),
        ), '', true, true);
$properties = include $sources['data'] . '/properties/properties.advsearch.gmapinfowindow.php';
$snippets['AdvSearch']->setProperties($properties);
unset($properties);


return $snippets;
