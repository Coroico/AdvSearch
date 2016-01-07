<?php

/**
 * XXX:
 * ONLY ENABLE THIS WHEN THE SOLR SEARCH ENGINE <http://lucene.apache.org/solr/>
 * HAS BEEN INSTALLED AND RUNNING CORRECTLY ON YOUR SERVER!
 *
 * using {core_path}components/advsearch/model/solr/solrresource.class.php
 */

ini_set('max_execution_time', 900);

$as = $modx->getOption('asId', $scriptProperties, 'as0') ? $scriptProperties['asId'] : 'as0';
$as = str_replace(' ', '', $as);

$defaultAdvSearchCorePath = $modx->getOption('core_path') . 'components/advsearch/';
$advSearchCorePath = $modx->getOption('advsearch.core_path', null, $defaultAdvSearchCorePath);
try {
    $$as = $modx->getService('advsearch', 'AdvSearch', $advSearchCorePath . 'model/advsearch/');
} catch (Exception $e) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] ' . $e->getMessage());
    return;
}

$autocommit = $modx->getOption('advsearch.autocommit', $scriptProperties, false);
$engineConfigFile = $modx->getOption('advsearch.engineConfigFile', $scriptProperties, '[[++core_path]]components/advsearch/controllers/configs/advsearchsolrconfig.php');
$maxIterations = (integer) $modx->getOption('parser_max_iterations', null, 10);
$modx->getParser()->processElementTags('', $engineConfigFile, false, false, '[[', ']]', array(), $maxIterations);
$modx->getParser()->processElementTags('', $engineConfigFile, true, true, '[[', ']]', array(), $maxIterations);

$engineConfig = include $engineConfigFile;

try {
    $solrResource = $modx->getService('solrresource', 'SolrResource', $advSearchCorePath . 'model/solr/', array(
        'engineConfigFile' => $engineConfigFile
    ));
} catch (Exception $e) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] ' . $e->getMessage());
    return;
}

$ids = $modx->getOption('advsearch.ids', $scriptProperties, 0);
$ids = @explode(',', $ids);

switch ($modx->event->name) {
    case 'OnDocFormSave':
    case 'OnDocPublished':
    case 'OnDocUnpublished':
    case 'OnDocUnPublished':
        if (empty($id) || !is_numeric($id)) {
            return;
        }
        $resourceArray = $modx->getObject('modResource', $id)->toArray();
        $isDescendant = $solrResource->isDescendant($ids, $resourceArray['id']);
        if (!$isDescendant) {
            return FALSE;
        }
        if ($resourceArray['searchable'] == 0 ||
                $resourceArray['deleted'] == 1 ||
                $resourceArray['published'] != 1
        ) {
            $solrResource->removeIndex($resourceArray['id']);
        } else {
            $solrResource->addIndex($resourceArray['id']);
        }

        break;
    case 'OnResourceDuplicate':
        $resourceArray = $newResource->toArray();
        $children = $solrResource->getDescendants($resourceArray['id']);
        $solrResource->addIndex($children);

        break;
    case 'OnResourceDelete':
        $resourceArray = $resource->toArray();
        $solrResource->removeIndex($resourceArray['id']);
        $children = $solrResource->getDescendants($resourceArray['id']);
        $solrResource->removeIndex($children);

        break;
    case 'OnResourceUndelete':
        $resourceArray = $resource->toArray();
        $solrResource->addIndex($resourceArray['id']);
        $children = $solrResource->getDescendants($resourceArray['id']);
        $solrResource->addIndex($children);

        break;
}

return;
