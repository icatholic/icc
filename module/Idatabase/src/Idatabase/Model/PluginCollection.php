<?php
namespace Idatabase\Model;

use My\Common\Model\Mongo;

class PluginCollection extends Mongo
{

    protected $collection = IDATABASE_PLUGINS_COLLECTIONS;

    public function init()
    {
        $this->_project_plugin = new ProjectPlugin($this->config);
        $this->_plugin_structure = new PluginStructure($this->config);
        $this->_structure = new Structure($this->config);
        $this->_collection = new Collection($this->config);
        $this->_project = new Project($this->config);
        $this->_mapping = new Mapping($this->config);
    }
    

    /**
     * 添加集合到插件集合管理
     *
     * @param array $datas
     * @return string
     */
    public function addPluginCollection($datas)
    {
        if (empty($datas['plugin_id']))
            return '';
    
        unset($datas['project_id']);
        $this->insertRef($datas);
        if ($datas['_id'] instanceof \MongoId)
            return $datas['_id']->__toString();
    
        return '';
    }
    
    /**
     * 添加集合到插件集合管理
     *
     * @param array $datas
     * @return string
     */
    public function editPluginCollection($datas)
    {
        unset($datas['project_id']);
        $plugin_collection_id = isset($datas['plugin_collection_id']) ? $datas['plugin_collection_id'] : '';
        if (!empty($plugin_collection_id)) {
            $this->update(array(
                    '_id' => myMongoId($plugin_collection_id)
            ), array(
                    '$set' => $datas
            ));
        }
        else {
            return $this->addPluginCollection($datas);
        }
        return $plugin_collection_id;
    }

    /**
     * 同步指定项目的指定插件
     *
     * @param string $project_id
     *            项目编号
     * @param string $plugin_id
     *            插件编号
     * @param string $collectionName
     *            集合名称
     * @return true false
     */
    public function syncPluginCollection($project_id, $plugin_id, $collectionName)
    {
        $pluginCollectionInfo = $this->findOne(array(
            'plugin_id' => $plugin_id,
            'alias' => $collectionName
        ));
        
        if ($pluginCollectionInfo == null) {
            fb('$pluginCollectionInfo is null', 'LOG');
            return false;
        }
        
        // 同步数据结构
        $syncPluginStructure = function ($plugin_id, $collection_id) use($pluginCollectionInfo)
        {
            if ($collection_id instanceof \MongoId)
                $collection_id = $collection_id->__toString();
            
            $this->_structure->physicalRemove(array(
                'collection_id' => $collection_id
            ));
            
            // 插入新的数据结构
            $cursor = $this->_plugin_structure->find(array(
                'plugin_id' => $plugin_id,
                'plugin_collection_id' => $pluginCollectionInfo['_id']->__toString()
            ));
            
            while ($cursor->hasNext()) {
                $row = $cursor->getNext();
                array_unset_recursive($row, array(
                    '_id',
                    'collection_id',
                    '__CREATE_TIME__',
                    '__MODIFY_TIME__',
                    '__REMOVED__'
                ));
                $row['collection_id'] = $collection_id;
                $this->_structure->update(array(
                    'collection_id' => $collection_id,
                    'field' => $row['field']
                ), array(
                    '$set' => $row
                ), array(
                    'upsert' => true
                ));
            }
            return true;
        };
        
        // 添加映射关系
        $createMapping = function ($collection_id, $collectionName) use($project_id, $plugin_id)
        {
            if ($collection_id instanceof \MongoId)
                $collection_id = $collection_id->__toString();
            
            $projectPluginInfo = $this->_project_plugin->findOne(array(
                'project_id' => $project_id,
                'plugin_id' => $plugin_id
            ));
            
            if ($projectPluginInfo !== null) {
                $source_project_id = $projectPluginInfo['source_project_id'];
                if (! empty($source_project_id)) {
                    $collectionInfo = $this->_collection->findOne(array(
                        'project_id' => $source_project_id,
                        'plugin_id' => $plugin_id,
                        'alias' => $collectionName
                    ));
                    
                    $this->_mapping->update(array(
                        'project_id' => $project_id,
                        'collection_id' => $collection_id
                    ), array(
                        '$set' => array(
                            'collection' => 'idatabase_collection_' . myMongoId($collectionInfo['_id']),
                            'database' => DEFAULT_DATABASE,
                            'cluster' => DEFAULT_CLUSTER,
                            'active' => true
                        )
                    ), array(
                        'upsert' => true
                    ));
                    return true;
                }
            }
            return false;
        };
        
        if ($pluginCollectionInfo != null) {
            unset($pluginCollectionInfo['_id']);
            $collectionInfo = $pluginCollectionInfo;
            $collectionInfo['project_id'] = array($project_id);
            
            $check = $this->_collection->findOne(array(
                'project_id' => $project_id,
                'alias' => $collectionName
            ));
            
            if ($check == null) {
                $this->_collection->insertRef($collectionInfo);
                $syncPluginStructure($plugin_id, $collectionInfo['_id']);
                $createMapping($collectionInfo['_id'], $collectionName);
                return $collectionInfo;
            } else {
                $this->_collection->update(array(
                    '_id' => $check['_id']
                ), array(
                    '$set' => $collectionInfo
                ));
                $syncPluginStructure($plugin_id, $check['_id']);
                $createMapping($check['_id'], $collectionName);
            }
            return $check;
        }
        
        return false;
    }

    /**
     * 删除插件集合
     *
     * @param string $project_id            
     * @param string $plugin_id            
     * @param string $alias            
     */
    public function removePluginCollection($project_id, $plugin_id, $alias)
    {
        if (empty($plugin_id))
            return false;
        
        $this->_collection->remove(array(
            'project_id' => $project_id,
            'plugin_id' => $plugin_id,
            'alias' => $alias
        ));
        
        $this->remove(array(
            'plugin_id' => $plugin_id,
            'alias' => $alias
        ));
        return true;
    }
}