<?php
namespace Idatabase;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\EventManager\EventManager;
use Zend\EventManager\GlobalEventManager;

class Module
{

    /**
     * 加载配置信息
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__
                )
            )
        );
    }

    public function getControllerConfig()
    {
        return array(
            'abstract_factories' => array(
                'My\Common\AbstractFactory\Controller'
            )
        );
    }

    public function getServiceConfig()
    {
        return array(
            'abstract_factories' => array(
                'My\Common\AbstractFactory\Controller'
            )
        );
    }

    public function onBootstrap(MvcEvent $e)
    {
        $app = $e->getApplication();
        $eventManager = $app->getEventManager();
        $serviceLocator = $app->getServiceManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
    }
}