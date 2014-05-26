<?php
namespace Idatabase\Model;

use My\Common\Model\Mongo;

class Collection extends Mongo
{

    protected $collection = IDATABASE_COLLECTIONS;

    private $_structure;

    /**
     * 初始化功能解释
     */
    public function init()
    {
        $this->_structure = new Structure($this->config);
    }

    /**
     * 根据集合的名称获取集合的_id
     *
     * @param string $project_id            
     * @param string $alias            
     * @throws \Exception or string
     */
    public function getCollectionIdByAlias($project_id, $alias)
    {
        try {
            new \MongoId($alias);
            return $alias;
        } catch (\MongoException $ex) {}
        
        $collectionInfo = $this->findOne(array(
            'project_id' => $project_id,
            'alias' => $alias
        ));
        
        if ($collectionInfo == null) {
            fb('集合名称不存在于指定项目', 'LOG');
            return false;
        } else {
            return $collectionInfo['_id']->__toString();
        }
    }

    /**
     * 获取被关联的结合数据列表用于集合数据的替换
     * @param string $rshCollectionAlias 集合别名 
     * @param string $field 字段
     * @return array 
     */
    public function getCollectionRshMap($collectionAlias,$field)
    {
        $rshCollection = $this->getCollectionIdByAlias($collectionAlias);
        $rshFields = $this->_structure->getRshFields($rshCollection);
        
    }
}