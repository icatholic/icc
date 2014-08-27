<?php
return array (
  'router' => 
  array (
    'routes' => 
    array (
      'home' => 
      array (
        'type' => 'Zend\\Mvc\\Router\\Http\\Literal',
        'options' => 
        array (
          'route' => '/',
          'defaults' => 
          array (
            'controller' => 'Application\\Controller\\Index',
            'action' => 'index',
          ),
        ),
        'may_terminate' => true,
        'child_routes' => 
        array (
          'homeWildcard' => 
          array (
            'type' => 'Zend\\Mvc\\Router\\Http\\Wildcard',
            'may_terminate' => true,
          ),
        ),
      ),
      'login' => 
      array (
        'type' => 'Zend\\Mvc\\Router\\Http\\Segment',
        'options' => 
        array (
          'route' => '/login[/:failure][/:code]',
          'defaults' => 
          array (
            'controller' => 'Application\\Controller\\Auth',
            'action' => 'index',
          ),
        ),
      ),
      'install' => 
      array (
        'type' => 'Zend\\Mvc\\Router\\Http\\Literal',
        'options' => 
        array (
          'route' => '/install',
          'defaults' => 
          array (
            'controller' => 'Application\\Controller\\Index',
            'action' => 'install',
          ),
        ),
      ),
      'version' => 
      array (
        'type' => 'Zend\\Mvc\\Router\\Http\\Literal',
        'options' => 
        array (
          'route' => '/version',
          'defaults' => 
          array (
            'controller' => 'Application\\Controller\\Index',
            'action' => 'version',
          ),
        ),
      ),
      'application' => 
      array (
        'type' => 'Literal',
        'options' => 
        array (
          'route' => '/application',
          'defaults' => 
          array (
            '__NAMESPACE__' => 'Application\\Controller',
            'controller' => 'Index',
            'action' => 'index',
          ),
        ),
        'may_terminate' => true,
        'child_routes' => 
        array (
          'default' => 
          array (
            'type' => 'Zend\\Mvc\\Router\\Http\\Segment',
            'options' => 
            array (
              'route' => '/[:controller[/:action]]',
              'constraints' => 
              array (
                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
              ),
              'defaults' => 
              array (
              ),
            ),
            'may_terminate' => true,
            'child_routes' => 
            array (
              'Wildcard' => 
              array (
                'type' => 'Zend\\Mvc\\Router\\Http\\Wildcard',
                'may_terminate' => true,
              ),
            ),
          ),
        ),
      ),
      'idatabase' => 
      array (
        'type' => 'Literal',
        'options' => 
        array (
          'route' => '/idatabase',
          'defaults' => 
          array (
            '__NAMESPACE__' => 'Idatabase\\Controller',
            'controller' => 'Project',
            'action' => 'index',
          ),
        ),
        'may_terminate' => true,
        'child_routes' => 
        array (
          'default' => 
          array (
            'type' => 'Zend\\Mvc\\Router\\Http\\Segment',
            'options' => 
            array (
              'route' => '/[:controller[/:action]]',
              'constraints' => 
              array (
                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
              ),
              'defaults' => 
              array (
              ),
            ),
            'may_terminate' => true,
            'child_routes' => 
            array (
              'Wildcard' => 
              array (
                'type' => 'Zend\\Mvc\\Router\\Http\\Wildcard',
                'may_terminate' => true,
              ),
            ),
          ),
        ),
      ),
      'service' => 
      array (
        'type' => 'Literal',
        'options' => 
        array (
          'route' => '/service',
          'defaults' => 
          array (
            '__NAMESPACE__' => 'Service\\Controller',
            'controller' => 'Index',
            'action' => 'index',
          ),
        ),
        'may_terminate' => true,
        'child_routes' => 
        array (
          'default' => 
          array (
            'type' => 'Zend\\Mvc\\Router\\Http\\Segment',
            'options' => 
            array (
              'route' => '/[:controller[/:action]]',
              'constraints' => 
              array (
                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
              ),
              'defaults' => 
              array (
              ),
            ),
            'may_terminate' => true,
            'child_routes' => 
            array (
              'Wildcard' => 
              array (
                'type' => 'Zend\\Mvc\\Router\\Http\\Wildcard',
                'may_terminate' => true,
              ),
            ),
          ),
        ),
      ),
      'file' => 
      array (
        'type' => 'Literal',
        'options' => 
        array (
          'route' => '/file',
          'defaults' => 
          array (
            '__NAMESPACE__' => 'Service\\Controller',
            'controller' => 'File',
            'action' => 'index',
          ),
        ),
        'may_terminate' => true,
        'child_routes' => 
        array (
          'default' => 
          array (
            'type' => 'Zend\\Mvc\\Router\\Http\\Segment',
            'options' => 
            array (
              'route' => '/:id',
              'defaults' => 
              array (
              ),
            ),
            'may_terminate' => true,
            'child_routes' => 
            array (
              'Wildcard' => 
              array (
                'type' => 'Zend\\Mvc\\Router\\Http\\Wildcard',
                'may_terminate' => true,
              ),
            ),
          ),
        ),
      ),
      'gearman' => 
      array (
        'type' => 'Literal',
        'options' => 
        array (
          'route' => '/gearman',
          'defaults' => 
          array (
            '__NAMESPACE__' => 'Gearman\\Controller',
            'controller' => 'index',
            'action' => 'index',
          ),
        ),
        'may_terminate' => true,
        'child_routes' => 
        array (
          'default' => 
          array (
            'type' => 'Zend\\Mvc\\Router\\Http\\Segment',
            'options' => 
            array (
              'route' => '/[:controller[/:action]]',
              'constraints' => 
              array (
                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
              ),
              'defaults' => 
              array (
              ),
            ),
            'may_terminate' => true,
            'child_routes' => 
            array (
              'Wildcard' => 
              array (
                'type' => 'Zend\\Mvc\\Router\\Http\\Wildcard',
                'may_terminate' => true,
              ),
            ),
          ),
        ),
      ),
    ),
  ),
  'service_manager' => 
  array (
    'factories' => 
    array (
      'translator' => 'Zend\\I18n\\Translator\\TranslatorServiceFactory',
    ),
    'abstract_factories' => 
    array (
      0 => 'Zend\\Cache\\Service\\StorageCacheAbstractServiceFactory',
      1 => 'My\\Common\\AbstractFactory\\Mongo',
      2 => 'My\\Common\\AbstractFactory\\Model',
    ),
  ),
  'translator' => 
  array (
  ),
  'caches' => 
  array (
    'fileCache' => 
    array (
      'adapter' => 
      array (
        'name' => 'filesystem',
      ),
      'options' => 
      array (
        'cache_dir' => 'D:\\MyCode\\icatholic\\icc/data/cache/datas',
      ),
    ),
    'memcachedCache' => 
    array (
      'adapter' => 
      array (
        'name' => 'memcached',
      ),
      'options' => 
      array (
        'servers' => 
        array (
          0 => 
          array (
            0 => '127.0.0.1',
            1 => 11211,
          ),
          1 => 
          array (
            0 => '127.0.0.1',
            1 => 11211,
          ),
        ),
      ),
    ),
    'redisCache' => 
    array (
      'adapter' => 
      array (
        'name' => 'redis',
      ),
      'options' => 
      array (
        'servers' => 
        array (
          0 => 
          array (
            0 => '127.0.0.1',
            1 => 6379,
          ),
        ),
      ),
    ),
  ),
  'mongos' => 
  array (
    'cluster' => 
    array (
      'default' => 
      array (
        'servers' => 
        array (
          0 => '127.0.0.1:27017',
          1 => '127.0.0.1:27017',
          2 => '127.0.0.1:27017',
        ),
        'dbs' => 
        array (
          0 => 'ICCv1',
          1 => 'admin',
          2 => 'mapreduce',
          3 => 'backup',
          4 => 'logs',
        ),
      ),
      'analysis' => 
      array (
        'servers' => 
        array (
          0 => '127.0.0.1:27017',
          1 => '127.0.0.1:27017',
          2 => '127.0.0.1:27017',
        ),
        'dbs' => 
        array (
          0 => 'ICCv1',
          1 => 'admin',
          2 => 'mapreduce',
          3 => 'backup',
          4 => 'logs',
        ),
      ),
      'umav3' => 
      array (
        'servers' => 
        array (
          0 => '127.0.0.1:27017',
          1 => '127.0.0.1:27017',
          2 => '127.0.0.1:27017',
        ),
        'dbs' => 
        array (
          0 => 'umav3',
        ),
      ),
    ),
  ),
  'controllers' => 
  array (
    'abstract_factories' => 
    array (
      0 => 'My\\Common\\AbstractFactory\\Controller',
    ),
  ),
  'controller_plugins' => 
  array (
    'invokables' => 
    array (
      'log' => 'My\\Common\\Plugin\\Log',
      'model' => 'My\\Common\\Plugin\\Model',
      'collection' => 'My\\Common\\Plugin\\Collection',
      'cache' => 'My\\Common\\Plugin\\Cache',
      'debug' => 'My\\Common\\Plugin\\Debug',
      'gearman' => 'My\\Common\\Plugin\\Gearman',
    ),
    'aliases' => 
    array (
      'm' => 'model',
      'c' => 'collection',
      'd' => 'debug',
      'g' => 'gearman',
    ),
  ),
  'view_manager' => 
  array (
    'display_not_found_reason' => true,
    'display_exceptions' => true,
    'doctype' => 'HTML5',
    'not_found_template' => 'error/404',
    'exception_template' => 'error/index',
    'template_map' => 
    array (
      'layout/layout' => 'D:\\MyCode\\icatholic\\icc/view/layout/layout.phtml',
      'application/index/index' => 'D:\\MyCode\\icatholic\\icc/view/application/index/index.phtml',
      'error/404' => 'D:\\MyCode\\icatholic\\icc/view/error/404.phtml',
      'error/index' => 'D:\\MyCode\\icatholic\\icc/view/error/index.phtml',
    ),
    'template_path_stack' => 
    array (
      0 => 'D:\\MyCode\\icatholic\\icc/view',
    ),
    'strategies' => 
    array (
      0 => 'ViewJsonStrategy',
      1 => 'ViewFeedStrategy',
    ),
  ),
  'console' => 
  array (
    'router' => 
    array (
      'routes' => 
      array (
        'notify_keys' => 
        array (
          'options' => 
          array (
            'route' => 'notify keys',
            'defaults' => 
            array (
              'controller' => 'Application\\Controller\\Notify',
              'action' => 'keys',
            ),
          ),
        ),
        'run-statistics' => 
        array (
          'options' => 
          array (
            'route' => 'dashboard run',
            'defaults' => 
            array (
              'controller' => 'Idatabase\\Controller\\Dashboard',
              'action' => 'run',
            ),
          ),
        ),
        'mapreduce_worker' => 
        array (
          'options' => 
          array (
            'route' => 'mapreduce worker',
            'defaults' => 
            array (
              'controller' => 'Gearman\\Controller\\Index',
              'action' => 'mr',
            ),
          ),
        ),
        'plugin_sync_worker' => 
        array (
          'options' => 
          array (
            'route' => 'plugin sync worker',
            'defaults' => 
            array (
              'controller' => 'Gearman\\Controller\\Plugin',
              'action' => 'sync',
            ),
          ),
        ),
        'data_export_worker' => 
        array (
          'options' => 
          array (
            'route' => 'data export worker',
            'defaults' => 
            array (
              'controller' => 'Gearman\\Controller\\Data',
              'action' => 'export',
            ),
          ),
        ),
        'data_import_worker' => 
        array (
          'options' => 
          array (
            'route' => 'data import worker',
            'defaults' => 
            array (
              'controller' => 'Gearman\\Controller\\Data',
              'action' => 'import',
            ),
          ),
        ),
        'common_worker' => 
        array (
          'options' => 
          array (
            'route' => 'common worker',
            'defaults' => 
            array (
              'controller' => 'Gearman\\Controller\\Common',
              'action' => 'worker',
            ),
          ),
        ),
      ),
    ),
  ),
);