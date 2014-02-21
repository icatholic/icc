<?php
namespace My\Common\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use My\Common\MongoCollection;
use My\Common\Model\Mongo;

class Model extends AbstractPlugin
{

    /**
     * 初始化插件并执行初始化集合调用
     *
     * @param string $modelName            
     * @return \My\Common\Plugin\ModelMongoCollection \My\Common\MongoCollection
     */
    public function __invoke($modelName = null)
    {
        if ($modelName === null)
            return $this;
        
        return $this->getController()
            ->getServiceLocator()
            ->get($modelName);
    }
}