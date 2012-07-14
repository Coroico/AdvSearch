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
 * @language   German
 * @author     M. Gartner | bitego
 */

/* advsearchform properties */
$_lang['advsearch.advsearchform_addCss_desc']            = 'Soll die von AdvSearch mitgelieferte CSS Datei automatisch eingebunden werden.';
$_lang['advsearch.advsearchform_addJQuery_desc']         = 'Soll die von AdvSearch mitgelieferte jQuery Bibliothek automatisch eingebunden werden. (vor dem schlie&szlig;enden HEAD oder BODY Tag).';
$_lang['advsearch.advsearchform_addJs_desc']             = 'Sollen die von AdvSearch mitgelieferten JavaScript Dateien automatisch eingebunden werden. (vor dem schlie&szlig;enden HEAD oder BODY Tag).';
$_lang['advsearch.advsearchform_ajaxResultsId_desc']     = 'Ajax Response Seite; leere Vorlage mit dem AdvSearch Snippet Aufruf.';
$_lang['advsearch.advsearchform_asId_desc']              = 'Eindeutige ID f&uuml; neue Instanzen von AdvSearch.';
$_lang['advsearch.advsearchform_clearDefault_desc']      = 'Stellen Sie diesen Eintrag auf 0, wenn Sie die automatische Clear Funktion f&uuml;r den standard Text im Suchfeld nicht verwenden m&ouml;chten.';
$_lang['advsearch.advsearchform_debug_desc']             = 'Debug';
$_lang['advsearch.advsearchform_help_desc']              = 'Hilfe Link im Suchformular anzeigen.';
$_lang['advsearch.advsearchform_jsSearchForm_desc']      = 'Pfad (URL) zum JavaScript Library, welches f&uuml; das Suchformular verwendet werden soll. (Feld Validierung, clearDefault, usw.)';
$_lang['advsearch.advsearchform_jsJQuery_desc']          = 'Pfad (URL) zum jQuery Library, welches f&uuml; AdvSearch verwendet werden soll.';
$_lang['advsearch.advsearchform_landing_desc']           = 'Resource, welche f&uuml;r die Anzeige der Suchergebnisse verwendet werden soll (muss AdvSearch Snippet Aufruf enthalten).';
$_lang['advsearch.advsearchform_liveSearch_desc']        = 'Live-Suche Modus. (nur im Ajax Modus)';
$_lang['advsearch.advsearchform_method_desc']            = 'HTTP REQUEST Parameters welchen AdvSearch verwendet.';
$_lang['advsearch.advsearchform_opacity_desc']           = 'Transparenz des Fensters f&uuml;r Suchergebnisse. [ 0. < float <= 1. ]';
$_lang['advsearch.advsearchform_searchIndex_desc']       = 'HTTP REQUEST Parameters welchen AdvSearch verwendet.';
$_lang['advsearch.advsearchform_toPlaceholder_desc']     = 'Soll die Ausgabe direkt zur&uuml;ck gegeben werden, oder ein Platzhalter mit diesem Namen verwendet werden.';
$_lang['advsearch.advsearchform_tpl_desc']               = 'Chunk, welcher f&uuml;r die Anzeige des Eingabe-Formulars verwendet wird.';
$_lang['advsearch.advsearchform_urlScheme_desc']         = 'Legt das Format f&uuml;r die generierte URL in den Suchergebnissen fest. (-1, full, abs, http, https)';
$_lang['advsearch.advsearchform_withAjax_desc']          = 'Suchergebnisse unter Verwendung von Ajax anzeigen.';

/* advsearch properties */
$_lang['advsearch.advsearch_asId_desc']                  = 'Eindeutige ID f&uuml; neue Instanzen von AdvSearch.';
$_lang['advsearch.advsearch_containerTpl_desc']          = 'Chunk, welcher als Wrapper f&uuml;r Such-Ergebnisse, Seitennummern und Nachrichten verwendet wird.';
$_lang['advsearch.advsearch_contexts_desc']              = 'Kommaseparierte Liste von Context-Namen in denen gesucht werden soll.';
$_lang['advsearch.advsearch_currentPageTpl_desc']        = 'Chunk, welcher f&uuml;r die Anzeige des aktuellen Seitennummern-Links verwendet wird.';
$_lang['advsearch.advsearch_debug_desc']                 = 'Debug Level.';
$_lang['advsearch.advsearch_docindexPath_desc']          = 'Pfad innerhalb von assets/files/ zur Ablage der Lucene Dokument-Indizes.';
$_lang['advsearch.advsearch_effect_desc']                = 'Name des Effekts f&uuml;r die Darstellung des Ergebnisfensters (Ajax Modus).';
$_lang['advsearch.advsearch_engine_desc']                = 'Verwendete Such-Engine.';
$_lang['advsearch.advsearch_extractEllipsis_desc']       = 'String der am Beginn und Ende von Textausz&uuml;gen verwendet wird.';
$_lang['advsearch.advsearch_extractLength_desc']         = 'L&auml;ge der Textausz&uuml;ge (zwischen 50 und 800 Zeichen).';
$_lang['advsearch.advsearch_extractTpl_desc']            = 'Chunk, welcher als Wrapper f&uuml;r die Anzeige von Textausz&uuml;gen verwendet wird.';
$_lang['advsearch.advsearch_fields_desc']                = 'Liste aller Resource-Felder, die f&uuml;r die Suche herangezogen werden.';
$_lang['advsearch.advsearch_fieldPotency_desc']          = 'Wertigkeit pro Feld f&uuml;r die Sortierung von Suchergebnissen. (Standardwert = 1 wenn nicht gesetzt)';
$_lang['advsearch.advsearch_highlightClass_desc']        = 'CSS Klasse f&uuml;r hervorgehobebne Suchbegriffe in Suchergebnissen.';
$_lang['advsearch.advsearch_highlightResults_desc']      = 'Sollen Suchbegriffe in den Suchergebnissen hervorgehoben werden.';
$_lang['advsearch.advsearch_highlightTag_desc']          = 'HTML Tag als Wrapper f&uuml;r hervorgehobene Suchbegriffe in Suchergebnissen.';
$_lang['advsearch.advsearch_ids_desc']                   = 'Kommaseparierte Liste von Resource-IDs auf die die Suche beschr&auml;nkt werden soll.';
$_lang['advsearch.advsearch_includeTVs_desc']            = 'TV Werte zu Suchergebnissen hinzuf&uuml;gen und diese als Platzhalter festlegen.';
$_lang['advsearch.advsearch_init_desc']                  = 'Legt fest ob die Suche alle Ergebnisse anzeigt oder keine, wenn die Seite zum ersten Mal geladen wird.';
$_lang['advsearch.advsearch_hideContainers_desc']        = 'Suche in Resoucen, die als Conainer markiert sind.';
$_lang['advsearch.advsearch_hideMenu_desc']              = 'Suche in Resoucen, die nicht in Men&uuml;s angezeigt werden sollen.';
$_lang['advsearch.advsearch_libraryPath_desc']           = 'Pfad innerhalb von assets/ zur Einbindung externer Bibliotheken.';
$_lang['advsearch.advsearch_maxWords_desc']              = 'Maximale Anzahl der Suchbegriffe';
$_lang['advsearch.advsearch_method_desc']                = 'HTTP REQUEST Parameters welchen AdvSearch verwendet.';
$_lang['advsearch.advsearch_minChars_desc']              = 'Minimale Anzahl der Zeichen f&uuml;r einen Suchbegriff.';
$_lang['advsearch.advsearch_moreResults_desc']           = 'ID der Resource f&uuml;r den Link "Weitere Suchergebnisse".';
$_lang['advsearch.advsearch_moreResultsTpl_desc']        = 'Chunk, welcher f&uuml;r die Anzeige des Links f&uuml;r "Weitere Suchergebnisse" verwendet wird.';
$_lang['advsearch.advsearch_offsetIndex_desc']           = 'HTTP REQUEST Parameter welcher f&uuml;r das Seitennummern-Offset verwendet wird.';
$_lang['advsearch.advsearch_output_desc']                = 'Ausgabe Typ.';
$_lang['advsearch.advsearch_pageTpl_desc']               = 'Chunk, welcher f&uuml;r die Seitennummern-Links verwendet wird.';
$_lang['advsearch.advsearch_pagingType_desc']            = 'Art der Seitennummerierung.';
$_lang['advsearch.advsearch_paging1Tpl_desc']            = 'Chunk, welcher f&uuml;r die Seitennummerierung Typ 1 verwendet wird.';
$_lang['advsearch.advsearch_paging2Tpl_desc']            = 'Chunk, welcher f&uuml;r die Seitennummerierung Typ 2 verwendet wird.';
$_lang['advsearch.advsearch_pagingSeparator_desc']       = 'String, welcher als Trenner f&uuml;r Seitennummern-Links verwendet wird. (nur bei pagingType0)';
$_lang['advsearch.advsearch_perPage_desc']               = 'Anzahl der Ergebnisse, die pro Seite angezeigt werden.';
$_lang['advsearch.advsearch_placeholderPrefix_desc']     = 'Pr&auml;fix f&uuml;r globale Platzhalter.';
$_lang['advsearch.advsearch_queryHook_desc']             = 'queryHook zur &Auml;nderung der standard Abfrage.';
$_lang['advsearch.advsearch_sortby_desc']                = 'Komma separierte Liste von Feldern nach welchen sortiert werden soll. (Schreibweise: "Feldname [ASC|DESC]")';
$_lang['advsearch.advsearch_showExtract_desc']           = 'Maximale Anzahl der Textausz&uuml;ge welche f&uuml;r Suchergebnisse angezeigt werden - gefolgt von der CSV Liste der Felder, worin Suchbegriffe hervorgehoben werden sollen.';
$_lang['advsearch.advsearch_searchIndex_desc']           = 'HTTP REQUEST Parameter welchen AdvSearch verwendet.';
$_lang['advsearch.advsearch_toPlaceholder_desc']         = 'Soll die Ausgabe direkt zur&uuml;ck gegeben werden, oder ein Platzhalter mit dem eingetragenen Namen verwendet werden.';
$_lang['advsearch.advsearch_tpl_desc']                   = 'Chunk, welcher f&uuml;r die Anzeige des Inhaltes von Suchergebnissen verwendet wird.';
$_lang['advsearch.advsearch_urlScheme_desc']             = 'Legt das Format f&uuml;r die generierte URL in den Suchergebnissen fest. (m&ouml;gliche Werte: -1, full, abs, http, https)';
$_lang['advsearch.advsearch_withFields_desc']            = 'Festlegen der Resource-Felder die f&uuml;r die Suche herangezogen werden sollen.';
$_lang['advsearch.advsearch_withTVs_desc']               = 'Festlegen der Template Variablen die f&uuml;r die Suche herangezogen werden sollen.';
