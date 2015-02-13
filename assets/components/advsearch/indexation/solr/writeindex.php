<?php

header('Content-Type: text/html; charset=utf-8');
ini_set('max_execution_time', 900);
error_reporting(-1);

define('MODX_API_MODE', true);
include ('../../../../../index.php');
include MODX_CORE_PATH . 'config/config.inc.php';

try {
    $db = new PDO("$database_type:host=$database_server;dbname=$dbase;charset=$database_connection_charset", "$database_user", "$database_password");
} catch (PDOException $e) {
    $output = json_encode(array(
        'success' => false,
        'message' => $e->getMessage()
    ));
    die($output);
}

$output = '';
if (!isset($_GET['siteId']) || $_GET['siteId'] !== $modx->site_id) {
    $output = json_encode(array(
        'success' => false,
        'message' => 'Wrong Site\'s ID'
    ));
    die($output);
}

if (!file_exists(MODX_ASSETS_PATH . 'libraries/solarium/vendor/autoload.php')) {
    $output = json_encode(array(
        'success' => false,
        'message' => 'Missing: ' . MODX_ASSETS_PATH . 'libraries/solarium/vendor/autoload.php'
    ));
    die($output);
}
require_once MODX_ASSETS_PATH . 'libraries/solarium/vendor/autoload.php';
if (!file_exists(MODX_ASSETS_PATH . 'libraries/solarium/library/Solarium/Autoloader.php')) {
    $output = json_encode(array(
        'success' => false,
        'message' => 'Missing: ' . MODX_ASSETS_PATH . 'libraries/solarium/library/Solarium/Autoloader.php'
    ));
    die($output);
}
require_once MODX_ASSETS_PATH . 'libraries/solarium/library/Solarium/Autoloader.php';

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

$reset = isset($_GET['reset']) ? ($_GET['reset'] === 'false' ? false : true) : null;
if ($reset) {
    $db->exec("UPDATE `{$table_prefix}advsearch_indexation` SET `is_indexed` = NULL, `error` = NULL WHERE `engine` = 'solr'");
}
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
$start = isset($_GET['start']) ? intval($_GET['start']) : 0;
$loop = isset($_GET['loop']) ? ($_GET['loop'] === 'false' ? false : true) : null;

$sql = "SELECT COUNT(*) FROM `{$table_prefix}advsearch_indexation`"
        . " WHERE `engine` = 'solr' AND `is_indexed` IS NULL";
//        . " WHERE `engine` = 'solr' AND `is_indexed` IS NULL AND `error` IS NULL";
$stmtCount = $db->query($sql);
$row = $stmtCount->fetch(PDO::FETCH_NUM);
$total = $row[0];

$sql = "SELECT `doc_id` FROM `{$table_prefix}advsearch_indexation`"
//        . " WHERE `engine` = 'solr' AND `is_indexed` IS NULL"
        . " WHERE `engine` = 'solr' AND `is_indexed` IS NULL AND `error` IS NULL"
        . " ORDER BY `doc_id` ASC"
        . " LIMIT $start, $limit";
$stmt = $db->query($sql);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $lstIds[] = $row['doc_id'];
}
if (empty($lstIds)) {
    $msg = 'Error getting the result: ' . $sql;
    $output = json_encode(array(
        'success' => false,
        'message' => $msg
    ));
    die($output);
}

$c = $modx->newQuery('modResource');
$c->distinct();
$c->where(array(
    'searchable' => true,
    'deleted' => false,
    'published' => true,
    'id:IN' => $lstIds
));

//$total = $modx->getCount('modResource', $c);
//$c->limit($limit, $start);
$c->sortby('id', 'ASC');
$resources = $modx->getIterator('modResource', $c);

$includeTVs = isset($_GET['include_tvs']) ? ($_GET['include_tvs'] === 'false' ? false : true) : null;
$processTVs = isset($_GET['process_tvs']) ? ($_GET['process_tvs'] === 'false' ? false : true) : null;

$update = $client->createUpdate();

if ($reset) {
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
    $db->exec("UPDATE `{$table_prefix}advsearch_indexation` SET `is_indexed` = NULL, `error` = NULL WHERE `engine` = 'solr'");
}

$count = 0;
$docs = array();
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
    $count++;
}

// add the documents and a commit command to the update query
$update->addDocuments($docs, true);
$update->addCommit();
try {
    // this executes the query and returns the result
    $result = $client->update($update);
    if ($result) {
        foreach ($lstIds as $id) {
            $db->exec("UPDATE `{$table_prefix}advsearch_indexation` SET `is_indexed` = 1, `error` = NULL WHERE `doc_id` = $id");
        }
    }
    if (($total > $start + $limit) && $loop) {
        $nextStart = $start;
    } else {
        $nextStart = $total + 1; // just to prevent javascript
    }
    $output = json_encode(array(
        'success' => true,
        'message' => '<div>Update query executed - Query time: ' . $result->getQueryTime() . ' milliseconds</div>',
        'total' => $total,
        'nextStart' => $nextStart
    ));
} catch (Exception $e) {
    $msg = '<div>Error updating the data: ' . $e->getMessage() . '</div>';
    $errorContinue = isset($_GET['errorContinue']) ? ($_GET['errorContinue'] === 'false' ? false : true) : null;
    if ($errorContinue && ($total > $start + $limit) && $loop) {
        $nextStart = $start + 1;
    } else {
        $nextStart = $total + 1; // just to prevent javascript
    }
    $output = json_encode(array(
        'success' => false,
        'message' => $msg,
        'total' => $total,
        'nextStart' => $nextStart
    ));
    foreach ($lstIds as $id) {
        $db->exec("UPDATE `{$table_prefix}advsearch_indexation` SET `error` = '$msg' WHERE `doc_id` = $id");
    }
}

// close connection
$db = NULL;
die($output);
