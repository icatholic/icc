<?php

/**
 * iDatabase索引控制器
 *
 * @author young 
 * @version 2013.11.11
 * 
 */
namespace Idatabase\Controller;

use Zend\Json\Json;
use My\Common\Controller\Action;

class IndexController extends Action
{

    /**
     * 当前的model 读取索引集合的mongocollection实例
     *
     * @var object
     */
    private $_model;

    /**
     * 当前collection的_id
     *
     * @var string
     */
    private $_collection_id;

    /**
     * 获取集合的数据结构的实例
     *
     * @var object
     */
    private $_structure = null;

    /**
     * 存储当前集合的结局结构信息
     *
     * @var array
     */
    private $_schema = array();

    /**
     * 需要被添加或者删除索引的物理集合的mongocollection实例
     *
     * @var object
     */
    private $_targetCollection;

    /**
     * 索引类型
     * 
     * @var array
     */
    private $_indexType = array(
        '2d',
        '2dsphere',
        'text',
        'hashed'
    );

    /**
     * init
     * 
     * @see \My\Common\Controller\Action::init()
     */
    public function init()
    {
        try {
            $this->_model = $this->model('Idatabase\Model\Index');
            $this->_collection_id = isset($_REQUEST['__COLLECTION_ID__']) ? trim($_REQUEST['__COLLECTION_ID__']) : '';
            if (empty($this->_collection_id)) {
                throw new \Exception('$this->_collection_id值未设定');
            }
            
            $this->getSchema();
            $this->_targetCollection = $this->model('idatabase_collection_' . $this->_collection_id);
        } catch (\Exception $e) {
            return $this->msg(false, $e->getMessage());
        }
    }

    /**
     * 获取全部索引信息
     *
     * @author young
     * @name 数据集合的索引管理
     * @version 2013.11.11 young
     */
    public function indexAction()
    {
        return $this->findAll(IDATABASE_INDEXES, array(
            'collection_id' => $this->_collection_id
        ), array(
            '_id' => 1
        ));
    }

    /**
     * 添加数据集合的索引
     *
     * @author young
     * @name 添加数据集合的索引
     * @version 2013.12.22 young
     */
    public function addAction()
    {
        $keys = $this->params()->fromPost('keys', '');
        if (! isJson($keys)) {
            return $this->msg(false, 'keys必须符合json格式,例如：{"index_name":1,"2d":"2d"}');
        }
        
        $keys = Json::decode($keys, Json::TYPE_ARRAY);
        if (! is_array($keys) || empty($keys)) {
            return $this->msg(false, '请检查$keys是否为空');
        }
        $keys = $this->filterKey($keys);
        // 检测字段是否都存在
        if (! $this->checkKeys(array_keys($keys))) {
            return $this->msg(false, '键值中包含未定义的字段');
        }
        
        if (! $this->_targetCollection->ensureIndex($keys, array(
            'background' => true
        ))) {
            return $this->msg(false, '创建索引失败');
        }
        
        $datas = array();
        $datas['keys'] = Json::encode($keys);
        $datas['collection_id'] = $this->_collection_id;
        $this->_model->insert($datas);
        return $this->msg(true, '创建索引成功');
    }

    /**
     * 删除数据集合的索引
     *
     * @author young
     * @name 删除数据集合的索引
     * @version 2013.12.22 young
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
            $index = $this->_model->findOne(array(
                '_id' => myMongoId($row)
            ));
            if ($index == null) {
                return $this->msg(false, '无效的索引');
            }
            $keys = Json::decode($index['keys']);
            if (! $this->_targetCollection->deleteIndex($keys)) {
                return $this->msg(false, '删除索引失败');
            }
            $this->_model->remove(array(
                '_id' => myMongoId($row)
            ));
        }
        
        return $this->msg(true, '删除索引成功');
    }

    /**
     * 检测所得的键名是否
     *
     * @param array $keys            
     * @return boolean
     */
    private function checkKeys($keys)
    {
        if (! is_array($keys)) {
            throw new \Exception('$keys必须为数组');
        }
        if (empty($this->_schema)) {
            $this->_schema = $this->getSchema();
        }
        return count($keys) === count(array_intersect($keys, array_values($this->_schema)));
    }

    /**
     * 获取当前结合的结构
     */
    private function getSchema()
    {
        if ($this->_structure == null) {
            $this->_structure = $this->model('Idatabase\Model\Structure');
        }
        
        $query = array(
            'collection_id' => $this->_collection_id
        );
        
        $structures = $this->_structure->findAll($query);
        if (! empty($structures)) {
            foreach ($structures as $row) {
                $this->_schema[] = $row['field'];
            }
        } else {
            throw new \Exception("你尚未定义数据结构");
        }
    }

    /**
     * 规范化创建索引的keys
     *
     * @param array $keys            
     * @return array
     */
    private function filterKey($keys)
    {
        if (! is_array($keys)) {
            throw new \Exception('$keys必须是数组');
        }
        
        array_walk($keys, function (&$items, $index)
        {
            $items = trim($items);
            if (preg_match("/^[-]?1$/", $items)) {
                $items = intval($items);
            } else {
                $items = strtolower($items);
                if (in_array($items, $this->_indexType)) {
                    $items = strval($items);
                } else {
                    throw new \Exception("无效的索引类型");
                }
            }
        });
        return $keys;
    }
}
