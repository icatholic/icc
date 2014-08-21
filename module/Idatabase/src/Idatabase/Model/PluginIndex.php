<?php
namespace Idatabase\Model;

use My\Common\Model\Mongo;
use Aws\CloudFront\Exception\Exception;

class PluginIndex extends Mongo
{
    protected $collection = IDATABASE_PLUGINS_INDEXES;
    
    private $_pluginCollection;
    
    public function init()
    {
        try {
            $this->_pluginCollection = new PluginCollection($this->config);
        } catch (Exception $e) {
            fb($e, 'LOG');
        }
    }
    
    public function addIndexesToPlugin($plugin_id,$alias,$index) {
        $datas = array();
        $datas['plugin_id'] = $plugin_id;
        $datas['project_collection_id'] = 
        $this->insert($datas);
    }
    
    public function getIndexesByPluginAlias($plugin_id,$alias) {
        $this->findOne();
    }
    
    public function syncIndexes() {
        $this->
    }
    
    
}