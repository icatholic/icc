<?php
/**
 * iDatabase项目管理
 *
 * @author young 
 * @version 2013.11.11
 * 
 */
namespace Idatabase\Controller;

use Zend\View\Model\JsonModel;
use Zend\Json\Json;
use My\Common\Controller\Action;

class ProjectController extends Action
{

    private $_project;

    private $_acl;

    public function init()
    {
        $this->_project = $this->model('Idatabase\Model\Project');
        $this->_acl = $this->collection(SYSTEM_ACCOUNT_PROJECT_ACL);
        $this->getAcl();
    }

    /**
     * 读取全部项目列表
     *
     * @author young
     * @name 读取全部项目列表
     * @version 2013.11.07 young
     */
    public function indexAction()
    {
        $query = array();
        $isSystem = filter_var($this->params()->fromQuery('isSystem', ''), FILTER_VALIDATE_BOOLEAN);
        $search = $this->params()->fromQuery('query', null);
        if ($search != null) {
            $search = myMongoRegex($search);
            $searchQuery = array(
                '$or' => array(
                    array(
                        'name' => $search
                    ),
                    array(
                        'sn' => $search
                    ),
                    array(
                        'desc' => $search
                    )
                )
            );
            $query['$and'][] = $searchQuery;
        }
        
        $query['$and'][] = array(
            'isSystem' => $isSystem
        );
        
        if (! $_SESSION['acl']['admin']) {
            $query['$and'][] = array(
                '_id' => array(
                    '$in' => myMongoId($_SESSION['acl']['project'])
                )
            );
        }

        return $this->findAll(IDATABASE_PROJECTS, $query);
    }

    /**
     * 添加新的项目
     *
     * @author young
     * @name 添加新的项目
     * @version 2013.11.14 young
     * @return JsonModel
     */
    public function addAction()
    {
        $name = $this->params()->fromPost('name', null);
        $sn = $this->params()->fromPost('sn', null);
        $isSystem = filter_var($this->params()->fromPost('isSystem', ''), FILTER_VALIDATE_BOOLEAN);
        $desc = $this->params()->fromPost('desc', null);
        
        if ($name == null) {
            return $this->msg(false, '请填写项目名称');
        }
        
        if ($sn == null) {
            return $this->msg(false, '请填写项目编号');
        }
        
        if ($desc == null) {
            return $this->msg(false, '请填写项目描述');
        }
        
        if ($this->checkProjectNameExist($name)) {
            return $this->msg(false, '项目名称已经存在');
        }
        
        if ($this->checkProjectSnExist($sn)) {
            return $this->msg(false, '项目编号已经存在');
        }
        
        $project = array();
        $project['name'] = $name;
        $project['sn'] = $sn;
        $project['isSystem'] = isset($_SESSION['account']['role']) && $_SESSION['account']['role'] === 'root' ? $isSystem : false;
        $project['desc'] = $desc;
        $this->_project->insert($project);
        
        return $this->msg(true, '添加信息成功');
    }

    /**
     * 编辑新的项目
     *
     * @author young
     * @name 编辑新的项目
     * @version 2013.11.14 young
     * @return JsonModel
     */
    public function editAction()
    {
        $_id = $this->params()->fromPost('_id', null);
        $name = $this->params()->fromPost('name', null);
        $sn = $this->params()->fromPost('sn', null);
        $isSystem = filter_var($this->params()->fromPost('isSystem', ''), FILTER_VALIDATE_BOOLEAN);
        $desc = $this->params()->fromPost('desc', null);
        
        if ($_id == null) {
            return $this->msg(false, '无效的项目编号');
        }
        
        if ($name == null) {
            return $this->msg(false, '请填写项目名称');
        }
        
        if ($sn == null) {
            return $this->msg(false, '请填写项目编号');
        }
        
        if ($desc == null) {
            return $this->msg(false, '请填写项目描述');
        }
        
        $oldProjectInfo = $this->_project->findOne(array(
            '_id' => myMongoId($_id)
        ));
        
        if ($this->checkProjectNameExist($name) && $oldProjectInfo['name'] != $name) {
            return $this->msg(false, '项目名称已经存在');
        }
        
        if ($this->checkProjectSnExist($sn) && $oldProjectInfo['sn'] != $sn) {
            return $this->msg(false, '项目编号已经存在');
        }
        
        $project = array();
        $project['name'] = $name;
        $project['sn'] = $sn;
        $project['isSystem'] = isset($_SESSION['account']['role']) && $_SESSION['account']['role'] === 'root' ? $isSystem : false;
        $project['desc'] = $desc;
        $this->_project->update(array(
            '_id' => myMongoId($_id)
        ), array(
            '$set' => $project
        ));
        
        return $this->msg(true, '编辑信息成功');
    }

    /**
     * 删除新的项目
     *
     * @author young
     * @name 删除新的项目
     * @version 2013.11.14 young
     * @return JsonModel
     */
    public function removeAction()
    {
        $_id = $this->params()->fromPost('_id', null);
        try {
            $_id = Json::decode($_id, Json::TYPE_ARRAY);
        } catch (\Exception $e) {
            return $this->msg(false, '无效的json字符串');
        }
        
        if (! is_array($_id)) {
            return $this->msg(false, '请选择你要删除的项');
        }
        foreach ($_id as $row) {
            $this->_project->remove(array(
                '_id' => myMongoId($row)
            ));
        }
        return $this->msg(true, '删除信息成功');
    }

    /**
     * 检测一个项目是否存在，根据名称和编号
     *
     * @param string $info            
     * @return boolean
     */
    private function checkProjectNameExist($info)
    {
        $info = $this->_project->findOne(array(
            'name' => $info
        ));
        
        if ($info == null) {
            return false;
        }
        return true;
    }

    private function checkProjectSnExist($info)
    {
        $info = $this->_project->findOne(array(
            'sn' => $info
        ));
        
        if ($info == null) {
            return false;
        }
        return true;
    }

    private function getAcl()
    {
        $_SESSION['acl']['admin'] = false;
        $_SESSION['acl']['project'] = array();
        $_SESSION['acl']['collection'] = array();
        
        if (isset($_SESSION['account']['role']) && ! in_array($_SESSION['account']['role'], array(
            'root',
            'admin'
        ), true)) {
            $cursor = $this->_acl->find(array(
                'username' => $_SESSION['account']['username']
            ));
            while ($cursor->hasNext()) {
                $row = $cursor->getNext();
                $_SESSION['acl']['project'][] = $row['project_id'];
                $_SESSION['acl']['collection'] = array_merge($_SESSION['acl']['collection'], $row['collection_ids']);
            }
        } else {
            $_SESSION['acl']['admin'] = true;
        }
    }
}
