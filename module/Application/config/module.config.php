<?php
return array(
    'router' => array(
        'routes' => array(
            'home' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route' => '/',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Index',
                        'action' => 'index'
                    )
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'homeWildcard' => array(
                        'type' => 'Zend\Mvc\Router\Http\Wildcard',
                        'may_terminate' => true
                    )
                )
            ),
            'login' => array(
                'type' => 'Zend\Mvc\Router\Http\Segment',
                'options' => array(
                    'route' => '/login[/:failure][/:code]',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Auth',
                        'action' => 'index'
                    )
                )
            ),
            'install' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route' => '/install',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Index',
                        'action' => 'install'
                    )
                )
            ),
            'version' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route' => '/version',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Index',
                        'action' => 'version'
                    )
                )
            ),
            'application' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/application',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Application\Controller',
                        'controller' => 'Index',
                        'action' => 'index'
                    )
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type' => 'Zend\Mvc\Router\Http\Segment',
                        'options' => array(
                            'route' => '/[:controller[/:action]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*'
                            ),
                            'defaults' => array()
                        ),
                        'may_terminate' => true,
                        'child_routes' => array(
                            'Wildcard' => array(
                                'type' => 'Zend\Mvc\Router\Http\Wildcard',
                                'may_terminate' => true
                            )
                        )
                    )
                )
            )
        )
    ),
    'service_manager' => array(
        'factories' => array(
            'translator' => 'Zend\I18n\Translator\TranslatorServiceFactory'
        ),
        'abstract_factories' => array(
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            'My\Common\AbstractFactory\Mongo',
            'My\Common\AbstractFactory\Model'
        )
    ),
    'translator' => array(),
    'caches' => array(
        'fileCache' => array(
            'adapter' => array(
                'name' => 'filesystem'
            ),
            'options' => array(
                'cache_dir' => ROOT_PATH . '/data/cache/datas'
            )
        ),
        'memcachedCache' => array(
            'adapter' => array(
                'name' => 'memcached'
            ),
            'options' => array(
                'servers' => array(
                    array(
                        '127.0.0.1',
                        11211
                    )
                )
            )
        ),
        'redisCache' => array(
            'adapter' => array(
                'name' => 'redis'
            ),
            'options' => array(
                'servers' => array(
                    array(
                        '127.0.0.1',
                        6379
                    )
                )
            )
        )
    ),
    'mongos' => array(
        'cluster' => array(
            'default' => array(
                'servers' => array(
                    '127.0.0.1:27017',
                    '127.0.0.1:27017',
                    '127.0.0.1:27017'
                ),
                'dbs' => array(
                    DEFAULT_DATABASE,
                    DB_ADMIN,
                    DB_MAPREDUCE,
                    DB_BACKUP,
                    DB_LOGS
                )
            ),
            'analysis' => array(
                'servers' => array(
                    '127.0.0.1:27017',
                    '127.0.0.1:27017',
                    '127.0.0.1:27017'
                ),
                'dbs' => array(
                    DEFAULT_DATABASE,
                    DB_ADMIN,
                    DB_MAPREDUCE,
                    DB_BACKUP,
                    DB_LOGS
                )
            )
        )
    ),
    'controllers' => array(
        'abstract_factories' => array(
            'My\Common\AbstractFactory\Controller'
        )
    ),
    'controller_plugins' => array(
        'invokables' => array(
            'log' => 'My\Common\Plugin\Log',
            'model' => 'My\Common\Plugin\Model',
            'collection' => 'My\Common\Plugin\Collection',
            'cache' => 'My\Common\Plugin\Cache'
        ),
        'aliases' => array(
            'm' => 'model',
            'c' => 'collection'
        )
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions' => true,
        'doctype' => 'HTML5',
        'not_found_template' => 'error/404',
        'exception_template' => 'error/index',
        'template_map' => array(
            'layout/layout' => ROOT_PATH . '/view/layout/layout.phtml',
            'application/index/index' => ROOT_PATH . '/view/application/index/index.phtml',
            'error/404' => ROOT_PATH . '/view/error/404.phtml',
            'error/index' => ROOT_PATH . '/view/error/index.phtml'
        ),
        'template_path_stack' => array(
            ROOT_PATH . '/view'
        ),
        'strategies' => array(
            'ViewJsonStrategy',
            'ViewFeedStrategy'
        )
    )
);
