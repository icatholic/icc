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
        resetTimeMemLimit(0,'8192M');
        $this->_worker = $this->gearman()->worker();
    }

    /**
     * 通用worker组件
     */
    public function workerAction()
    {
        try {
            $cache = $this->cache();
            $this->_worker->addFunction("commonworker", function (\GearmanJob $job) use($cache)
            {
                $job->handle();
                $workload = $job->workload();
                $params = unserialize($workload);
                $cmd = $params['__CMD__'];
                
                $result = '';
                $handle = popen("/usr/bin/php -l /home/webs/cloud.umaman.com/public/index.php -cw {$cmd}");
                while (! feof($handle)) {
                    $result .= fread($handle, 4096);
                }
                pclose($handle);
                $job->sendComplete($result);
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
