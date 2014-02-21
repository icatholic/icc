<?php
namespace Idatabase\Model;

use My\Common\Model\Mongo;

class Collection extends Mongo
{

    protected $collection = IDATABASE_COLLECTIONS;

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
}