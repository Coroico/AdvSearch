<?php

header('Content-Type: text/html; charset=utf-8');
ini_set('max_execution_time', 900);
error_reporting(-1);

$mtime = microtime();
$mtime = explode(' ', $mtime);
$mtime = $mtime[1] + $mtime[0];
$tstart = $mtime;
    
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
$preRecordIds = isset($_GET['preRecordIds']) ? trim($_GET['preRecordIds']) : '';
if ($preRecordIds === 'truncate') {
    $db->exec("TRUNCATE `modx_advsearch_indexation`");
} else if ($preRecordIds === 'reset') {
    $db->exec("UPDATE modx_advsearch_indexation SET is_indexed = NULL");
}
if (!empty($lstIds)) {
    foreach ($lstIds as $id) {
        $check = $db->prepare("SELECT * FROM modx_advsearch_indexation WHERE doc_id = :id LIMIT 1");
        $check->bindValue(':id', $id);
        $check->execute();
        $check = $check->fetch(PDO::FETCH_ASSOC);
        if (empty($check)) {
            $db->exec("INSERT INTO modx_advsearch_indexation(doc_id, engine) VALUES ('$id', 'solr')");
        }
    }
}
// close connection
$db = NULL;

$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$tend = $mtime;
$totalTime = ($tend - $tstart);
$totalTime = sprintf("%2.4f s", $totalTime);

$output = json_encode(array(
    'success' => true,
    'message' => '<div>Update query executed - Query time: ' . $totalTime . ' milliseconds</div>',
    'total' => count($lstIds),
    'nextStart' => false
        ));
die($output);
