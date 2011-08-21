<?php
/**
 * AdvSearchHelp
 *
 * Dynamic content search add-on that supports results highlighting and faceted searches.
 *
 * Use AdvSearchHelp to display the help about Lucene. Language is context dependant
 *
 * @category    Third Party Component
 * @version     1.0.0 RC2
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 *
 * @author      Coroico <coroico@wangba.fr>
 * @date        14/08/2011
 *
 * -----------------------------------------------------------------------------
 */

 // load help lexicon
$modx->lexicon->load('advsearch:help');

$output = $modx->lexicon('advsearch.help');
return $output;
?>