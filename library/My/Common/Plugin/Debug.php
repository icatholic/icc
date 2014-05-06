<?php
/**
 * 增加firephp输出插件，调试变量信息，将伴随header进行输出
 * 
 * @author young 2014-05-06
 *
 */
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