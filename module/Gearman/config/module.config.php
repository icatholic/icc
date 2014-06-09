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
                )
            )
        )
    )
);
