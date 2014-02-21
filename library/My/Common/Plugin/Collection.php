<?php
namespace My\Common\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use My\Common\MongoCollection;
use My\Common\Model\Mongo;

class Collection extends AbstractPlugin
{

    /**
     * 初始化插件并执行初始化集合调用
     *
     * @param string $modelName            
     * @return \My\Common\MongoCollection
     */
    public function __invoke($collection = null, $database = DEFAULT_DATABASE, $cluster = DEFAULT_CLUSTER, $collectionOptions = null)
    {
        if ($collection === null)
            return $this;
        
        return $this->collection($collection, $database, $cluster, $collectionOptions);
    }

    /**
     * 初始化集合调用
     *
     * @param string $collection            
     * @param string $database            
     * @param string $cluster            
     * @return \My\Common\MongoCollection
     */
    public function collection($collection = null, $database = DEFAULT_DATABASE, $cluster = DEFAULT_CLUSTER, $collectionOptions = null)
    {
        if ($collection === null)
            throw new \Exception('请设定集合名称');
        
        $mongoConfig = $this->getController()
            ->getServiceLocator()
            ->get('mongos');
        
        return new MongoCollection($mongoConfig, $collection, $database, $cluster, $collectionOptions);
    }
}