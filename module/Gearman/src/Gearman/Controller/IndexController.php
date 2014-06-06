<?php
namespace Gearman\Controller;

use Zend\View\Model\ViewModel;
use My\Common\Controller\Action;

class IndexController extends Action
{

    private $_worker;

    public function init()
    {
        $this->_worker = $this->gearman()->worker();
    }

    public function indexAction()
    {
        echo "Gearman worker is fine.";
        return $this->response;
    }

    public function cacheAction()
    {
        if (($rst = $this->cache()->load($key)) === false) {
            $datas = 'my cache content';
            $this->cache()->save($datas);
            echo "no cache,but have been saved";
            return $this->response;
        }
        var_dump($rst);
        return $this->response;
    }

    /**
     * 处理map reduce统计
     *
     * @return string
     */
    public function mrAction()
    {
        $gmworker->addFunction("mapreduce", array(
            $this,
            'mapReduceWorker'
        ));
        
        while ($this->_worker->work()) {
            if ($this->_worker->returnCode() != GEARMAN_SUCCESS) {
                echo "return_code: " . $this->_worker->returnCode() . "\n";
            }
        }
    }

    private function mapReduceWorker(\GearmanJob $job)
    {
        try {
            $job->handle();
            $params = unserialize($job->workload());
            
            $out = $params['out'];
            $dataModel = $params['dataModel'];
            $statisticInfo = $params['statisticInfo'];
            $query = $params['query'];
            $method = $params['method'];
            mapReduce($out, $dataModel, $statisticInfo, $query, $method);
            $job->sendComplete(serialize($rst));
            return true;
        } catch (\Exception $e) {
            $job->sendException(exceptionMsg($e));
        }
    }
}
