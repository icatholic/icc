<?php
/**
 * 定义全局的常量
 */
defined('ROOT_PATH') || define('ROOT_PATH', dirname(__DIR__));

//切换到本地环境，请修改下列三个参数，开始
defined('CACHE_ADAPTER') || define('CACHE_ADAPTER', 'fileCache'); // [fileCache|memcachedCache|redisCache]
defined('APPLICATION_ENV') || define('APPLICATION_ENV', 'development'); // [development|production]
defined('DOMAIN') || define('DOMAIN', 'http://localhost');
//切换到本地环境，请修改以上三个参数，结束

defined('DEFAULT_DATABASE') || define('DEFAULT_DATABASE', 'ICCv1');
defined('DEFAULT_CLUSTER') || define('DEFAULT_CLUSTER', 'default');

/**
 * ICC指定数据库列表
 */
defined('DB_ADMIN') || define('DB_ADMIN', 'admin');
defined('DB_BACKUP') || define('DB_BACKUP', 'backup');
defined('DB_MAPREDUCE') || define('DB_MAPREDUCE', 'mapreduce');
defined('DB_LOGS') || define('DB_LOGS', 'logs');
defined('DB_UMA') || define('DB_UMA', 'umav3');
defined('GRIDFS_PREFIX') || define('GRIDFS_PREFIX', 'icc');

/**
 * 系统全局设定数据库
 */
defined('SYSTEM_ACCOUNT') || define('SYSTEM_ACCOUNT', 'system_account');
defined('SYSTEM_ACCOUNT_PROJECT_ACL') || define('SYSTEM_ACCOUNT_PROJECT_ACL', 'system_account_project_acl');
defined('SYSTEM_ROLE') || define('SYSTEM_ROLE', 'system_role');
defined('SYSTEM_RESOURCE') || define('SYSTEM_RESOURCE', 'system_resource');
defined('SYSTEM_SETTING') || define('SYSTEM_SETTING', 'system_setting');

/**
 * iDatabase常量定义,防止集合命名错误的发生
 */
defined('IDATABASE_INDEXES') || define('IDATABASE_INDEXES', 'idatabase_indexes');
defined('IDATABASE_COLLECTIONS') || define('IDATABASE_COLLECTIONS', 'idatabase_collections');
defined('IDATABASE_STRUCTURES') || define('IDATABASE_STRUCTURES', 'idatabase_structures');
defined('IDATABASE_PROJECTS') || define('IDATABASE_PROJECTS', 'idatabase_projects');
defined('IDATABASE_PLUGINS') || define('IDATABASE_PLUGINS', 'idatabase_plugins');
defined('IDATABASE_PLUGINS_COLLECTIONS') || define('IDATABASE_PLUGINS_COLLECTIONS', 'idatabase_plugins_collections');
defined('IDATABASE_PLUGINS_STRUCTURES') || define('IDATABASE_PLUGINS_STRUCTURES', 'idatabase_plugins_structures');
defined('IDATABASE_PLUGINS_DATAS') || define('IDATABASE_PLUGINS_DATAS', 'idatabase_plugins_datas');
defined('IDATABASE_PROJECT_PLUGINS') || define('IDATABASE_PROJECT_PLUGINS', 'idatabase_project_plugins');
defined('IDATABASE_VIEWS') || define('IDATABASE_VIEWS', 'idatabase_views');
defined('IDATABASE_STATISTIC') || define('IDATABASE_STATISTIC', 'idatabase_statistic');
defined('IDATABASE_PROMISSION') || define('IDATABASE_PROMISSION', 'idatabase_promission');
defined('IDATABASE_KEYS') || define('IDATABASE_KEYS', 'idatabase_keys');
defined('IDATABASE_COLLECTION_ORDERBY') || define('IDATABASE_COLLECTION_ORDERBY', 'idatabase_collection_orderby');
defined('IDATABASE_MAPPING') || define('IDATABASE_MAPPING', 'idatabase_mapping');
defined('IDATABASE_LOCK') || define('IDATABASE_LOCK', 'idatabase_lock');
defined('IDATABASE_QUICK') || define('IDATABASE_QUICK', 'idatabase_quick');
defined('IDATABASE_DASHBOARD') || define('IDATABASE_DASHBOARD', 'idatabase_dashboard');
defined('IDATABASE_FILES') || define('IDATABASE_FILES', 'idatabase_files');
// 2014.08.21增加插件索引与统计同步增加
defined('IDATABASE_PLUGINS_INDEXES') || define('IDATABASE_PLUGINS_INDEXES', 'idatabase_plugins_indexes');
defined('IDATABASE_PLUGINS_STATISTIC') || define('IDATABASE_PLUGINS_STATISTIC', 'idatabase_plugins_statistic');
// 2014.08.20增加用户访问日志增加
defined('IDATABASE_LOGS') || define('IDATABASE_LOGS', 'idatabase_logs');

/**
 * 自定义事件
 */
defined('EVENT_LOG_ERROR') || define('EVENT_LOG_ERROR', 'event_log_error');
defined('EVENT_LOG_DEBUG') || define('EVENT_LOG_DEBUG', 'event_log_debug');

/**
 * 服务器配置信息
 */
defined('MEMCACHED_01') || define('MEMCACHED_01', APPLICATION_ENV === 'production' ? '10.0.0.1' : '127.0.0.1');
defined('MEMCACHED_02') || define('MEMCACHED_02', APPLICATION_ENV === 'production' ? '10.0.0.2' : '127.0.0.1');

defined('REDIS_01') || define('REDIS_01', APPLICATION_ENV === 'production' ? '10.0.0.1' : '127.0.0.1');

defined('MONGOS_DEFAULT_01') || define('MONGOS_DEFAULT_01', APPLICATION_ENV === 'production' ? '10.0.0.30:57017' : '127.0.0.1:27017');
defined('MONGOS_DEFAULT_02') || define('MONGOS_DEFAULT_02', APPLICATION_ENV === 'production' ? '10.0.0.31:57017' : '127.0.0.1:27017');
defined('MONGOS_DEFAULT_03') || define('MONGOS_DEFAULT_03', APPLICATION_ENV === 'production' ? '10.0.0.32:57017' : '127.0.0.1:27017');

defined('MONGOS_ANALYSIS_01') || define('MONGOS_ANALYSIS_01', APPLICATION_ENV === 'production' ? '10.0.0.30:57017' : '127.0.0.1:27017');
defined('MONGOS_ANALYSIS_02') || define('MONGOS_ANALYSIS_02', APPLICATION_ENV === 'production' ? '10.0.0.31:57017' : '127.0.0.1:27017');
defined('MONGOS_ANALYSIS_03') || define('MONGOS_ANALYSIS_03', APPLICATION_ENV === 'production' ? '10.0.0.32:57017' : '127.0.0.1:27017');

defined('MONGOS_UMA_01') || define('MONGOS_UMA_01', APPLICATION_ENV === 'production' ? '10.0.0.30:27017' : '127.0.0.1:27017');
defined('MONGOS_UMA_02') || define('MONGOS_UMA_02', APPLICATION_ENV === 'production' ? '10.0.0.31:27017' : '127.0.0.1:27017');
defined('MONGOS_UMA_03') || define('MONGOS_UMA_03', APPLICATION_ENV === 'production' ? '10.0.0.32:27017' : '127.0.0.1:27017');

defined('GEARMAN_SERVERS') || define('GEARMAN_SERVERS', APPLICATION_ENV === 'production' ? '10.0.0.200:4730' : '127.0.0.1:4730,127.0.0.1:4730');

