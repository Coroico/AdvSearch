<?php

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

if (!file_exists(MODX_ASSETS_PATH . 'libraries/solarium/vendor/autoload.php')) {
    $msg = 'Missing: ' . MODX_ASSETS_PATH . 'libraries/solarium/vendor/autoload.php';
    $modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] ' . $msg);
    return;
}
require_once MODX_ASSETS_PATH . 'libraries/solarium/vendor/autoload.php';

if (!file_exists(MODX_ASSETS_PATH . 'libraries/solarium/library/Solarium/Autoloader.php')) {
    $msg = 'Missing: ' . MODX_ASSETS_PATH . 'libraries/solarium/library/Solarium/Autoloader.php';
    $modx->log(modX::LOG_LEVEL_ERROR, '[AdvSearch] ' . $msg);
    return;
}
require_once $$as->config['libraryPath'] . 'solarium/library/Solarium/Autoloader.php';

try {
    \Solarium\Autoloader::register();
    $client = new Solarium\Client($engineConfig);
} catch (Exception $e) {
    $msg = 'Error connecting to Solr server: ' . $e->getMessage();
    $modx->log(modX::LOG_LEVEL_ERROR, $msg);
    return;
}
// get an update query instance
$update = $client->createUpdate();

$action = 'add';
$resourcesToIndex = array();

$ids = $modx->getOption('advsearch.ids', $scriptProperties, 0);
$ids = @explode(',', $ids);

/**
 * helper method for missing params in events
 *
 * @author splittingred <splittingred@gmail.com>
 * @param modX $modx
 * @param array $children
 * @param int $parent
 * @return boolean
 */
if (!function_exists('SimpleSearchGetChildren')) {

    function SimpleSearchGetChildren(&$modx, &$children, $parent) {
        $success = false;
        $kids = $modx->getCollection('modResource', array(
            'parent' => $parent,
        ));
        if (!empty($kids)) {
            /** @var modResource $kid */
            foreach ($kids as $kid) {
                $children[] = $kid->toArray();
                SimpleSearchGetChildren($modx, $children, $kid->get('id'));
            }
        }
        return $success;
    }

}

function isDescendant(modX $modx, $rootIds, $resourceId) {
    if (empty($rootIds) || empty($resourceId)) {
        return FALSE;
    }
    if (!is_array($rootIds)) {
        $rootIds = array_map('trim', @explode(',', $rootIds));
    }
    if (in_array($resourceId, $rootIds)) {
        return TRUE;
    }
    $parentId = $modx->getObject('modResource', $resourceId)->get('parent');
    if (in_array($parentId, $rootIds)) {
        return TRUE;
    }
    return isDescendant($modx, $rootIds, $parentId);
}

switch ($modx->event->name) {
    case 'OnDocFormSave':
    case 'OnDocPublished':
    case 'OnDocUnpublished':
    case 'OnDocUnPublished':
        if (empty($id) || !is_numeric($id)) {
            return;
        }
        $resourceArray = $modx->getObject('modResource', $id)->toArray();
        if (!isDescendant($modx, $ids, $resourceArray['id'])) {
            return FALSE;
        }
        if ($resourceArray['searchable'] == 0 ||
                $resourceArray['deleted'] == 1 ||
                $resourceArray['published'] != 1
        ) {
            $action = 'delete';
        } else {
            $action = 'add';
            $tvs = $scriptProperties['resource']->getTemplateVars();
            foreach ($tvs as $tv) {
                $tvValue = $tv->renderOutput($resource->get('id'));
                if (is_array($tvValue)) {
                    $tvValue = implode('||', $tvValue);
                }
                $resourceArray[$tv->get('name')] = $tvValue;
                $modx->log(modX::LOG_LEVEL_DEBUG, '[AdvSearch] Indexing ' . $tv->get('name') . ': ' . $resourceArray[$tv->get('name')]);
            }
        }
        $resourcesToIndex[] = $resourceArray;
        
        break;
    case 'OnResourceDuplicate':
        $action = 'add';
        /** @var modResource $newResource */
        $resourcesToIndex[] = $newResource->toArray();
        $children = array();
        SimpleSearchGetChildren($modx, $children, $newResource->get('id'));
        foreach ($children as $child) {
            $resourcesToIndex[] = $child;
        }

        break;
    case 'OnResourceDelete':
        $action = 'delete';
        $resourcesToIndex[] = $resource->toArray();
        $children = array();
        SimpleSearchGetChildren($modx, $children, $resource->get('id'));
        foreach ($children as $child) {
            $resourcesToIndex[] = $child;
        }

        break;
    case 'OnResourceUndelete':
        $action = 'add';
        $resourcesToIndex[] = $resource->toArray();
        $children = array();
        SimpleSearchGetChildren($modx, $children, $resource->get('id'));
        foreach ($children as $child) {
            $resourcesToIndex[] = $child;
        }

        break;
}
if (empty($resourcesToIndex)) {
    return;
}

$docs = array();
foreach ($resourcesToIndex as $resourceArray) {
    if (empty($resourceArray['id'])) {
        continue;
    }
    // revert back the properties field into json form.
    if (isset($resourceArray['properties']) && !empty($resourceArray['properties'])) {
        $resourceArray['properties'] = json_encode($resourceArray['properties']);
    }

    // create a new document for the data
    if ($action == 'add') {
        $doc = $update->createDocument();
    }
    foreach ($resourceArray as $k => $v) {
        if ($action == 'delete') {
            $update->addDeleteById($resourceArray['id']);
            $update->addCommit();
        } elseif ($action == 'add') {
            if ($k === 'createdon' ||
                    $k === 'editedon' ||
                    $k === 'deletedon' ||
                    $k === 'publishedon' ||
                    $k === 'pub_date' ||
                    $k === 'unpub_date'
            ) {
                if ($v == 0) {
                    $v = '0000-00-00T00:00:00Z';
                } else {
                    $matches = null;
                    preg_match('/(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)/', $v, $matches);
                    if (!empty($matches)) {
                        $v = $matches[1] . '-' . $matches[2] . '-' . $matches[3] . 'T' . $matches[4] . ':' . $matches[5] . ':' . $matches[6] . 'Z';
                    }
                }
            }

            $doc->$k = $v;
        }
    }
    if ($action == 'add') {
        $docs[] = $doc;
    }
}

if (!empty($docs)) {
    if ($action == 'add') {
        $update->addDocuments($docs);
        $update->addCommit();
    }
    $result = $client->update($update);
    $modx->log(modX::LOG_LEVEL_DEBUG, '[AdvSearch] Update query executed. Query status: ' . $result->getStatus() . '. Query time: ' . $result->getQueryTime() . ' milliseconds');

}
return;