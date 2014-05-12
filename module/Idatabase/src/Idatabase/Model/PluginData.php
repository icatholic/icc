<?php
namespace Idatabase\Model;

use My\Common\Model\Mongo;

class PluginData extends Mongo
{

    protected $collection = IDATABASE_PLUGINS_DATAS;

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
     * 设定插件集合的默认数据
     *
     * @param string $plugin_collection_id
     *            设定插件集合的编号
     * @param string $data_collection_id
     *            设定默认数据集合的编号
     */
    public function setDefault($plugin_collection_id, $data_collection_id)
    {
        $this->remove(array(
            'plugin_collection_id' => $plugin_collection_id
        ));
        $this->insert(array(
            'plugin_collection_id' => $plugin_collection_id,
            'data_collection_id' => $data_collection_id
        ));
    }

    /**
     * 测试
     * @param string $plugin_collection_id
     * @return array
     */
    public function copy($plugin_collection_id)
    {
    	$this->findOne(array('plugin_collection_id' => $plugin_collection_id));
    }
}