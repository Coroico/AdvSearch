<?php

class SolrResource {

    public $modx;
    public $config;
    public $client;
    public $descendants = array();
    public $queryTime = null;

    public function __construct(modX $modx, $config) {
        $this->modx = & $modx;
        $this->config = array_merge(array(
            'libraryPath' => $this->modx->getOption('core_path') . 'libraries/'
                ), $config);
        $this->initSolr();
    }

    /**
     * Initiate Solr
     * @return void $this->client
     * @throws Exception
     */
    public function initSolr() {
        if (!is_null($this->client)) {
            return $this->client;
        }
        require_once $this->config['libraryPath'] . 'solarium/vendor/autoload.php';
        require_once $this->config['libraryPath'] . 'solarium/library/Solarium/Autoloader.php';

        if (!isset($this->config['engineConfigFile']) || empty($this->config['engineConfigFile']) || !is_file($this->config['engineConfigFile'])) {
            $this->config['engineConfigFile'] = MODX_CORE_PATH . 'components/advsearch/configs/advsearchsolrconfig.php';
        }
        $engineConfig = include $this->config['engineConfigFile'];
        try {
            \Solarium\Autoloader::register();
            $this->client = new Solarium\Client($engineConfig);
        } catch (Exception $e) {
            $msg = 'Error connecting to Solr server: ' . $e->getMessage();
            $this->modx->log(modX::LOG_LEVEL_ERROR, $msg);
            throw new Exception($msg);
        }
    }

    /**
     * Add resources into Solr's data
     * @param mixed $ids array | numeric of resources' IDs
     * @return boolean
     */
    public function addIndex($ids) {
        if (empty($ids)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, __FILE__ . ' ');
            $this->modx->log(modX::LOG_LEVEL_ERROR, __METHOD__ . ' ');
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'ID is missing');
            return false;
        }
        if (!is_array($ids)) {
            $ids = array_map('trim', @explode(',', $ids));
        }
        $update = $this->client->createUpdate();
        $docs = array();
        foreach ($ids as $id) {
            $id = intval($id);
            $doc = $update->createDocument();
            $resourceArray = $this->getResource($id);
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
            $docs[$id] = $doc;
        }
        $update->addDocuments($docs, true);
        $update->addCommit();
        $result = $this->client->update($update);
        $this->queryTime = $result->getQueryTime();
        return $result->getStatus();
    }

    /**
     * Remove a resource from Solr's data
     * @param mixed $ids array | numeric of resources' IDs
     * @return boolean
     */
    public function removeIndex($ids) {
        if (empty($ids)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, __FILE__ . ' ');
            $this->modx->log(modX::LOG_LEVEL_ERROR, __METHOD__ . ' ');
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'ID is missing');
            return false;
        }
        if (!is_array($ids)) {
            $ids = array_map('trim', @explode(',', $ids));
        }
        foreach ($ids as $k => $id) {
            $ids[$k] = intval($id);
        }
        $update = $this->client->createUpdate();
        $update->addDeleteByIds($ids);
        $update->addCommit();
        $result = $this->client->update($update);
        $this->queryTime = $result->getQueryTime();
        return $result->getStatus();
    }

    /**
     * Check whether a resource is a descendat of the given root(s)
     * @param mixed $rootIds array | numeric of root ids
     * @param int $resourceId resource's ID
     * @return boolean
     */
    public function isDescendant($rootIds, $resourceId) {
        if (empty($rootIds) || empty($resourceId)) {
            return FALSE;
        }
        if (!is_array($rootIds)) {
            $rootIds = array_map('trim', @explode(',', $rootIds));
        }
        if (in_array($resourceId, $rootIds)) {
            return TRUE;
        }
        $parentId = $this->modx->getObject('modResource', $resourceId)->get('parent');
        if (in_array($parentId, $rootIds)) {
            return TRUE;
        }
        return self::isDescendant($rootIds, $parentId);
    }

    /**
     * Get all descendants of the given root(s)
     * @param mixed array | numeric of parents' IDs
     * @return array descendants' array
     */
    public function getDescendants($parents) {
        if (empty($parents)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, __FILE__ . ' ');
            $this->modx->log(modX::LOG_LEVEL_ERROR, __METHOD__ . ' ');
            $this->modx->log(modX::LOG_LEVEL_ERROR, '$parents ID is missing');
            return FALSE;
        }
        if (!is_array($parents)) {
            $parents = array_map('trim', @explode(',', $parents));
        }
        $kids = $this->modx->getCollection('modResource', array(
            'parent:IN' => $parents,
        ));
        if (!empty($kids)) {
            /** @var modResource $resource */
            foreach ($kids as $resource) {
                $resourceArray = $resource->toArray();
                if (isset($resourceArray['properties']) && !empty($resourceArray['properties'])) {
                    $resourceArray['properties'] = json_encode($resourceArray['properties']);
                }

                $tvs = $resource->getTemplateVars();
                foreach ($tvs as $tv) {
                    $tvValue = $tv->renderOutput($resource->get('id'));
                    if (is_array($tvValue)) {
                        $tvValue = implode('||', $tvValue);
                    }
                    $resourceArray[$tv->get('name')] = $tvValue;
                }

                $this->descendants[$resource->get('id')] = $resourceArray;
                $this->getDescendants($resource->get('id'));
            }
        }

        return $this->descendants;
    }

    /**
     * Get resource by ID
     * @param int $id resource's ID
     * @return array resource's array
     */
    public function getResource($id) {
        if (empty($id) || !is_numeric($id)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, __FILE__ . ' ');
            $this->modx->log(modX::LOG_LEVEL_ERROR, __METHOD__ . ' ');
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'ID is missing');
            return false;
        }

        $resource = $this->modx->getObject('modResource', array(
            'id' => $id,
        ));
        $resourceArray = array();
        if ($resource) {
            $resourceArray = $resource->toArray();
            if (isset($resourceArray['properties']) && !empty($resourceArray['properties'])) {
                $resourceArray['properties'] = json_encode($resourceArray['properties']);
            }

            $tvs = $resource->getTemplateVars();
            if (!empty($tvs)) {
                foreach ($tvs as $tv) {
                    $tvValue = $tv->renderOutput($resource->get('id'));
                    if (is_array($tvValue)) {
                        $tvValue = implode('||', $tvValue);
                    }
                    $resourceArray[$tv->get('name')] = $tvValue;
                }
            }
        }

        return $resourceArray;
    }

}
