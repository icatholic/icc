<?php
namespace Logs\Service;

use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MonologServiceAbstractFactory implements AbstractFactoryInterface
{

    /**
     *
     * @var array
     */
    protected $config;

    /**
     *
     * @ERROR!!!
     *
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $config = $this->getConfig($serviceLocator);
        return isset($config[$requestedName]);
    }

    /**
     *
     * @ERROR!!!
     *
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $config = $this->getConfig($serviceLocator);
        $factory = new MonologServiceFactory();
        return $factory->createLogger($serviceLocator, new MonologOptions($config[$requestedName]));
    }

    /**
     *
     * @param ServiceLocatorInterface $serviceLocator            
     * @return array
     */
    public function getConfig(ServiceLocatorInterface $serviceLocator)
    {
        if (null !== $this->config) {
            return $this->config;
        }
        
        $config = $serviceLocator->get('config');
        
        if (isset($config['Logs'])) {
            $this->config = $config['Logs'];
        } else {
            $this->config = array();
        }
        return $this->config;
    }

    /**
     *
     * @param array $config            
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }
}