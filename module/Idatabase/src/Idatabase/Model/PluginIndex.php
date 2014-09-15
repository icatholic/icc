<?php
namespace Idatabase\Model;

use My\Common\Model\Mongo;
use Aws\CloudFront\Exception\Exception;

class PluginIndex extends Mongo
{

    protected $collection = IDATABASE_PLUGINS_INDEXES;

    private $_pluginCollection;

    private $_collection;

    private $_data;

    public function init()
    {
        try {
            $this->ensureIndex(array(
                'plugin_id' => 1
            ));
            $this->_pluginCollection = new PluginCollection($this->config);
            $this->_collection = new Collection($this->config);
            $this->_data = new Data($this->config);
        } catch (Exception $e) {
            fb($e, 'LOG');
        }
    }

    public function autoCreateIndexes($project_id, $plugin_id)
    {
        // 给插件内的组件自动创建索引
        $datas = $this->findAll(array(
            'plugin_id' => $plugin_id
        ));
        if (! empty($datas)) {
            foreach ($datas as $row) {
                $plugin_collection_id = $row['plugin_collection_id'];
                $pluginCollectionInfo = $this->_pluginCollection->findOne(array(
                    '_id' => myMongoId($plugin_collection_id)
                ));
                $targetCollection = $this->_collection->findOne(array(
                    'project_id' => $project_id,
                    'alias' => $pluginCollectionInfo['alias']
                ));
                
                $this->_data->setCollection(iCollectionName($targetCollection['_id']));
                $rst = $this->_data->ensureIndex($row['indexes']);
//                 fb(iCollectionName($targetCollection['_id']),'LOG');
//                 fb($row['indexes'],'LOG');
//                 fb($rst,'LOG');
            }
        }
        
        return true;
    }
}