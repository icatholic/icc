<?php
namespace Idatabase\Model;

use My\Common\Model\Mongo;

class Log extends Mongo
{

    protected $collection = IDATABASE_LOGS;

    protected $database = DB_LOGS;

    public function init()
    {
//         $this->createIndex(array(
//             'uri' => true
//         ), array(
//             'background' => true
//         ));
        
//         $this->createIndex(array(
//             '__CREATE_TIME__' => - 1
//         ), array(
//             'background' => true,
//             'expireAfterSeconds' => 15 * 24 * 3600
//         ));
    }

    /**
     * 授权用户行为的跟踪日志
     *
     * @return Ambigous <boolean, multitype:>
     */
    public function trackingLog()
    {
        return $this->insert(array(
            'uri' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'php cli',
            'session' => isset($_SESSION) ? $_SESSION : array(),
            'post' => isset($_POST) ? $_POST : array(),
            'get' => isset($_GET) ? $_GET : array(),
            'server' => isset($_SERVER) ? $_SERVER : array()
        ), array(
            'w' => 0
        ));
    }
}