<?php
namespace Logs;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\Mvc\MvcEvent;
use Zend\EventManager\GlobalEventManager;
use Monolog\Logger;
use Zend\EventManager\Event;
use Zend\Json\Encoder;

class Module implements AutoloaderProviderInterface, ConfigProviderInterface
{

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/../../src/' . __NAMESPACE__
                )
            )
        );
    }

    public function getConfig()
    {
        return include __DIR__ . "/../../config/module.config.php";
    }

    public function onBootstrap(MvcEvent $e)
    {
        $serviceLocator = $e->getApplication()->getServiceManager();
        GlobalEventManager::attach(EVENT_LOG_ERROR, function (Event $event) use($serviceLocator)
        {
            $message = Encoder::encode($event->getParams());
            $serviceLocator->get('LogMongodbService')->addRecord(Logger::ERROR, $message, array());
            return true;
        });
        
        GlobalEventManager::attach(EVENT_LOG_DEBUG, function (Event $event) use($serviceLocator)
        {
        	$message = Encoder::encode($event->getParams());
        	$serviceLocator->get('LogMongodbService')->addRecord(Logger::DEBUG, $message, array());
        	return true;
        });
    }
}