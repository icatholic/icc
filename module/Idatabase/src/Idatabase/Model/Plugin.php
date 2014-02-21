<?php
namespace Idatabase\Model;

use My\Common\Model\Mongo;

class Plugin extends Mongo
{

    protected $collection = IDATABASE_PLUGINS;

    /**
     * 同步全部plugin_id的文档自定义结构
     *
     * @param string $plugin_id            
     */
    public function syncAll($plugin_id)
    {}
}