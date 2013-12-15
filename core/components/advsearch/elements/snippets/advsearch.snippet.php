<?php

/**
 * AdvSearch
 *
 * Dynamic content search add-on that supports results highlighting and faceted searches.
 *
 * Use AdvSearch to display search results on a landing page
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
$scriptProperties['contexts'] = $modx->getOption('contexts', $scriptProperties, $modx->context->key);
$scriptProperties['fields'] = $modx->getOption('fields', $scriptProperties, 'pagetitle,longtitle,alias,description,introtext,content');

$as = $modx->getOption('asId', $scriptProperties, 'as0') ? $scriptProperties['asId'] : 'as0';
$as = str_replace(' ', '', $as);

$defaultAdvSearchCorePath = $modx->getOption('core_path') . 'components/advsearch/';
$advSearchCorePath = $modx->getOption('advsearch.core_path', null, $defaultAdvSearchCorePath);

try {
    $$as = $modx->getService('advsearch', 'AdvSearch', $advSearchCorePath . 'model/advsearch/', $scriptProperties);
} catch (Exception $e) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] ' .  $e->getMessage());
    return;
}

if (!($$as instanceof AdvSearch)) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] AdvSearch class was not found.');
    return false;
}

$output = $$as->output();

return $output;
