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
        } catch (\Exception $e) {
            var_dump($e);
        }
    }
}
