<?php
namespace My\Common\AbstractFactory;

use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use My\Common\Factory\Mongo as MongoFactory;

class Mongo implements AbstractFactoryInterface
{

    /**
     *
     * @var array
     */
    protected $config;

    /**
     * Configuration key for cache objects
     *
     * @var string
     */
    protected $configKey = 'mongos';

    /**
     * Determine if we can create a service with name
     *
     * @param ServiceLocatorInterface $serviceLocator            
     * @param
     *            $name
     * @param
     *            $requestedName
     * @return bool
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $config = $this->getConfig($serviceLocator);
        if (empty($config)) {
            return false;
        }
        return (isset($config[$requestedName]) && is_array($config[$requestedName]));
    }

    /**
     * Create service with name
     *
     * @param ServiceLocatorInterface $serviceLocator            
     * @param
     *            $name
     * @param
     *            $requestedName
     * @return mixed
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $config = $this->getConfig($serviceLocator);
        $config = $config[$requestedName];
        return MongoFactory::factory($config);
    }

    /**
     * Retrieve cache configuration, if any
     *
     * @param ServiceLocatorInterface $services            
     * @return array
     */
    protected function getConfig(ServiceLocatorInterface $services)
    {
        if ($this->config !== null) {
            return $this->config;
        }
        
        if (! $services->has('Config')) {
            $this->config = array();
            return $this->config;
        }
        
        return $services->get('Config');
    }
}