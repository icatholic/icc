<?php
namespace Idatabase\Model;

use My\Common\Model\Mongo;

class Structure extends Mongo
{

    protected $collection = IDATABASE_STRUCTURES;

    public function init()
    {
        // 添加索引
        $this->ensureIndex(array(
            'collection_id' => 1
        ));
        
        $this->ensureIndex(array(
            'field' => 1
        ));
        
        $this->ensureIndex(array(
            'rshKey' => 1
        ));
        
        $this->ensureIndex(array(
            'rshValue' => 1
        ));
    }

    /**
     * 获取一个集合的关联字段以及对应的关联表
     *
     * @param string $collection_id            
     * @return array
     */
    public function getRshFields($collection_id)
    {
        $rshFields = $this->findAll(array(
            'collection_id' => $collection_id,
            'rshCollection' => array(
                '$ne' => ''
            )
        ), array(
            '$natural' => 1
        ), 0, array(
            'rshCollection' => true
        ));
        
        $rst = array();
        if (! empty($rshFields)) {
            foreach ($rshFields as $field) {
                if (isset($field['field']) && isset($field['rshCollection'])) {
                    $rst[$field['field']] = $field['rshCollection'];
                }
            }
        }
        
        return $rst;
    }

    /**
     * 获取当前集合的combobox的提交和显示字段信息
     *
     * @param string $collection_id            
     * @throws \Exception
     * @return array
     */
    public function getComboboxKeyValueField($collection_id)
    {
        $rshKeyFieldInfo = $this->findOne(array(
            'collection_id' => $collection_id,
            'rshKey' => true
        ));
        if (empty($rshKeyFieldInfo)) {
            throw new \Exception('该集合未设定过combobox的key显示值属性');
        }
        
        $rshValueFieldInfo = $this->findOne(array(
            'collection_id' => $collection_id,
            'rshValue' => true
        ));
        $rshValueField = ! empty($rshValueFieldInfo) ? $rshValueFieldInfo['field'] : '_id';
        $rshKeyField = $rshKeyFieldInfo['field'];
        return array(
            'rshCollectionKeyField' => $rshKeyField,
            'rshCollectionValueField' => $rshValueField
        );
    }
}