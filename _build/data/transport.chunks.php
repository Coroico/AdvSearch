<?php
/**
 * AdvSearch transport chunks
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
 * Description: Array of chunk objects for AdvSearch package
 * @package advsearch
 * @subpackage build
 */

$chunks = array();

$chunks[1]= $modx->newObject('modChunk');
$chunks[1]->fromArray(array(
    'id' => 1,
    'name' => 'AdvSearchForm',
    'description' => 'SearchForm for AdvSearch',
    'snippet' => file_get_contents($sources['source_core'].'/elements/chunks/searchform.chunk.tpl'),
    'properties' => '',
),'',true,true);

$chunks[2]= $modx->newObject('modChunk');
$chunks[2]->fromArray(array(
    'id' => 2,
    'name' => 'AdvSearchResults',
    'description' => 'Results for AdvSearch',
    'snippet' => file_get_contents($sources['source_core'].'/elements/chunks/searchresults.chunk.tpl'),
    'properties' => '',
),'',true,true);

$chunks[3]= $modx->newObject('modChunk');
$chunks[3]->fromArray(array(
    'id' => 3,
    'name' => 'AdvSearchResult',
    'description' => 'Result for AdvSearch',
    'snippet' => file_get_contents($sources['source_core'].'/elements/chunks/searchresult.chunk.tpl'),
    'properties' => '',
),'',true,true);

$chunks[4]= $modx->newObject('modChunk');
$chunks[4]->fromArray(array(
    'id' => 4,
    'name' => 'Extract',
    'description' => 'Extract for AdvSearch',
    'snippet' => file_get_contents($sources['source_core'].'/elements/chunks/extract.chunk.tpl'),
    'properties' => '',
),'',true,true);

$chunks[5]= $modx->newObject('modChunk');
$chunks[5]->fromArray(array(
    'id' => 5,
    'name' => 'Paging2',
    'description' => 'Paging type 2 for AdvSearch',
    'snippet' => file_get_contents($sources['source_core'].'/elements/chunks/paging2.chunk.tpl'),
    'properties' => '',
),'',true,true);

$chunks[6]= $modx->newObject('modChunk');
$chunks[6]->fromArray(array(
    'id' => 6,
    'name' => 'Paging1',
    'description' => 'Paging type 1 for AdvSearch',
    'snippet' => file_get_contents($sources['source_core'].'/elements/chunks/paging1.chunk.tpl'),
    'properties' => '',
),'',true,true);

$chunks[7]= $modx->newObject('modChunk');
$chunks[7]->fromArray(array(
    'id' => 7,
    'name' => 'PageLink',
    'description' => 'Page Link for AdvSearch',
    'snippet' => file_get_contents($sources['source_core'].'/elements/chunks/pagelink.chunk.tpl'),
    'properties' => '',
),'',true,true);

$chunks[8]= $modx->newObject('modChunk');
$chunks[8]->fromArray(array(
    'id' => 8,
    'name' => 'CurrentPageLink',
    'description' => 'Current Page Link for AdvSearch',
    'snippet' => file_get_contents($sources['source_core'].'/elements/chunks/currentpagelink.chunk.tpl'),
    'properties' => '',
),'',true,true);

$chunks[9]= $modx->newObject('modChunk');
$chunks[9]->fromArray(array(
    'id' => 9,
    'name' => 'HelpLink',
    'description' => 'Link for AdvSearch Help',
    'snippet' => file_get_contents($sources['source_core'].'/elements/chunks/helplink.chunk.tpl'),
    'properties' => '',
),'',true,true);

$chunks[10]= $modx->newObject('modChunk');
$chunks[10]->fromArray(array(
    'id' => 10,
    'name' => 'ResultsWindow',
    'description' => 'Div section to set the ajax window of results',
    'snippet' => file_get_contents($sources['source_core'].'/elements/chunks/resultswindow.chunk.tpl'),
    'properties' => '',
),'',true,true);

$chunks[11]= $modx->newObject('modChunk');
$chunks[11]->fromArray(array(
    'id' => 11,
    'name' => 'MoreResults',
    'description' => 'More results link of the ajax window of results',
    'snippet' => file_get_contents($sources['source_core'].'/elements/chunks/moreresults.chunk.tpl'),
    'properties' => '',
),'',true,true);

return $chunks;