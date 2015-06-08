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
use My\Common\MongoCollection;

class ProjectController extends Action
{

    private $_project;

    private $_collection;

    private $_acl;

    private $_model;

    private $_gmClient;

    private $_file;

    public function init()
    {
        $this->_project = $this->model('Idatabase\Model\Project');
        $this->_collection = $this->model('Idatabase\Model\Collection');
        $this->_acl = $this->collection(SYSTEM_ACCOUNT_PROJECT_ACL);
        $this->_file = $this->model('Idatabase\Model\File');
        
        $this->_project->setReadPreference(\MongoClient::RP_SECONDARY);
        $this->_collection->setReadPreference(\MongoClient::RP_SECONDARY);
        $this->_acl->setReadPreference(\MongoClient::RP_SECONDARY);
        
        $this->_gmClient = $this->gearman()->client();
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
        $search = $this->params()->fromQuery('query', $this->params()
            ->fromQuery('search', null));
        $start = intval($this->params()->fromQuery('start', 0));
        $limit = intval($this->params()->fromQuery('limit', 10));
        
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
        
        $cursor = $this->_project->find($query);
        $total = $cursor->count();
        $cursor->sort(array(
            '_id' => - 1
        ));
        $cursor->skip($start);
        $cursor->limit($limit);
        
        return $this->rst(iterator_to_array($cursor, false), $total, true);
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
        $this->_project->insertRef($project);
        
        // 为添加项目的用户，添加项目的权限
        if (isset($_SESSION['account']['role']) && $_SESSION['account']['role'] !== 'root') {
            // 添加集合权限给到该用户
            if (isset($_SESSION['account']['username'])) {
                if ($project['_id'] instanceof \MongoId) {
                    $project_id = $project['_id']->__toString();
                    if ($this->_acl->findOne(array(
                        'username' => $_SESSION['account']['username'],
                        'project_id' => $project_id
                    )) === null) {
                        $this->_acl->insert(array(
                            'username' => $_SESSION['account']['username'],
                            'project_id' => $project_id,
                            'collection_ids' => array()
                        ));
                        $this->getAcl();
                    }
                }
            }
        }
        
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
     * 导出该站点的bson文件
     */
    public function exportBsonAction()
    {
        resetTimeMemLimit();
        $project_id = isset($_REQUEST['__PROJECT_ID__']) ? trim($_REQUEST['__PROJECT_ID__']) : '';
        $wait = $this->params()->fromQuery('wait', null);
        $cacheKey = 'bson_export_' . $project_id;
        if ($wait) {
            if ($this->cache($cacheKey) !== null) {
                return $this->msg(true, '处理完成');
            } else {
                return $this->msg(false, '请求处理中……');
            }
        } elseif ($this->cache($cacheKey) !== null) {
            $zip = $this->cache($cacheKey);
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment;filename="bson_' . date('YmdHis') . '.zip"');
            header('Cache-Control: max-age=0');
            echo $this->_file->getFileFromGridFS($zip);
            // 执行清理工作
            $this->cache()->remove($cacheKey);
            $this->_file->removeFileFromGridFS($zip);
            exit();
        } else {
            // 任务交给后台worker执行
            $params = array(
                'key' => $cacheKey,
                '_id' => $project_id
            );
            fb($params, 'LOG');
            $jobHandle = $this->_gmClient->doBackground('bsonExport', serialize($params), $cacheKey);
            return $this->msg(false, '请求已被受理');
        }
        
        // $tmp = tempnam(sys_get_temp_dir(), 'zip_');
        // $zip = new \ZipArchive();
        // $res = $zip->open($tmp, \ZipArchive::CREATE);
        // if ($res === true) {
        // // 添加项目数据
        // $filename = $this->collection2bson(IDATABASE_PROJECTS, array(
        // '_id' => myMongoId($_id)
        // ));
        // $zip->addFile($filename, IDATABASE_PROJECTS . '.bson');
        
        // // 获取密钥信息
        // $filename = $this->collection2bson(IDATABASE_KEYS, array(
        // 'project_id' => $_id
        // ));
        // $zip->addFile($filename, IDATABASE_KEYS . '.bson');
        
        // // 添加集合数据
        // $filename = $this->collection2bson(IDATABASE_COLLECTIONS, array(
        // 'project_id' => $_id
        // ));
        // $zip->addFile($filename, IDATABASE_COLLECTIONS . '.bson');
        
        // // 添加结构数据
        // $collection_ids = array();
        // $cursor = $this->_collection->find(array(
        // 'project_id' => $_id
        // ));
        // while ($cursor->hasNext()) {
        // $row = $cursor->getNext();
        // $collection_ids[] = $row['_id']->__toString();
        // }
        
        // $filename = $this->collection2bson(IDATABASE_STRUCTURES, array(
        // 'collection_id' => array(
        // '$in' => $collection_ids
        // )
        // ));
        // $zip->addFile($filename, IDATABASE_STRUCTURES . '.bson');
        
        // // 获取映射信息
        // $filename = $this->collection2bson(IDATABASE_MAPPING, array(
        // 'collection_id' => array(
        // '$in' => $collection_ids
        // )
        // ));
        // $zip->addFile($filename, IDATABASE_MAPPING . '.bson');
        
        // // 导出集合数据信息
        // if (! empty($collection_ids)) {
        // foreach ($collection_ids as $collection_id) {
        // $filename = $this->collection2bson(iCollectionName($collection_id), array());
        // $zip->addFile($filename, iCollectionName($collection_id) . '.bson');
        // }
        // }
        // }
        // $zip->close();
    }

    /**
     * 将指定集合内的数据转化成bson文件
     *
     * @param string $collectionName            
     * @param array $query            
     * @return string
     */
    private function collection2bson($collectionName, $query = array(), $out = 'file')
    {
        $dataModel = $this->collection($collectionName);
        $cursor = $dataModel->find($query);
        if ($out == 'file') {
            $tmp = tempnam(sys_get_temp_dir(), 'bson_');
            $fp = fopen($tmp, 'w');
            while ($cursor->hasNext()) {
                $row = $cursor->getNext();
                fwrite($fp, bson_encode($row));
            }
            fclose($fp);
            return $tmp;
        } else {
            $bson = '';
            while ($cursor->hasNext()) {
                $row = $cursor->getNext();
                $bson .= bson_encode($row);
            }
            return $bson;
        }
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
