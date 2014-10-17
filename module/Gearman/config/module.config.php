<?php
return array(
    'router' => array(
        'routes' => array(
            'gearman' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/gearman',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Gearman\Controller',
                        'controller' => 'index',
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
    'service_manager' => array(),
    'controller_plugins' => array(
        'invokables' => array(
            'gearman' => 'My\Common\Plugin\Gearman'
        ),
        'aliases' => array(
            'g' => 'gearman'
        )
    ),
    'console' => array(
        'router' => array(
            'routes' => array(
                'mapreduce_worker' => array(
                    'options' => array(
                        'route' => 'mapreduce worker',
                        'defaults' => array(
                            'controller' => 'Gearman\Controller\Index',
                            'action' => 'mr'
                        )
                    )
                ),
                'plugin_sync_worker' => array(
                    'options' => array(
                        'route' => 'plugin sync worker',
                        'defaults' => array(
                            'controller' => 'Gearman\Controller\Plugin',
                            'action' => 'sync'
                        )
                    )
                ),
                'data_export_worker' => array(
                    'options' => array(
                        'route' => 'data export worker',
                        'defaults' => array(
                            'controller' => 'Gearman\Controller\Data',
                            'action' => 'export'
                        )
                    )
                ),
                'data_import_worker' => array(
                    'options' => array(
                        'route' => 'data import worker',
                        'defaults' => array(
                            'controller' => 'Gearman\Controller\Data',
                            'action' => 'import'
                        )
                    )
                ),
                'data_import_java_worker' => array(
                    'options' => array(
                        'route' => 'data import java worker',
                        'defaults' => array(
                            'controller' => 'Gearman\Controller\Data',
                            'action' => 'importJava'
                        )
                    )
                ),
                'data_bson_import_worker' => array(
                    'options' => array(
                        'route' => 'data import bson worker',
                        'defaults' => array(
                            'controller' => 'Gearman\Controller\Data',
                            'action' => 'importBson'
                        )
                    )
                ),
                'data_bson_export_worker' => array(
                    'options' => array(
                        'route' => 'data export bson worker',
                        'defaults' => array(
                            'controller' => 'Gearman\Controller\Data',
                            'action' => 'exportBson'
                        )
                    )
                ),
                'data_export_collection_bson_worker' => array(
                    'options' => array(
                        'route' => 'data export collection bson worker',
                        'defaults' => array(
                            'controller' => 'Gearman\Controller\Data',
                            'action' => 'exportCollectionBson'
                        )
                    )
                ),
                'common_worker' => array(
                    'options' => array(
                        'route' => 'common worker',
                        'defaults' => array(
                            'controller' => 'Gearman\Controller\Common',
                            'action' => 'worker'
                        )
                    )
                )
            )
        )
    )
);
