<?php
namespace My\Common\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class Gearman extends AbstractPlugin
{

    public function __invoke()
    {
        if ($message === null)
            return $this;
        return $this->logger($message, $level, $context);
    }

    /**
     * 任务端
     */
    public function job()
    {
        return $this->getController()
            ->getServiceLocator()
            ->get('LogMongodbService');
    }
    
    /**
     * 服务端
     */
    public function worker() {
        
    }
}