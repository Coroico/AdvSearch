<?php

header('Content-Type: text/html; charset=utf-8');
ini_set('max_execution_time', 300);
error_reporting(-1);

define('MODX_API_MODE', true);
include ('../../../../index.php');

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
$output = json_encode(array(
    'success' => true,
    'message' => '',
    'total' => count($lstIds),
    'object' => $lstIds
        ));
die($output);
