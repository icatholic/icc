<?php
namespace Idatabase\Model;

use My\Common\Model\Mongo;

class PluginStructure extends Mongo
{

    protected $collection = IDATABASE_PLUGINS_STRUCTURES;

    /**
     * 定义文档结构时，同步插件的文档结构
     *
     * @param array $datas            
     * @return Ambigous <boolean, multitype:>
     */
    public function sync($datas)
    {
        if (! empty($datas['plugin_id'])) {
            return $this->update(array(
                'plugin_id' => $datas['plugin_id'],
                'plugin_collection_id' => $datas['plugin_collection_id'],
                'field' => isset($datas['__OLD_FIELD__']) ? $datas['__OLD_FIELD__'] : $datas['field']
            ), array(
                '$set' => $datas
            ), array(
                'upsert' => true
            ));
        }
    }

    /**
     * 删除插件的数据结构
     *
     * @param string $plugin_id            
     * @param string $datas            
     */
    public function removePluginStructure($plugin_id, $datas)
    {
        if (! empty($plugin_id) && ! empty($field)) {
            return $this->_plugin_structure->remove(array(
                'plugin_id' => $plugin_id,
                'plugin_collection_id' => $datas['plugin_collection_id'],
                'field' => $datas['field']
            ));
        }
    }
}