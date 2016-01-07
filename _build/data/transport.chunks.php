<?php
/**
 * AdvSearch transport chunks
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
 * Description: Array of chunk objects for AdvSearch package
 * @package advsearch
 * @subpackage build
 */

$chunks = array();

$chunks['AdvSearchForm']= $modx->newObject('modChunk');
$chunks['AdvSearchForm']->fromArray(array(
    'name' => 'AdvSearchForm',
    'description' => 'SearchForm for AdvSearch',
    'snippet' => file_get_contents($sources['source_core'].'/elements/chunks/searchform.chunk.tpl'),
    'properties' => '',
),'',true,true);

$chunks['AdvSearchResults']= $modx->newObject('modChunk');
$chunks['AdvSearchResults']->fromArray(array(
    'name' => 'AdvSearchResults',
    'description' => 'Results for AdvSearch',
    'snippet' => file_get_contents($sources['source_core'].'/elements/chunks/searchresults.chunk.tpl'),
    'properties' => '',
),'',true,true);

$chunks['AdvSearchResult']= $modx->newObject('modChunk');
$chunks['AdvSearchResult']->fromArray(array(
    'name' => 'AdvSearchResult',
    'description' => 'Result for AdvSearch',
    'snippet' => file_get_contents($sources['source_core'].'/elements/chunks/searchresult.chunk.tpl'),
    'properties' => '',
),'',true,true);

$chunks['Extract']= $modx->newObject('modChunk');
$chunks['Extract']->fromArray(array(
    'name' => 'Extract',
    'description' => 'Extract for AdvSearch',
    'snippet' => file_get_contents($sources['source_core'].'/elements/chunks/extract.chunk.tpl'),
    'properties' => '',
),'',true,true);

$chunks['Paging1']= $modx->newObject('modChunk');
$chunks['Paging1']->fromArray(array(
    'id' => 6,
    'name' => 'Paging1',
    'description' => 'Paging type 1 for AdvSearch',
    'snippet' => file_get_contents($sources['source_core'].'/elements/chunks/paging1.chunk.tpl'),
    'properties' => '',
),'',true,true);

$chunks['Paging2']= $modx->newObject('modChunk');
$chunks['Paging2']->fromArray(array(
    'name' => 'Paging2',
    'description' => 'Paging type 2 for AdvSearch',
    'snippet' => file_get_contents($sources['source_core'].'/elements/chunks/paging2.chunk.tpl'),
    'properties' => '',
),'',true,true);

$chunks['Paging3']= $modx->newObject('modChunk');
$chunks['Paging3']->fromArray(array(
    'name' => 'Paging3',
    'description' => 'Paging type 3 for AdvSearch',
    'snippet' => file_get_contents($sources['source_core'].'/elements/chunks/paging3.chunk.tpl'),
    'properties' => '',
),'',true,true);

$chunks['Paging3RangeSplitter']= $modx->newObject('modChunk');
$chunks['Paging3RangeSplitter']->fromArray(array(
    'name' => 'Paging3RangeSplitter',
    'description' => 'Paging splitter for paging type 3 of AdvSearch',
    'snippet' => file_get_contents($sources['source_core'].'/elements/chunks/paging3rangesplitter.chunk.tpl'),
    'properties' => '',
),'',true,true);

$chunks['PageLink']= $modx->newObject('modChunk');
$chunks['PageLink']->fromArray(array(
    'name' => 'PageLink',
    'description' => 'Page Link for AdvSearch',
    'snippet' => file_get_contents($sources['source_core'].'/elements/chunks/pagelink.chunk.tpl'),
    'properties' => '',
),'',true,true);

$chunks['CurrentPageLink']= $modx->newObject('modChunk');
$chunks['CurrentPageLink']->fromArray(array(
    'name' => 'CurrentPageLink',
    'description' => 'Current Page Link for AdvSearch',
    'snippet' => file_get_contents($sources['source_core'].'/elements/chunks/currentpagelink.chunk.tpl'),
    'properties' => '',
),'',true,true);

$chunks['HelpLink']= $modx->newObject('modChunk');
$chunks['HelpLink']->fromArray(array(
    'name' => 'HelpLink',
    'description' => 'Link for AdvSearch Help',
    'snippet' => file_get_contents($sources['source_core'].'/elements/chunks/helplink.chunk.tpl'),
    'properties' => '',
),'',true,true);

$chunks['ResultsWindow']= $modx->newObject('modChunk');
$chunks['ResultsWindow']->fromArray(array(
    'name' => 'ResultsWindow',
    'description' => 'Div section to set the ajax window of results',
    'snippet' => file_get_contents($sources['source_core'].'/elements/chunks/resultswindow.chunk.tpl'),
    'properties' => '',
),'',true,true);

$chunks['MoreResults']= $modx->newObject('modChunk');
$chunks['MoreResults']->fromArray(array(
    'name' => 'MoreResults',
    'description' => 'More results link of the ajax window of results',
    'snippet' => file_get_contents($sources['source_core'].'/elements/chunks/moreresults.chunk.tpl'),
    'properties' => '',
),'',true,true);

return $chunks;