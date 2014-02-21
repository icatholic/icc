<?php

/**
 * iDatabase表单排序设定，并在默认的搜索中添加排序
 *
 * @author young 
 * @version 2013.12.05
 * 
 */
namespace Idatabase\Controller;

use Zend\View\Model\JsonModel;
use Zend\Json\Json;
use My\Common\Controller\Action;

class OrderController extends Action
{

    private $_project_id;

    private $_collection_id;

    private $_order;

    private $_model;

    public function init()
    {
        $this->_project_id = isset($_REQUEST['__PROJECT_ID__']) ? trim($_REQUEST['__PROJECT_ID__']) : '';
        
        if (empty($this->_project_id)) {
            throw new \Exception('$this->_project_id值未设定');
        }
        
        $this->_collection_id = isset($_REQUEST['__COLLECTION_ID__']) ? trim($_REQUEST['__COLLECTION_ID__']) : '';
        if (empty($this->_collection_id)) {
            throw new \Exception('$this->_collection_id值未设定');
        }
        
        $this->_order = $this->model('Idatabase\Model\Order');

    }

    /**
     * IDatabase集合排列顺序管理
     *
     * @author young
     * @name IDatabase仪表盘显示界面
     * @version 2013.12.05 young
     */
    public function indexAction()
    {
        $query = array(
            'collection_id' => $this->_collection_id
        );
        
        $sort = array(
            'priority' => - 1,
            '_id' => 1
        );
        return $this->findAll(IDATABASE_COLLECTION_ORDERBY, $query, $sort);
    }

    public function addAction()
    {
        $field = filter_var($this->params()->fromPost('field', null), FILTER_SANITIZE_STRING);
        $order = (int) filter_var($this->params()->fromPost('order', 0), FILTER_SANITIZE_NUMBER_INT);
        $priority = (int) filter_var($this->params()->fromPost('priority', 0), FILTER_SANITIZE_NUMBER_INT);
        
        $datas = array();
        $datas['collection_id'] = $this->_collection_id;
        $datas['field'] = $field;
        $datas['order'] = $order;
        $datas['priority'] = $priority;
        $this->_order->insert($datas);
        
        return $this->msg(true, '添加信息成功');
    }

    /**
     * 编辑某项排序
     *
     * @author young
     * @name 编辑某项排序
     * @version 2013.11.14 young
     * @return JsonModel
     */
    public function editAction()
    {
        $_id = $this->params()->fromPost('_id', null);
        $field = filter_var($this->params()->fromPost('field', null), FILTER_SANITIZE_STRING);
        $order = (int) filter_var($this->params()->fromPost('order', 0), FILTER_SANITIZE_NUMBER_INT);
        $priority = (int) filter_var($this->params()->fromPost('priority', 0), FILTER_SANITIZE_NUMBER_INT);
        
        $datas = array();
        $datas['collection_id'] = $this->_collection_id;
        $datas['field'] = $field;
        $datas['order'] = $order;
        $datas['priority'] = $priority;
        
        $this->_order->update(array(
            '_id' => myMongoId($_id)
        ), array(
            '$set' => $datas
        ));
        return $this->msg(true, '编辑信息成功');
    }
    
    /**
     * 批量编辑集合排序
     *
     * @author young
     * @name 编辑集合排序
     * @version 2013.12.06 young
     * @return JsonModel
     */
    public function saveAction() {
        $updateInfos = $this->params()->fromPost('updateInfos', null);
        try {
            $updateInfos = Json::decode($updateInfos, Json::TYPE_ARRAY);
        } catch (\Exception $e) {
            return $this->msg(false, '无效的json字符串');
        }
        
        if (! is_array($updateInfos)) {
            return $this->msg(false, '更新数据无效');
        }
        
        foreach ($updateInfos as $row) {
            $_id = $row['_id'];
            unset($row['_id']);
            $this->_order->update(array(
                '_id' => myMongoId($_id),
                'collection_id' => $this->_collection_id
            ), array(
                '$set' => $row
            ));
        }
        return $this->msg(true, '更新字段属性成功');
    }

    /**
     * 删除某项排序
     *
     * @author young
     * @name 删除某项排序
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
            $this->_order->remove(array(
                '_id' => myMongoId($row),
                'collection_id' => $this->_collection_id
            ));
        }
        return $this->msg(true, '删除信息成功');
    }
}