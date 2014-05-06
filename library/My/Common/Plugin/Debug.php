<?php
namespace My\Common\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class Debug extends AbstractPlugin
{

    public function __invoke($var)
    {
        return $this->debug($var);
    }

    public function debug($var)
    {
        return fb($var, 'LOG');
    }
}