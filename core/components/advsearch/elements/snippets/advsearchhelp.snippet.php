<?php
/**
 * AdvSearchHelp
 *
 * Dynamic content search add-on that supports results highlighting and faceted searches.
 *
 * Use AdvSearchHelp to display the help about Lucene. Language is context dependant
 *
 * @category    Third Party Component
 * @since       1.0.0 pl
 * @version     dev
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 *
 * @author      Coroico <coroico@wangba.fr>
 * @date        25/01/2012
 *
 * -----------------------------------------------------------------------------
 */

 // load help lexicon
$modx->lexicon->load('advsearch:help');

$output = $modx->lexicon('advsearch.help');
return $output;