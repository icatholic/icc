<?php
return array(
    'Logs' => array(
        'LogMongodbService' => array(
            'name' => 'default',
            'handlers' => array(
                'default' => array(
                    'name' => 'Monolog\Handler\MongoDBHandler',
                    'args' => array(
                        'mongo' => new \MongoClient("mongodb://127.0.0.1:27017"),
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