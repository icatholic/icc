<?php
namespace Idatabase\Model;

use My\Common\Model\Mongo;
use Zend\Json\Json;

class Index extends Mongo
{

    protected $collection = IDATABASE_INDEXES;

    /**
     * 初始化功能解释
     */
    public function init()
    {
//         $this->ensureIndex(array(
//             'collection_id' => 1
//         ));
    }

    /**
     * 自动给给定集合创建索引
     *
     * @param string $collection_id            
     *
     */
    public function autoCreateIndexes($collection_id)
    {
        $cursor = $this->find(array(
            'collection_id' => $collection_id
        ));
        
        $dataCollection = new Data($this->config);
        $dataCollection->setCollection(iCollectionName($collection_id));
        while ($cursor->hasNext()) {
            $index = $cursor->getNext();
            $keys = Json::decode($index['keys'], Json::TYPE_ARRAY);
            $dataCollection->ensureIndex($keys, array(
                'background' => true
            ));
        }
        
        return true;
    }
}