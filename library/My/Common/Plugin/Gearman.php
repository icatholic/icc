<?php
/**
 * 为控制器添加Gearman插件
 * 
 * @author Young 2014-06-05 
 *
 */
namespace My\Common\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class Gearman extends AbstractPlugin
{

    /**
     * 
     * @return \My\Common\Plugin\Gearman
     */
    public function __invoke()
    {
        return $this;
    }

    /**
     * 
     * @return \GearmanClient
     */
    public function client()
    {
        $gmClient = new \GearmanClient();
        $gmClient->addServers(GEARMAN_SERVERS);
        return $gmClient;
    }

    /**
     * 
     * @return \GearmanWorker
     */
    public function worker()
    {
        $gmWorker = new \GearmanWorker();
        $gmWorker->addServers(GEARMAN_SERVERS);
        return $gmWorker;
    }
}