<?php
namespace My\Common\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Mvc\MvcEvent;
use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Role\GenericRole as Role;
use Zend\Permissions\Acl\Resource\GenericResource as Resource;
use Zend\Permissions\Acl\Exception\InvalidArgumentException;
use Zend\Soap\Server as SoapServer;
use Zend\Soap\AutoDiscover;

abstract class Action extends AbstractActionController
{

    protected $controller;

    protected $action;

    public function __construct()
    {
        // 添加初始化事件函数
        $eventManager = $this->getEventManager();
        $serviceLocator = $this->getServiceLocator();
        $eventManager->attach(MvcEvent::EVENT_DISPATCH, function ($event) use($eventManager, $serviceLocator)
        {
            // 权限控制
            $namespace = $this->params('__NAMESPACE__');
            $controller = $this->params('controller');
            $action = $this->params('action');
            
            if ($namespace == 'Idatabase\Controller' && php_sapi_name() !== 'cli') {
                // 身份验证不通过的情况下，执行以下操作
                if (! isset($_SESSION['account'])) {
                    $event->stopPropagation(true);
                    $event->setViewModel($this->msg(false, '未通过身份验证'));
                }
                
                // 授权登录后，检查是否有权限访问指定资源
                $role = isset($_SESSION['account']['role']) ? $_SESSION['account']['role'] : false;
                $resources = isset($_SESSION['account']['resources']) ? $_SESSION['account']['resources'] : array();
                $action = $this->getMethodFromAction($action);
                $currentResource = $controller . 'Controller\\' . $action;
                if ($role && $role !== 'root') {
                    $acl = new Acl();
                    $acl->addRole(new Role($role));
                    foreach ($resources as $resource) {
                        $acl->addResource(new Resource($resource));
                        $acl->allow($role, $resource);
                    }
                    
                    $isAllowed = false;
                    try {
                        if ($acl->isAllowed($role, $currentResource) === true) {
                            $isAllowed = true;
                        }
                    } catch (InvalidArgumentException $e) {}
                    
                    if (! $isAllowed) {
                        $event->stopPropagation(true);
                        $event->setViewModel($this->deny());
                    }
                }
            }
            
            $this->preDispatch();
            
            if (method_exists($this, 'init')) {
                try {
                    $this->init();
                } catch (\Exception $e) {
                    $event->stopPropagation(true);
                    $event->setViewModel($this->deny($e->getMessage()));
                }
            }
        }, 200);
    }

    public function preDispatch()
    {
        $routerMatch = $this->getEvent()->getRouteMatch();
        $this->action = $routerMatch->getParam('action', null);
        $this->controller = $routerMatch->getParam('controller', null);
    }

    /**
     * 可以在controller中调用该方法，以便在执行action之前执行某些初始化的操作
     */
    public function init()
    {}

    /**
     * 获取指定集合的指定条件的全部数据
     * 默认返回json数组直接输出
     *
     * @param string $collection            
     * @param array $query            
     * @param array $sort            
     * @param boolean $jsonModel            
     * @param string $jsonpCallback            
     * @throws \Exception
     * @return \Zend\View\Model\JsonModel Ambigous multitype:multitype: string >
     */
    public function findAll($collection, $query = array(), $sort = array('_id'=>-1), $jsonModel = true, $jsonpCallback = null)
    {
        $model = $this->collection($collection);
        $cursor = $model->find($query);
        if (! $cursor instanceof \MongoCursor)
            throw new \Exception('$query error:' . json_encode($query));
        
        $cursor->sort($sort);
        $skip = (int) $this->params()->fromQuery('start', 0);
        if ($skip > 0) {
            $cursor->skip($skip);
        }
        $limit = (int) $this->params()->fromQuery('limit', 0);
        if ($limit > 0) {
            $cursor->limit($limit);
        }
        
        $datas = iterator_to_array($cursor, false);
        if ($jsonModel) {
            $obj = new JsonModel($this->rst($datas));
            if ($jsonpCallback !== null) {
                $obj->setJsonpCallback($jsonpCallback);
            }
            return $obj;
        }
        return $datas;
    }

    /**
     * 返回结果集
     *
     * @param array $datas            
     * @param number $total            
     * @param boolean $jsonModel            
     * @param string $jsonpCallback            
     * @return \Zend\View\Model\JsonModel multitype:unknown
     */
    public function rst($datas, $total = 0, $jsonModel = false, $jsonpCallback = null)
    {
        $total = intval($total);
        $rst = array(
            'result' => $datas,
            'total' => $total ? $total : count($datas)
        );
        if ($jsonModel) {
            $obj = new JsonModel($rst);
            if ($jsonpCallback !== null) {
                $obj->setJsonpCallback($jsonpCallback);
            }
            return $obj;
        }
        return $rst;
    }

    /**
     * 返回信息
     *
     * @param bool $status            
     * @param string $message            
     * @param string $jsonModel            
     * @return \Zend\View\Model\JsonModel multitype:unknown <boolean, unknown>
     */
    public function msg($status, $message, $jsonModel = true, $jsonpCallback = null)
    {
        $rst = array(
            'success' => is_bool($status) ? $status : false,
            'msg' => $message
        );
        
        if ($jsonModel) {
            $obj = new JsonModel($rst);
            if ($jsonpCallback !== null) {
                $obj->setJsonpCallback($jsonpCallback);
            }
            return $obj;
        }
        return $rst;
    }

    /**
     * 权限不足，拒绝访问提示
     *
     * @param string $message            
     * @return JsonModel
     */
    public function deny($message = '很遗憾，您无权访问部分资源，请联系管理员开放权限；或者您的登录已经注销，请重新登录')
    {
        return new JsonModel(array(
            'success' => false,
            'access' => 'deny',
            'msg' => $message
        ));
    }

    /**
     * 创建一个服务
     */
    public function soap($uri, $className, $config = null)
    {
        if (isset($_GET['wsdl'])) {
            $autodiscover = new AutoDiscover();
            $autodiscover->setClass($className)->setUri($uri);
            return $autodiscover->toXml();
        } else {
            $wsdl = strpos($uri, '?') === false ? $uri . '?wsdl' : $uri . '&wsdl';
            $server = new SoapServer($wsdl);
            $obj = $config == null ? new $className() : new $className($config);
            $server->setObject($obj);
            $server->handle();
            $response = $server->getLastRequest();
            if ($response instanceof \SoapFault) {
                $response = exceptionMsg($response);
                $this->log($response);
            }
            return $response;
        }
    }
}