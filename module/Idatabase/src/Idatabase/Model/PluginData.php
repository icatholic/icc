<?php
namespace Idatabase\Model;

use My\Common\Model\Mongo;
use Aws\CloudFront\Exception\Exception;

class PluginData extends Mongo
{

    protected $collection = IDATABASE_PLUGINS_DATAS;

    private $_project_plugin;

    private $_plugin_structure;

    private $_structure;

    private $_collection;

    private $_project;

    private $_mapping;

    private $_sourceData;

    private $_targetData;

    public function init()
    {
        try {
            $this->_project_plugin = new ProjectPlugin($this->config);
            $this->_plugin_structure = new PluginStructure($this->config);
            $this->_structure = new Structure($this->config);
            $this->_collection = new Collection($this->config);
            $this->_project = new Project($this->config);
            $this->_mapping = new Mapping($this->config);
            $this->_sourceData = new Data($this->config);
            $this->_targetData = new Data($this->config);
        } catch (Exception $e) {
            fb($e, 'LOG');
        }
    }

    /**
     * 设定插件集合的默认数据
     *
     * @param string $plugin_collection_id
     *            设定插件集合的编号
     * @param string $data_collection_id
     *            设定默认数据集合的编号
     */
    public function setDefault($plugin_collection_id, $data_collection_id)
    {
        $this->update(array(
            'plugin_collection_id' => $plugin_collection_id
        ), array(
            '$set' => array(
                'data_collection_id' => $data_collection_id
            )
        ), array(
            'upsert' => true
        ));
    }

    /**
     * 取消默认设置
     *
     * @param string $plugin_collection_id            
     * @param string $data_collection_id            
     */
    public function cancelDefault($plugin_collection_id, $data_collection_id)
    {
        $check = $this->findOne(array(
            'plugin_collection_id' => $plugin_collection_id,
            'data_collection_id' => $data_collection_id
        ));
        
        if (! empty($check)) {
            $this->remove(array(
                'plugin_collection_id' => $plugin_collection_id
            ));
        }
    }

    /**
     * 复制插件集合默认数据
     *
     * @param string $plugin_collection_id            
     * @param string $target_collection_id            
     * @return boolean
     */
    public function copy($plugin_collection_id, $target_collection_id)
    {
        $source = $this->findOne(array(
            'plugin_collection_id' => $plugin_collection_id
        ));
        
        if ($source == null) {
            return false;
        }
        
        if (! empty($source['data_collection_id'])) {
            $data_collection_id = $source['data_collection_id'];
            $this->_sourceData->setCollection(iCollectionName($data_collection_id));
            $this->_sourceData->setReadPreference(\MongoClient::RP_SECONDARY);
            
            $this->_targetData->setCollection(iCollectionName($target_collection_id));
            $cursor = $this->_sourceData->find(array());
            while ($cursor->hasNext()) {
                $row = $cursor->getNext();
                array_unset_recursive($row, array(
                    '_id',
                    '__CREATE_TIME__',
                    '__MODIFY_TIME__'
                ));
                $this->_targetData->update($row, array(
                    '$set' => $row
                ), array(
                    'upsert' => true
                ));
            }
            return true;
        }
    }
}