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
            '__CREATE_TIME__' => true
        ), array(
            'background' => true,
            'expireAfterSeconds' => 15 * 24 * 3600
        ));
    }
}