<?php
/**
 * Gearman通用调用组件
 *
 * @author young 
 * @version 2014.07.21
 * 
 */
namespace Gearman\Controller;

use My\Common\Controller\Action;

class CommonController extends Action
{

    private $_worker;

    public function init()
    {
        $this->_worker = $this->gearman()->worker();
    }

    /**
     * 通用worker组件
     */
    public function exportAction()
    {
        try {
            $cache = $this->cache();
            $this->_worker->addFunction("commonworker", function (\GearmanJob $job) use($cache)
            {
                $job->handle();
                $workload = $job->workload();
                $params = unserialize($workload);
                
                $job->sendComplete('complete');
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
