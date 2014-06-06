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
        $this->_worker->addFunction("mapreduce", array(
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
            
            $rst = mapReduce($out, $dataModel, $statisticInfo, $query, $method);
            
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
            $job->sendComplete();
            return true;
        } catch (\Exception $e) {
            $job->sendException(exceptionMsg($e));
        }
    }
}
