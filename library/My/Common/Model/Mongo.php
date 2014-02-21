<?php
/**
 * 
 * Model基类，初始化后，如果init方法存在，自动调用init方法
 * @author Young
 * 
 */
namespace My\Common\Model;

use Zend\Config\Config;
use My\Common\MongoCollection;

class Mongo extends MongoCollection
{
    
    /**
     * 集群环境配置信息
     *
     * @var Config
     */
    protected $config;

    /**
     * 需要调用的集合名称
     *
     * @var string
     */
    protected $collection = null;

    /**
     * 数据库名称
     *
     * @var string
     */
    protected $database = DEFAULT_DATABASE;

    /**
     * 集群名称
     *
     * @var string
     */
    protected $cluster = DEFAULT_CLUSTER;

    /**
     * 初始化相关配置
     *
     * @param Config $config            
     * @throws \Exception
     */
    public function __construct(Config $config)
    {
        if ($this->collection == null) {
            throw new \Exception('请设定你要操作的集合');
        }
        
        $this->config = $config;
        parent::__construct($config, $this->collection, $this->database, $this->cluster);
        if (method_exists($this, 'init')) {
            $this->init();
        }
    }

}