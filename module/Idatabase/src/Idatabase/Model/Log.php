<?php
namespace Idatabase\Model;

use My\Common\Model\Mongo;

class Log extends Mongo
{

    protected $collection = IDATABASE_LOGS;

    protected $database = DB_LOGS;
}