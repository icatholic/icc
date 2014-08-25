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
        if (APPLICATION_ENV == 'production') {
            //生产环境记录日志，采用gearman解决方案,抛接方式完成记录
            $gm = new Gearman();
            $gmClient = $gm->client();
            return $gmClient->doBackground('logError',$message);
        }
        else {
            return logError($message);
        }
    }
}