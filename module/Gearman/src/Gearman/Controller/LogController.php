<?php
/**
 * Gearman方式同步日志数据处理插件
*
* @author young
* @version 2014.06.16
*
*/
namespace Gearman\Controller;

use My\Common\Controller\Action;

class LogController extends Action
{

    private $_worker;


    public function init()
    {
        resetTimeMemLimit(0,'8192M');
        $this->_worker = $this->gearman()->worker();
    }

    /**
     * 记录日志在worker机器
     */
    public function logAction()
    {
        try {
            $this->_worker->addFunction("logError", function (\GearmanJob $job)
            {
                $job->handle();
                $workload = $job->workload();
                logError($workload);
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
