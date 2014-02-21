<?php
/**
 * 自动加载Model
 * 
 * @author Young
 *
 */
namespace My\Common\AbstractFactory;

use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class Model implements AbstractFactoryInterface
{

    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        if (class_exists($requestedName) && strpos($requestedName, '\\Model\\') !== false)
            return true;
        return false;
    }

    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $class = $requestedName;
        return new $class($serviceLocator->get('mongos'));
    }
}