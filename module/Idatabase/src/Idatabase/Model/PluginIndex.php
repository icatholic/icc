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
}