<?php

/**
 * AdvSearchForm
 *
 * Dynamic content search add-on that supports results highlighting and faceted searches.
 *
 * Use AdvSearchForm to display a filter & search form
 *
 * @category    Third Party Component
 * @since       1.0.0 pl
 * @version     dev
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 *
 * @author      Coroico <coroico@wangba.fr>
 *              goldsky <goldsky@virtudraft.com>
 * @date        23/11/2013
 *
 * -----------------------------------------------------------------------------
 */

$asf = $modx->getOption('asId', $scriptProperties, 'as0') ? $scriptProperties['asId'] : 'as0';
$asf = str_replace(' ', '', $asf) . 'f';

$defaultAdvSearchCorePath = $modx->getOption('core_path') . 'components/advsearch/';
$advsearchCorePath = $modx->getOption('advsearch.core_path', null, $defaultAdvSearchCorePath);

try {
    $$asf = $modx->getService('advsearchform', 'AdvSearchForm', $advsearchCorePath . 'model/advsearch/', $scriptProperties);
} catch (Exception $e) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] ' .  $e->getMessage());
    return;
}

if (!($$asf instanceof AdvSearchForm)) {
    return;
}

$output = $$asf->output();

return $output;