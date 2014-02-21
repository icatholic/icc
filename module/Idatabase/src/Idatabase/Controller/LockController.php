<?php
/**
 * iDatabase集合加密管理
 *
 * @author young 
 * @version 2014.01.04
 * 
 */
namespace Idatabase\Controller;

use Zend\View\Model\JsonModel;
use Zend\Json\Json;
use My\Common\Controller\Action;

class LockController extends Action
{

    private $_lock;

    private $_project_id;

    private $_collection_id;

    public function init()
    {
        $this->_project_id = isset($_REQUEST['__PROJECT_ID__']) ? trim($_REQUEST['__PROJECT_ID__']) : '';
        if (empty($this->_project_id))
            throw new \Exception('$this->_project_id值未设定');
        
        $this->_collection_id = isset($_REQUEST['__COLLECTION_ID__']) ? trim($_REQUEST['__COLLECTION_ID__']) : '';
        if (empty($this->_collection_id))
            throw new \Exception('$this->_collection_id值未设定');
        
        $this->_lock = $this->model('Idatabase\Model\Lock');
    }

    /**
     * 读取安全密码
     *
     * @author young
     * @name 读取安全密码
     * @version 2013.01.04 young
     */
    public function indexAction()
    {
        $query = array(
            'project_id' => $this->_project_id,
            'collection_id' => $this->_collection_id
        );
        return $this->findAll(IDATABASE_LOCK, $query);
    }

    /**
     * 更新安全密码
     *
     * @author young
     * @name 更新安全密码
     * @version 2014.01.02 young
     * @return JsonModel
     */
    public function updateAction()
    {
        $oldPassword = trim($this->params()->fromPost('oldPassword', ''));
        $password = trim($this->params()->fromPost('password', ''));
        $repeatPassword = trim($this->params()->fromPost('repeatPassword', ''));
        $active = filter_var($this->params()->fromPost('active', ''), FILTER_VALIDATE_BOOLEAN);
        
        $criteria = array(
            'project_id' => $this->_project_id,
            'collection_id' => $this->_collection_id
        );
        
        $lockInfo = $this->_lock->findOne($criteria);
        if ($lockInfo != null) {
            if (empty($oldPassword)) {
                return $this->msg(true, '请输入原密码');
            }
            if (sha1($oldPassword) !== $lockInfo['password']) {
                return $this->msg(true, '身份验证未通过');
            }
        }
        
        if (empty($password) || empty($repeatPassword)) {
            return $this->msg(true, '请输入新密码或者确认密码');
        }
        
        if ($password !== $repeatPassword) {
            return $this->msg(true, '两次密码输入不一致');
        }
        
        $datas = array(
            'password' => sha1($password),
            'active' => $active
        );
        
        if ($active) {
            $rst = $this->_lock->update($criteria, array(
                '$set' => $datas
            ), array(
                'upsert' => true
            ));
            
            if ($rst['ok']) {
                return $this->msg(true, '设定集合访问密钥成功');
            } else {
                return $this->msg(false, Json::encode($rst));
            }
        } else {
            $this->_lock->remove($criteria);
            return $this->msg(true, '清除安全密钥成功');
        }
    }

    /**
     * 验证密码
     */
    public function verifyAction()
    {
        $password = trim($this->params()->fromPost('password', ''));
        if (empty($password)) {
            return $this->msg(false, '请输入安全密码');
        }
        
        $lockInfo = $this->_lock->findOne(array(
            'project_id' => $this->_project_id,
            'collection_id' => $this->_collection_id,
            'active' => true
        ));
        
        if ($lockInfo['password'] !== sha1($password)) {
            return $this->msg(false, '验证失败');
        }
        
        return $this->msg(true, '通过安全验证');
    }
}
