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
$_lang['advsearch.advsearchform_addCss_desc'] = 'Si vous voulez inclure ou pas le fichier css par défaut dans vos pages automatiquement.';
$_lang['advsearch.advsearchform_addJQuery_desc'] = 'Si vous voulez inclure ou pas la librairie jQuery manuellement dans vos pages (Avant le tag de fermeture HEAD ou avant le tag de fermeture BODY).';
$_lang['advsearch.advsearchform_addJs_desc'] = 'Si vous voulez inclure ou pas les scripts js automatiquement dans vos pages (Avant le tag de fermeture HEAD ou avant le tag de fermeture BODY).';
$_lang['advsearch.advsearchform_ajaxResultsId_desc'] = 'Page de réponse Ajax; Template vide avec l\'appel du snippet AdvSearch.';
$_lang['advsearch.advsearchform_asId_desc'] = 'id unique pour une instance advsearch.';
$_lang['advsearch.advsearchform_clearDefault_desc'] = 'Mettre à 0 si vous ne voulez pas de la fonctionalité d\'effacement du texte par défaut.';
$_lang['advsearch.advsearchform_debug_desc'] = 'Debogueur';
$_lang['advsearch.advsearchform_help_desc'] = 'Ajoute le lien vers l\'aide à côté du formulaire de recherche.';
$_lang['advsearch.advsearchform_jsSearchForm_desc'] = 'Url où se trouve la librairie js utilisé avec le formulaire de recherche (Validation de champs, effacement du texte par défaut ...)';
$_lang['advsearch.advsearchform_jsJQuery_desc'] = 'Url où se trouve la librairie javascript JQuery.';
$_lang['advsearch.advsearchform_landing_desc'] = 'La resource utilisé par advSearch pour afficher les résultats de la recherche.';
$_lang['advsearch.advsearchform_liveSearch_desc'] = 'Mode LiveSearch. (Unique avec le mode ajax)';
$_lang['advsearch.advsearchform_method_desc'] = 'Le nom du paramètre utilisé par la recherche pour la requête HTTP (POST ou GET).';
$_lang['advsearch.advsearchform_opacity_desc'] = 'Opacité de la fenêtre de résultats de recherche. [ 0. < réel <= 1. ]';
$_lang['advsearch.advsearchform_searchIndex_desc'] = 'Le nom du paramètre de la requête utilisé pour le terme de recherche.';
$_lang['advsearch.advsearchform_toPlaceholder_desc'] = 'Pour afficher les résultats dans un placeholder avec ce nom de propriété.';
$_lang['advsearch.advsearchform_tpl_desc'] = 'Chunk utilisé pour définir le formulaire de recherche.';
$_lang['advsearch.advsearchform_urlScheme_desc'] = 'Indique dans quel formal l\'url est générée. (-1, full, abs, http, https)';
$_lang['advsearch.advsearchform_withAjax_desc'] = 'A utiliser pour afficher les résultats de la recherche via ajax.';

/* advsearch properties */
$_lang['advsearch.advsearch_asId_desc'] = 'Identifiant unique pour une instance AdvSearch.';
$_lang['advsearch.advsearch_containerTpl_desc'] = 'Le chunk qui sera utilisé pour englober les résultats, la pagination et le message nombre de résultats trouvés.';
$_lang['advsearch.advsearch_contexts_desc'] = 'La liste des contextes dans lesquels doit se faire la recherche.';
$_lang['advsearch.advsearch_currentPageTpl_desc'] = 'Le chunk a utiliser pour le lien de pagination: page courante.';
$_lang['advsearch.advsearch_debug_desc'] = 'Niveau de debug.';
$_lang['advsearch.advsearch_docindexPath_desc'] = 'Le chemin sous assets/files/ ou se situe l\'index lucene.';
$_lang['advsearch.advsearch_effect_desc'] = 'Nom de l\'effet à utiliser pour afficher la fenêtre de resultats (mode ajax).';
$_lang['advsearch.advsearch_engine_desc'] = 'Moteur de recherche sélectionné.';
$_lang['advsearch.advsearch_extractEllipsis_desc'] = 'Chaine de caractères utilisée pour marquer le début et la fin d\'un extrait lorsque le texte est coupé.';
$_lang['advsearch.advsearch_extractLength_desc'] = 'Longueur de l\'extrait autour des termes de recherche trouvés. Entre 50 et 800 caractères.';
$_lang['advsearch.advsearch_extractTpl_desc'] = 'Le chunk a utiliser pour encadrer chaque extrait.';
$_lang['advsearch.advsearch_fields_desc'] = 'La liste des champs d\'une ressource disponible avec les résultats de recherche.';
$_lang['advsearch.advsearch_fieldPotency_desc'] = 'Poids par champs pour évaluer et trier les résultats de recherche. Poids de valeur 1 par defaut.';
$_lang['advsearch.advsearch_highlightClass_desc'] = 'Le nom de la classe CSS a utiliser pour mettre en valeur les termes de recherche trouvés dans les résultats.';
$_lang['advsearch.advsearch_highlightResults_desc'] = 'Mise en valeur ou non des termes de recherche dans les résultats de recherche.';
$_lang['advsearch.advsearch_highlightTag_desc'] = 'Le tag html a utiliser pour mettre en valeur le terme de recherche trouvé dans les résultats. Par défaut "span"';
$_lang['advsearch.advsearch_ids_desc'] = 'La liste des IDs de documents, séparés par des virgules, où limiter la recherche.';
$_lang['advsearch.advsearch_includeTVs_desc'] = 'La liste des TVs à ajouter en tant que champ de résultats de recherche et en tant que placeholders.';
$_lang['advsearch.advsearch_init_desc'] = 'Défini si tous les résultats sont ou non affichés lorsque la page de résultats est chargée la première fois.';
$_lang['advsearch.advsearch_hideContainers_desc'] = 'Defini si la recherche a lieu ou non dans les documents de type containeur.';
$_lang['advsearch.advsearch_hideMenu_desc'] = 'Recherche ou non dans les documents visible des menus.';
$_lang['advsearch.advsearch_libraryPath_desc'] = 'Le chemin sous assets/ où sont situées les bibliothèques externes.';
$_lang['advsearch.advsearch_maxWords_desc'] = 'Nombre maximum de caratères pour les termes de recherches.';
$_lang['advsearch.advsearch_method_desc'] = 'Nom de la méthode HTTP REQUEST a utiliser pour la recherche. GET ou POST.';
$_lang['advsearch.advsearch_minChars_desc'] = 'Nombre minimum de caractères requis pour un terme de recherche.';
$_lang['advsearch.advsearch_moreResults_desc'] = 'Id du document, page de résultats, pointé par lien "more results".';
$_lang['advsearch.advsearch_moreResultsTpl_desc'] = 'Le chunk à utiliser pour le lien "Plus de résultats".';
$_lang['advsearch.advsearch_offsetIndex_desc'] = 'Nom du paramètre HTTP REQUEST à utiliser pour l\'offset de pagination.';
$_lang['advsearch.advsearch_output_desc'] = 'Type de sortie.';
$_lang['advsearch.advsearch_pageTpl_desc'] = 'Le chunk a utiliser pour le lien de pagination.';
$_lang['advsearch.advsearch_pagingType_desc'] = 'Type de pagination.';
$_lang['advsearch.advsearch_paging1Tpl_desc'] = 'Le chunk a utiliser pour la pagination de type 1.';
$_lang['advsearch.advsearch_paging2Tpl_desc'] = 'Le chunk a utiliser pour la pagination de type 2.';
$_lang['advsearch.advsearch_pagingSeparator_desc'] = 'Chaine de caractères utilisée comme séparateur dans la liste de pages. Utilisé par le type de pagination 2.';
$_lang['advsearch.advsearch_perPage_desc'] = 'Nombre de resultats de recherche à afficher par page.';
$_lang['advsearch.advsearch_placeholderPrefix_desc'] = 'Prefix pour les placeholders.';
$_lang['advsearch.advsearch_queryHook_desc'] = 'Nom du snippet de type QueryHook. Permet de modifier la requête de recherche par défaut.';
$_lang['advsearch.advsearch_sortby_desc'] = 'Liste de couple "champs [ASC|DESC]", séparés par une virgule. Défini le critère de tri.';
$_lang['advsearch.advsearch_showExtract_desc'] = 'Nombre maximum d\'extraits par resultats de recherche, suivi de la liste des champs, séparés par une virgule, dans lesquels sont recherchés les termes à mettre en valeur.';
$_lang['advsearch.advsearch_searchIndex_desc'] = 'Nom du paramètre HTTP REQUEST à utiliser pour la soumission de la recherche.';
$_lang['advsearch.advsearch_toPlaceholder_desc'] = 'Retourne directement le résultat de la recherche ou le place dans le placeholder indiqué.';
$_lang['advsearch.advsearch_tpl_desc'] = 'Le chunk a utiliser pour l\'affichage du contenu de chaque résultat de recherche.';
$_lang['advsearch.advsearch_urlScheme_desc'] = 'Indique dans quel format l\'URL est générée. (-1, full, abs, http, https)';
$_lang['advsearch.advsearch_withFields_desc'] = 'Défini dans quels champs du document, la recherche doit se faire.';
$_lang['advsearch.advsearch_withTVs_desc'] = 'Défini dans quelles TVs du document, la recherche doit se faire.';