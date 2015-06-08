<?php
namespace Gearman\Controller;

use My\Common\Controller\Action;

class IndexController extends Action
{

    private $_worker;

    private $_data;

    public function init()
    {
        resetTimeMemLimit(0,'8192M');
        $this->_worker = $this->gearman()->worker();
        $this->_data = $this->model('Idatabase\Model\Data');
    }

    public function indexAction()
    {
        echo "Gearman worker is fine.";
        return $this->response;
    }
    
    /**
     * 处理map reduce统计
     *
     * @return string
     */
    public function mrAction()
    {
    	$cache = $this->cache();
    	$this->_worker->addFunction("mapreduce", function (\GearmanJob $job) use($cache)
    	{
    		try {
    			$job->handle();
    			$params = unserialize($job->workload());
    			$out = $params['out'];
    			$this->_data->setCollection($params['dataCollection']);
    			$this->_data->setReadPreference(\MongoClient::RP_SECONDARY);
    			$dataModel = $this->_data;
    			$statisticInfo = $params['statisticInfo'];
    			$query = $params['query'];
    			$method = $params['method'];
    			echo "params start \n";
    			var_dump($params);
    			echo "params end \n";
    			$rst = mapReduce($out, $dataModel, $statisticInfo, $query, $method);
    			echo "rst start \n";
    			var_dump($rst);
    			echo "rst end \n";
    			$cache->remove($out);
    			if (is_array($rst) && isset($rst['ok']) && $rst['ok'] === 0) {
    				switch ($rst['code']) {
    					case 500:
    						$job->sendWarning('根据查询条件，未检测到有效的统计数据');
    						break;
    					case 501:
    						$job->sendWarning('MapReduce执行失败，原因：' . $rst['msg']);
    						break;
    					case 502:
    						$job->sendWarning('程序正在执行中，请勿频繁尝试');
    						break;
    					case 503:
    						$job->sendWarning('程序异常：' . $rst['msg']);
    						break;
    				}
    				$job->sendFail();
    				return false;
    			}
    			echo "Complete \n";
    			$job->sendComplete('Complete');
    			return true;
    		} catch (\Exception $e) {
    		    $cache->remove($out);
    		    echo "Exception start \n";
    			var_dump(exceptionMsg($e));
    			echo "Exception end \n";
    			$job->sendException(exceptionMsg($e));
    		}
    	});
    
    	while ($this->_worker->work()) {
    		if ($this->_worker->returnCode() != GEARMAN_SUCCESS) {
    			echo "return_code: " . $this->_worker->returnCode() . "\n";
    		}
    	}
    
    	return $this->response;
    }

}
