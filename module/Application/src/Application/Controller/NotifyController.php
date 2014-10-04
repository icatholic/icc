<?php
/**
 * 计划任务通知控制器
 * 
 * @author ming
 *
 */
namespace Application\Controller;

use My\Common\Controller\Action;

class NotifyController extends Action
{

    private $_keys;

    private $_project;

    public function init()
    {
        $this->_keys = $this->model('Idatabase\Model\Key');
        $this->_project = $this->model('Idatabase\Model\Project');
        
        $this->_keys->setReadPreference(\MongoClient::RP_SECONDARY);
        $this->_project->setReadPreference(\MongoClient::RP_SECONDARY);
    }

    public function keysAction()
    {
        $datas = $this->_keys->findAll(array(
            'expire' => array(
                '$gte' => new \MongoDate(time()),
                '$lte' => new \MongoDate(time() + 7 * 24 * 3600)
            )
        ));
        
        $emailContent = '';
        foreach ($datas as $row) {
            $project_id = $row['project_id'];
            $projectInfo = $this->_project->findOne(array(
                '_id' => myMongoId($project_id)
            ));
            if (! empty($projectInfo)) {
                $emailContent .= "项目：“{$projectInfo['name']}”的密钥将于" . date('Y-m-d H:i:s', $row['expire']->sec) . "过期\n";
            }
        }
        
        if (! empty($emailContent))
            sendEmail(array(
                'youngyang@icatholic.net.cn',
                'dkding@icatholic.net.cn',
                'willwan@icatholic.net.cn',
                'stephen_shao@catholic.net.cn'
            ), 'ICC密钥过期提醒', $emailContent);
        
        echo 'OK';
        return $this->response;
    }
}