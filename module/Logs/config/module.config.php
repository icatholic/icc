<?php
return array(
    'Logs' => array(
        'LogMongodbService' => array(
            'name' => 'default',
            'handlers' => array(
                'default' => array(
                    'name' => 'Monolog\Handler\MongoDBHandler',
                    'args' => array(
                        'mongo' => new \MongoClient('mongodb://'.MONGOS_DEFAULT_01.','.MONGOS_DEFAULT_02.','.MONGOS_DEFAULT_03),
                        'database' => 'logs',
                        'collection' => 'logs' . date("Ym"),
                        'level' => \Monolog\Logger::ERROR,
                        'bubble' => true
                    )
                )
            )
        )
    ),
    'service_manager' => array(
        'abstract_factories' => array(
            'Logs\Service\MonologServiceAbstractFactory'
        )
    )
);