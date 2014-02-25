<?php
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

include_once $advSearchCorePath . 'vendors/solarium/vendor/autoload.php';
include_once $advSearchCorePath . 'vendors/solarium/library/Solarium/Autoloader.php';

try {
    \Solarium\Autoloader::register();
    $client = new Solarium\Client($engineConfig);
} catch (Exception $e) {
    $msg = 'Error connecting to Solr server: ' . $e->getMessage();
    $modx->log(xPDO::LOG_LEVEL_ERROR, $msg);
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
 * @param id $parent
 * @return boolean
 */
function AdvSearchGetChildrenIds(&$modx, &$children, $parent) {
    $success = false;
    $kids = $modx->getCollection('modResource', array(
        'parent' => $parent,
    ));
    if (!empty($kids)) {
        /** @var modResource $kids */
        foreach ($kids as $kid) {
            $children[] = $kid->get('id');
            AdvSearchGetChildrenIds($modx, $children, $kid->get('id'));
        }
    }
    return $success;
}

/**
 * helper method for missing params in events
 *
 * @author splittingred <splittingred@gmail.com>
 * @param modX $modx
 * @param array $children
 * @param id $parent
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

$children = array();
foreach ($ids as $id) {
    AdvSearchGetChildrenIds($modx, $children, $id);
}

switch ($modx->event->name) {
    case 'OnDocFormSave':
    case 'OnDocPublished':
    case 'OnDocUnpublished':
    case 'OnDocUnPublished':
        $resourceArray = $scriptProperties['resource']->toArray();
        if (!in_array($resourceArray['id'], $children)) {
            return FALSE;
        }
        if ($resourceArray['searchable'] == 0 ||
                $resourceArray['deleted'] == 1 ||
                $resourceArray['published'] != 1
        ) {
            $action = 'delete';
        } else {
            $action = 'add';
            foreach ($_POST as $k => $v) {
                if (substr($k, 0, 2) == 'tv') {
                    $id = str_replace('tv', '', $k);
                    /** @var modTemplateVar $tv */
                    $tv = $modx->getObject('modTemplateVar', $id);
                    if ($tv) {
                        $tvValue = $tv->renderOutput($resource->get('id'));
                        if (is_array($tvValue)) {
                            $tvValue = implode('||', $tvValue);
                        }
                        $resourceArray[$tv->get('name')] = $tvValue;
                        $modx->log(modX::LOG_LEVEL_DEBUG, '[AdvSearch] Indexing ' . $tv->get('name') . ': ' . $resourceArray[$tv->get('name')]);
                    }
                    unset($resourceArray[$k]);
                }
            }
        }
        unset($resourceArray['ta'], $resourceArray['action'], $resourceArray['tiny_toggle'], $resourceArray['HTTP_MODAUTH'], $resourceArray['modx-ab-stay'], $resourceArray['resource_groups']);
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
    $doc = $update->createDocument();
    foreach ($resourceArray as $k => $v) {
        if ($action == 'delete') {
            $update->addDeleteById($resourceArray['id']);
            $update->addCommit();
            continue;
        }
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
    $docs[] = $doc;
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