<?php
/**
 * iDatabase项目内数据集合管理
 *
 * @author young 
 * @version 2013.11.19
 * 
 */
namespace Idatabase\Controller;

use Zend\View\Model\JsonModel;
use Zend\Json\Json;
use My\Common\Controller\Action;
use Idatabase\Model\PluginData;
use Zend\Validator\File\Md5;

class CollectionController extends Action
{

    private $_collection;

    private $_structure;

    private $_plugin;

    private $_project_plugin;

    private $_plugin_collection;

    private $_plugin_structure;

    private $_plugin_data;

    private $_plugin_index;

    private $_lock;

    private $_mapping;

    private $_project_id;

    private $_plugin_id = '';

    private $_sync;

    private $_gmClient;

    public function init()
    {
        $this->_project_id = isset($_REQUEST['__PROJECT_ID__']) ? trim($_REQUEST['__PROJECT_ID__']) : '';
        $this->_plugin_id = isset($_REQUEST['__PLUGIN_ID__']) ? trim($_REQUEST['__PLUGIN_ID__']) : '';
        $this->_sync = isset($_REQUEST['__SYNC__']) ? filter_var($_REQUEST['__SYNC__'], FILTER_VALIDATE_BOOLEAN) : false;
        
        if (empty($this->_project_id))
            throw new \Exception('$this->_project_id值未设定');
        
        $this->_collection = $this->model('Idatabase\Model\Collection');
        $this->_structure = $this->model('Idatabase\Model\Structure');
        $this->_project_plugin = $this->model('Idatabase\Model\ProjectPlugin');
        $this->_plugin = $this->model('Idatabase\Model\Plugin');
        $this->_plugin_collection = $this->model('Idatabase\Model\PluginCollection');
        $this->_plugin_structure = $this->model('Idatabase\Model\PluginStructure');
        $this->_plugin_data = $this->model('Idatabase\Model\PluginData');
        $this->_lock = $this->model('Idatabase\Model\Lock');
        $this->_mapping = $this->model('Idatabase\Model\Mapping');
        $this->_plugin_index = $this->model('Idatabase\Model\PluginIndex');
        
        $this->_collection->setReadPreference(\MongoClient::RP_SECONDARY);
        $this->_structure->setReadPreference(\MongoClient::RP_SECONDARY);
        $this->_project_plugin->setReadPreference(\MongoClient::RP_SECONDARY);
        $this->_plugin_collection->setReadPreference(\MongoClient::RP_SECONDARY);
        $this->_plugin_structure->setReadPreference(\MongoClient::RP_SECONDARY);
        $this->_lock->setReadPreference(\MongoClient::RP_SECONDARY);
        $this->_mapping->setReadPreference(\MongoClient::RP_SECONDARY);
        $this->_plugin_index->setReadPreference(\MongoClient::RP_SECONDARY);
        
        $this->_gmClient = $this->gearman()->client();
    }

    /**
     * 读取指定项目内的全部集合列表
     * 支持专家模式和普通模式显示，对于一些说明表和关系表，请在定义时，定义为普通模式
     *
     * @author young
     * @name 读取指定项目内的全部集合列表
     * @version 2014.01.21 young
     */
    public function indexAction()
    {
        $startTime = microtime(true);
        $search = trim($this->params()->fromQuery('query', ''));
        if (empty($search)) {
            $search = trim($this->params()->fromQuery('search', ''));
        }
        $action = trim($this->params()->fromQuery('action', ''));
        $start = intval($this->params()->fromQuery('start', 0));
        $limit = intval($this->params()->fromQuery('limit', 10));
        $plugin_id = $this->_plugin_id;
        $sort = array(
            'orderBy' => 1,
            '_id' => - 1
        );
        
        $query = array();
        $query['project_id'] = $this->_project_id;
        if ($action !== 'all') {
            $query['plugin_id'] = $plugin_id;
            
            // 如果加载插件，则自动检测是否添加索引
            //$this->_plugin_index->autoCreateIndexes($this->_project_id, $plugin_id);
        }
        
        if ($search != '') {
            $search = myMongoRegex($search);
            $query['name'] = $search;
        }
        
        $isProfessional = isset($_SESSION['account']['isProfessional']) ? $_SESSION['account']['isProfessional'] : false;
        if ($isProfessional === false) {
            $query['isProfessional'] = false;
        }
        
        if (! $_SESSION['acl']['admin']) {
            $query['_id'] = array(
                '$in' => myMongoId($_SESSION['acl']['collection'])
            );
        }
        
        $datas = array();
        $cursor = $this->_collection->find($query);
        $cursor->sort($sort);
        $cursor->skip($start)->limit($limit);
        while ($cursor->hasNext()) {
            $row = $cursor->getNext();
            $row['locked'] = false;
            $lockInfo = $this->_lock->count(array(
                'project_id' => $this->_project_id,
                'collection_id' => myMongoId($row['_id']),
                'active' => true
            ));
            if ($lockInfo > 0) {
                $row['locked'] = true;
            }
            
            // 判断当前集合是否默认数据集合，如果是显示正确的集合
            if ($this->_plugin_data->isDefault(myMongoId($row['_id']))) {
                $row['defaultSourceData'] = true;
            } else {
                $row['defaultSourceData'] = false;
            }
            
            $datas[] = $row;
        }
        return $this->rst($datas, $cursor->count(), true);
    }

    /**
     * 增加创建索引的操作增加性能，当集合很大时，可能导致创建失败，建议在集合创建之处就进行索引设置。
     */
    public function createIndexAction()
    {
        $plugin_id = $this->_plugin_id;
        $this->_plugin_index->autoCreateIndexes($this->_project_id, $plugin_id);
        return $this->msg(true, '创建索引成功');
    }

    /**
     * 同步插件集合数据结构
     *
     * @author young
     * @name 同步插件集合数据结构
     * @version 2014.01.24 young
     */
    public function syncAction()
    {
        if (! empty($this->_plugin_id)) {
            $datas = array();
            $cursor = $this->_plugin_collection->find(array(
                'plugin_id' => $this->_plugin_id
            ));
            if ($cursor->count() > 0) {
                while ($cursor->hasNext()) {
                    $row = $cursor->getNext();
                    $this->_plugin_collection->syncPluginCollection($this->_project_id, $this->_plugin_id, $row['alias']);
                }
                return $this->msg(true, '同步成功');
            } else {
                return $this->msg(false, '该插件中的未发现有效集合');
            }
        } else {
            return $this->msg(false, '插件编号为空');
        }
    }

    /**
     * 采用Gearman的方式同步插件数据
     *
     * @return Ambigous <\Zend\View\Model\JsonModel, multitype:string Ambigous <boolean, bool> >
     */
    public function syncGearmanAction()
    {
        if (! empty($this->_plugin_id)) {
            
            $wait = $this->params()->fromPost('wait', false);
            $params = array();
            $params['project_id'] = $this->_project_id;
            $params['plugin_id'] = $this->_plugin_id;
            
            $key = md5(serialize($params));
            if ($this->cache($key) !== null) {
                return $this->msg(false, '同步进行中……');
            } elseif (! empty($wait)) {
                return $this->msg(true, '同步成功');
            } else {
                $jobHandle = $this->_gmClient->doBackground('pluginCollectionSync', serialize($params), $key);
                $stat = $this->_gmClient->jobStatus($jobHandle);
                if (isset($stat[0]) && $stat[0]) {
                    $this->cache()->save(true, $key, 60);
                }
                return $this->msg(false, '请求受理');
            }
        } else {
            return $this->msg(false, '插件编号为空');
        }
    }

    /**
     * 触发关联动作
     *
     * @author young
     * @name 触发关联动作
     * @version 2014.02.12 young
     */
    public function hookAction()
    {
        $collection_id = isset($_REQUEST['__COLLECTION_ID__']) ? trim($_REQUEST['__COLLECTION_ID__']) : '';
        $collectionInfo = $this->_collection->findOne(array(
            '_id' => myMongoId($collection_id),
            'project_id' => $this->_project_id
        ));
        if ($collectionInfo != null) {
            try {
                $postDatas = array(
                    '__PROJECT_ID__' => $this->_project_id,
                    '__COLLECTION_ID__' => $collection_id
                );
                $url = $collectionInfo['hook'];
                $hookKey = $collectionInfo['hookKey'];
                $sign = dataSignAlgorithm($postDatas, $hookKey);
                $postDatas['__SIGN__'] = $sign;
                $response = doPost($url, $postDatas);
                if ($response === false)
                    return $this->msg(false, '网络请求失败');
                $this->_collection->update(array(
                    '_id' => myMongoId($collection_id)
                ), array(
                    '$set' => array(
                        'hookLastResponseResult' => $response
                    )
                ));
                return $this->msg(true, '触发联动操作成功' . $response);
            } catch (\Exception $e) {
                return $this->msg(false, $e->getMessage());
            }
        } else {
            return $this->msg(false, '触发联动操作失败');
        }
    }

    /**
     * 添加新的集合
     *
     * @author young
     * @name 添加新的集合
     * @version 2013.11.20 young
     * @return JsonModel
     */
    public function addAction()
    {
        try {
            $project_id = $this->_project_id;
            $name = $this->params()->fromPost('name', null);
            $alias = $this->params()->fromPost('alias', null);
            $isProfessional = filter_var($this->params()->fromPost('isProfessional', false), FILTER_VALIDATE_BOOLEAN);
            $isTree = filter_var($this->params()->fromPost('isTree', false), FILTER_VALIDATE_BOOLEAN);
            $desc = $this->params()->fromPost('desc', null);
            $orderBy = intval($this->params()->fromPost('orderBy', 0));
            $isRowExpander = filter_var($this->params()->fromPost('isRowExpander', false), FILTER_VALIDATE_BOOLEAN);
            $rowExpanderTpl = $this->params()->fromPost('rowExpanderTpl', '');
            $plugin = filter_var($this->params()->fromPost('plugin', false), FILTER_VALIDATE_BOOLEAN);
            $plugin_id = $this->_plugin_id;
            $isAutoHook = filter_var($this->params()->fromPost('isAutoHook', false), FILTER_VALIDATE_BOOLEAN);
            $defaultSourceData = filter_var($this->params()->fromPost('defaultSourceData', false), FILTER_VALIDATE_BOOLEAN);
            $hook = trim($this->params()->fromPost('hook', ''));
            $hookKey = trim($this->params()->fromPost('hookKey', ''));
            $hookNotifyEmail = trim($this->params()->fromPost('hook_notify_email', ''));
            $isAllowHttpAccess = filter_var($this->params()->fromPost('isAllowHttpAccess', false), FILTER_VALIDATE_BOOLEAN);
            $promissionDefinition = $this->params()->fromPost('promissionDefinition', array());
            $hookDebugMode = filter_var($this->params()->fromPost('hook_debug_mode', false), FILTER_VALIDATE_BOOLEAN);
            
            if ($project_id == null) {
                return $this->msg(false, '无效的项目编号');
            }
            
            if ($name == null) {
                return $this->msg(false, '请填写集合名称');
            }
            
            if ($alias == null || ! preg_match("/[a-z0-9_]/i", $alias)) {
                return $this->msg(false, '请填写集合别名，只接受英文与字母');
            }
            
            if ($desc == null) {
                return $this->msg(false, '请填写集合描述');
            }
            
            if ($this->checkPluginNameExist($name) || $this->checkPluginAliasExist($alias)) {
                return $this->msg(false, '集合名称或者别名在插件命名中已经存在，请勿重复使用');
            }
            
            if ($this->checkCollecionNameExist($name) || $this->checkCollecionAliasExist($alias)) {
                return $this->msg(false, '集合名称或者别名已经被使用，请勿重复使用');
            }
            
            $datas = array();
            $datas['project_id'] = array(
                $project_id
            );
            $datas['name'] = $name;
            $datas['alias'] = $alias;
            $datas['isProfessional'] = $isProfessional;
            $datas['isTree'] = $isTree;
            $datas['desc'] = $desc;
            $datas['orderBy'] = $orderBy;
            $datas['plugin'] = $plugin;
            $datas['plugin_id'] = $plugin_id;
            $datas['isRowExpander'] = $isRowExpander;
            $datas['rowExpanderTpl'] = $rowExpanderTpl;
            $datas['isAutoHook'] = $isAutoHook;
            $datas['defaultSourceData'] = $defaultSourceData;
            $datas['hook'] = $hook;
            $datas['hookKey'] = $hookKey;
            $datas['hook_notify_email'] = $hookNotifyEmail;
            $datas['hook_debug_mode'] = $hookDebugMode;
            $datas['isAllowHttpAccess'] = $isAllowHttpAccess;
            $datas['promissionDefinition'] = $promissionDefinition;
            $datas['plugin_collection_id'] = $this->_plugin_collection->addPluginCollection($datas);
            $rst = $this->_collection->insert($datas);
            
            // 设定或者取消当前集合为插件默认的数据集合
            if (! empty($plugin_id)) {
                if ($defaultSourceData) {
                    if (isset($rst['_id']) && $rst['_id'] instanceof \MongoId) {
                        $data_collection_id = $rst['_id']->__toString();
                        $this->_plugin_data->setDefault($datas['plugin_collection_id'], $data_collection_id);
                    }
                }
            }
            
            return $this->msg(true, '添加集合成功');
        } catch (\Exception $e) {
            var_dump($e->getTraceAsString());
        }
    }

    /**
     * 批量编辑集合信息
     *
     * @author young
     * @name 批量编辑集合信息
     * @version 2013.12.02 young
     * @return JsonModel
     */
    public function saveAction()
    {
        return $this->msg(true, '集合编辑不支持批量修改功能');
    }

    /**
     * 编辑新的集合信息
     *
     * @author young
     * @name 编辑新的集合信息
     * @version 2013.11.20 young
     * @return JsonModel
     */
    public function editAction()
    {
        $_id = $this->params()->fromPost('_id', null);
        $project_id = $this->_project_id;
        $name = $this->params()->fromPost('name', null);
        $alias = $this->params()->fromPost('alias', null);
        $isProfessional = filter_var($this->params()->fromPost('isProfessional', false), FILTER_VALIDATE_BOOLEAN);
        $isTree = filter_var($this->params()->fromPost('isTree', false), FILTER_VALIDATE_BOOLEAN);
        $desc = $this->params()->fromPost('desc', null);
        $orderBy = intval($this->params()->fromPost('orderBy', 0));
        $isRowExpander = filter_var($this->params()->fromPost('isRowExpander', false), FILTER_VALIDATE_BOOLEAN);
        $rowExpanderTpl = $this->params()->fromPost('rowExpanderTpl', '');
        $plugin = filter_var($this->params()->fromPost('plugin', false), FILTER_VALIDATE_BOOLEAN);
        $plugin_id = $this->_plugin_id;
        $isAutoHook = filter_var($this->params()->fromPost('isAutoHook', false), FILTER_VALIDATE_BOOLEAN);
        $defaultSourceData = filter_var($this->params()->fromPost('defaultSourceData', false), FILTER_VALIDATE_BOOLEAN);
        $hook = trim($this->params()->fromPost('hook', ''));
        $hookKey = trim($this->params()->fromPost('hookKey', ''));
        $hookNotifyEmail = trim($this->params()->fromPost('hook_notify_email', ''));
        $hookDebugMode = filter_var($this->params()->fromPost('hook_debug_mode', false), FILTER_VALIDATE_BOOLEAN);
        $isAllowHttpAccess = filter_var($this->params()->fromPost('isAllowHttpAccess', false), FILTER_VALIDATE_BOOLEAN);
        $promissionDefinition = $this->params()->fromPost('promissionDefinition', array());
        $plugin_collection_id = trim($this->params()->fromPost('__PLUGIN_COLLECTION_ID__', ''));
        
        if ($_id == null) {
            return $this->msg(false, '无效的集合编号');
        }
        
        if ($project_id == null) {
            return $this->msg(false, '无效的项目编号');
        }
        
        if ($name == null) {
            return $this->msg(false, '请填写集合名称');
        }
        
        if ($alias == null || ! preg_match("/[a-z0-9]/i", $alias)) {
            return $this->msg(false, '请填写集合别名，只接受英文与字母');
        }
        
        if ($desc == null) {
            return $this->msg(false, '请填写集合描述');
        }
        
        $oldCollectionInfo = $this->_collection->findOne(array(
            '_id' => myMongoId($_id)
        ));
        
        if ($this->checkCollecionNameExist($name) && $oldCollectionInfo['name'] != $name) {
            return $this->msg(false, '集合名称已经存在');
        }
        
        if ($this->checkCollecionAliasExist($alias) && $oldCollectionInfo['alias'] != $alias) {
            return $this->msg(false, '集合别名已经存在');
        }
        
        if (($this->checkPluginNameExist($name) && $oldCollectionInfo['name'] != $name) || ($this->checkPluginAliasExist($alias) && $oldCollectionInfo['alias'] != $alias)) {
            return $this->msg(false, '集合名称或者别名在插件命名中已经存在，请勿重复使用');
        }
        
        $datas = array();
        $datas['project_id'] = array(
            $project_id
        );
        $datas['name'] = $name;
        $datas['alias'] = $alias;
        $datas['isProfessional'] = $isProfessional;
        $datas['isTree'] = $isTree;
        $datas['desc'] = $desc;
        $datas['orderBy'] = $orderBy;
        $datas['plugin'] = $plugin;
        $datas['plugin_id'] = $plugin_id;
        $datas['isRowExpander'] = $isRowExpander;
        $datas['rowExpanderTpl'] = $rowExpanderTpl;
        $datas['isAutoHook'] = $isAutoHook;
        $datas['defaultSourceData'] = $defaultSourceData;
        $datas['hook'] = $hook;
        $datas['hookKey'] = $hookKey;
        $datas['hook_notify_email'] = $hookNotifyEmail;
        $datas['hook_debug_mode'] = $hookDebugMode;
        $datas['isAllowHttpAccess'] = $isAllowHttpAccess;
        $datas['promissionDefinition'] = $promissionDefinition;
        $datas['plugin_collection_id'] = $plugin_collection_id;
        $datas['plugin_collection_id'] = $this->_plugin_collection->editPluginCollection($datas);
        
        $this->_collection->update(array(
            '_id' => myMongoId($_id)
        ), array(
            '$set' => $datas
        ));
        
        // 设定或者取消当前集合为插件默认的数据集合
        if (! empty($plugin_id)) {
            if ($defaultSourceData) {
                $this->_plugin_data->setDefault($datas['plugin_collection_id'], $_id);
            } else {
                $this->_plugin_data->cancelDefault($datas['plugin_collection_id'], $_id);
            }
        }
        
        return $this->msg(true, '编辑信息成功');
    }

    /**
     * 删除集合
     *
     * @author youngl
     * @name 删除集合
     * @version 2013.11.14 young
     * @return JsonModel
     */
    public function removeAction()
    {
        $_id = $this->params()->fromPost('_id', null);
        $plugin_id = $this->_plugin_id;
        
        try {
            $_id = Json::decode($_id, Json::TYPE_ARRAY);
        } catch (\Exception $e) {
            return $this->msg(false, '无效的json字符串');
        }
        
        if (! is_array($_id)) {
            return $this->msg(false, '请选择你要删除的项');
        }
        foreach ($_id as $row) {
            $rowInfo = $this->_collection->findOne(array(
                '_id' => myMongoId($row)
            ));
            
            if ($rowInfo != null) {
                // 禁止在集合中删除插件中的集合内容
                // $this->_plugin_collection->removePluginCollection($this->_project_id, $this->_plugin_id, $rowInfo['alias']);
                $this->_collection->remove(array(
                    '_id' => myMongoId($row)
                ));
            }
        }
        return $this->msg(true, '删除信息成功');
    }

    /**
     * 检测一个集合是否存在，根据名称和编号
     *
     * @param string $info            
     * @return boolean
     */
    private function checkCollecionNameExist($info)
    {
        // 检查当前项目集合中是否包含这些命名
        $info = $this->_collection->findOne(array(
            'name' => $info,
            'project_id' => $this->_project_id
        ));
        if ($info == null) {
            return false;
        }
        return true;
    }

    private function checkCollecionAliasExist($info)
    {
        // 检查当前项目集合中是否包含这些命名
        $info = $this->_collection->findOne(array(
            'alias' => $info,
            'project_id' => $this->_project_id
        ));
        if ($info == null) {
            return false;
        }
        return true;
    }

    private function checkPluginNameExist($info)
    {
        // 检查插件集合中是否包含这些名称信息
        $info = $this->_collection->findOne(array(
            'project_id' => $this->_project_id,
            'name' => $info,
            'plugin' => true
        ));
        
        if ($info == null) {
            return false;
        }
        return true;
    }

    private function checkPluginAliasExist($info)
    {
        // 检查插件集合中是否包含这些名称信息
        $info = $this->_collection->findOne(array(
            'project_id' => $this->_project_id,
            'alias' => $info,
            'plugin' => true
        ));
        
        if ($info == null) {
            return false;
        }
        return true;
    }
}
