<?php
/**
 * AdvSearch
 *
 * Dynamic content search add-on that supports results highlighting and faceted searches.
 *
 * Use AdvSearch to display search results on a landing page
 *
 * @category    Third Party Component
 * @version     1.0.0 pl
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 *
 * @author      Coroico <coroico@wangba.fr>
 * @date        25/01/2012
 *
 * -----------------------------------------------------------------------------
 */

require_once $modx->getOption('advsearch.core_path',null,$modx->getOption('core_path').'components/advsearch/').'model/advsearch/advsearch.class.php';
if (!class_exists('AdvSearch')) {
    $this->modx->log(modX::LOG_LEVEL_ERROR,'[AdvSearch] AdvSearch class not found.');
    return false;
}

$as = $modx->getOption('asId',$scriptProperties,'as0') ? $scriptProperties['asId'] : 'as0';
$as = str_replace(' ', '',$as);
$$as = new AdvSearch($modx,$scriptProperties);

$output = $$as->output();

return $output;
?>