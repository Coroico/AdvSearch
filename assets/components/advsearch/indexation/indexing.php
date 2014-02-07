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

if (!isset($_GET['id'])) {
    $output = json_encode(array(
        'success' => false,
        'message' => 'ID parameter is required'
    ));
    die($output);
}

// load the zend lucene librairy
define('MODX_ASSETS_PATH', MODX_BASE_PATH . 'assets/');
define('LIBRARY_PATH', MODX_ASSETS_PATH . 'libraries/');

// First make sure the Zend library is in the include path:
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . LIBRARY_PATH);

require_once LIBRARY_PATH . 'Zend/Search/Lucene.php';

if (!class_exists('ModxIndexedDocument')) {

    class ModxIndexedDocument extends Zend_Search_Lucene_Document {

        /**
         * Creates our indexable document and adds all
         * necessary fields to it using the passed in document
         */
        public function __construct(modX $modx, $document) {
            $modx->resource = & $document;

            $docId = $document->get('id');
            $url = $modx->makeUrl($docId, '', '', 'full');

            $this->addField(Zend_Search_Lucene_Field::Keyword('docid', $docId));
            $this->addField(Zend_Search_Lucene_Field::UnIndexed('url', $url));
            $this->addField(Zend_Search_Lucene_Field::UnIndexed('createdon', $document->get('createdon')));
            if ($document->get('introtext'))
                $this->addField(Zend_Search_Lucene_Field::Text('introtext', $document->get('introtext')));
            $this->addField(Zend_Search_Lucene_Field::Text('pagetitle', $document->get('pagetitle')));
            if ($document->get('longtitle'))
                $this->addField(Zend_Search_Lucene_Field::Text('longtitle', $document->get('longtitle')));

            // process content
            // get unparsed content
            $content = $document->get('content');

            if (!empty($content)) {
                // get the max iterations tags are processed before processing is terminated
                $maxIterations = (integer) $modx->getOption('parser_max_iterations', null, 10);

                // parse all cacheable tags first
                $modx->getParser()->processElementTags('', $content, false, false, '[[', ']]', array(), $maxIterations);

                // parse all non-cacheable and remove unprocessed tags
                $modx->getParser()->processElementTags('', $content, true, true, '[[', ']]', array(), $maxIterations);
            }

            $this->addField(Zend_Search_Lucene_Field::UnStored('content', $content));
        }

    }

}
if (!class_exists('AdvSearchIndex')) {

    class AdvSearchIndex {

        protected $modx;
        protected $config = array();

        function __construct(modX & $modx, array $properties = array()) {

            $this->modx = & $modx;

            $this->config = & $properties;
        }

        function getResources() {

            // get the documents to index
            $contextResourceTbl = $this->modx->getTableName('modContextResource');

            /* multiple context support */
            $context = $this->config['contexts'];
            if (!empty($context)) {
                $context = explode(',', $context);
                $contexts = array();
                foreach ($context as $ctx) {
                    $contexts[] = $this->modx->quote($ctx);
                }
                $context = implode(',', $contexts);
                unset($contexts, $ctx);
            } else {
                $context = $this->modx->quote($this->modx->context->get('key'));
            }

            $c = $this->modx->newQuery('modResource', array(
                "(modResource.context_key IN ({$context}) OR EXISTS(SELECT 1 FROM {$contextResourceTbl} ctx WHERE ctx.resource = modResource.id AND ctx.context_key IN ({$context})))"
            ));

            // not deleted, published and searchable
            $c->andCondition(array(
                'deleted' => 0,
                'published' => 1,
                'searchable' => 1,
            ));

            // hideMenu
//        if ($this->config['hideMenu'] == 0) {
//            $c->andCondition(array('hidemenu' => '0'));
//        } elseif ($this->config['hideMenu'] == 1) {
//            $c->andCondition(array('hidemenu' => '1'));
//        }
            // hideContainers
            if ($this->config['hideContainers']) {
                $c->andCondition(array('isfolder' => '0'));
            }

            // which fields
            $fields = array_keys($this->modx->getFields('modResource'));
            $c->select($fields);

            // lstIds
            if (!empty($this->config['ids'])) {
                $c->andCondition(array("modResource.id IN ({$this->config['ids']})"));
            }

            // get documents where to search
            $resources = $this->modx->getCollection('modResource', $c);

            return $resources;
        }

    }

}

// create an index folder
$indexPath = MODX_ASSETS_PATH . 'files/docindex';
if (file_exists($indexPath))
    $index = Zend_Search_Lucene::open($indexPath);
else
    $index = Zend_Search_Lucene::create($indexPath);

// get Ids resources
$config = array(
    'ids' => intval($_GET['id']),
    'contexts' => 'web',
    'hideMenu' => 1,
    'hideContainers' => 1,
    'method' => 'POST',
    'perPage' => 20,
    'debug' => 1,
);

// get resources to index
$asIndex = new AdvSearchIndex($modx, $config);
$documents = $asIndex->getResources();

if (!empty($documents)) {
    $outputs = array();
    foreach ($documents as $document) {
        $docId = $document->get('id');

        $outputs[] = $document->get('pagetitle') . " ($docId)";

        // find the document $id based on the indexed "id" field
        $docIds = $index->termDocs(new Zend_Search_Lucene_Index_Term($docId, 'docid'));
        foreach ($docIds as $docId)
            $index->delete($docId);

        // re-add the document
        $index->addDocument(new ModxIndexedDocument($modx, $document));

    }

    // optimize the index (remove deleted documents)
    $index->optimize();

    // write the index to disk
    $index->commit();
}

$output = json_encode(array(
    'success' => true,
    'message' => '',
    'total' => 1,
    'object' => $outputs
        ));
die($output);