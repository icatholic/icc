<?php

/**
 * iDatabase数据管理控制器
 *
 * @author young 
 * @version 2013.11.22
 * 
 */
namespace Idatabase\Controller;

use Zend\View\Model\JsonModel;
use Zend\Json\Json;
use My\Common\Controller\Action;
use My\Common\MongoCollection;
use Psr\Log\AbstractLogger;
use Aws\CloudFront\Exception\Exception;
use Zend\Serializer\Serializer;
use MyProject\Proxies\__CG__\OtherProject\Proxies\__CG__\stdClass;

class DataController extends Action
{

    /**
     * 读取当前数据集合的mongocollection实例
     *
     * @var object
     */
    private $_data;

    /**
     * 读取数据属性结构的mongocollection实例
     *
     * @var object
     */
    private $_structure;

    /**
     * 读取集合列表集合的mongocollection实例
     *
     * @var object
     */
    private $_collection;

    /**
     * 读取统计信息集合的mongocollection实例
     *
     * @var object
     */
    private $_statistic;

    /**
     * 当前集合所属项目
     *
     * @var string
     */
    private $_project_id = '';

    /**
     * 当前集合所属集合 集合的alias别名或者_id的__toString()结果
     *
     * @var string
     */
    private $_collection_id = '';

    /**
     * 存储数据的物理集合名称
     *
     * @var string
     */
    private $_collection_name = '';

    /**
     * 存储数据的物理集合别名
     *
     * @var string
     */
    private $_collection_alias = '';

    /**
     * 存储当前集合的结局结构信息
     *
     * @var array
     */
    private $_schema = null;

    /**
     * 存储查询显示字段列表
     *
     * @var array
     */
    private $_fields = array(
        '_id' => true,
        '__CREATE_TIME__' => true,
        '__MODIFY_TIME__' => true
    );

    /**
     * 存储字段与字段名称的数组
     *
     * @var array
     */
    private $_title = array(
        '_id' => '系统编号',
        '__CREATE_TIME__' => '创建时间',
        '__MODIFY_TIME__' => '更新时间'
    );

    /**
     * 存储关联数据的集合数据
     *
     * @var array
     */
    private $_rshData = array();

    /**
     * 排序的mongocollection实例
     *
     * @var string
     */
    private $_order;

    /**
     * 数据集合映射物理集合
     *
     * @var object
     */
    private $_mapping;

    /**
     * 索引管理集合
     *
     * @var object
     */
    private $_index;

    /**
     * 当集合为树状集合时，存储父节点数据的集合名称
     *
     * @var string
     */
    private $_fatherField = '';

    /**
     * 存储当前collection的关系集合数据
     *
     * @var array
     */
    private $_rshCollection = array();

    /**
     * 无法解析的json数组异常时，错误提示信息
     *
     * @var string
     */
    private $_jsonExceptMessage = '子文档类型数据必须符合标准json格式，示例：{"a":1}<br />1.请注意属性务必使用双引号包裹<br />2.请检查Json数据是否完整<br />';

    /**
     * 为了防止死循环
     *
     * @var int
     */
    private $_maxDepth = 1000;

    /**
     * Gearman客户端对象
     *
     * @var object
     */
    private $_gmClient = null;

    /**
     * 保留字段
     *
     * @var array
     */
    private $_filter = array(
        'action',
        'start',
        'page',
        'limit',
        '__removed__',
        // '__modify_time__',
        '__old_id__',
        '__old_data__',
        '__project_id__',
        '__collection_id__',
        '__plugin_id__',
        '__plugin_collection_id__'
    );

    private $_file;

    /**
     * 初始化函数
     *
     * @see \My\Common\ActionController::init()
     */
    public function init()
    {
        resetTimeMemLimit();
        
        // 特殊处理包含点的变量,将__DOT__转换为.
        convertVarNameWithDot($_POST);
        convertVarNameWithDot($_FILES);
        convertVarNameWithDot($_REQUEST);
        
        // 获取传递参数
        $this->_project_id = isset($_REQUEST['__PROJECT_ID__']) ? trim($_REQUEST['__PROJECT_ID__']) : '';
        $this->_collection_id = isset($_REQUEST['__COLLECTION_ID__']) ? trim($_REQUEST['__COLLECTION_ID__']) : '';
        
        // 初始化model
        $this->_collection = $this->model('Idatabase\Model\Collection');
        $this->_structure = $this->model('Idatabase\Model\Structure');
        $this->_plugin_structure = $this->model('Idatabase\Model\PluginStructure');
        $this->_order = $this->model('Idatabase\Model\Order');
        $this->_mapping = $this->model('Idatabase\Model\Mapping');
        $this->_statistic = $this->model('Idatabase\Model\Statistic');
        $this->_index = $this->model('Idatabase\Model\Index');
        $this->_file = $this->model('Idatabase\Model\File');
        
        // 检查必要的参数
        if (empty($this->_project_id)) {
            throw new \Exception('$this->_project_id值未设定');
        }
        
        if (empty($this->_collection_id)) {
            throw new \Exception('$this->_collection_id值未设定');
        }
        
        // 进行内部私有变量的赋值
        $this->_collection_alias = $this->getCollectionAliasById($this->_collection_id);
        $this->_collection_id = $this->getCollectionIdByAlias($this->_collection_id);
        $this->_collection_name = 'idatabase_collection_' . $this->_collection_id;
        
        // 进行访问权限验证
        if (! $_SESSION['acl']['admin']) {
            if (! in_array($this->_collection_id, $_SESSION['acl']['collection'], true)) {
                return $this->deny();
            }
        }
        
        // 一次性获取当前集合的完整的文档结构信息
        $this->_schema = $this->getSchema();
        
        // 获取映射关系，初始化数据集合model
        $mapCollection = $this->_mapping->findOne(array(
            'project_id' => $this->_project_id,
            'collection_id' => $this->_collection_id,
            'active' => true
        ));
        if ($mapCollection != null) {
            $this->_data = $this->collection($mapCollection['collection'], $mapCollection['database'], $mapCollection['cluster']);
        } else {
            $this->_data = $this->collection($this->_collection_name);
        }
        $this->_data->setReadPreference(\MongoClient::RP_SECONDARY);
        
        // 自动化为集合创建索引
        $this->_index->autoCreateIndexes(isset($mapCollection['collection']) ? $mapCollection['collection'] : $this->_collection_id);
        
        // 建立gearman客户端连接
        $this->_gmClient = $this->gearman()->client();
    }

    /**
     * 读取集合内的全部数据
     *
     * @author young
     * @name 读取集合内的全部数据
     * @version 2013.12.23 young
     */
    public function indexAction()
    {
        $rst = array();
        $query = array();
        $sort = array();
        
        $action = $this->params()->fromQuery('action', null);
        $search = $this->params()->fromQuery('search', null);
        $sort = $this->params()->fromQuery('sort', null);
        $start = intval($this->params()->fromQuery('start', 0));
        $limit = intval($this->params()->fromQuery('limit', 10));
        $start = $start > 0 ? $start : 0;
        
        if ($action == 'search' || $action == 'excel') {
            try {
                $query = $this->searchCondition();
            } catch (\Exception $e) {
                fb($query, 'LOG');
                return $this->msg(false, '无效的检索条件，请检查你的输入');
            }
        }
        
        if ($search != null) {
            if (! isset($this->_schema['combobox']['rshCollectionKeyField'])) {
                return $this->msg(false, '关系集合的值');
            }
            $search = preg_replace("/\s/", '', $search);
            $explode = explode(',', $search);
            $query['$and'][] = array(
                $this->_schema['combobox']['rshCollectionKeyField'] => myMongoRegex(end($explode))
            );
        }
        
        $jsonSearch = $this->jsonSearch();
        if ($jsonSearch) {
            $query['$and'][] = $jsonSearch;
        }
        
        $linkageSearch = $this->linkageSearch();
        if ($linkageSearch) {
            $query['$and'][] = $linkageSearch;
        }
        
        if (empty($sort)) {
            $sort = $this->defaultOrder();
        }
        
        if (! empty($this->_schema['2d'])) {
            $keys2d = array_keys($this->_schema['2d']);
            foreach ($keys2d as $field2d) {
                if (isset($_REQUEST[$field2d])) {
                    $lng = isset($_REQUEST[$field2d]['lng']) ? floatval(trim($_REQUEST[$field2d]['lng'])) : 0;
                    $lat = isset($_REQUEST[$field2d]['lat']) ? floatval(trim($_REQUEST[$field2d]['lat'])) : 0;
                    $distance = ! empty($_REQUEST[$field2d]['distance']) ? floatval($_REQUEST[$field2d]['distance']) : 1;
                    
                    $pipeline = array(
                        array(
                            '$geoNear' => array(
                                'near' => array(
                                    $lng,
                                    $lat
                                ),
                                'limit' => 1000,
                                'spherical' => true,
                                'distanceMultiplier' => 6371 * 1000,
                                'query' => $query,
                                'distanceField' => '__DISTANCE__'
                            )
                        ),
                        array(
                            '$match' => array(
                                '__DISTANCE__' => array(
                                    '$lte' => $distance * 1000
                                )
                            )
                        )
                    );
                    $rst = $this->_data->aggregate($pipeline);
                    if (isset($rst['result'])) {
                        return $this->rst($rst['result'], count($rst['result']), true);
                    }
                }
            }
        }
        
        $fields = $this->_fields;
        if ($action == 'excel') {
            if (empty($this->_schema['export'])) {
                return $this->msg(true, '请联系管理员，设定允许导出数据字段的权限');
            }
            $fields = $this->_schema['export'];
            $fields['_id'] = true;
            $fields['__CREATE_TIME__'] = true;
            $fields['__MODIFY_TIME__'] = true;
        }
        
        // 增加gearman导出数据统计
        if ($action == 'excel') {
            $wait = $this->params()->fromQuery('wait', false);
            $download = $this->params()->fromQuery('download', false);
            
            $obj = new \stdClass();
            $obj->_collection_id = $this->_collection_id;
            $obj->_rshCollection = $this->_rshCollection;
            $obj->_title = $this->_title;
            $obj->_project_id = $this->_project_id;
            
            $exportGearmanKey = md5($this->_collection_id . serialize($query));
            if ($this->cache($exportGearmanKey) !== null) {
                return $this->msg(false, 'Excel表格创建中……');
            } elseif ($wait) {
                return $this->msg(true, 'Excel创建成功');
            } elseif ($download) {
                $params = array();
                $params['collection_id'] = $this->_collection_id;
                $params['query'] = $query;
                $params['fields'] = $fields;
                $params['scope'] = $obj;
                $workload = serialize($params);
                $exportKey = md5($workload);
                
                $file = $this->cache($exportKey);
                if ($file['outType'] == 'csv') {
                    header('Content-Type: application/zip');
                    header('Content-Disposition: attachment;filename="' . $exportGearmanKey . '.zip"');
                    header('Cache-Control: max-age=0');
                    $gridFSFile = $this->_data->getGridFsFileById($file['_id']);
                    echo fileToZipStream($exportGearmanKey . '.csv', $gridFSFile->getBytes());
                    exit();
                } elseif ($file['outType'] == 'table') {
                    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=UTF-8');
                    header('Content-Disposition: attachment;filename="' . $exportGearmanKey . '.xlsx"');
                    header('Cache-Control: max-age=0');
                    $gridFSFile = $this->_data->getGridFsFileById($file['_id']);
                    $stream = $gridFSFile->getResource();
                    while (! feof($stream)) {
                        echo fread($stream, 8192);
                        ob_flush();
                    }
                    exit();
                } else {
                    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=UTF-8');
                    header('Content-Disposition: attachment;filename="' . $exportGearmanKey . '.xlsx"');
                    header('Cache-Control: max-age=0');
                    $gridFSFile = $this->_data->getGridFsFileById($file['_id']);
                    echo $gridFSFile->getBytes();
                    exit();
                }
            } else {
                $params = array();
                $params['collection_id'] = $this->_collection_id;
                $params['query'] = $query;
                $params['fields'] = $fields;
                
                $params['scope'] = $obj;
                $workload = serialize($params);
                $exportKey = md5($workload);
                $jobHandle = $this->_gmClient->doBackground('dataExport', $workload, $exportKey);
                $stat = $this->_gmClient->jobStatus($jobHandle);
                if (isset($stat[0]) && $stat[0]) {
                    $this->cache()->save(true, $exportGearmanKey, 600);
                }
                return $this->msg(false, '请求被受理');
            }
        }
        
        // 开始修正录入点.的子属性节点时，出现覆盖父节点数据的问题。modify20140717
        foreach ($fields as $key => $value) {
            if (strpos($key, '.') !== false) {
                $tmp = explode('.', $key);
                if (! isset($fields[$tmp[0]])) {
                    $fields[$tmp[0]] = true;
                } else {
                    unset($fields[$key]);
                }
            }
        }
        // 结束修正录入点.的子属性节点时，出现覆盖父节点数据的问题。modify20140604
        $cursor = $this->_data->find($query, $fields);
        if (! ($cursor instanceof \MongoCursor)) {
            throw new \Exception('无效的$cursor');
        }
        
        $total = $cursor->count();
        if ($total <= 0) {
            return $this->rst(array(), 0, true);
        }
        
        $cursor->sort($sort)
            ->skip($start)
            ->limit($limit);
        
        $datas = iterator_to_array($cursor, false);
        $datas = $this->comboboxSelectedValues($datas);
        
        return $this->rst($datas, $total, true);
    }

    /**
     * 对集合数据进行统计
     * 目前支持的统计类型：
     * 计数、唯一数、求和、均值、中位数、方差、标准差、最大值、最小值
     *
     * @author young
     * @name 对集合数据进行统计
     * @version 2014.01.29 young
     */
    public function statisticAction()
    {
        $action = $this->params()->fromQuery('action', null);
        $wait = $this->params()->fromQuery('wait', null);
        $export = filter_var($this->params()->fromQuery('export', false));
        $statistic_id = $this->params()->fromQuery('__STATISTIC_ID__', null);
        
        if ($action !== 'statistic') {
            return $this->msg(false, '$action is not statistic');
        }
        
        if (empty($statistic_id)) {
            throw new \Exception('请选择统计方法');
        }
        
        $statisticInfo = $this->_statistic->findOne(array(
            '_id' => myMongoId($statistic_id)
        ));
        if ($statisticInfo == null) {
            throw new \Exception('统计方法不存在');
        }
        
        $map = array(
            '_id' => $statisticInfo['xAxisField']
        );
        
        try {
            $query = array();
            $query = $this->searchCondition();
            
            // 增加默认统计条件开始
            if (! empty($statisticInfo['defaultQuery'])) {
                if (isset($query['$and'])) {
                    $query['$and'][] = $statisticInfo['defaultQuery'];
                } else {
                    $query = array_merge($query, $statisticInfo['defaultQuery']);
                }
            }
            // 增加默认统计条件结束
            
            // 采用数据导出结果
            $rstCollectionName = $statistic_id;
            if (in_array($statisticInfo['yAxisType'], array(
                'unique',
                'distinct'
            ))) {
                $rstCollectionName .= '_unique';
            }
            
            if ($export) {
                if ($this->cache($statistic_id) !== null) {
                    return $this->msg(true, '重新统计中');
                } else {
                    $rst = $this->collection()->secondary($rstCollectionName, DB_MAPREDUCE, DEFAULT_CLUSTER);
                    $rst->setNoAppendQuery(true);
                }
            } else {
                if ($this->cache($statistic_id) !== null) {
                    return $this->msg(true, '统计进行中……');
                } elseif ($wait) {
                    $rst = $this->collection()->secondary($rstCollectionName, DB_MAPREDUCE, DEFAULT_CLUSTER);
                    if ($rst instanceof MongoCollection) {
                        $rst->setNoAppendQuery(true);
                    }
                } else {
                    // 任务交给后台worker执行
                    $params = array(
                        'out' => $statistic_id,
                        'dataCollection' => $this->_collection_name,
                        'statisticInfo' => $statisticInfo,
                        'query' => $query,
                        'method' => 'replace'
                    );
                    fb($params, 'LOG');
                    $jobHandle = $this->_gmClient->doBackground('mapreduce_test', serialize($params), $statistic_id);
                    $stat = $this->_gmClient->jobStatus($jobHandle);
                    if (isset($stat[0]) && $stat[0]) {
                        $this->cache()->save(true, $statistic_id, 60);
                    }
                    return $this->msg(true, '统计请求被受理');
                }
            }
            
            if (is_array($rst) && isset($rst['ok']) && $rst['ok'] === 0) {
                switch ($rst['code']) {
                    case 500:
                        return $this->deny('根据查询条件，未检测到有效的统计数据');
                        break;
                    case 501:
                        return $this->deny('MapReduce执行失败，原因：' . $rst['msg']);
                        break;
                    case 502:
                        return $this->deny('程序正在执行中，请勿频繁尝试');
                        break;
                    case 503:
                        return $this->deny('程序异常：' . $rst['msg']);
                        break;
                }
            }
            
            if (! $rst instanceof \MongoCollection) {
                return $this->deny('$rst不是MongoCollection的子类实例');
                throw new \Exception('$rst不是MongoCollection的子类实例');
            }
            
            $outCollectionName = $rst->getName(); // 输出集合名称
            
            if ($export) {
                $sort = array(
                    '_id' => 1
                );
                if ($statisticInfo['seriesType'] != 'line') {
                    $sort = array(
                        'value' => - 1
                    );
                }
                
                $datas = $rst->findAll(array(), $sort, 0, 1000);
                $datas = $this->replaceRshData($datas, $map);
                
                $excel = array();
                $excel['title'] = array(
                    '键',
                    '值'
                );
                $excel['result'] = $datas;
                arrayToExcel($excel);
            } else {
                if ($statisticInfo['seriesType'] != 'line') {
                    $limit = intval($statisticInfo['maxShowNumber']) > 0 ? intval($statisticInfo['maxShowNumber']) : 20;
                    $datas = $rst->findAll(array(), array(
                        'value' => - 1
                    ), 0, $limit);
                } else {
                    $limit = intval($statisticInfo['maxShowNumber']) > 0 ? intval($statisticInfo['maxShowNumber']) : 20;
                    $datas = $rst->findAll(array(), array(
                        '_id' => 1
                    ), 0, $limit);
                }
                
                $datas = $this->replaceRshData($datas, $map);
                
                return $this->rst($datas, 0, true);
            }
        } catch (\Exception $e) {
            return $this->deny('程序异常：' . $e->getLine() . $e->getMessage());
        }
    }

    /**
     * 导出该集合的的bson文件
     */
    public function exportBsonAction()
    {
        resetTimeMemLimit();
        $collection_id = isset($_REQUEST['__COLLECTION_ID__']) ? trim($_REQUEST['__COLLECTION_ID__']) : '';
        $wait = $this->params()->fromQuery('wait', null);
        $cacheKey = 'collection_bson_export_' . $collection_id;
        if ($wait) {
            if ($this->cache($cacheKey) !== null) {
                return $this->msg(true, '处理完成');
            } else {
                return $this->msg(false, '请求处理中……');
            }
        } elseif ($this->cache($cacheKey) !== null) {
            $zip = $this->cache($cacheKey);
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment;filename="bson_' . $collection_id . '_' . date('YmdHis') . '.zip"');
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
                'collection_id' => $collection_id
            );
            fb($params, 'LOG');
            $jobHandle = $this->_gmClient->doBackground('collectionBsonExport', serialize($params), $cacheKey);
            return $this->msg(false, '请求已被受理');
        }
    }

    /**
     * 替换数据
     *
     * @param array $datas            
     * @param array $map            
     * @return array
     */
    private function replaceRshData($datas, $map)
    {
        $this->dealRshData();
        convertToPureArray($datas);
        array_walk($datas, function (&$value, $key) use($map)
        {
            ksort($value);
            array_walk($value, function (&$cell, $field) use($map)
            {
                if (isset($map[$field])) {
                    $field = $map[$field];
                    if (isset($this->_rshData[$field])) {
                        $cell = isset($this->_rshData[$field][$cell]) ? $this->_rshData[$field][$cell] : '';
                    }
                }
            });
        });
        return $datas;
    }

    /**
     * 处理数据中的关联数据
     */
    private function dealRshData()
    {
        foreach ($this->_rshCollection as $_id => $detail) {
            $_id = $this->getCollectionIdByAlias($_id);
            $collectionName = 'idatabase_collection_' . $_id;
            $model = $this->collection($collectionName);
            $cursor = $model->find(array(), array(
                $detail['rshCollectionKeyField'] => true,
                $detail['rshCollectionValueField'] => true
            ));
            
            $datas = array();
            while ($cursor->hasNext()) {
                $row = $cursor->getNext();
                $key = $row[$detail['rshCollectionValueField']];
                $value = isset($row[$detail['rshCollectionKeyField']]) ? $row[$detail['rshCollectionKeyField']] : '';
                if ($key instanceof \MongoId) {
                    $key = $key->__toString();
                }
                if (! empty($key)) {
                    $datas[$key] = $value;
                }
            }
            $this->_rshData[$detail['collectionField']] = $datas;
        }
    }

    /**
     * 处理combobox产生的追加数据
     *
     * @param array $datas            
     * @return array
     */
    private function comboboxSelectedValues($datas)
    {
        $idbComboboxSelectedValue = trim($this->params()->fromQuery('idbComboboxSelectedValue', ''));
        if (! empty($idbComboboxSelectedValue)) {
            $comboboxSelectedLists = explode(',', $idbComboboxSelectedValue);
            if (is_array($comboboxSelectedLists) && ! empty($comboboxSelectedLists) && isset($this->_schema['combobox']['rshCollectionKeyField']) && isset($this->_schema['combobox']['rshCollectionValueField'])) {
                
                $rshCollectionValueField = $this->_schema['combobox']['rshCollectionValueField'];
                
                array_walk($comboboxSelectedLists, function (&$value, $index) use($rshCollectionValueField)
                {
                    $value = formatData($value, $this->_schema['post'][$rshCollectionValueField]['type']);
                });
                
                $cursor = $this->_data->find(array(
                    $rshCollectionValueField => array(
                        '$in' => $rshCollectionValueField == '_id' ? myMongoId($comboboxSelectedLists) : $comboboxSelectedLists
                    )
                ), $this->_fields);
                $extraDatas = iterator_to_array($cursor, false);
                $datas = array_merge($datas, $extraDatas);
                $uniqueArray = array();
                array_walk($datas, function ($value, $key) use(&$datas, &$uniqueArray)
                {
                    if (! in_array($value['_id'], $uniqueArray, true)) {
                        $uniqueArray[] = $value['_id'];
                    } else {
                        unset($datas[$key]);
                    }
                });
                $datas = array_values($datas);
            }
        }
        return $datas;
    }

    /**
     * 附加json查询条件
     *
     * @return boolean or array
     */
    private function jsonSearch()
    {
        $jsonSearch = trim($this->params()->fromQuery('jsonSearch', ''));
        if (! empty($jsonSearch)) {
            if (isJson($jsonSearch)) {
                try {
                    return Json::decode($jsonSearch, Json::TYPE_ARRAY);
                } catch (\Exception $e) {}
            }
        }
        return false;
    }

    /**
     * 联动信息检索
     *
     * @return Ambigous <\Zend\Json\mixed, mixed, NULL, \Zend\Json\$_tokenValue, multitype:, stdClass, multitype:Ambigous <\Zend\Json\mixed, \Zend\Json\$_tokenValue, NULL, multitype:, stdClass> , multitype:Ambigous <\Zend\Json\mixed, \Zend\Json\$_tokenValue, multitype:, multitype:Ambigous <\Zend\Json\mixed, \Zend\Json\$_tokenValue, NULL, multitype:, stdClass> , NULL, stdClass> >|boolean
     */
    private function linkageSearch()
    {
        $linkageSearch = trim($this->params()->fromQuery('linkageSearch', ''));
        if (! empty($linkageSearch)) {
            if (isJson($linkageSearch)) {
                try {
                    return Json::decode($linkageSearch, Json::TYPE_ARRAY);
                } catch (\Exception $e) {}
            }
        }
        return false;
    }

    /**
     * 导出excel表格
     *
     * @author young
     * @name 导出excel表格
     * @version 2013.11.19 young
     */
    public function excelAction()
    {
        // 先判断是否设定导出字段
        $forwardPlugin = $this->forward();
        $returnValue = $forwardPlugin->dispatch('idatabase/data/index', array(
            'action' => 'excel'
        ));
        return $returnValue;
    }

    /**
     * 获取树状表格数据
     */
    public function treeAction()
    {
        if (empty($this->_fatherField)) {
            return $this->msg(false, '树形结构，请设定字段属性和父字段属性');
        }
        
        $fatherValue = $this->params()->fromQuery('fatherValue', '');
        $tree = $this->tree($this->_fatherField, $fatherValue);
        if (! is_array($tree)) {
            return $tree;
        }
        return new JsonModel($tree);
    }

    /**
     * 递归的方式获取树状数据
     *
     * @param string $fatherField            
     * @param string $fatherValue            
     * @return Ambigous <\Zend\View\Model\JsonModel, multitype:string Ambigous <boolean, bool> >|multitype:|multitype:Ambigous <\MongoId, boolean>
     */
    private function tree($fatherField, $fatherValue = '', $depth = 0)
    {
        $rshCollection = isset($this->_schema['post'][$fatherField]['rshCollection']) ? $this->_schema['post'][$fatherField]['rshCollection'] : '';
        if (empty($rshCollection))
            return $this->msg(false, '无效的关联集合');
        
        if ($this->_schema['post'][$fatherField]['type'] === 'numberfield') {
            $fatherValue = preg_match("/^[0-9]+\.[0-9]+$/", $fatherValue) ? floatval($fatherValue) : intval($fatherValue);
        }
        
        $rshCollectionKeyField = $this->_rshCollection[$rshCollection]['rshCollectionKeyField'];
        $rshCollectionValueField = $this->_rshCollection[$rshCollection]['rshCollectionValueField'];
        
        if ($fatherField == '')
            return $this->msg(false, '$fatherField不存在');
        
        if ($fatherField === '_id')
            $fatherValue = myMongoId($fatherValue);
        
        $cursor = $this->_data->find(array(
            $fatherField => $fatherValue
        ));
        
        if ($cursor->count() == 0)
            return array();
        
        $datas = array();
        while ($cursor->hasNext()) {
            $row = $cursor->getNext();
            if ($row[$rshCollectionValueField] instanceof \MongoId) {
                $fatherValue = $row[$rshCollectionValueField]->__toString();
            } else {
                $fatherValue = $row[$rshCollectionValueField];
            }
            
            $children = null;
            if ($depth < $this->_maxDepth) {
                $children = $this->tree($fatherField, $fatherValue, $depth ++);
            }
            if (! empty($children)) {
                $row['expanded'] = true;
                $row['children'] = $children;
            } else {
                $row['leaf'] = true;
            }
            $datas[] = $row;
        }
        return $datas;
    }

    /**
     * 添加新数据
     *
     * @author young
     * @name 添加新数据
     * @version 2013.11.20 young
     * @return JsonModel
     */
    public function addAction()
    {
        try {
            $datas = array();
            $datas = array_intersect_key($_POST, $this->_schema['post']);
            $files = array_intersect_key($_FILES, $this->_schema['file']);
            
            if (empty($datas) && empty($files))
                return $this->msg(false, '提交数据中未包含有效字段');
            
            if (! empty($files)) {
                foreach ($_FILES as $fieldName => $file) {
                    if ($file['name'] != '') {
                        if ($file['error'] == UPLOAD_ERR_OK) {
                            $fileInfo = $this->_data->storeToGridFS($fieldName);
                            if (isset($fileInfo['_id']) && $fileInfo['_id'] instanceof \MongoId)
                                $datas[$fieldName] = $fileInfo['_id']->__toString();
                            else
                                return $this->msg(false, '文件写入GridFS失败');
                        } else {
                            return $this->msg(false, '文件上传失败,error code:' . $file['error']);
                        }
                    }
                }
            }
            
            try {
                $datas = $this->dealData($datas);
            } catch (\Zend\Json\Exception\RuntimeException $e) {
                return $this->msg(false, $e->getMessage() . $this->_jsonExceptMessage);
            } catch (\Exception $e) {
                return $this->msg(false, $e->getMessage());
            }
            if (empty($datas)) {
                return $this->msg(false, '未发现添加任何有效数据');
            }
            $datas = $this->_data->insertByFindAndModify($datas);
            
            // 快捷录入数据处理
            if (isset($datas['_id'])) {
                $this->quickOperation($datas);
            }
            
            return $this->msg(true, '提交数据成功');
        } catch (\Exception $e) {
            return $this->msg(false, $e->getTraceAsString());
        }
    }

    /**
     * 执行快捷录入的逻辑操作
     * 执行准则统一采用：先清空符合条件数据，然后全部重新插入的原则完成
     *
     * @param array $datas            
     * @name young
     * @version 2014.02.11
     * @return boolean
     */
    private function quickOperation($datas)
    {
        if (empty($this->_schema['quick'])) {
            return false;
        }
        
        $rshCollectionValueField = $this->_schema['combobox']['rshCollectionValueField'];
        if ($rshCollectionValueField == '_id') {
            $currentCollectionValue = $oldCollectionValue = $datas['_id']->__toString();
        } else {
            $currentCollectionValue = $datas[$rshCollectionValueField];
            $oldCollectionValue = $datas['__OLD_DATA__'][$rshCollectionValueField];
        }
        
        $quickValueField = function ($targetCollectionName, $rshCollection)
        {
            $targetCollectionId = $this->getCollectionIdByAlias($targetCollectionName);
            $fieldInfo = $this->_structure->findOne(array(
                'collection_id' => $targetCollectionId,
                'rshCollection' => $rshCollection
            ));
            return isset($fieldInfo['field']) ? $fieldInfo['field'] : false;
        };
        
        $removeOldData = function ($model, $primary)
        {
            return $model->remove($primary);
        };
        
        $findAndModify = function ($model, $data)
        {
            return $model->findAndModify($data, array(
                '$set' => $data
            ), null, array(
                'upsert' => true
            ));
        };
        
        $quickDatas = $this->quickData($datas);
        if (! empty($quickDatas)) {
            // 删除陈旧的数据，更新为新的数据
            foreach ($quickDatas as $field => $fieldValues) {
                $targetCollection = $this->_schema['quick'][$field]['quickTargetCollection'];
                $rshCollection = $this->_schema['quick'][$field]['rshCollection'];
                $model = $this->getTargetCollectionModel($targetCollection);
                
                $removeData = array(
                    $quickValueField($targetCollection, $this->_collection_alias) => $oldCollectionValue
                );
                $removeOldData($model, $removeData);
                
                if (is_array($fieldValues)) {
                    foreach ($fieldValues as $fieldValue) {
                        $data = array(
                            $quickValueField($targetCollection, $this->_collection_alias) => $currentCollectionValue,
                            $quickValueField($targetCollection, $rshCollection) => $fieldValue
                        );
                        $findAndModify($model, $data);
                    }
                } else {
                    $data = array(
                        $quickValueField($targetCollection, $this->_collection_alias) => $currentCollectionValue,
                        $quickValueField($targetCollection, $rshCollection) => $fieldValues
                    );
                    $findAndModify($model, $data);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * 获取目标集合的Model
     *
     * @param string $targetCollectionName            
     * @return object
     */
    private function getTargetCollectionModel($targetCollectionName)
    {
        $_id = $this->getCollectionIdByAlias($targetCollectionName);
        return $this->collection('idatabase_collection_' . $_id);
    }

    /**
     * 编辑新的集合信息/关联字段的集合信息/fatherField字段信息
     *
     * @author young
     * @name 编辑新的集合信息
     * @version 2013.11.20 young
     * @return JsonModel
     */
    public function editAction()
    {
        $_id = $this->params()->fromPost('_id', null);
        if ($_id == null) {
            return $this->msg(false, '无效的_id');
        }
        
        $datas = array();
        $datas = array_intersect_key($_POST, $this->_schema['post']);
        $files = array_intersect_key($_FILES, $this->_schema['file']);
        
        if (empty($datas) && empty($files))
            return $this->msg(false, '提交数据中未包含有效字段');
        
        $oldDataInfo = $this->_data->findOne(array(
            '_id' => myMongoId($_id)
        ));
        
        if ($oldDataInfo == null) {
            return $this->msg(false, '提交编辑的数据不存在');
        }
        
        if (! empty($files)) {
            foreach ($_FILES as $fieldName => $file) {
                if ($file['name'] != '') {
                    if ($file['error'] == UPLOAD_ERR_OK) {
                        if (isset($oldDataInfo[$fieldName])) {
                            $this->_data->removeFileFromGridFS($oldDataInfo[$fieldName]);
                        }
                        $fileInfo = $this->_data->storeToGridFS($fieldName);
                        if (isset($fileInfo['_id']) && $fileInfo['_id'] instanceof \MongoId)
                            $datas[$fieldName] = $fileInfo['_id']->__toString();
                        else
                            return $this->msg(false, '文件写入GridFS失败');
                    } else {
                        return $this->msg(false, '文件上传失败,error code:' . $file['error']);
                    }
                }
            }
        }
        
        try {
            $datas = $this->dealData($datas);
            // 修正更新数据时候出现mods错误的问题
            foreach ($datas as $key => $value) {
                if (strpos($key, '.') !== false) {
                    unset($datas[$key]);
                }
            }
        } catch (\Zend\Json\Exception\RuntimeException $e) {
            return $this->msg(false, $e->getMessage() . $this->_jsonExceptMessage);
        } catch (\Exception $e) {
            return $this->msg(false, $e->getMessage());
        }
        
        if (empty($datas)) {
            return $this->msg(false, '未发现任何信息变更');
        }
        
        try {
            $__OLD_DATA__ = $this->_data->findOne(array(
                '_id' => myMongoId($_id)
            ));
            
            $criteria = array(
                '_id' => myMongoId($_id)
            );
            // 增加提交的modify时间和系统的modify时间的比较，如果不匹配，说明数据已经被编辑而无法提交
            if (isset($_POST['__MODIFY_TIME__'])) {
                if ($__OLD_DATA__['__MODIFY_TIME__'] instanceof \MongoDate) {
                    if ($__OLD_DATA__['__MODIFY_TIME__']->sec !== strtotime($_POST['__MODIFY_TIME__'])) {
                        return $this->msg(false, '你提交的修改已经过期，原因为：该数据已经在其他场景下被更新。请刷新获取最新数据后，再进行编辑！');
                    }
                }
            }
            
            unset($datas['_id']);
            $this->_data->update($criteria, array(
                '$set' => $datas
            ));
            
            // 快捷录入数据处理
            $datas['_id'] = myMongoId($_id);
            $datas['__OLD_DATA__'] = $__OLD_DATA__;
            $this->quickOperation($datas);
        } catch (\Exception $e) {
            return $this->msg(false, $e->getMessage());
        }
        return $this->msg(true, '编辑信息成功');
    }

    /**
     * 批量更新数据
     *
     * @author young
     * @name 批量更新数据,只更新特定数据，不包含2的坐标和文件字段
     * @version 2013.12.10 young
     * @return JsonModel
     */
    public function saveAction()
    {
        try {
            $updateInfos = $this->params()->fromPost('updateInfos', null);
            try {
                $updateInfos = Json::decode($updateInfos, Json::TYPE_ARRAY);
            } catch (\Exception $e) {
                return $this->msg(false, '无效的json字符串');
            }
            
            if (! is_array($updateInfos)) {
                return $this->msg(false, '更新数据无效');
            }
            
            $partialUpdateFailure = false;
            foreach ($updateInfos as $row) {
                $_id = $row['_id'];
                unset($row['_id']);
                
                $oldDataInfo = $this->_data->findOne(array(
                    '_id' => myMongoId($_id)
                ));
                if ($oldDataInfo != null) {
                    $datas = array_intersect_key($row, $this->_schema['post']);
                    if (! empty($datas)) {
                        try {
                            $datas = $this->dealData($datas);
                            // 修正更新数据时候出现mods错误的问题
                            foreach ($datas as $key => $value) {
                                if (strpos($key, '.') !== false) {
                                    unset($datas[$key]);
                                }
                            }
                        } catch (\Zend\Json\Exception\RuntimeException $e) {
                            return $this->msg(false, $e->getMessage() . $this->_jsonExceptMessage);
                        } catch (\Exception $e) {
                            return $this->msg(false, $e->getMessage());
                        }
                        try {
                            $__OLD_DATA__ = $this->_data->findOne(array(
                                '_id' => myMongoId($_id)
                            ));
                            
                            $criteria = array(
                                '_id' => myMongoId($_id)
                            );
                            
                            // 增加提交的modify时间和系统的modify时间的比较，如果不匹配，说明数据已经被编辑而无法提交
                            if (isset($row['__MODIFY_TIME__'])) {
                                if ($__OLD_DATA__['__MODIFY_TIME__'] instanceof \MongoDate) {
                                    if ($__OLD_DATA__['__MODIFY_TIME__']->sec !== strtotime($row['__MODIFY_TIME__'])) {
                                        $partialUpdateFailure = true;
                                        continue;
                                    }
                                }
                            }
                            
                            $this->_data->update($criteria, array(
                                '$set' => $datas
                            ));
                            
                            // 快捷录入数据处理
                            $datas['_id'] = myMongoId($_id);
                            $datas['__OLD_DATA__'] = $__OLD_DATA__;
                            $this->quickOperation($datas);
                        } catch (\Exception $e) {
                            return $this->msg(false, exceptionMsg($e));
                        }
                    }
                }
            }
            
            if ($partialUpdateFailure) {
                return $this->msg(false, '部分更新失败提醒！你提交的部分修改已经过期，原因为：该数据已经在其他场景下被更新。请刷新获取最新数据后，再进行编辑！');
            }
            return $this->msg(true, '更新数据成功');
        } catch (\exception $e) {
            return $this->msg(false, $e->getTraceAsString());
        }
    }

    /**
     * 删除数据
     *
     * @author young
     * @name 删除数据
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
            $this->_data->remove(array(
                '_id' => myMongoId($row)
            ));
        }
        return $this->msg(true, '删除数据成功');
    }

    /**
     * 清空某个数据结合
     * 注意，为了确保数据安全，需要输入当前用户的登录密码
     */
    public function dropAction()
    {
        resetTimeMemLimit();
        $password = $this->params()->fromPost('password', null);
        if ($password == null) {
            return $this->msg(false, '请输入当前用户的登录密码');
        }
        
        if (empty($_SESSION['account']['password'])) {
            return $this->msg(false, '当前会话已经过期，请重新登录');
        }
        
        if ($_SESSION['account']['password'] !== sha1($password)) {
            return $this->msg(false, '您输入的登录密码错误，请重新输入');
        }
        
        // if ($this->_data->count() <= 5000) {
        // $rst = $this->_data->drop();
        // } else {
        $params = array(
            'collection_id' => $this->_collection_id
        );
        $this->_gmClient->doBackground('dropDatas', serialize($params), cacheKey('dropDatas', $params));
        return $this->msg(true, '清空数据任务提交成功，系统将在未来几分钟内清空该数据，请耐心等待！');
        // }
        
        // if ($rst['ok'] == 1) {
        // return $this->msg(true, '清空数据成功');
        // } else {
        // fb($rst, \FirePHP::LOG);
        // return $this->msg(false, '清空数据失败' . Json::encode($rst));
        // }
    }

    /**
     * 获取集合的数据结构
     *
     * @return array
     */
    private function getSchema()
    {
        $schema = array(
            '2d' => array(),
            'file' => array(),
            'export' => array(),
            'post' => array(
                '_id' => array(
                    'type' => '_idfield'
                )
            ),
            'all' => array(),
            'quick' => array(),
            'combobox' => array(
                'rshCollectionValueField' => '_id'
            )
        );
        
        $cursor = $this->_structure->find(array(
            'collection_id' => $this->_collection_id
        ));
        
        $cursor->sort(array(
            'orderBy' => 1,
            '_id' => - 1
        ));
        
        while ($cursor->hasNext()) {
            $row = $cursor->getNext();
            
            $type = $row['type'] == 'filefield' ? 'file' : 'post';
            $schema[$type][$row['field']] = $row;
            $schema['all'][$row['field']] = $row;
            $this->_fields[$row['field']] = true;
            $this->_title[$row['field']] = $row['label'];
            
            if ($row['type'] === '2dfield') {
                $schema['2d'][$row['field']] = $row;
            }
            
            if ($row['rshKey']) {
                $schema['combobox']['rshCollectionKeyField'] = $row['field'];
            }
            
            if ($row['rshValue']) {
                $schema['combobox']['rshCollectionValueField'] = $row['field'];
            }
            
            if (isset($row['isFatherField']) && $row['isFatherField']) {
                $this->_fatherField = $row['field'];
            }
            
            // 检查结构的时候，检查允许导出的字段
            if (! empty($row['export'])) {
                $schema['export'][$row['field']] = true;
            }
            
            if (isset($row['isQuick']) && $row['isQuick'] && $row['type'] == 'arrayfield') {
                $schema['quick'][$row['field']] = $row;
            }
            
            if (! empty($row['rshCollection'])) {
                $rshCollectionStructures = $this->_structure->findAll(array(
                    'collection_id' => $this->getCollectionIdByAlias($row['rshCollection'])
                ));
                if (! empty($rshCollectionStructures)) {
                    $rshCollectionKeyField = '';
                    $rshCollectionValueField = '_id';
                    $rshCollectionValueFieldType = 'textfield';
                    
                    foreach ($rshCollectionStructures as $rshCollectionStructure) {
                        if ($rshCollectionStructure['rshKey'])
                            $rshCollectionKeyField = $rshCollectionStructure['field'];
                        
                        if ($rshCollectionStructure['rshValue']) {
                            $rshCollectionValueField = $rshCollectionStructure['field'];
                            $rshCollectionValueFieldType = $rshCollectionStructure['type'];
                        }
                    }
                    
                    if (empty($rshCollectionKeyField))
                        throw new \Exception('字段' . $row['field'] . '的“关联集合”的键值属性尚未设定，请检查表表结构设定');
                    
                    $this->_rshCollection[$row['rshCollection']] = array(
                        'collectionField' => $row['field'],
                        'rshCollectionKeyField' => $rshCollectionKeyField,
                        'rshCollectionValueField' => $rshCollectionValueField,
                        'rshCollectionValueFieldType' => $rshCollectionValueFieldType
                    );
                } else {
                    throw new \Exception('字段' . $row['field'] . '的“关联集合”的键值属性尚未设定，请检查表表结构设定');
                }
            }
        }
        
        ksort($this->_title);
        $this->_schema = $schema;
        return $schema;
    }

    /**
     * 根据filter的值获取过滤器描述
     *
     * @param int $filter            
     * @return string
     */
    private function getFilterDesc($filter)
    {
        $map = array();
        $map['int'] = '整数验证';
        $map['boolean'] = '是非验证';
        $map['float'] = '浮点验证';
        $map['validate_url'] = '是否URL';
        $map['validate_email'] = '是否Email';
        $map['validate_ip'] = '是否IP地址';
        $map['string'] = '过滤字符串';
        $map['encoded'] = '去除或编码特殊字符';
        $map['special_chars'] = 'HTML转义';
        $map['unsafe_raw'] = '无过滤字符串';
        $map['email'] = '过滤非Email字符';
        $map['url'] = '过滤非URL字符';
        $map['number_int'] = '数字过滤非整型';
        $map['number_float'] = '数字过滤非浮点';
        $map['magic_quotes'] = '转义字符';
        
        foreach (filter_list() as $key => $value) {
            if (filter_id($value) === $filter) {
                return $map[$value];
            }
        }
    }

    /**
     * 处理入库的数据
     *
     * @param array $datas            
     * @return array
     */
    private function dealData($datas)
    {
        $validPostData = array_intersect_key($datas, $this->_schema['post']);
        array_walk($validPostData, function (&$value, $key)
        {
            $filter = isset($this->_schema['post'][$key]['filter']) ? $this->_schema['post'][$key]['filter'] : '';
            $type = $this->_schema['post'][$key]['type'];
            $rshCollection = isset($this->_schema['post'][$key]['rshCollection']) ? $this->_schema['post'][$key]['rshCollection'] : '';
            
            if (! empty($filter)) {
                $filterFailure = false;
                if ($filter === FILTER_VALIDATE_BOOLEAN) {
                    $value = filter_var($value, $filter, FILTER_NULL_ON_FAILURE);
                    $filterFailure = $value === null ? true : false;
                } else {
                    $value = filter_var($value, $filter);
                    $filterFailure = $value === false ? true : false;
                }
                
                if ($filterFailure) {
                    $label = $this->_schema['post'][$key]['label'];
                    $filterFailureReason = $this->getFilterDesc($filter);
                    throw new \Exception("属性:<font color=red>{$label}</font>({$key})的输入不符合'<font color=red>{$filterFailureReason}</font>'要求，请重新输入");
                }
            }
            
            if ($type == 'arrayfield' && isset($this->_rshCollection[$rshCollection])) {
                $rowType = $this->_rshCollection[$rshCollection]['rshCollectionValueFieldType'];
                
                if (! is_array($value) && is_string($value)) {
                    if (! isJson($value)) {
                        throw new \Zend\Json\Exception\RuntimeException($key);
                    }
                    try {
                        $value = Json::decode($value, Json::TYPE_ARRAY);
                    } catch (\Zend\Json\Exception\RuntimeException $e) {
                        throw new \Zend\Json\Exception\RuntimeException($key);
                    }
                }
                
                array_walk($value, function (&$row, $index) use($rowType, $key)
                {
                    $row = formatData($row, $rowType, $key);
                });
            }
            $value = formatData($value, $type, $key);
        });
        
        $validFileData = array_intersect_key($datas, $this->_schema['file']);
        $validData = array_merge($validPostData, $validFileData);
        return $validData;
    }

    /**
     * 快速输入信息
     *
     * @param array $datas            
     * @return array
     */
    private function quickData($datas)
    {
        $validQuickData = array_intersect_key($datas, $this->_schema['quick']);
        array_walk($validQuickData, function (&$value, $field)
        {
            $type = $this->_schema['post'][$field]['type'];
            if ($type == 'arrayfield') {
                $rshCollection = $this->_schema['post'][$field]['rshCollection'];
                $rowType = $this->_rshCollection[$rshCollection]['rshCollectionValueFieldType'];
                if (is_array($value)) {
                    array_walk($value, function (&$row, $index) use($rowType, $field)
                    {
                        $row = formatData($row, $rowType, $field);
                    });
                }
            }
            $value = formatData($value, $type, $field);
        });
        return $validQuickData;
    }

    /**
     * 处理检索条件
     */
    private function searchCondition()
    {
        $query = array();
        
        // 扩展两个系统默认参数加入查询条件
        $this->_schema['post'] = array_merge($this->_schema['post'], array(
            '__CREATE_TIME__' => array(
                'type' => 'datefield'
            ),
            '__MODIFY_TIME__' => array(
                'type' => 'datefield'
            ),
            '__ID__' => array(
                'type' => 'textfield'
            )
        ));
        
        foreach ($this->_schema['post'] as $field => $detail) {
            $subQuery = array();
            $not = false;
            $exact = false;
            
            if (in_array(strtolower($field), $this->_filter)) {
                continue;
            }
            
            if (isset($_REQUEST['exclusive__' . $field]) && filter_var($_REQUEST['exclusive__' . $field], FILTER_VALIDATE_BOOLEAN))
                $not = true;
            
            if (isset($_REQUEST['exactMatch__' . $field]) && filter_var($_REQUEST['exactMatch__' . $field], FILTER_VALIDATE_BOOLEAN))
                $exact = true;
            
            if (! empty($detail['rshCollection'])) {
                $exact = true;
            }
            
            if (isset($_REQUEST[$field])) {
                if (is_array($_REQUEST[$field]) && trim(join('', $_REQUEST[$field])) == '')
                    continue;
                
                if (! is_array($_REQUEST[$field]) && trim($_REQUEST[$field]) == '')
                    continue;
                
                switch ($detail['type']) {
                    case 'numberfield':
                        if (is_array($_REQUEST[$field])) {
                            $min = isset($_REQUEST[$field]['min']) ? trim($_REQUEST[$field]['min']) : '';
                            $max = isset($_REQUEST[$field]['max']) ? trim($_REQUEST[$field]['max']) : '';
                            $min = preg_match("/^[0-9]+\.[0-9]+$/", $min) ? floatval($min) : intval($min);
                            $max = preg_match("/^[0-9]+\.[0-9]+$/", $max) ? floatval($max) : intval($max);
                            
                            if ($min === $max) {
                                if ($not) {
                                    $subQuery[$field]['$ne'] = $min;
                                } else {
                                    $subQuery[$field] = $min;
                                }
                            } else {
                                if ($not) {
                                    if (! empty($min))
                                        $subQuery['$or'][][$field]['$lte'] = $min;
                                    if (! empty($max))
                                        $subQuery['$or'][][$field]['$gte'] = $max;
                                } else {
                                    if (! empty($min))
                                        $subQuery[$field]['$gte'] = $min;
                                    if (! empty($max))
                                        $subQuery[$field]['$lte'] = $max;
                                }
                            }
                        } else {
                            $value = preg_match("/^[0-9]+\.[0-9]+$/", $_REQUEST[$field]) ? floatval($_REQUEST[$field]) : intval($_REQUEST[$field]);
                            if ($not) {
                                $subQuery[$field]['$ne'] = $value;
                            } else {
                                $subQuery[$field] = $value;
                            }
                        }
                        break;
                    case 'datefield':
                        $start = isset($_REQUEST[$field]['start']) ? trim($_REQUEST[$field]['start']) : null;
                        $end = isset($_REQUEST[$field]['end']) ? trim($_REQUEST[$field]['end']) : null;
                        if (! empty($start))
                            $start = preg_match("/^[0-9]+$/", $start) ? new \MongoDate(intval($start)) : new \MongoDate(strtotime($start));
                        if (! empty($end))
                            $end = preg_match("/^[0-9]+$/", $end) ? new \MongoDate(intval($end)) : new \MongoDate(strtotime($end));
                        if ($not) {
                            if (! empty($start))
                                $subQuery['$or'][][$field]['$lte'] = $start;
                            if (! empty($end))
                                $subQuery['$or'][][$field]['$gte'] = $end;
                        } else {
                            if (! empty($start))
                                $subQuery[$field]['$gte'] = $start;
                            if (! empty($end))
                                $subQuery[$field]['$lte'] = $end;
                        }
                        break;
                    case '2dfield':
                        // $lng = floatval(trim($_REQUEST[$field]['lng']));
                        // $lat = floatval(trim($_REQUEST[$field]['lat']));
                        // $distance = ! empty($_REQUEST[$field]['distance']) ? floatval($_REQUEST[$field]['distance']) : 10;
                        // $subQuery[$field] = array(
                        // '$near' => array(
                        // $lng,
                        // $lat
                        // ),
                        // '$maxDistance' => $distance / 111.12
                        // );
                        
                        // // 在mognodb2.5.5以前，无法支持$and查询故如果进行地理位置信息检索，则强制其他检索条件失效。
                        // // 迁移到2.6以后，请注释掉该部分代码
                        // $geoQuery = array();
                        // $geoQuery[$field] = array(
                        // '$near' => array(
                        // $lng,
                        // $lat
                        // ),
                        // '$maxDistance' => $distance / 111.12
                        // );
                        // return $geoQuery;
                        break;
                    case 'boolfield':
                        $subQuery[$field] = filter_var(trim($_REQUEST[$field]), FILTER_VALIDATE_BOOLEAN);
                        break;
                    case 'arrayfield':
                        $rshCollection = $detail['rshCollection'];
                        if (! empty($rshCollection)) {
                            $rowType = $this->_rshCollection[$rshCollection]['rshCollectionValueFieldType'];
                            if ($not)
                                $subQuery[$field]['$ne'] = formatData($_REQUEST[$field], $rowType, $field);
                            else
                                $subQuery[$field] = formatData($_REQUEST[$field], $rowType, $field);
                        }
                        break;
                    default:
                        if ($field == '__ID__') {
                            if ($not)
                                $subQuery["_id"]['$ne'] = new \MongoId($_REQUEST[$field]);
                            else
                                $subQuery["_id"] = new \MongoId($_REQUEST[$field]);
                        } else {
                            if ($not)
                                $subQuery[$field]['$ne'] = trim($_REQUEST[$field]);
                            else
                                $subQuery[$field] = $exact ? trim($_REQUEST[$field]) : myMongoRegex($_REQUEST[$field]);
                        }
                        break;
                }
                if (! empty($subQuery)) {
                    $query['$and'][] = $subQuery;
                }
            }
        }
        
        if (empty($query['$and'])) {
            return array();
        }
        
        return $query;
    }

    /**
     * 根据条件创建排序条件
     *
     * @return array
     */
    private function sortCondition()
    {
        $sort = $this->defaultOrder();
        return $sort;
    }

    /**
     * 获取当前集合的排列顺序
     *
     * @return array
     */
    private function defaultOrder()
    {
        $cursor = $this->_order->find(array(
            'collection_id' => $this->_collection_id
        ));
        $cursor->sort(array(
            'priority' => - 1,
            '_id' => - 1
        ));
        
        $order = array();
        while ($cursor->hasNext()) {
            $row = $cursor->getNext();
            $order[$row['field']] = $row['order'];
        }
        
        if (! isset($order['_id'])) {
            $order['_id'] = - 1;
        }
        return $order;
    }

    /**
     * 根据集合的名称获取集合的_id
     *
     * @param string $alias            
     * @throws \Exception or string
     */
    private function getCollectionIdByAlias($alias)
    {
        try {
            new \MongoId($alias);
            return $alias;
        } catch (\MongoException $ex) {}
        
        $collectionInfo = $this->_collection->findOne(array(
            'project_id' => $this->_project_id,
            'alias' => $alias
        ));
        
        if ($collectionInfo == null) {
            throw new \Exception('集合名称不存在于指定项目');
        }
        
        return $collectionInfo['_id']->__toString();
    }

    /**
     * 根据集合的编号获取集合的别名
     *
     * @param string|object $_id            
     * @throws \Exception
     */
    private function getCollectionAliasById($_id)
    {
        if (! ($_id instanceof \MongoId)) {
            try {
                $_id = new \MongoId($_id);
            } catch (\MongoException $ex) {
                return $_id;
            }
        }
        $collectionInfo = $this->_collection->findOne(array(
            '_id' => $_id,
            'project_id' => $this->_project_id
        ));
        if ($collectionInfo == null) {
            throw new \Exception('集合名称不存在于指定项目');
        }
        
        return $collectionInfo['alias'];
    }

    /**
     * 对于集合进行了任何操作，那么出发联动事件，联动修改其他集合的相关数据
     * 提交全部POST参数以及系统默认的触发参数__TRIGER__
     * $_POST['__TRIGER__']['collection'] 触发事件集合的名称
     * $_POST['__TRIGER__']['controller'] 触发控制器
     * $_POST['__TRIGER__']['action'] 触发动作
     * 为了确保调用安全，签名方法为所有POST参数按照字母顺序排列，构建的字符串substr(sha1(k1=v1&k2=v2连接密钥),0,32)，做个小欺骗，让签名看起来很像MD5的。
     */
    public function __destruct()
    {
        @fastcgi_finish_request();
        $emails = array(
            'youngyang@icatholic.net.cn'
        );
        try {
            $controller = $this->params('controller');
            $action = $this->params('action');
            
            if (in_array($action, array(
                'add',
                'edit',
                'save',
                'remove',
                'drop'
            ))) {
                $_POST['__TRIGER__'] = array(
                    'collection' => $this->getCollectionAliasById($this->_collection_id),
                    'controller' => $controller,
                    'action' => $action
                );
                $collectionInfo = $this->_collection->findOne(array(
                    '_id' => myMongoId($this->_collection_id),
                    'isAutoHook' => true
                ));
                
                // 设定告警邮箱
                if (isset($collectionInfo['hook_notify_email']) && filter_var($collectionInfo['hook_notify_email'], FILTER_VALIDATE_EMAIL))
                    array_push($emails, $collectionInfo['hook_notify_email']);
                
                if ($collectionInfo !== null && isset($collectionInfo['hook']) && filter_var($collectionInfo['hook'], FILTER_VALIDATE_URL) !== false) {
                    $sign = dataSignAlgorithm($_POST, $collectionInfo['hookKey']);
                    $_POST['__SIGN__'] = $sign;
                    
                    $response = doPost($collectionInfo['hook'], $_POST, true);
                    if ($response->isServerError() || $response->isClientError()) {
                        $error = '';
                        $error .= 'HookUrl:' . $collectionInfo['hook'] . "\r\n";
                        $error .= 'CollectionId:' . $this->_collection_id . "\r\n";
                        $error .= 'StatusCode:' . $response->getStatusCode() . "\r\n";
                        $error .= 'Body:' . $response->getBody();
                        $this->sendEmailGearMan($emails, '触发器响应错误提醒', $error);
                        // sendEmail($emails, '触发器响应错误提醒', $error);
                        return false;
                    }
                    
                    $this->_collection->update(array(
                        '_id' => $collectionInfo['_id']
                    ), array(
                        '$set' => array(
                            'hookLastResponseResult' => $response->getBody()
                        )
                    ));
                    
                    // 开启debug模式时，进行提醒
                    if (isset($collectionInfo['hook_debug_mode']) && is_bool($collectionInfo['hook_debug_mode']) && $collectionInfo['hook_debug_mode']) {
                        $error = '';
                        $error .= 'HookUrl:' . $collectionInfo['hook'] . "\r\n";
                        $error .= 'CollectionId:' . $this->_collection_id . "\r\n";
                        $error .= 'StatusCode:' . $response->getStatusCode() . "\r\n";
                        $error .= 'Body:' . $response->getBody();
                        $this->sendEmailGearMan($emails, '触发器响应Debug提醒', $error);
                        // sendEmail($emails, '触发器响应Debug提醒', $error);
                    }
                    
                    return true;
                }
            }
        } catch (\Exception $e) {
            $error = '';
            $error .= 'HookUrl:' . $collectionInfo['hook'] . "\r\n";
            $error .= 'CollectionId:' . $this->_collection_id . "\r\n";
            $error .= 'ExceptionMsg:' . exceptionMsg($e) . "\r\n";
            
            $this->sendEmailGearMan($emails, '触发器调用异常警告', $error);
            // sendEmail($emails, '触发器调用异常警告', $error);
            return false;
        }
        return false;
    }

    /**
     * 采用异步的方式发送电子邮件，避免因为繁重的邮件发送拖慢发送速度
     * @param string $toEmail
     * @param string $subject
     * @param string $content
     * @return boolean
     */
    private function sendEmailGearMan($toEmail, $subject, $content)
    {
        $params = array(
            'toEmail' => $toEmail,
            'subject' => $subject,
            'content' => $content
        );
        $this->_gmClient->doBackground('sendEmailWorker', serialize($params));
        return true;
    }
}
