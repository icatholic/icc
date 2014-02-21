<?php
$vendorPath = dirname(__DIR__).'/vendor/autoload.php';
if (file_exists($vendorPath)) {
    include $vendorPath;
}

// Your shell script
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;

$http = new HttpServer(new MyWebPage());

$server = IoServer::factory($http);
$server->run();