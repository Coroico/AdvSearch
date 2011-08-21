<?php
/**
 * AdvSearchForm
 *
 * Dynamic content search add-on that supports results highlighting and faceted searches.
 *
 * Use AdvSearchForm to display a filter & search form
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

require_once $modx->getOption('advsearch.core_path',null,$modx->getOption('core_path').'components/advsearch/').'model/advsearch/advsearchform.class.php';
if (!class_exists('AdvSearchForm')) {
    $this->modx->log(modX::LOG_LEVEL_ERROR,'[AdvSearch] AdvSearchForm class not found.');
    return false;
}

$asf = $modx->getOption('asId',$scriptProperties,'as0') ? $scriptProperties['asId'] : 'as0';
$asf = str_replace(' ', '',$asf) .'f';
$$asf = new AdvSearchForm($modx,$scriptProperties);

$output = $$asf->output();

return $output;
?>