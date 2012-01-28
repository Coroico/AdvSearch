<?php
/**
 * AdvSearch
 *
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
 * AdvSearch; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @package advsearch
 */
/**
 * Properties (property descriptions) Lexicon Topic
 *
 * @package advsearch
 * @subpackage lexicon
 */

/* advsearchform properties */
$_lang['advsearch.advsearchform_addCss_desc'] = 'If you would like to include or not the default css file in your pages automatically.';
$_lang['advsearch.advsearchform_addJQuery_desc'] = 'If you would like to include or not jQuery library in your pages automatically (Before closure of HEAD tag or before the closure of BODY tag).';
$_lang['advsearch.advsearchform_addJs_desc'] = 'If you would like to include or not the js scripts in your pages automatically (Before closure of HEAD tag or before the closure of BODY tag).';
$_lang['advsearch.advsearchform_ajaxResultsId_desc'] = 'Ajax response page; Blank template page with AdvSearch snippet call.';
$_lang['advsearch.advsearchform_asId_desc'] = 'Unique id for newSearch instance.';
$_lang['advsearch.advsearchform_clearDefault_desc'] = 'Set this to 0 if you wouldn\'t like the clear default text feature.';
$_lang['advsearch.advsearchform_debug_desc'] = 'Debug';
$_lang['advsearch.advsearchform_help_desc'] = 'Add a help link near the search form';
$_lang['advsearch.advsearchform_jsSearchForm_desc'] = 'Url where is located the js library used with the form (Fields validation, clearDefault, ...)';
$_lang['advsearch.advsearchform_jsJQuery_desc'] = 'Url where is located the jquery javascript library.';
$_lang['advsearch.advsearchform_landing_desc'] = 'The resource that the AdvSearch snippet is called on, that will display the results of the search';
$_lang['advsearch.advsearchform_liveSearch_desc'] = 'LiveSearch mode. (Only with the ajax mode)';
$_lang['advsearch.advsearchform_method_desc'] = 'The name of the REQUEST parameter that the search will use.';
$_lang['advsearch.advsearchform_opacity_desc'] = 'Opacity of the search results window. [ 0. < float <= 1. ]';
$_lang['advsearch.advsearchform_searchIndex_desc'] = 'The name of the REQUEST parameter that the search will use.';
$_lang['advsearch.advsearchform_toPlaceholder_desc'] = 'Whether to set the output to directly return, or set to a placeholder with this property name.';
$_lang['advsearch.advsearchform_tpl_desc'] = 'Chunk to style input form.';
$_lang['advsearch.advsearchform_urlScheme_desc'] = 'Indicates in what format the URL is generated. (-1, full, abs, http, https)';
$_lang['advsearch.advsearchform_withAjax_desc'] = 'Use this to display the search results using ajax.';

/* advsearch properties */
$_lang['advsearch.advsearch_asId_desc'] = 'Unique id for newSearch instance.';
$_lang['advsearch.advsearch_containerTpl_desc'] = 'The chunk that will be used to wrap all the search results, pagination and message.';
$_lang['advsearch.advsearch_contexts_desc'] = 'The contexts to search. Comma separated context names.';
$_lang['advsearch.advsearch_currentPageTpl_desc'] = 'The chunk to use for the current pagination link.';
$_lang['advsearch.advsearch_debug_desc'] = 'Debug level.';
$_lang['advsearch.advsearch_docindexPath_desc'] = 'The path under assets/files/ where are located Lucene document indexes.';
$_lang['advsearch.advsearch_effect_desc'] = 'Effect name to use to display the window of results (mode ajax).';
$_lang['advsearch.advsearch_engine_desc'] = 'Search engine selected.';
$_lang['advsearch.advsearch_extractEllipsis_desc'] = 'String used to mark the beginning and the end of an extract when the sentence is cutting.';
$_lang['advsearch.advsearch_extractLength_desc'] = 'Length of extract around the search words found - between 50 and 800 characters.';
$_lang['advsearch.advsearch_extractTpl_desc'] = 'The chunk that will be used to wrap each extract.';
$_lang['advsearch.advsearch_fields_desc'] = 'The list of fields from a resource available with search results.';
$_lang['advsearch.advsearch_fieldPotency_desc'] = 'Potency per field to score and sort results. Defaults to 1 if not set.';
$_lang['advsearch.advsearch_highlightClass_desc'] = 'The CSS class name to add to highlighted terms in results.';
$_lang['advsearch.advsearch_highlightResults_desc'] = 'Whether or not to highlight the search term in results.';
$_lang['advsearch.advsearch_highlightTag_desc'] = 'The html tag to wrap the highlighted term with in search results.';
$_lang['advsearch.advsearch_ids_desc'] = 'A comma-separated list of IDs to restrict the search to.';
$_lang['advsearch.advsearch_includeTVs_desc'] = 'Add TVs values to search results and set them as placeholders.';
$_lang['advsearch.advsearch_init_desc'] = 'Defines if the search display all the results or none when the page is loaded at the first time';
$_lang['advsearch.advsearch_hideContainers_desc'] = 'Search or not in any documents marked as a container.';
$_lang['advsearch.advsearch_hideMenu_desc'] = 'Search in documents regardless if they are visible from the menus.';
$_lang['advsearch.advsearch_libraryPath_desc'] = 'The path under assets/where are located external librairies.';
$_lang['advsearch.advsearch_maxWords_desc'] = 'Maximum number of words for searching';
$_lang['advsearch.advsearch_method_desc'] = 'The name of the REQUEST parameter that the search will use.';
$_lang['advsearch.advsearch_minChars_desc'] = 'Minimum number of characters to require for a word to be valid for searching.';
$_lang['advsearch.advsearch_moreResults_desc'] = 'The document id of the page you want the more results link to point to.';
$_lang['advsearch.advsearch_moreResultsTpl_desc'] = 'The chunk to use for the More results link.';
$_lang['advsearch.advsearch_offsetIndex_desc'] = 'The name of the REQUEST parameter to use for the pagination offset.';
$_lang['advsearch.advsearch_output_desc'] = 'Output type.';
$_lang['advsearch.advsearch_pageTpl_desc'] = 'The chunk to use for a pagination link.';
$_lang['advsearch.advsearch_pagingType_desc'] = 'Type of pagination.';
$_lang['advsearch.advsearch_paging1Tpl_desc'] = 'The chunk to use for a pagination type 1';
$_lang['advsearch.advsearch_paging2Tpl_desc'] = 'The chunk to use for a pagination type 2';
$_lang['advsearch.advsearch_pagingSeparator_desc'] = 'String as page number links separator. Used with pagingType0.';
$_lang['advsearch.advsearch_perPage_desc'] = 'The number of search results to show per page.';
$_lang['advsearch.advsearch_placeholderPrefix_desc'] = 'prefix of global placeholders.';
$_lang['advsearch.advsearch_queryHook_desc'] = 'queryHook to change the default query.';
$_lang['advsearch.advsearch_sortby_desc'] = 'comma separated list of couple "field [ASC|DESC]" to sort by.';
$_lang['advsearch.advsearch_showExtract_desc'] = 'Maximum number of extracts displayed for each search result followed by the csv list of fields where to search terms to highlight.';
$_lang['advsearch.advsearch_searchIndex_desc'] = 'The name of the REQUEST parameter that the search will use.';
$_lang['advsearch.advsearch_toPlaceholder_desc'] = 'Whether to set the output to directly return, or set to a placeholder with this property name.';
$_lang['advsearch.advsearch_tpl_desc'] = 'The chunk that will be used to display the content of each search result.';
$_lang['advsearch.advsearch_urlScheme_desc'] = 'Indicates in what format the URL is generated. (-1, full, abs, http, https)';
$_lang['advsearch.advsearch_withFields_desc'] = 'Define which fields are used for the search in fields of a resource';
$_lang['advsearch.advsearch_withTVs_desc'] = 'Define which TVs are used for the search in TVs.';

