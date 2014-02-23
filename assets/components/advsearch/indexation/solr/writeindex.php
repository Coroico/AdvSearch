<?php

header('Content-Type: text/html; charset=utf-8');
ini_set('max_execution_time', 900);
error_reporting(-1);

define('MODX_API_MODE', true);
include ('../../../../../index.php');

$output = '';
if (!isset($_GET['siteId']) || $_GET['siteId'] !== $modx->site_id) {
    $output = json_encode(array(
        'success' => false,
        'message' => 'Wrong Site\'s ID'
    ));
    die($output);
}

if (!isset($_GET['ids'])) {
    $output = json_encode(array(
        'success' => false,
        'message' => 'IDs parameter is required'
    ));
    die($output);
}

if (!isset($_GET['ids'])) {
    $output = json_encode(array(
        'success' => false,
        'message' => 'IDs parameter is required'
    ));
    die($output);
}

$checkSnippet = $modx->getObject('modSnippet', array('name' => 'GetIds'));
if (!$checkSnippet) {
    $output = json_encode(array(
        'success' => false,
        'message' => 'Please install GetIds snippet first!'
    ));
    die($output);
}

$lstIds = $modx->runSnippet('GetIds', array(
    'ids' => $_GET['ids'],
        ));
$lstIds = @explode(',', $lstIds);

include_once MODX_CORE_PATH . 'components/advsearch/vendors/solarium/vendor/autoload.php';
require_once MODX_CORE_PATH . 'components/advsearch/vendors/solarium/library/Solarium/Autoloader.php';

if (!empty($_GET['config_file'])) {
    $maxIterations = (integer) $modx->getOption('parser_max_iterations', null, 10);
    $configFile = $_GET['config_file'];
    $modx->getParser()->processElementTags('', $configFile, false, false, '[[', ']]', array(), $maxIterations);
    $modx->getParser()->processElementTags('', $configFile, true, true, '[[', ']]', array(), $maxIterations);
    if (is_file($configFile)) {
        $config = include $configFile;
    } else {
        $output = json_encode(array(
            'success' => false,
            'message' => $configFile . ' is not a real file.'
        ));
        die($output);
    }
} else {
    $config = include MODX_CORE_PATH . 'components/advsearch/configs/advsearchsolrconfig.php';
}

try {
    \Solarium\Autoloader::register();
    // create a client instance
    $client = new Solarium\Client($config);
} catch (Exception $e) {
    $msg = 'Error connecting to Solr server: ' . $e->getMessage();
    $output = json_encode(array(
        'success' => false,
        'message' => $msg
    ));
    die($output);
}

if (!$client) {
    $output = json_encode(array(
        'success' => false,
        'message' => 'Failed to load Solarium instance.'
    ));
    die($output);
}

$update = $client->createUpdate();

// add the delete query and a commit command to the update query
$update->addDeleteQuery('id:*');
$update->addCommit();

try {
    // this executes the query and returns the result
    $result = $client->update($update);
} catch (Exception $e) {
    $msg = 'Error connecting to Solr server: ' . $e->getMessage();
    $output = json_encode(array(
        'success' => false,
        'message' => $msg
    ));
    die($output);
}

$docs = array();

$c = $modx->newQuery('modResource');
$c->where(array(
    'searchable' => true,
    'deleted' => false,
    'published' => true,
    'id:IN' => $lstIds
));
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : null;
$c->limit($limit);
$c->sortby('id', 'ASC');
$resources = $modx->getIterator('modResource', $c);

$includeTVs = isset($_GET['include_tvs']) ? (boolean) $_GET['include_tvs'] : null;
$processTVs = isset($_GET['process_tvs']) ? (boolean) $_GET['process_tvs'] : null;
$total = 0;
foreach ($resources as $resource) {
    $resourceArray = $resource->toArray();
    $templateVars = & $resource->getMany('TemplateVars');
    if (!empty($templateVars) && $includeTVs) {
        foreach ($templateVars as $tvId => $templateVar) {
            $resourceArray[$templateVar->get('name')] = !empty($processTVs) ? $templateVar->renderOutput($resource->get('id')) : $templateVar->get('value');
        }
    }

    // revert back the properties field into json form.
    if (isset($resourceArray['properties']) && !empty($resourceArray['properties'])) {
        $resourceArray['properties'] = json_encode($resourceArray['properties']);
    }

    // create a new document for the data
    $doc = $update->createDocument();
    foreach ($resourceArray as $k => $v) {
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
    $total++;
}

// add the documents and a commit command to the update query
$update->addDocuments($docs);
$update->addCommit();

try {
    // this executes the query and returns the result
    $result = $client->update($update);
} catch (Exception $e) {
    $msg = 'Error updating the data: ' . $e->getMessage();
    $output = json_encode(array(
        'success' => false,
        'message' => $msg
    ));
    die($output);
}

$output = json_encode(array(
    'success' => true,
    'message' => 'Update query executed<br>Query time: ' . $result->getQueryTime() . ' milliseconds',
    'total' => $total,
    'object' => ''
        ));
die($output);