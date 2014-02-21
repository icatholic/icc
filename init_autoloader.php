<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

/**
 * This autoloading setup is really more complicated than it needs to be for most
 * applications.
 * The added complexity is simply to reduce the time it takes for
 * new developers to be productive with a fresh skeleton. It allows autoloading
 * to be correctly configured, regardless of the installation method and keeps
 * the use of composer completely optional. This setup should work fine for
 * most users, however, feel free to configure autoloading however you'd like.
 */

// Composer autoloading
if (file_exists('vendor/autoload.php')) {
    $loader = include 'vendor/autoload.php';
}

if (! class_exists('Zend\Loader\AutoloaderFactory')) {
    exit('Unable to load ZF2. Run `php composer.phar install` or define a ZF2_PATH environment variable.');
}

// 加载自己的函数库
if (file_exists(__DIR__ . '/library/function.php')) {
    include __DIR__ . '/library/function.php';
}

// 加载自己的函数库
$myAutoLoaderClass = array(
    'My\Common' => __DIR__ . '/library/My/Common',
    'My\Service' => __DIR__ . '/library/My/Service'
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

