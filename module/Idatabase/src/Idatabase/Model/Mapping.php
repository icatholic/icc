<?php
namespace Idatabase\Model;

use My\Common\Model\Mongo;

class Mapping extends Mongo
{

    protected $collection = IDATABASE_MAPPING;

    public function getMapping($collection_id)
    {
        return $this->findOne(array(
            'collection_id' => $collection_id,
            'active' => true
        ));
    }
}