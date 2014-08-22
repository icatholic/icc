<?php
namespace Idatabase\Model;

use My\Common\Model\Mongo;

class Log extends Mongo
{

    protected $collection = IDATABASE_LOGS;

    protected $database = DB_LOGS;

    public function init()
    {
        $this->createIndex(array(
            'uri' => true
        ), array(
            'background' => true
        ));
        
        $this->createIndex(array(
            '__CREATE_TIME__' => -1
        ), array(
            'background' => true,
            'expireAfterSeconds' => 15 * 24 * 3600
        ));
    }

    /**
     * 授权用户行为的跟踪日志
     *
     * @return Ambigous <boolean, multitype:>
     */
    public function trackingLog()
    {
        return $this->insert(array(
            'uri' => $_SERVER['REQUEST_URI'],
            'session' => $_SESSION,
            'post' => $_POST,
            'get' => $_GET,
            'server' => $_SERVER
        ), array(
            'w' => 0
        ));
    }
}