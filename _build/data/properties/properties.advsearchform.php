<?php

/**
 * Default properties for the AdvSearchForm snippet
 * @author Coroico <coroico@wangba.fr>
 * 14/08/2011
 *
 * @package advsearch
 * @subpackage build
 */

global $modx;

$properties = array(

// &addJQuery - [1 | 0]  (optional - ajax mode)
// Set this to 1 if you would like to include or not the jQuery library in the header of your pages automatically
// Default: 1
    array(
        'name' => 'addJQuery',
        'desc' => 'advsearch.advsearchform_addJQuery_desc',
        'type' => 'list',
        'options' => array(
            array('text' => 'No','value' => 0),
            array('text' => 'Yes','value' => 1),
        ),
        'value' => 1,
        'lexicon' => 'advsearch:properties',
    ),

// &ajaxResultsId - [ int id of a document ] (mandatory with ajax mode)
// Ajax response page; blank template page with AdvSearch snippet call
// 0 when not set - Used by ajax mode
    array(
        'name' => 'ajaxResultsId',
        'desc' => 'advsearch.advsearchform_ajaxResultsId_desc',
        'type' => 'numberfield',
        'options' => '',
        'value' => 0,
        'lexicon' => 'advsearch:properties',
    ),
    
// &asId - [Unique id for newSearch instance | 'advsea' ]  (optional)
// this allows to distinguish several newSearch instances on the same page
// Any combination of characters a-z, underscores, and numbers 0-9
// This is case sensitive. Default = 'as0'
    array(
        'name' => 'asId',
        'desc' => 'advsearch.advsearchform_asId_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'as0',
        'lexicon' => 'advsearch:properties',
    ),

// &clearDefault - [ 1 | 0 ]  (optional)
// Clearing default text
// Set this to 0 if you wouldn't like the clear default text feature
// Default: 1
    array(
        'name' => 'clearDefault',
        'desc' => 'advsearch.advsearchform_clearDefault_desc',
        'type' => 'list',
        'options' => array(
            array('text' => 'No','value' => 0),
            array('text' => 'Yes','value' => 1),
        ),
        'value' => 1,
        'lexicon' => 'advsearch:properties',
    ),

// &debug - [ 0 | 1 ]  (optional)
// Output logged into Modx log
// Default: 0 - no logs
    array(
        'name' => 'debug',
        'desc' => 'advsearch.advsearchform_debug_desc',
        'type' => 'list',
        'options' => array(
            array('text' => 'No','value' => 0),
            array('text' => 'Yes','value' => 1),
        ),
        'value' => 0,
        'lexicon' => 'advsearch:properties',
    ),

// &help - [ 0 | 1 ]  (optional)
// to add a help link near the search form
// Default: 1 - help link displayed
    array(
        'name' => 'help',
        'desc' => 'advsearch.advsearchform_help_desc',
        'type' => 'list',
        'options' => array(
            array('text' => 'No','value' => 0),
            array('text' => 'Yes','value' => 1),
        ),
        'value' => 1,
        'lexicon' => 'advsearch:properties',
    ),
    
// &jsSearchForm - [ url | 'assets/components/advsearch/js/advsearchform.min.js' ]  (optional)
// Url (under assets/) where is located the js library used with the form (Fields validation, clearDefault, ...)
// Default: 'assets/components/advsearch/js/advsearchform.min.js'
    array(
        'name' => 'jsSearchForm',
        'desc' => 'advsearch.advsearchform_jsSearchForm_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'assets/components/advsearch/js/advsearchform.min.js',
        'lexicon' => 'advsearch:properties',
    ),

// &jsJQuery - [ Location of the jQuery javascript library ]
// Url where is located the jquery javascript library
// Default: 'assets/components/advsearch/js/jquery-1.5.1.min.js'
    array(
        'name' => 'jsJQuery',
        'desc' => 'advsearch.advsearchform_jsJQuery_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'assets/components/advsearch/js/jquery-1.5.1.min.js',
        'lexicon' => 'advsearch:properties',
    ),

// &landing - [ int id of a document ] (optional)
// The resource that the AdvSearch snippet is called on, that will display the results of the search
// Default: id of the current document. Used by non-ajax mode
    array(
        'name' => 'landing',
        'desc' => 'advsearch.advsearchform_landing_desc',
        'type' => 'numberfield',
        'options' => '',
        'value' => 0,
        'lexicon' => 'advsearch:properties',
    ),
    
// $liveSearch - [ 1 | 0 ] (optional - ajax mode)
// Set this to 1 if you would like to use the live search (i.e. results as you type)
// Default: 0 - livesearch mode inactivated
    array(
        'name' => 'liveSearch',
        'desc' => 'advsearch.advsearchform_liveSearch_desc',
        'type' => 'list',
        'options' => array(
            array('text' => 'No','value' => 0),
            array('text' => 'Yes','value' => 1),
        ),
        'value' => 0,
        'lexicon' => 'advsearch:properties',
    ),

// &method - [ 'POST' | 'GET' ] (optional)
// Whether to send the search over POST or GET.
// Default: GET
    array(
        'name' => 'method',
        'desc' => 'advsearch.advsearchform_method_desc',
        'type' => 'list',
        'options' => array(
            array('text' => 'POST','value' => 'POST'),
            array('text' => 'GET','value' => 'GET'),
        ),
        'value' => 'GET',
        'lexicon' => 'advsearch:properties',
    ),

// &opacity - [ 0. < float <= 1. ]  Should be a float value  (optional - mode ajax)
// set the opacity of the div results 
// Default: 1.
    array(
        'name' => 'opacity',
        'desc' => 'advsearch.advsearchform_opacity_desc',
        'type' => 'numberfield',
        'options' => '',
        'value' => 1.,
        'lexicon' => 'advsearch:properties',
    ),
    
// &searchIndex - [ string | 'search' ] (optional)
// The name of the REQUEST parameter that the search will use.
// Default: 'search'
    array(
        'name' => 'searchIndex',
        'desc' => 'advsearch.advsearchform_searchIndex_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'search',
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

// &tpl - [ chunk name | 'SearchForm' ]  (optional)
// Chunk to style search form
// Default: 'AdvSearchForm'
    array(
        'name' => 'tpl',
        'desc' => 'advsearch.advsearchform_tpl_desc',
        'type' => 'textfield',
        'options' => '',
        'value' => 'AdvSearchForm',
        'lexicon' => 'advsearch:properties',
    ),

// &withAjax - [ 1 | 0 ]  (optional)
// Use this to display the search results using ajax You must include the jquery library in your template
// Default: 0 - ajax mode unselected
    array(
        'name' => 'withAjax',
        'desc' => 'advsearch.advsearchform_withAjax_desc',
        'type' => 'list',
        'options' => array(
            array('text' => 'Non-ajax mode','value' => 0),
            array('text' => 'Ajax mode','value' => 1),
        ),
        'value' => 0,
        'lexicon' => 'advsearch:properties',
    ),

);

return $properties;