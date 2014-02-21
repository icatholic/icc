<?php
namespace Idatabase\Model;

use My\Common\Model\Mongo;

class Order extends Mongo
{
     
    protected $collection = IDATABASE_COLLECTION_ORDERBY;

}