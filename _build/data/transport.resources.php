<?php
/**
 * Resource objects for the AdvSearch package
 * @author Coroico <coroico@wangba.fr>
 * 28/11/2012
 *
 * @package advsearch
 * @subpackage build
 */

$resources = array();

$modx->log(modX::LOG_LEVEL_INFO,'Packaging resource: AdvSearchHelp handler<br />');
$resources[1]= $modx->newObject('modResource');
$resources[1]->fromArray(array(
    'id' => 1,
    'class_key' => 'modResource',
    'context_key' => 'web',
    'type' => 'document',
    'contentType' => 'text/plain',
    'pagetitle' => 'AdvSearch Help',
    'longtitle' => 'AdvSearch Help',
    'description' => 'Resouce used by AdvSearch add-on to handle the help content window.',
    'introtext' => 'You could move this document anywhere in the tree of resources but don\'t change his pagetitle!',
    'alias' => 'advsearch-help',
    'published' => '1',
    'parent' => '0',
    'isfolder' => '0',
    'richtext' => '0',
    'menuindex' => '',
    'searchable' => '0',
    'cacheable' => '1',
    'menutitle' => 'AdvSearchHelp',
    'donthit' => '0',
    'hidemenu' => '1',
    'template' => '1',
),'',true,true);
$resources[1]->setContent(file_get_contents($sources['build'] . 'data/resources/advsearchhelp.content.html'));

return $resources;
