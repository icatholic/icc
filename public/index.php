<?php
//启动session
session_start();

//PHP配置文件修改
date_default_timezone_set('Asia/Shanghai');
error_reporting(E_ALL);//开启全部错误显示
ini_set("display_errors", 1);//打开PHP错误提示
ini_set('mongo.native_long', 1);//Mongo采用长整形
ini_set('memory_limit', '512M');//适当放大脚本执行内存的限制
set_time_limit(300);//与PHP-FPM的设定保持一致

//初始化应用程序
chdir(dirname(__DIR__));
require 'init_autoloader.php';
Zend\Mvc\Application::init(require 'config/application.config.php')->run();
