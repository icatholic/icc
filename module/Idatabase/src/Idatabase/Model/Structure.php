<?php
namespace Idatabase\Model;

use My\Common\Model\Mongo;

class Structure extends Mongo
{

    protected $collection = IDATABASE_STRUCTURES;

    public function init()
    {
        // 添加索引
        $this->ensureIndex(array(
            'collection_id' => 1
        ));
        $this->ensureIndex(array(
            'field' => 1
        ));
    }
}