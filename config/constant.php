<?php
/**
 * 定义全局的常量
 */
defined('ROOT_PATH') || define('ROOT_PATH', dirname(__DIR__));
defined('CACHE_ADAPTER') || define('CACHE_ADAPTER', 'fileCache'); // [fileCache|memcachedCache|redisCache]
defined('APPLICATION_ENV') || define('APPLICATION_ENV', 'development'); // [development|production]
defined('DEFAULT_DATABASE') || define('DEFAULT_DATABASE', 'ICCv1');
defined('DEFAULT_CLUSTER') || define('DEFAULT_CLUSTER', 'default');
defined('DOMAIN') || define('DOMAIN', 'http://localhost');

/**
 * ICC指定数据库列表
 */
defined('DB_ADMIN') || define('DB_ADMIN', 'admin');
defined('DB_BACKUP') || define('DB_BACKUP', 'backup');
defined('DB_MAPREDUCE') || define('DB_MAPREDUCE', 'mapreduce');
defined('DB_LOGS') || define('DB_LOGS', 'logs');
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

/**
 * 自定义事件
 */
defined('EVENT_LOG_ERROR') || define('EVENT_LOG_ERROR', 'event_log_error');
defined('EVENT_LOG_DEBUG') || define('EVENT_LOG_DEBUG', 'event_log_debug');



