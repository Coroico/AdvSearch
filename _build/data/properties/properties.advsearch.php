<?php

/**
 * Default properties for the AdvSearch snippet
 * @author Coroico <coroico@wangba.fr>
 * 14/08/2011
 *
 * @package advsearch
 * @subpackage build
 */

global $modx;

$properties = array(

// &asId - [Unique id for AdvSearch instance | 'advsea' ]  (optional)
// this allows to distinguish several AdvSearch instances on the same page
// Any combination of characters a-z, underscores, and numbers 0-9
// This is case sensitive. Default = 'as0'
// With ajax mode, the first snippet call of the page shouldn't use the fsId parameter
    array(
        'name' => 'asId',
        'desc' => 'advsearch.advsearch_asId_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'as0',
        'lexicon' => 'advsearch:properties',
    ),

// &containerTpl - [ chunk name | 'SearchResults' ]  (optional)
// The chunk that will be used to wrap all the search results, pagination and message.
// Default: 'AdvSearchResults'
    array(
        'name' => 'containerTpl',
        'desc' => 'advsearch.advsearch_containerTpl_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'AdvSearchResults',
        'lexicon' => 'advsearch:properties',
    ),

// &contexts - [ comma separated context names | $modx->context->get('key') ] (optional)
// The contexts to search. Defaults to the current context if none are explicitly specified.
// Default: 'web' context used
    array(
        'name' => 'contexts',
        'desc' => 'advsearch.advsearch_contexts_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'web',
        'lexicon' => 'advsearch:properties',
    ),

// &currentPageTpl  - [ chunk name | 'CurrentPageLink' ] (optional)
// The chunk to use for a pagination link.
// Default: CurrentPageLink
    array(
        'name' => 'currentPageTpl',
        'desc' => 'advsearch.advsearch_currentPageTpl_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'CurrentPageLink',
        'lexicon' => 'advsearch:properties',
    ),

// &debug - [ 0 | 1 ]  (optional)
// Output logged into Modx log
// Default: 0 - no logs
    array(
        'name' => 'debug',
        'desc' => 'advsearch.advsearch_debug',
        'type' => 'numberfield',
        'options' => array(
            array('text' => 'No','value' => 0),
            array('text' => 'Yes','value' => 1),
        ),
        'value' => 0,
        'lexicon' => 'advsearch:properties',
    ),

// &fields [csv list of fields | 'pagetitle,longtitle,alias,description,introtext,content' ] (optional)
// The list of fields available with search results
// Default: 'pagetitle,longtitle,alias,description,introtext,content'
    array(
        'name' => 'fields',
        'desc' => 'advsearch.advsearch_fields_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'pagetitle,longtitle,alias,description,introtext,content',
        'lexicon' => 'advsearch:properties',
    ),

// &docindexPath - under assets/files/ [ path | 'docindex/' ] (optional)
// The path where are located Lucene document indexes
// Default: 'docindex/'
    array(
        'name' => 'docindexPath',
        'desc' => 'advsearch.advsearch_docindexPath_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'docindex/',
        'lexicon' => 'advsearch:properties',
    ),

// &effect - [ 'basic' | 'showfade' | 'slidefade' ]  (optional)
// effect name to use to display the window of results (mode ajax)
// Default: 'basic'
    array(
        'name' => 'effect',
        'desc' => 'advsearch.advsearch_effect_desc',
        'type' => 'list',
        'options' => array(
            array('text' => 'basic','value' => 'basic'),
            array('text' => 'showfade','value' => 'showfade'),
            array('text' => 'slidefade','value' => 'slidefade'),
        ),
        'value' => 'basic',
        'lexicon' => 'advsearch:properties',
    ),
	
// &engine - [ 'mysql' | 'zend' | 'all' ]  (optional)
// Search engine selected
// Default: 'mysql'
    array(
        'name' => 'engine',
        'desc' => 'advsearch.advsearch_engine_desc',
        'type' => 'list',
        'options' => array(
            array('text' => 'mysql','value' => 'mysql'),
            array('text' => 'zend','value' => 'zend'),
            array('text' => 'all','value' => 'all'),
        ),
        'value' => 'mysql',
        'lexicon' => 'advsearch:properties',
    ),

// &extractEllipsis - [ string | '...' ]  (optional)
// Ellipside to mark the beginning and the end of an extract when the sentence is cutting
// Default: '...'
    array(
        'name' => 'extractEllipsis',
        'desc' => 'advsearch.advsearch_extractEllipsis_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => '...',
        'lexicon' => 'advsearch:properties',
    ),

// &extractLength - [ 50 < int < 800 | 200 ]  (optional)
// Length of extract around the search words found - between 50 and 800 characters
// Default: 200
    array(
        'name' => 'extractLength',
        'desc' => 'advsearch.advsearch_extractLength_desc',
        'type' => 'numberfield',
        'options' => '',
        'value' => 200,
        'lexicon' => 'advsearch:properties',
    ),

// &extractTpl - [ chunk name | 'Extract' ]  (optional)
// The chunk that will be used to wrap each extract
// Default: 'Extract'
    array(
        'name' => 'extractTpl',
        'desc' => 'advsearch.advsearch_extractTpl_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'Extract',
        'lexicon' => 'advsearch:properties',
    ),

// &fieldPotency - [ comma separated list of field : potency ]  (optional)
// e.g: pagetitle:10,content:1
// potency per field defaults to 1 if not set
// Default: 'createdon'
// used for mysql search when sortby = score
    array(
        'name' => 'fieldPotency',
        'desc' => 'advsearch.advsearch_fieldPotency_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'createdon',
        'lexicon' => 'advsearch:properties',
    ),

// &highlightClass - [ string | 'fs-highlight']  (optional)
// The CSS class name to add to highlighted terms in results.
// Default: 'advsea-highlight'
    array(
        'name' => 'highlightClass',
        'desc' => 'advsearch.advsearch_highlightClass_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'advsea-highlight',
        'lexicon' => 'advsearch:properties',
    ),

// &highlightResults - [ 0 | 1 ]  (optional)
// create links so that search terms will be highlighted when linked page clicked
// Default: 1 - Results highlighted
    array(
        'name' => 'highlightResults',
        'desc' => 'advsearch.advsearch_highlightResults_desc',
        'type' => 'list',
        'options' => array(
            array('text' => 'Yes','value' => 1),
            array('text' => 'No','value' => 0),
        ),
        'value' => 1,
        'lexicon' => 'advsearch:properties',
    ),

// &highlightTag - [ tag name | 'span' ]  (optionel)
// The html tag to wrap the highlighted term with in search results.
// Default: 'span'
    array(
        'name' => 'highlightTag',
        'desc' => 'advsearch.advsearch_highlightTag_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'span',
        'lexicon' => 'advsearch:properties',
    ),

// &hideContainers - [ 0 | 1 ]  Search in container resources.  (optional)
// 0 - search in all resources
// 1 - will not search in any resources marked as a container (is_folder).
// Default: 0
    array(
        'name' => 'hideContainers',
        'desc' => 'advsearch.advsearch_hideContainers_desc',
        'type' => 'list',
        'options' => array(
            array('text' => 'Search in all documents','value' => 0),
            array('text' => 'Don\'t search in documents marked as container.','value' => 1),
        ),
        'value' => 0,
        'lexicon' => 'advsearch:properties',
    ),


// &hideMenu - [ 0 | 1 | 2 ]  Search in documents regardless if they are visible from the menus.  (optional)
// Whether or not to return Resources that have hidemenu on.
// 0 shows only visible Resources, 1 shows only hidden Resources, 2 shows both.
// Default: 2
    array(
        'name' => 'hideMenu',
        'desc' => 'advsearch.advsearch_hideMenu_desc',
        'type' => 'list',
        'options' => array(
            array('text' => 'Search only in visible documents','value' => 0),
            array('text' => 'Search only in hidden documents','value' => 1),
            array('text' => 'Search in hidden and visible documents','value' => 2),
        ),
        'value' => 2,
        'lexicon' => 'advsearch:properties',
    ),


// &ids - [ comma separated list of Ids | '' ]  (optional)
// A comma-separated list of IDs to restrict the search to.
// Default: '' - empty list
    array(
        'name' => 'ids',
        'desc' => 'advsearch.advsearch_ids_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => '',
        'lexicon' => 'advsearch:properties',
    ),

// &includeTVs - [ comma separated tv names | '' ]  (optional)
// Add TVs values to search results and set them as placeholders.
// Default: '' disallow the feature
    array(
        'name' => 'includeTVs',
        'desc' => 'advsearch.advsearch_includeTVs_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => '',
        'lexicon' => 'advsearch:properties',
    ),

// &init - [ 'none' | 'all' ]  (optional)
// init defines if the search display all the results or none when the page is loaded at the first time
// Default: none
    array(
        'name' => 'init',
        'desc' => 'advsearch.advsearch_init_desc',
        'type' => 'list',
        'options' => array(
            array('text' => 'None','value' => 'none'),
            array('text' => 'All','value' => 'all'),
        ),
        'value' => 'none',
        'lexicon' => 'advsearch:properties',
    ),

// &libraryPath - [ path | 'libraries/' ] (optional)
// The path under assets where are located external librairies like the Zend library
// Default: 'assets/libraries/'
    array(
        'name' => 'libraryPath',
        'desc' => 'advsearch.advsearch_libraryPath_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'libraries/',
        'lexicon' => 'advsearch:properties',
    ),

// &maxWords - [ int ]  (optional)
// Maximum number of words for searching
// Default: 20
    array(
        'name' => 'maxWords',
        'desc' => 'advsearch.advsearch_maxWords_desc',
        'type' => 'numberfield',
        'options' => '',
        'value' => 20,
        'lexicon' => 'advsearch:properties',
    ),

// &method - [ 'POST' | 'GET' ] (optional)
// Whether to send the search over POST or GET.
// Default: GET
    array(
        'name' => 'method',
        'desc' => 'advsearch.advsearch_method_desc',
        'type' => 'list',
        'options' => array(
            array('text' => 'POST','value' => 'POST'),
            array('text' => 'GET','value' => 'GET'),
        ),
        'value' => 'GET',
        'lexicon' => 'advsearch:properties',
    ),

// &minChars - [  2 < int < 10 ]  (optional)
// Minimum number of characters to require for a word to be valid for searching.
// Default: 3
    array(
        'name' => 'minChars',
        'desc' => 'advsearch.advsearch_minChars_desc',
        'type' => 'numberfield',
        'options' => '',
        'value' => 3,
        'lexicon' => 'advsearch:properties',
    ),

// &moreResults - [ int id of a document | 0 ] (optional - mode ajax)
// The document id of the page you want the more results link to point to
// Default: 0
    array(
        'name' => 'moreResults',
        'desc' => 'advsearch.advsearch_moreResults_desc',
        'type' => 'numberfield',
        'options' => '',
        'value' => 0,
        'lexicon' => 'advsearch:properties',
    ),

// &moreResultsTpl  - [ chunk name | 'MoreResults' ] (optional)
// The chunk to use for the "more results" link. Used with ajax mode
// Default: MoreResults
    array(
        'name' => 'moreResultsTpl',
        'desc' => 'advsearch.advsearch_moreResultsTpl_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'MoreResults',
        'lexicon' => 'advsearch:properties',
    ),
	
// &offsetIndex - [ string | 'offset' ] (optional)
// The name of the offset parameter that the search will use.
// Default: 'offset'
    array(
        'name' => 'offsetIndex',
        'desc' => 'advsearch.advsearch_offsetIndex_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'offset',
        'lexicon' => 'advsearch:properties',
    ),
	
// &output - [ 'json' | 'html' ] (optional)
// output type. 
// 'json' : Array of all results as json string
// 'html' : Page of results as html string
// Default: 'html'
    array(
        'name' => 'output',
        'desc' => 'advsearch.advsearch_output_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'html',
        'lexicon' => 'advsearch:properties',
    ),

// &pagingSeparator  - [ string | ' | ' ] (optional)
// Page number links separator. Used with pagingType0
// default : ' | '
    array(
        'name' => 'pagingSeparator',
        'desc' => 'advsearch.advsearch_pagingSeparator_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => ' | ',
        'lexicon' => 'advsearch:properties',
    ),
	
// &pagingType  - [ 0 | 1 | 2 ] (optional)
// Type of pagination.
// 0 : no pagination
// 1 : Previous 6-10/13 Next
// 2 : Result pages: 1 | 2 | 3 ...
// default : 1
    array(
        'name' => 'pagingType',
        'desc' => 'advsearch.advsearch_pagingType_desc',
        'type' => 'list',
        'options' => array(
            array('text' => 'No pagination','value' => 0),
            array('text' => 'Paging type 1','value' => 1),
            array('text' => 'Paging type 2','value' => 2),
        ),
        'value' => 1,
        'lexicon' => 'advsearch:properties',
    ),

// &pageTpl  - [ chunk name | 'PageLink' ] (optional)
// The chunk to use for a pagination link. Used by paging type 2
// Default: PageLink
    array(
        'name' => 'pageTpl',
        'desc' => 'advsearch.advsearch_pageTpl_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'PageLink',
        'lexicon' => 'advsearch:properties',
    ),

// &paging1Tpl  - [ chunk name | 'Paging1' ] (optional)
// The chunk to use for the paging type 1
// Default: Paging1
    array(
        'name' => 'paging1Tpl',
        'desc' => 'advsearch.advsearch_paging1Tpl_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'Paging1',
        'lexicon' => 'advsearch:properties',
    ),

// &paging2Tpl  - [ chunk name | 'Paging2' ] (optional)
// The chunk to use for the paging type 2
// Default: Paging2
    array(
        'name' => 'paging2Tpl',
        'desc' => 'advsearch.advsearch_paging2Tpl_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'Paging2',
        'lexicon' => 'advsearch:properties',
    ),

// &perPage - [ int | 10 ] (optional)
// Set to the max number of results you would like on each page. Set to 0 if unlimited.
// Default: 10
    array(
        'name' => 'perPage',
        'desc' => 'advsearch.advsearch_perPage_desc',
        'type' => 'numberfield',
        'options' => '',
        'value' => 10,
        'lexicon' => 'advsearch:properties',
    ),

	
// &placeholderPrefix - [ string | '' ] (optional)
// prefix of global placeholders
// Default: ''
    array(
        'name' => 'placeholderPrefix',
        'desc' => 'advsearch.advsearch_placeholderPrefix_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => '',
        'lexicon' => 'advsearch:properties',
    ),

	
// &queryHook - [ snippetName | '' ] (optional)
// queryHook to change the default query
// Default: ''
    array(
        'name' => 'queryHook',
        'desc' => 'advsearch.advsearch_queryHook_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => '',
        'lexicon' => 'advsearch:properties',
    ),
	
// &searchIndex - [ string | 'search' ] (optional)
// The name of the REQUEST parameter that the search will use.
// Default: 'search'
    array(
        'name' => 'searchIndex',
        'desc' => 'advsearch.advsearch_searchIndex_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'search',
        'lexicon' => 'advsearch:properties',
    ),

// &sortby - [ csv list of ' field [ DESC | ASC]'  | 'createdon DESC' ]  (optional)
// comma separated list of couple "field [ASC|DESC]" to sort by.
// field could be : field name or TV name. They should be defined in fields and/or withTVs
// DIR could be DESC or ASC. DESC by default
// e.g: 'tv1 ASC, pagetitle, longtitle ASC'
// Default: 'createdon DESC'
    array(
        'name' => 'sortby',
        'desc' => 'advsearch.advsearch_sortby_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'createdon DESC',
        'lexicon' => 'advsearch:properties',
    ),

// &showExtract - [ string | '1:content' ]  (optional)
// show the search terms highlighted in one or several extract
// string as n: csv list of fields
// n : maximum number of extracts displayed for each search result
// csv list of fields where to search terms to highlight
// Default: '1:content' - One extract displayed
    array(
        'name' => 'showExtract',
        'desc' => 'advsearch.advsearch_showExtract_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => '1:content',
        'lexicon' => 'advsearch:properties',
    ),

// &toPlaceholder - [ string | '' ] (optional)
// Whether to set the output to directly return, or set to a placeholder with this property name.
// Default: ''
    array(
        'name' => 'toPlaceholder',
        'desc' => 'advsearch.advsearchform_toPlaceholder_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => '',
        'lexicon' => 'advsearch:properties',
    ),

// &tpl - [ chunk name | 'AdvSearchResult' ]  (optional)
// The chunk that will be used to display the contents of each search result.
// Default: 'AdvSearch_Result'
    array(
        'name' => 'tpl',
        'desc' => 'advsearch.advsearch_tpl_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'AdvSearchResult',
        'lexicon' => 'advsearch:properties',
    ),

// &urlScheme - [ -1 | full | abs | http | https ]  (optional)
// indicates in what format the URL is generated.
// -1, full, abs, http, https
// Default: -1 (URL is relative to site_url)
    array(
        'name' => 'urlScheme',
        'desc' => 'advsearch.advsearch_urlScheme_desc',
        'type' => 'list',
        'options' => array(
            array('text' => 'relative to site_url','value' => -1),
            array('text' => 'prepended with site_url from config','value' => 'full'),
            array('text' => 'prepended with base_url from config','value' => 'abs'),
            array('text' => 'absolute url, forced to http scheme','value' => 'http'),
            array('text' => 'absolute url, forced to https scheme','value' => 'https')
        ),
        'value' => -1,
        'lexicon' => 'advsearch:properties',
    ),
	
// &withFields [csv list of fields | 'pagetitle,longtitle,alias,description,introtext,content' ] (optional)
// Define which fields are used for the search in fields of document resource.
// Default: 'pagetitle,longtitle,alias,description,introtext,content'
    array(
        'name' => 'withFields',
        'desc' => 'advsearch.advsearch_withFields_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'pagetitle,longtitle,alias,description,introtext,content',
        'lexicon' => 'advsearch:properties',
    ),

// &withTVs - [ a comma separated list of TV names | '' ]  (optional)
// Define which TVs are used for the search in TVs
// Default: '' - no TV used
    array(
        'name' => 'withTVs',
        'desc' => 'advsearch.advsearch_withTVs_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => '',
        'lexicon' => 'advsearch:properties',
    ),
);

return $properties;