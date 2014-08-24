<?php
namespace My\Common\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Monolog\Logger;

class Log extends AbstractPlugin
{

    public function __invoke($message)
    {
        if ($message === null)
            return $this;
        return $this->logger($message);
    }

    public function logger($message)
    {
        return logError($message);
    }
}