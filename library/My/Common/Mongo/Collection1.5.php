<?php
namespace My\Common;

use Zend\Config\Config;
use Zend\EventManager\GlobalEventManager;
use Zend\Json\Json;

class MongoCollection extends \MongoCollection
{

    /**
     * 连接的集合名称
     *
     * @var string
     */
    private $_collection = '';

    /**
     * 连接的数据库名称，默认为系统默认数据库
     *
     * @var string
     */
    private $_database = DEFAULT_DATABASE;

    /**
     * 连接集群的名称，模拟人生为系统默认集合
     *
     * @var string
     */
    private $_cluster = DEFAULT_CLUSTER;

    /**
     * 集合操作参数
     *
     * @var array
     */
    private $_collectionOptions = NULL;

    /**
     * 当前数据库连接实例
     *
     * @var object
     */
    private $_db;

    /**
     * 管理数据连接实例
     *
     * @var object
     */
    private $_admin;

    /**
     * 备份数据库连接实例
     *
     * @var object
     */
    private $_backup;

    /**
     * mapreduce保存数据的数据库连接实例
     *
     * @var object
     */
    private $_mapreduce;

    /**
     * 相关数据库配置参数的数组
     *
     * @var array
     */
    private $_config;

    /**
     * 相关数据库配置参数的Config实例
     *
     * @var Config
     */
    private $_configInstance;

    /**
     * GridFS连接实例
     *
     * @var \MongoGridFS
     */
    private $_fs;

    /**
     * 查询操作列表
     *
     * @var array
     */
    private $_queryHaystack = array(
        '$and',
        '$or',
        '$nor',
        '$not',
        '$where'
    );

    /**
     * 更新操作列表
     *
     * @var array
     */
    private $_updateHaystack = array(
        '$set',
        '$inc',
        '$unset',
        '$rename',
        '$setOnInsert',
        '$addToSet',
        '$pop',
        '$pullAll',
        '$pull',
        '$pushAll',
        '$push',
        '$each',
        '$slice',
        '$sort',
        '$bit',
        '$isolated'
    );

    /**
     * 是否开启追加参数__REMOVED__:true
     *
     * @var boolean
     */
    private $_noAppendQuery = false;

    /**
     * 强制同步写入操作
     *
     * @var boolean
     */
    const fsync = false;

    /**
     * 是否开启更新不存在插入数据
     *
     * @var boolean
     */
    const upsert = false;

    /**
     * 允许更改多项
     *
     * @var boolean
     */
    const multiple = true;

    /**
     * 仅此一项
     *
     * @var boolean
     */
    const justOne = false;

    /**
     * 开启调试模式
     *
     * @var boolean
     */
    const debug = false;

    /**
     * 构造函数
     *
     * @param Config $config            
     * @param string $collection            
     * @param string $database            
     * @param string $cluster            
     * @param string $collectionOptions            
     * @throws \Exception
     */
    public function __construct(Config $config, $collection = null, $database = DEFAULT_DATABASE, $cluster = DEFAULT_CLUSTER, $collectionOptions = null)
    {
        // 检测是否加载了FirePHP
        if (! class_exists("FirePHP")) {
            throw new \Exception('请安装FirePHP');
        }
        
        if (! class_exists("MongoClient")) {
            throw new \Exception('请安装MongoClient');
        }
        
        if ($collection === null) {
            throw new \Exception('$collection集合为空');
        }
        
        $this->_collection = $collection;
        $this->_database = $database;
        $this->_cluster = $cluster;
        $this->_collectionOptions = $collectionOptions;
        $this->_configInstance = $config;
        $this->_config = $config->toArray();
        
        if (! isset($this->_config[$this->_cluster]))
            throw new \Exception('Config error:no cluster key');
        
        if (! isset($this->_config[$this->_cluster]['dbs'][$this->_database]))
            throw new \Exception('Config error:no database init');
        
        if (! isset($this->_config[$this->_cluster]['dbs'][DB_ADMIN]))
            throw new \Exception('Config error:admin database init');
        
        if (! isset($this->_config[$this->_cluster]['dbs'][DB_BACKUP]))
            throw new \Exception('Config error:backup database init');
        
        if (! isset($this->_config[$this->_cluster]['dbs'][DB_MAPREDUCE]))
            throw new \Exception('Config error:mapreduce database init');
        
        $this->_db = $this->_config[$this->_cluster]['dbs'][$this->_database];
        if (! $this->_db instanceof \MongoDB)
            throw new \Exception('$this->_db is not instanceof \MongoDB');
        
        $this->_admin = $this->_config[$this->_cluster]['dbs'][DB_ADMIN];
        if (! $this->_admin instanceof \MongoDB) {
            throw new \Exception('$this->_admin is not instanceof \MongoDB');
        }
        
        $this->_backup = $this->_config[$this->_cluster]['dbs'][DB_BACKUP];
        if (! $this->_backup instanceof \MongoDB) {
            throw new \Exception('$this->_backup is not instanceof \MongoDB');
        }
        
        $this->_mapreduce = $this->_config[$this->_cluster]['dbs'][DB_MAPREDUCE];
        if (! $this->_mapreduce instanceof \MongoDB) {
            throw new \Exception('$this->_mapreduce is not instanceof \MongoDB');
        }
        
        $this->_fs = new \MongoGridFS($this->_db, GRIDFS_PREFIX);
        
        // 默认执行几个操作
        // 第一个操作，判断集合是否创建，如果没有创建，则进行分片处理（目前采用_ID作为片键）
        if (APPLICATION_ENV === 'production') {
            $this->shardingCollection();
        }
        parent::__construct($this->_db, $this->_collection);
        
        /**
         * 设定读取优先级
         * MongoClient::RP_PRIMARY 只读取主db
         * MongoClient::RP_PRIMARY_PREFERRED 读取主db优先
         * MongoClient::RP_SECONDARY 只读从db优先
         * MongoClient::RP_SECONDARY_PREFERRED 读取从db优先
         */
        // $this->db->setReadPreference(\MongoClient::RP_SECONDARY_PREFERRED);
        $this->db->setReadPreference(\MongoClient::RP_PRIMARY_PREFERRED);
        self::autoCreateSystemIndex();
    }

    /**
     * 自动创建系统字段索引
     */
    public function autoCreateSystemIndex()
    {
        if (rand(0, 100) === 1) {
            static::createIndex(array(
                '__REMOVED__' => 1
            ), array(
                'background' => true
            ));
            
            static::createIndex(array(
                '__CREATE_TIME__' => - 1
            ), array(
                'background' => true
            ));
            
            static::createIndex(array(
                '__MODIFY_TIME__' => - 1
            ), array(
                'background' => true
            ));
        }
    }

    /**
     * 是否开启追加模式
     *
     * @param boolean $boolean            
     */
    public function setNoAppendQuery($boolean)
    {
        $this->_noAppendQuery = is_bool($boolean) ? $boolean : false;
    }

    /**
     * 检测是简单查询还是复杂查询，涉及复杂查询采用$and方式进行处理，简单模式采用连接方式进行处理
     *
     * @param array $query            
     * @throws \Exception
     */
    private function appendQuery(array $query = null)
    {
        if (! is_array($query)) {
            $query = array();
        }
        if ($this->_noAppendQuery) {
            return $query;
        }
        
        $keys = array_keys($query);
        $intersect = array_intersect($keys, $this->_queryHaystack);
        if (! empty($intersect)) {
            $query = array(
                '$and' => array(
                    array(
                        '__REMOVED__' => false
                    ),
                    $query
                )
            );
        } else {
            $query['__REMOVED__'] = false;
        }
        return $query;
    }

    /**
     * 检查某个数组中，是否包含某个键
     *
     * @param array $array            
     * @param array $keys            
     * @return boolean
     */
    private function checkKeyExistInArray($array, $keys)
    {
        if (! is_array($keys)) {
            $keys = array(
                $keys
            );
        }
        $result = false;
        array_walk_recursive($array, function ($items, $key) use($keys, &$result)
        {
            if (in_array($key, $keys, true))
                $result = true;
        });
        return $result;
    }

    /**
     * ICC采用_id自动分片机制，故需要判断是否增加片键字段，用于分片集合update数据时使用upsert=>true的情况
     *
     * @param array $query            
     * @return multitype: Ambigous multitype:\MongoId >
     */
    private function addSharedKeyToQuery(array $query = null)
    {
        if (! is_array($query)) {
            throw new \Exception('$query必须为数组');
        }
        
        if ($this->checkKeyExistInArray($query, '_id')) {
            return $query;
        }
        
        $keys = array_keys($query);
        $intersect = array_intersect($keys, $this->_queryHaystack);
        if (! empty($intersect)) {
            $query = array(
                '$and' => array(
                    array(
                        '_id' => new \MongoId()
                    ),
                    $query
                )
            );
        } else {
            $query['_id'] = new \MongoId();
        }
        return $query;
    }

    /**
     * 对于新建集合进行自动分片
     *
     * @return boolean
     */
    private function shardingCollection()
    {
        return true;
        $defaultCollectionOptions = array(
            'capped' => false, // 是否开启固定集合
            'size' => pow(1024, 3), // 如果简单开启capped=>true,单个集合的最大尺寸为2G
            'max' => pow(10, 8), // 如果简单开启capped=>true,单个集合的最大条数为1亿条数据
            'autoIndexId' => true
        );
        
        if ($this->_collectionOptions !== NULL) {
            $this->_collectionOptions = array_merge($defaultCollectionOptions, $this->_collectionOptions);
        }
        
        if (rand(0, 100) === 1) {
            $checkCollection = $this->_db->selectCollection($this->_collection);
            $check = $checkCollection->validate(false);
            if (empty($check['ok'])) {
                $this->_db->createCollection($this->_collection, $this->_collectionOptions);
                $rst = $this->_admin->command(array(
                    'shardCollection' => $this->_database . '.' . $this->_collection,
                    'key' => array(
                        '_id' => 'hashed'
                    )
                ));
                if ($rst['ok'] == 1) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 处理检索条件
     *
     * @param string $text            
     */
    private function search($text)
    {
        return new \MongoRegex('/' . preg_replace("/[\s\r\t\n]/", '.*', $text) . '/i');
    }

    /**
     * aggregate框架指令达成
     *
     * @return mixed
     */
    public function aggregate($pipeline, $op = NULL, $op1 = NULL)
    {
        if (! $this->_noAppendQuery) {
            if (isset($pipeline[0]['$geoNear'])) {
                $first = array_shift($pipeline);
                array_unshift($pipeline, array(
                    '$match' => array(
                        '__REMOVED__' => false
                    )
                ));
                array_unshift($pipeline, $first);
            } elseif (isset($pipeline[0]['$match'])) {
                // 解决率先执行$match:{__REMOVED__:false}导致的性能问题
                $pipeline[0]['$match'] = $this->appendQuery($pipeline[0]['$match']);
            } else {
                array_unshift($pipeline, array(
                    '$match' => array(
                        '__REMOVED__' => false
                    )
                ));
            }
        }
        
        return parent::aggregate($pipeline);
    }

    /**
     * Execute an aggregation pipeline command and retrieve results through a cursor
     *
     * @param array $pipeline            
     * @param array $options            
     */
    public function aggregateCursor(array $pipeline, array $options = NULL)
    {
        if (! $this->_noAppendQuery) {
            if (isset($pipeline[0]['$geoNear'])) {
                $first = array_shift($pipeline);
                array_unshift($pipeline, array(
                    '$match' => array(
                        '__REMOVED__' => false
                    )
                ));
                array_unshift($pipeline, $first);
            } elseif (isset($pipeline[0]['$match'])) {
                // 解决率先执行$match:{__REMOVED__:false}导致的性能问题
                $pipeline[0]['$match'] = $this->appendQuery($pipeline[0]['$match']);
            } else {
                array_unshift($pipeline, array(
                    '$match' => array(
                        '__REMOVED__' => false
                    )
                ));
            }
        }
        
        return parent::aggregateCursor($pipeline, $options);
    }

    /**
     * 批量插入数据
     *
     * @see MongoCollection::batchInsert()
     */
    public function batchInsert(array $documents, array $options = NULL)
    {
        array_walk($documents, function (&$row, $key)
        {
            $row['__CREATE_TIME__'] = $row['__MODIFY_TIME__'] = new \MongoDate();
            $row['__REMOVED__'] = false;
        });
        return parent::batchInsert($documents, $options);
    }

    /**
     * 统计符合条件的数量
     *
     * @see MongoCollection::count()
     */
    public function count($query = NULL, $limit = NULL, $skip = NULL)
    {
        $query = $this->appendQuery($query);
        return parent::count($query, $limit, $skip);
    }

    /**
     * 根据指定字段
     *
     * @param string $key            
     * @param array $query            
     */
    public function distinct($key, $query = null)
    {
        $query = $this->appendQuery($query);
        return parent::distinct($key, $query);
    }

    /**
     * 直接禁止drop操作,注意备份表中只包含当前集合中的有效数据，__REMOVED__为true的不在此列
     * 本操作仅适用于小数据量的集合，对于大数据量集合将会耗时很长，尤其是在集群分片的环境下
     *
     * @see MongoCollection::drop()
     */
    function drop()
    {
        // 做法1：抛出异常禁止Drop操作
        // throw new \Exception('ICC deny execute "drop()" collection operation');
        // 做法2：复制整个集合的数据到新的集合中，用于备份，备份数据不做片键，不做索引以便节约空间，仅出于安全考虑，原有_id使用保留字__OLD_ID__进行保留
        $targetCollection = 'bak_' . date('YmdHis') . '_' . $this->_collection;
        $target = new \MongoCollection($this->_backup, $targetCollection);
        // 变更为重命名某个集合或者复制某个集合的操作作为替代。
        $cursor = $this->find(array());
        while ($cursor->hasNext()) {
            $row = $cursor->getNext();
            $row['__OLD_ID__'] = $row['_id'];
            unset($row['_id']);
            $target->insert($row, array(
                'w' => 0
            ));
        }
        return parent::drop();
    }

    /**
     * 物理删除数据集合
     */
    public function physicalDrop()
    {
        return parent::drop();
    }

    /**
     * 在同一个数据库内，复制集合数据
     *
     * @param string $from            
     * @param string $to            
     */
    public function copyTo($to)
    {
        switch ($this->_database) {
            case DEFAULT_DATABASE:
                $db = $this->_db;
                break;
            case DB_MAPREDUCE:
                $db = $this->_mapreduce;
                break;
            case DB_BACKUP:
                $db = $this->_backup;
                break;
            case DB_ADMIN:
                $db = $this->_admin;
                break;
            default:
                $db = $this->_db;
                break;
        }
        $target = new \MongoCollection($db, $to);
        if (method_exists($target, 'setWriteConcern'))
            $target->setWriteConcern(0);
        else
            $target->w = 0;
        
        $cursor = $this->find(array());
        while ($cursor->hasNext()) {
            $row = $cursor->getNext();
            if ($row['_id'] instanceof \MongoId) {
                $row['__OLD_ID__'] = $row['_id'];
                unset($row['_id']);
            }
            $target->insert($row);
        }
        return true;
    }

    /**
     * 新驱动已经抛弃了该方法，但是为了保持一致性，继续支持，但是不建议采用
     *
     * @see MongoCollection::ensureIndex()
     */
    public function ensureIndex($key_keys, array $options = NULL)
    {
        $default = array();
        $default['background'] = true;
        $default['w'] = 0;
        // $default['expireAfterSeconds'] = 3600; // 请充分了解后开启此参数，慎用
        $options = ($options === NULL) ? $default : array_merge($default, $options);
        return parent::createIndex($key_keys, $options);
    }

    /**
     * 新的写法，创建索引
     *
     * @param array $key_keys            
     * @param array $options            
     */
    public function createIndex($keys, array $options = NULL)
    {
        if ($this->checkIndexExist($keys)) {
            return true;
        }
        
        $default = array();
        $default['background'] = true;
        $default['w'] = 0;
        // $default['expireAfterSeconds'] = 3600; // 请充分了解后开启此参数，慎用
        $options = ($options === NULL) ? $default : array_merge($default, $options);
        return parent::createIndex($keys, $options);
    }

    /**
     * 检测集合的某个索引是否存在
     * 
     * @param array $keys            
     * @return boolean
     */
    public function checkIndexExist($keys)
    {
        $indexs = parent::getIndexInfo();
        if (! empty($indexs) && is_array($indexs)) {
            foreach ($indexs as $index) {
                if ($index['key'] == $keys) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 查询符合条件的项目，自动排除__REMOVED__:true的结果集
     *
     * @see MongoCollection::find()
     */
    public function find($query = NULL, $fields = NULL)
    {
        $fields = empty($fields) ? array() : $fields;
        $query = $this->appendQuery($query);
        return parent::find($query, $fields);
    }

    /**
     * 查询符合条件的一条数据
     *
     * @see MongoCollection::findOne()
     */
    public function findOne($query = NULL, $fields = NULL, array $options = NULL)
    {
        $default = array();
        $options = ($options === NULL) ? $default : array_merge($default, $options);
        $fields = empty($fields) ? array() : $fields;
        return parent::findOne($this->appendQuery($query), $fields, $options);
    }

    /**
     * 获取符合条件的全部数据
     *
     * @param array $query            
     * @param array $sort            
     * @param int $skip            
     * @param int $limit            
     * @param array $fields            
     * @return array
     */
    public function findAll($query = array(), $sort = array('$natural'=>1), $skip = 0, $limit = 0, $fields = array())
    {
        $fields = empty($fields) ? array() : $fields;
        $cursor = $this->find($query, $fields);
        if (! $cursor instanceof \MongoCursor)
            throw new \Exception('$query error:' . json_encode($query));
        
        if (! empty($sort))
            $cursor->sort($sort);
        
        if (! empty($skip))
            $cursor->skip($skip);
        
        if ($limit > 0) {
            $cursor->limit($limit);
        }
        
        if ($cursor instanceof \Traversable)
            return iterator_to_array($cursor, false);
        
        return array();
    }

    /**
     * findAndModify操作
     * 特别注意：__REMOVED__ __MODIFY_TIME__ __CREATE_TIME__ 3个系统保留变量在update参数中的使用
     *
     * @param array $query            
     * @param array $update            
     * @param array $fields            
     * @param array $options            
     * @return array
     */
    public function findAndModify(array $query, array $update = NULL, array $fields = NULL, array $options = NULL)
    {
        $query = $this->appendQuery($query);
        if (parent::count($query) == 0 && ! empty($options['upsert'])) {
            $query = $this->addSharedKeyToQuery($query);
        } else {
            unset($options['upsert']);
        }
        return parent::findAndModify($query, $update, $fields, $options);
    }

    /**
     * 增加findAndModify方法
     * 特别注意：__REMOVED__ __MODIFY_TIME__ __CREATE_TIME__ 3个系统保留变量在update参数中的使用
     *
     * @param array $option            
     * @param string $collection            
     * @return mixed array|null
     */
    public function findAndModifyByCommand($option, $collection = NULL)
    {
        $cmd = array();
        $targetCollection = $collection === NULL ? $this->_collection : $collection;
        $cmd['findandmodify'] = $targetCollection;
        if (isset($option['query']))
            $cmd['query'] = $this->appendQuery($option['query']);
        if (isset($option['sort']))
            $cmd['sort'] = $option['sort'];
        if (isset($option['remove']))
            $cmd['remove'] = is_bool($option['remove']) ? $option['remove'] : false;
        if (isset($option['update']))
            $cmd['update'] = $option['update'];
        if (isset($option['new']))
            $cmd['new'] = is_bool($option['new']) ? $option['new'] : false;
        if (isset($option['fields']))
            $cmd['fields'] = $option['fields'];
        if (isset($option['upsert']))
            $cmd['upsert'] = is_bool($option['upsert']) ? $option['upsert'] : false;
        
        if (parent::count($cmd['query']) == 0 && ! empty($option['upsert'])) {
            $cmd['query'] = $this->addSharedKeyToQuery($cmd['query']);
        } else {
            unset($cmd['upsert']);
        }
        return $this->_db->command($cmd);
    }

    /**
     * 插入特定的数据,并保持insert第一个参数$a在没有_id的时候添加_id属性
     *
     * @param array $object            
     * @param array $options            
     */
    public function insertRef(&$a, array $options = NULL)
    {
        if (empty($a))
            throw new \Exception('$object is NULL');
        
        $default = array(
            'fsync' => self::fsync
        );
        $options = ($options === NULL) ? $default : array_merge($default, $options);
        
        array_unset_recursive($a, array(
            '__CREATE_TIME__',
            '__MODIFY_TIME__',
            '__REMOVED__'
        ));
        
        if (! isset($a['__CREATE_TIME__'])) {
            $a['__CREATE_TIME__'] = new \MongoDate();
        }
        
        if (! isset($a['__MODIFY_TIME__'])) {
            $a['__MODIFY_TIME__'] = new \MongoDate();
        }
        
        if (! isset($a['__REMOVED__'])) {
            $a['__REMOVED__'] = false;
        }
        
        $b = $a;
        $res = parent::insert($b, $options);
        $a = $b;
        return $res;
    }

    /**
     * 插入特定的数据，注意此方法無法針對$a添加_id属性，详见参数丢失原因的文档说明
     * 解决这个问题，请使用上面的方法insertRef
     * 注意因为参数检查的原因，无法直接覆盖insert方法并采用引用，如下原因
     * <b>Strict Standards</b>: Declaration of My\Common\MongoCollection::insert() should be compatible with MongoCollection::insert($array_of_fields_OR_object, array $options = NULL)
     *
     * @param array $object            
     * @param array $options            
     */
    public function insert($a, array $options = NULL)
    {
        if (empty($a))
            throw new \Exception('$object is NULL');
        
        $default = array(
            'fsync' => self::fsync
        );
        $options = ($options === NULL) ? $default : array_merge($default, $options);
        
        array_unset_recursive($a, array(
            '__CREATE_TIME__',
            '__MODIFY_TIME__',
            '__REMOVED__'
        ));
        
        if (! isset($a['__CREATE_TIME__'])) {
            $a['__CREATE_TIME__'] = new \MongoDate();
        }
        
        if (! isset($a['__MODIFY_TIME__'])) {
            $a['__MODIFY_TIME__'] = new \MongoDate();
        }
        
        if (! isset($a['__REMOVED__']) && ! $this->_noAppendQuery) {
            $a['__REMOVED__'] = false;
        }
        
        return parent::insert($a, $options);
    }

    /**
     * 通过findAndModify的方式，插入数据。
     * 这样可以使用$a['a.b']的方式插入结构为{a:{b:xxx}}的数据,这是insert所不能办到的
     * 采用update也可以实现类似的效果，区别在于findAndModify可以返回插入之后的新数据，更接近insert的原始行为
     *
     * @param array $a            
     * @return array
     */
    public function insertByFindAndModify($a)
    {
        if (empty($a))
            throw new \Exception('$a is NULL');
        
        array_unset_recursive($a, array(
            '__CREATE_TIME__',
            '__MODIFY_TIME__',
            '__REMOVED__'
        ));
        
        if (! isset($a['__CREATE_TIME__'])) {
            $a['__CREATE_TIME__'] = new \MongoDate();
        }
        
        if (! isset($a['__MODIFY_TIME__'])) {
            $a['__MODIFY_TIME__'] = new \MongoDate();
        }
        
        if (! isset($a['__REMOVED__'])) {
            $a['__REMOVED__'] = false;
        }
        
        $query = array(
            '_id' => new \MongoId()
        );
        $a = array(
            '$set' => $a
        );
        $fields = null;
        $options = array(
            'new' => true,
            'upsert' => true
        );
        
        return parent::findAndModify($query, $a, $fields, $options);
    }

    /**
     * 删除指定范围的数据
     *
     * @param array $criteria            
     * @param array $options            
     */
    public function remove($criteria = NULL, array $options = NULL)
    {
        if ($criteria === NULL)
            throw new \Exception('$criteria is NULL');
        
        $default = array(
            'justOne' => self::justOne,
            'fsync' => self::fsync
        );
        
        $options = ($options === NULL) ? $default : array_merge($default, $options);
        
        // 方案一 真实删除
        // return parent::remove($criteria, $options);
        // 方案二 伪删除
        
        if (! $options['justOne']) {
            $options['multiple'] = true;
        }
        
        $criteria = $this->appendQuery($criteria);
        return parent::update($criteria, array(
            '$set' => array(
                '__REMOVED__' => true
            )
        ), $options);
    }

    /**
     * 物理删除指定范围的数据
     *
     * @param array $criteria            
     * @param array $options            
     */
    public function physicalRemove($criteria = NULL, array $options = NULL)
    {
        if ($criteria === NULL)
            throw new \Exception('$criteria is NULL');
        
        $default = array(
            'justOne' => self::justOne,
            'fsync' => self::fsync
        );
        
        $options = ($options === NULL) ? $default : array_merge($default, $options);
        return parent::remove($criteria, $options);
    }

    /**
     * 物理更新数据
     *
     * @param array $criteria            
     * @param array $object            
     * @param array $options            
     * @throws \Exception
     */
    public function physicalUpdate($criteria, $object, array $options = NULL)
    {
        if (! is_array($criteria))
            throw new \Exception('$criteria is array');
        
        if (empty($object))
            throw new \Exception('$object is empty');
        
        $keys = array_keys($object);
        foreach ($keys as $key) {
            // $key = strtolower($key);
            if (! in_array($key, $this->_updateHaystack, true)) {
                throw new \Exception('$key must contain ' . join(',', $this->_updateHaystack));
            }
        }
        
        $default = array(
            'upsert' => self::upsert,
            'multiple' => self::multiple,
            'fsync' => self::fsync
        );
        
        $options = ($options === NULL) ? $default : array_merge($default, $options);
        return parent::update($criteria, $object, $options);
    }

    /**
     * 更新指定范围的数据
     *
     * @param array $criteria            
     * @param array $object            
     * @param array $options            
     */
    public function update($criteria, $object, array $options = NULL)
    {
        if (! is_array($criteria))
            throw new \Exception('$criteria is array');
        
        if (empty($object))
            throw new \Exception('$object is empty');
        
        $keys = array_keys($object);
        foreach ($keys as $key) {
            // $key = strtolower($key);
            if (! in_array($key, $this->_updateHaystack, true)) {
                throw new \Exception('$key must contain ' . join(',', $this->_updateHaystack));
            }
        }
        $default = array(
            'upsert' => self::upsert,
            'multiple' => self::multiple,
            'fsync' => self::fsync
        );
        
        $options = ($options === NULL) ? $default : array_merge($default, $options);
        
        $criteria = $this->appendQuery($criteria);
        array_unset_recursive($object, array(
            '_id',
            '__CREATE_TIME__',
            '__MODIFY_TIME__',
            '__REMOVED__'
        ));
        
        if (parent::count($criteria) == 0) {
            if (isset($options['upsert']) && $options['upsert']) {
                $criteria = $this->addSharedKeyToQuery($criteria);
                parent::update($criteria, array(
                    '$set' => array(
                        '__CREATE_TIME__' => new \MongoDate(),
                        '__MODIFY_TIME__' => new \MongoDate(),
                        '__REMOVED__' => false
                    )
                ), $options);
            }
        } else {
            unset($options['upsert']);
            parent::update($criteria, array(
                '$set' => array(
                    '__MODIFY_TIME__' => new \MongoDate()
                )
            ), $options);
        }
        
        return parent::update($criteria, $object, $options);
    }

    /**
     * 保存并保持引用修改状态
     *
     * @param array $a            
     * @param array $options            
     * @return mixed
     */
    public function save($a, array $options = NULL)
    {
        if (! isset($a['__CREATE_TIME__'])) {
            $a['__CREATE_TIME__'] = new \MongoDate();
        }
        $a['__REMOVED__'] = false;
        $a['__MODIFY_TIME__'] = new \MongoDate();
        if ($options == null) {
            $options = array(
                'w' => 1
            );
        }
        return parent::save($a, $options);
    }

    /**
     * 保存并保持引用修改状态
     *
     * @param array $a            
     * @param array $options            
     * @return mixed
     */
    public function saveRef(&$a, array $options = NULL)
    {
        if (! isset($a['__CREATE_TIME__'])) {
            $a['__CREATE_TIME__'] = new \MongoDate();
        }
        $a['__REMOVED__'] = false;
        $a['__MODIFY_TIME__'] = new \MongoDate();
        if ($options == null) {
            $options = array(
                'w' => 1
            );
        }
        
        $b = $a;
        $res = parent::save($b, $options);
        $a = $b;
        return $res;
    }

    /**
     * 执行DB的command操作,直接运行命令行操作数据库中的数据，慎用
     *
     * @param array $command            
     * @return array
     */
    public function command($command)
    {
        return $this->db->command($command);
    }

    /**
     * 执行map reduce操作,为了防止数据量过大，导致无法完成mapreduce,统一采用集合的方式，取代内存方式
     * 内存方式，不允许执行过程的数据量量超过物理内存的10%，故无法进行大数量分析工作。
     *
     * @param array $command            
     */
    public function mapReduce($out = null, $map, $reduce, $query = array(), $finalize = null, $method = 'replace', $scope = null, $sort = array('$natural'=>1), $limit = null)
    {
        if ($out == null) {
            $out = md5(serialize(func_get_args()));
        }
        try {
            // map reduce执行锁管理开始
            $locks = new self($this->_configInstance, 'locks', DB_MAPREDUCE, $this->_cluster);
            $locks->setReadPreference(\MongoClient::RP_PRIMARY_PREFERRED);
            
            $releaseLock = function ($out, $rst = null) use($locks)
            {
                return $locks->update(array(
                    'out' => $out
                ), array(
                    '$set' => array(
                        'isRunning' => false,
                        'rst' => is_string($rst) ? $rst : Json::encode($rst)
                    )
                ));
            };
            
            $checkLock = function ($out) use($locks,$releaseLock)
            {
                $check = $locks->findOne(array(
                    'out' => $out
                ));
                if ($check == null) {
                    $locks->insert(array(
                        'out' => $out,
                        'isRunning' => true,
                        'expire' => new \MongoDate(time() + 300),
                        'rst' => ''
                    ));
                    return false;
                } else {
                    if ($check['isRunning'] && isset($check['expire']) && $check['expire'] instanceof \MongoDate) {
                        if ($check['expire']->sec > time()) {
                            return true;
                        } else {
                            $releaseLock($out);
                            return false;
                        }
                    }
                    
                    if (isset($check['isRunning']) && $check['isRunning']) {
                        return true;
                    }
                    
                    $locks->update(array(
                        'out' => $out
                    ), array(
                        '$set' => array(
                            'isRunning' => true,
                            'expire' => new \MongoDate(time() + 300),
                            'rst' => ''
                        )
                    ));
                    return false;
                }
            };
            
            $failure = function ($code, $msg)
            {
                if (is_array($msg)) {
                    $msg = Json::encode($msg);
                }
                return array(
                    'ok' => 0,
                    'code' => $code,
                    'msg' => $msg
                );
            };
            // map reduce执行锁管理结束
            
            if (! $checkLock($out)) {
                $command = array();
                $command['mapreduce'] = $this->_collection;
                $command['map'] = ($map instanceof \MongoCode) ? $map : new \MongoCode($map);
                $command['reduce'] = ($reduce instanceof \MongoCode) ? $reduce : new \MongoCode($reduce);
                $query = $this->appendQuery($query);
                if (! empty($query))
                    $command['query'] = $query;
                
                if (! empty($finalize))
                    $command['finalize'] = ($finalize instanceof \MongoCode) ? $finalize : new \MongoCode($finalize);
                if (! empty($sort))
                    $command['sort'] = $sort;
                if (! empty($limit))
                    $command['limit'] = $limit;
                if (! empty($scope))
                    $command['scope'] = $scope;
                $command['verbose'] = true;
                
                if (! in_array($method, array(
                    'replace',
                    'merge',
                    'reduce'
                ), true)) {
                    $method = 'replace';
                }
                
                $command['out'] = array(
                    $method => $out,
                    'db' => DB_MAPREDUCE,
                    'sharded' => false,
                    'nonAtomic' => in_array($method, array(
                        'merge',
                        'reduce'
                    ), true) ? true : false
                );
                
                $this->db->setReadPreference(\MongoClient::RP_SECONDARY);
                $rst = $this->command($command);
                $releaseLock($out, $rst);
                
                if ($rst['ok'] == 1) {
                    if ($rst['counts']['emit'] > 0 && $rst['counts']['output'] > 0) {
                        $outMongoCollection = new self($this->_configInstance, $out, DB_MAPREDUCE, $this->_cluster);
                        $outMongoCollection->setNoAppendQuery(true);
                        return $outMongoCollection;
                    }
                    return $failure(500, $rst['counts']);
                } else {
                    return $failure(501, $rst);
                }
            } else {
                return $failure(502, '程序正在执行中，请勿频繁尝试');
            }
        } catch (\Exception $e) {
            if (isset($releaseLock) && isset($failure)) {
                $releaseLock($out, exceptionMsg($e));
                return $failure(503, exceptionMsg($e));
            }
            var_dump(exceptionMsg($e));
        }
    }

    /**
     * 云存储文件
     *
     * @param string $fieldName
     *            上传表单字段的名称
     * @return array 返回上传文件成功后的object
     *        
     *         object结构如下:
     *         array(
     *         ['_id'] =>
     *         MongoId(
     *        
     *         $id =
     *         '537cc9b54896194b228b4581'
     *         )
     *         ['collection_id'] =>
     *         NULL
     *         ['name'] =>
     *         'b21c8701a18b87d624ac8e2d050828381f30fd11.jpg'
     *         ['type'] =>
     *         'image/jpeg'
     *         ['tmp_name'] =>
     *         '/tmp/phpeBS799'
     *         ['error'] =>
     *         0
     *         ['size'] =>
     *         350522
     *         ['mime'] =>
     *         'image/jpeg; charset=binary'
     *         ['filename'] =>
     *         'b21c8701a18b87d624ac8e2d050828381f30fd11.jpg'
     *         ['uploadDate'] =>
     *         MongoDate(
     *        
     *         sec =
     *         1400687029
     *         usec =
     *         515000
     *         )
     *         ['length'] =>
     *         350522
     *         ['chunkSize'] =>
     *         262144
     *         ['md5'] =>
     *         '3a736c4eed22030dde16df11fee263e7'
     *         )
     *        
     */
    public function storeToGridFS($fieldName, $metadata = array())
    {
        if (! is_array($metadata))
            $metadata = array();
        
        if (! isset($_FILES[$fieldName]))
            throw new \Exception('$_FILES[$fieldName]无效');
        
        $metadata = array_merge($metadata, $_FILES[$fieldName]);
        $finfo = new \finfo(FILEINFO_MIME);
        $mime = $finfo->file($_FILES[$fieldName]['tmp_name']);
        if ($mime !== false)
            $metadata['mime'] = $mime;
        
        try {
            $id = $this->_fs->storeUpload($fieldName, $metadata);
        } catch (\MongoGridFSException $e) {
            fb(exceptionMsg($e), 'LOG');
            throw new \Exception($e->getMessage());
        }
        $gridfsFile = $this->_fs->get($id);
        if (! ($gridfsFile instanceof \MongoGridFSFile)) {
            fb($gridfsFile, 'LOG');
            throw new \Exception('$gridfsFile is not instanceof MongoGridFSFile');
        }
        
        return $gridfsFile->file;
    }

    /**
     * 存储二进制内容
     *
     * @param bytes $bytes            
     * @param string $filename            
     * @param array $metadata            
     */
    public function storeBytesToGridFS($bytes, $filename = '', $metadata = array())
    {
        if (! is_array($metadata))
            $metadata = array();
        
        if (! empty($filename))
            $metadata['filename'] = $filename;
        
        $finfo = new \finfo(FILEINFO_MIME);
        $mime = $finfo->buffer($bytes);
        if ($mime !== false)
            $metadata['mime'] = $mime;
        try {
            $id = $this->_fs->storeBytes($bytes, $metadata);
        } catch (\MongoGridFSException $e) {
            fb(exceptionMsg($e), 'LOG');
            throw new \Exception($e->getMessage());
        }
        $gridfsFile = $this->_fs->get($id);
        return $gridfsFile->file;
    }

    /**
     * 获取指定ID的GridFSFile对象
     *
     * @param string $id            
     * @return \MongoGridFSFile object
     */
    public function getGridFsFileById($id)
    {
        if (! $id instanceof \MongoId) {
            $id = new \MongoId($id);
        }
        return $this->_fs->get($id);
    }

    /**
     * 根据查询条件获取指定数量的结果集
     *
     * @param array $query            
     * @param number $start            
     * @param number $limit            
     * @return multitype:
     */
    public function getGridFsFileBy($query, $sort = array('_id'=>-1), $start = 0, $limit = 20, $fields = array())
    {
        if (! is_array($query)) {
            $query = array();
        }
        $cursor = $this->_fs->find($query, $fields);
        $cursor->sort($sort)
            ->skip($start)
            ->limit($limit);
        $rst = iterator_to_array($cursor, false);
        return $rst;
    }

    /**
     * 获取GridFS对象
     *
     * @return \MongoGridFS
     */
    public function getGridFS()
    {
        return $this->_fs;
    }

    /**
     * 根据ID获取文件的信息
     *
     * @param string $id            
     * @return array 文件信息数组
     */
    public function getInfoFromGridFS($id)
    {
        if (! $id instanceof \MongoId) {
            $id = new \MongoId($id);
        }
        $gridfsFile = $this->_fs->get($id);
        return $gridfsFile->file;
    }

    /**
     * 根据ID获取文件内容，二进制
     *
     * @param string $id            
     * @return bytes
     */
    public function getFileFromGridFS($id)
    {
        if (! $id instanceof \MongoId) {
            $id = new \MongoId($id);
        }
        $gridfsFile = $this->_fs->get($id);
        if ($gridfsFile instanceof \MongoGridFSFile) {
            return $gridfsFile->getBytes();
        } else {
            return false;
        }
    }

    /**
     * 删除陈旧的文件
     *
     * @param mixed $id
     *            \MongoID or String
     * @return bool true or false
     */
    public function removeFileFromGridFS($id)
    {
        if (! $id instanceof \MongoId) {
            $id = new \MongoId($id);
        }
        return $this->_fs->remove(array(
            '_id' => $id
        ));
    }

    /**
     * 打印最后一个错误信息
     */
    private function debug()
    {
        if ($this->_db instanceof \MongoDB) {
            $err = $this->_db->lastError();
            // 在浏览器中输出错误信息以便发现问题
            if (self::debug) {
                fb($err, \FirePHP::LOG);
            }
            
            if ($err['err'] != null) {
                logError($err);
            }
        }
    }

    /**
     * 在析构函数中调用debug方法
     */
    public function __destruct()
    {
        if (! empty($_GET['__DEBUG__']))
            $this->debug();
    }
}