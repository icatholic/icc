<?php
/**
 * iDatabase集合映射管理系统
 *
 * @author young 
 * @version 2014.01.02
 * 
 */
namespace Idatabase\Controller;

use Zend\View\Model\JsonModel;
use Zend\Json\Json;
use My\Common\Controller\Action;

class MappingController extends Action
{

    private $_mapping;

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
        
        $this->_mapping = $this->model('Idatabase\Model\Mapping');
    }

    /**
     * 读取映射关系
     *
     * @author young
     * @name 读取映射关系
     * @version 2013.01.04 young
     */
    public function indexAction()
    {
        $query = array(
            'project_id' => $this->_project_id,
            'collection_id' => $this->_collection_id
        );
        return $this->findAll(IDATABASE_MAPPING, $query);
    }

    /**
     * 更新映射关系
     *
     * @author young
     * @name 更新映射关系
     * @version 2014.01.02 young
     * @return JsonModel
     */
    public function updateAction()
    {
        $collection = trim($this->params()->fromPost('collection', ''));
        $database = trim($this->params()->fromPost('database', DEFAULT_DATABASE));
        $cluster = trim($this->params()->fromPost('cluster', DEFAULT_CLUSTER));
        $active = filter_var($this->params()->fromPost('active', ''), FILTER_VALIDATE_BOOLEAN);
        
        $criteria = array(
            'project_id' => $this->_project_id,
            'collection_id' => $this->_collection_id
        );
        
        $datas = array(
            'collection' => $collection,
            'database' => $database,
            'cluster' => $cluster,
            'active' => $active
        );
        
        $rst = $this->_mapping->update($criteria, array(
            '$set' => $datas
        ), array(
            'upsert' => true
        ));
        
        if ($rst['ok']) {
            return $this->msg(true, '设定映射关系成功');
        } else {
            return $this->msg(false, Json::encode($rst));
        }
    }
}
