<?php
/**
 * Gearman方式同步plugin插件信息
 *
 * @author young 
 * @version 2014.06.16
 * 
 */
namespace Gearman\Controller;

use My\Common\Controller\Action;

class PluginController extends Action
{

    private $_project_plugin;

    private $_plugin_collection;

    private $_worker;

    public function init()
    {
        $this->_worker = $this->gearman()->worker();
        $this->_plugin_collection = $this->model('Idatabase\Model\PluginCollection');
        $this->_plugin_collection->setReadPreference(\MongoClient::RP_SECONDARY);
    }

    /**
     * 同步插件集合数据结构
     *
     * @author young
     * @name 同步插件集合数据结构
     * @version 2014.06.16 young
     */
    public function syncAction()
    {
        try {
            $cache = $this->cache();
            $this->_worker->addFunction("pluginCollectionSync", function (\GearmanJob $job) use($cache)
            {
                $job->handle();
                $workload = $job->workload();
                $key = md5($workload);
                $params = unserialize($workload);
                $project_id = $params['project_id'];
                $plugin_id = $params['plugin_id'];
                
                $datas = array();
                $cursor = $this->_plugin_collection->find(array(
                    'plugin_id' => $plugin_id
                ));
                if ($cursor->count() > 0) {
                    while ($cursor->hasNext()) {
                        $row = $cursor->getNext();
                        $this->_plugin_collection->syncPluginCollection($project_id, $plugin_id, $row['alias']);
                    }
                    $cache->remove($key);
                    $job->sendComplete('Complete');
                    return true;
                } else {
                    $cache->remove($key);
                    $job->sendException('程序异常：' . $rst['msg']);
                    return false;
                }
            });
            
            while ($this->_worker->work()) {
                if ($this->_worker->returnCode() != GEARMAN_SUCCESS) {
                    echo "return_code: " . $this->_worker->returnCode() . "\n";
                }
            }
            
            return $this->response;
        } catch (\Exception $e) {
            var_dump(exceptionMsg($e));
            $job->sendException(exceptionMsg($e));
            return false;
        }
    }
}
