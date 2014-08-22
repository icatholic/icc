<?php
/**
 * 应用框架设定
 * @author ming
 *
 */
namespace Application;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\Session as SessionStorage;
use phpbrowscap\Browscap;
use Zend\Console\Response as ConsoleResponse;

class Module
{

    /**
     * 加载配置信息
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__
                )
            )
        );
    }

    public function onBootstrap(MvcEvent $e)
    {
        $app = $e->getApplication();
        $eventManager = $app->getEventManager();
        $locator = $app->getServiceManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        
        $this->initFirePHP();
        
        // 微软这个流氓，低于IE10版本一下的IE浏览器都需要使用text/html格式的Response，否则json在浏览器中会提示下载
        $eventManager->attach(MvcEvent::EVENT_RENDER, function (MvcEvent $event)
        {
            $objResponse = $event->getResponse();
            if (method_exists($objResponse, 'getHeaders')) {
                $objHeaders = $objResponse->getHeaders();
                $contentType = $objHeaders->get('Content-Type');
                if ($contentType) {
                    $contentType = $contentType->getFieldValue();
                    if (strpos($contentType, 'application/json') !== false) {
                        $objHeaders->addHeaderLine('Content-Type', 'text/html;charset=utf-8');
                    }
                } else {
                    $objHeaders->addHeaderLine('Content-Type', 'text/html;charset=utf-8');
                }
            }
        }, - 10000);
        
        // 错误提示的时候，执行特殊的layout
        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, function (MvcEvent $event)
        {
            $viewModel = $event->getViewModel();
            $viewModel->setTemplate('layout/error');
        }, - 200);
        
        // 在请求完成的时候记录用户行为
        $eventManager->attach(MvcEvent::EVENT_FINISH, function (MvcEvent $event)
        {
            try {
                $app = $event->getApplication();
                $request = $event->getRequest();
                // if($request->get)
                $mongos = $app->getServiceManager()
                    ->get('mongos');
                $objLog = new \Idatabase\Model\Log($mongos);
                $objLog->insert(array(
                    'uri' => $_SERVER['REQUEST_URI'],
                    'session' => $_SESSION,
                    'post' => $_POST,
                    'get' => $_GET
                ));
            } catch (\Exception $e) {
                fb(exceptionMsg($e), 'LOG');
            }
        }, 0);
    }

    public function initFirePHP()
    {
        // 开启FirePHP调试或者关闭
        $options = array(
            'maxObjectDepth' => 10,
            'maxArrayDepth' => 10,
            'maxDepth' => 10,
            'useNativeJsonEncode' => true,
            'includeLineNumbers' => true
        );
        \FirePHP::getInstance(true)->setEnabled(true);
        \FB::setOptions($options);
    }

}