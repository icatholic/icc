<?php
/**
 * 自动加载Controller
 * 
 * @author Young
 *
 */
namespace My\Common\AbstractFactory;

use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class Controller implements AbstractFactoryInterface
{

    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        if (class_exists($requestedName . 'Controller'))
            return true;
        return false;
    }

    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $class = $requestedName . 'Controller';
        return new $class();
    }
}