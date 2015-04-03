<?php
// 启动session
session_start();

// PHP配置文件修改
date_default_timezone_set('Asia/Shanghai');
error_reporting(E_ALL); // 开启全部错误显示
ini_set("display_errors", 1); // 打开PHP错误提示
ini_set('memory_limit', PHP_SAPI === 'cli' ? '2048M' : '512M'); // 适当放大脚本执行内存的限制
set_time_limit(PHP_SAPI === 'cli' ? 0 : 30); // 与PHP-FPM的设定保持一致
                                             
// 初始化应用程序
chdir(dirname(__DIR__));
define('ZF_CLASS_CACHE', realpath(dirname(__DIR__)) . '/optimize/classes.php.cache');
if (file_exists(ZF_CLASS_CACHE))
    require_once ZF_CLASS_CACHE;

require 'config/constant.php';
require 'init_autoloader.php';
Zend\Mvc\Application::init(require 'config/application.config.php')->run();
