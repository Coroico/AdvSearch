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

$reset = isset($_GET['reset']) ? ($_GET['reset'] === 'false' ? false : true) : null;
if ($reset) {
    $db->exec("UPDATE `{$table_prefix}advsearch_indexation` SET `is_indexed` = NULL, `error` = NULL WHERE `engine` = 'zend'");
}
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
$start = isset($_GET['start']) ? intval($_GET['start']) : 0;
$loop = isset($_GET['loop']) ? ($_GET['loop'] === 'false' ? false : true) : null;

$sql = "SELECT COUNT(*) FROM `{$table_prefix}advsearch_indexation`"
        . " WHERE `engine` = 'zend' AND `is_indexed` IS NULL";
//        . " WHERE `engine` = 'zend' AND `is_indexed` IS NULL AND `error` IS NULL";
$stmtCount = $db->query($sql);
$row = $stmtCount->fetch(PDO::FETCH_NUM);
$total = $row[0];

$sql = "SELECT `doc_id` FROM `{$table_prefix}advsearch_indexation`"
//        . " WHERE `engine` = 'zend' AND `is_indexed` IS NULL"
        . " WHERE `engine` = 'zend' AND `is_indexed` IS NULL AND `error` IS NULL"
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

if (empty($resources)) {
    $msg = '$resources empty.';
    $output = json_encode(array(
        'success' => false,
        'message' => $msg
    ));
    die($output);
}

// load the zend lucene library
$file = MODX_CORE_PATH . 'components/advsearch/libraries/ZendSearch/vendor/autoload.php';
if (!file_exists($file)) {
    $output = json_encode(array(
        'success' => false,
        'message' => 'Missing: ' . $file
    ));
    die($output);
}
require_once $file;

$dataPath = dirname(MODX_CORE_PATH) . '/zendresources/_files';

if ($reset) {
    @rmdir($dataPath);
}

try {
    $index = ZendSearch\Lucene\Lucene::create($dataPath);
} catch (Exception $e) {
    $msg = 'Error to create ZendSearch\'s index: ' . $e->getMessage();
    $output = json_encode(array(
        'success' => false,
        'message' => $msg
    ));
    die($output);
}

$includeTVs = isset($_GET['include_tvs']) ? ($_GET['include_tvs'] === 'false' ? false : true) : null;
$processTVs = isset($_GET['process_tvs']) ? ($_GET['process_tvs'] === 'false' ? false : true) : null;

$count = 0;
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
    $doc = new ZendSearch\Lucene\Document();

    foreach ($resourceArray as $k => $v) {
        switch($k) {
            case 'id':
                $doc->addField(ZendSearch\Lucene\Document\Field::Keyword('docid', $v)); // lucene has its own ID system
                break;
            case 'parent':
                $doc->addField(ZendSearch\Lucene\Document\Field::Keyword($k, $v));
                break;
            case 'pub_date':
            case 'unpub_date':
            case 'createdby':
            case 'createdon':
            case 'editedby':
            case 'editedon':
            case 'publishedon':
            case 'publishedby':
            case 'uri':
                $doc->addField(ZendSearch\Lucene\Document\Field::UnIndexed($k, $v));
                break;
            case 'type':
            case 'contentType':
            case 'alias':
            case 'link_attributes':
            case 'isfolder':
            case 'richtext':
            case 'template':
            case 'menuindex':
            case 'searchable':
            case 'cacheable':
            case 'donthit':
            case 'privateweb':
            case 'privatemgr':
            case 'content_dispo':
            case 'hidemenu':
            case 'class_key':
            case 'context_key':
            case 'content_type':
            case 'uri_override':
            case 'hide_children_in_tree':
            case 'show_in_tree':
                // ignore
                break;
            default:
                // other fields and template variables go to "Text"
                $doc->addField(ZendSearch\Lucene\Document\Field::Text($k, $v, 'utf-8'));
                break;
        }
    }

    try {
        $index->addDocument($doc);

        $db->exec("UPDATE `{$table_prefix}advsearch_indexation` SET `is_indexed` = 1, `error` = NULL WHERE `doc_id` = {$resourceArray['id']}");
    } catch (Exception $e) {
        $db->exec("UPDATE `{$table_prefix}advsearch_indexation` SET `error` = '{$e->getMessage()}' WHERE `doc_id` = {$resourceArray['id']}");
    }
    unset($doc);
    unset($modx->resource);
    $count++;
}

$index->optimize();
$index->commit();

if (($total > $start + $limit) && $loop) {
    $nextStart = $start;
} else {
    $nextStart = $total + 1; // just to prevent javascript
}
$output = json_encode(array(
    'success' => true,
    'message' => '<div>Indexing succeeded</div>',
    'total' => $total,
    'nextStart' => $nextStart
));

// close connection
$db = NULL;
die($output);
