<?php
namespace Idatabase\Model;

use My\Common\Model\Mongo;

class Lock extends Mongo
{
     
    protected $collection = IDATABASE_LOCK;

}