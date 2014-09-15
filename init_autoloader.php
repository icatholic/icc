<?php
if (APPLICATION_ENV == 'production') {
    $loader = include 'icc/vendor/autoload.php';
} else {
    $loader = include 'vendor/autoload.php';
}

if (! class_exists('Zend\Loader\AutoloaderFactory')) {
    exit('Unable to load ZF2. Run `php composer.phar install` or define a ZF2_PATH environment variable.');
}

// 加载自定义函数库
include __DIR__ . '/library/function.php';

// 加载自定义命名空间
$myAutoLoaderClass = array(
    'My' => __DIR__ . '/library/My'
);

try {
    if (is_array($myAutoLoaderClass)) {
        foreach ($myAutoLoaderClass as $namespace => $libraryPath) {
            if (is_dir($libraryPath)) {
                Zend\Loader\AutoloaderFactory::factory(array(
                    'Zend\Loader\StandardAutoloader' => array(
                        'namespaces' => array(
                            $namespace => $libraryPath
                        )
                    )
                ));
            } else {
                throw new Exception($libraryPath . ' is not a dir');
            }
        }
    }
} catch (Exception $e) {
    exit(exceptionMsg($e));
}

