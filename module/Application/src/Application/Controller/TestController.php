<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Gregwar\Captcha\CaptchaBuilder;
use Zend\Json\Json;
use My\Common\MongoCollection;

class TestController extends AbstractActionController
{

    public function indexAction()
    {
        echo __CLASS__;
        echo get_class($this);
        echo str_replace(array(
            __NAMESPACE__,
            '\\'
        ), '', __CLASS__);
        return $this->response;
    }

    public function testAction()
    {
        $events = $this->getEventManager();
        echo $this->params()->fromRoute('index');
        echo $this->params()->fromQuery('get');
        echo $this->params()->fromFiles('file');
        echo $this->params()->fromRoute('r');
        $events->attach('do', function (EventInterface $e)
        {
            var_dump($e->getTarget());
            $event = $e->getName();
            $params = $e->getParams();
            printf('Handled event "%s", with parameters %s', $event, json_encode($params));
        });
        $params = array(
            'foo' => 'bar',
            'baz' => 'bat'
        );
        $events->trigger('do', array(), $params);
        return new ViewModel();
    }

    public function noViewAction()
    {
        phpinfo();
        return $this->response;
    }

    public function cacheAction()
    {
        $cache = $this->getServiceLocator()->get(CACHE_ADAPTER);
        if (($data = $cache->getItem('key')) === NULL) {
            $data = time();
            $cache->setItem('key', $data);
            echo 'no cache' . $data;
        } else {
            echo 'cache' . $data;
            $cache->removeItem('key');
        }
        return $this->response;
    }

    public function mongoAction()
    {
        try {
            $db = $this->getServiceLocator()->get('mongos');
            return $this->response;
        } catch (\Exception $e) {
            var_dump($e->getMessage() . $e->getTraceAsString());
        }
    }

    public function triggerAction()
    {
        $evt = $this->getEventManager()->getSharedManager();
        $evt->getEvents();
        // $view = new ViewModel();
        // $view->setTerminal(true);
        $eventManager = GlobalEventManager::getEventCollection();
        $params = $this->params()->fromQuery();
        $result = $eventManager->trigger('cache.pre', null, $params);
        if ($result->stopped()) {
            $content = 'cache' . $result->last();
            $this->response->setContent($content);
        } else {
            $content = 123;
            $params['__RESULT__'] = $content;
            $this->response->setContent($content);
            $eventManager->trigger('cache.post', null, $params);
        }
        
        return $this->response;
    }

    public function staticEventAction()
    {
        $eventManager = new \Zend\EventManager\StaticEventManager();
        $eventManager::getInstance();
    }

    public function insertMongoAction()
    {
        try {
            $this->_model = $this->m('yangming');
            if ($this->_model instanceof MongoCollection)
                echo '$this->_model instanceof MongoCollection';
            else
                echo 'error';
            var_dump($this->_model->insertByFindAndModify(array(
                'a.b' => time()
            )));
            // var_dump($this->_model->findOne());
            echo 'OK';
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
        return $this->response;
    }

    /**
     * 登录验证码生成
     *
     * @return \Zend\Stdlib\ResponseInterface
     */
    public function captchaAction()
    {
        $builder = new CaptchaBuilder();
        // $builder->setBackgroundColor($r, $g, $b);
        // $builder->build($width = 150, $height = 40);
        $builder->build(150, 40);
        $_SESSION['phrase'] = $builder->getPhrase();
        // $builder->output($quality = 80);
        header('Content-type: image/jpeg');
        $this->response->setContent($builder->output(80));
        return $this->response;
    }

    public function cachePluginAction()
    {
        if (($datas = $this->cache('123')) === null) {
            echo $datas = time();
            $this->cache()->save($datas);
        }
        echo $datas;
        return $this->response;
    }

    public function logAction()
    {
        // var_dump($this->getServiceLocator()->get('EnliteMonologService'));
        // var_dump($this->getServiceLocator()->get('LogMongodbService')->addDebug('hello world'));
        // var_dump($this->log()->logger('OK plugin'));
        var_dump($this->log('123'));
        
        return $this->response;
    }

    public function irAction()
    {
        $o = $this->model('test');
        $o->insertByFindAndModify(array(
            'a.b' => 'a.b',
            'c' => 'c'
        ));
        echo '<pre>';
        var_dump($o->findAll(array(), array(
            '_id' => - 1
        ), 0, 0, array(
            'a.b' => true
        )));
        return $this->response;
    }

    public function saveAction()
    {
        $o = $this->collection('test');
        $data = array(
            'a' => 123
        );
        $o->saveRef($data);
        var_dump($data);
        return $this->response;
    }

    public function geoAction()
    {
        try {
            $c = $this->collection(iCollectionName('537e1f8b489619c6668b459f'));
            // $c->setNoAppendQuery(true);
            var_dump($c->aggregate(array(
                array(
                    '$geoNear' => array(
                        'near' => array(
                            121.43785642996,
                            31.189866744357
                        ),
                        'num' => 100,
                        'spherical' => true,
                        'maxDistance' => 0.089992800575954,
                        'distanceField' => 'location'
                    )
                )
            )));
        } catch (\Exception $e) {
            var_dump($e);
        }
        return $this->response;
    }

    /**
     * 测试邮件发送
     */
    public function sendEmailAction()
    {
        echo sendEmail('youngyang@icatholic.net.cn', '测试内容', '测试内容');
        return $this->response;
    }
    
    public function jsonDecodeAction() {
        var_dump(Json::decode('{"_id":{"$id":"556525cd48961906188b45d1"},"activity_name":"5\u670827\u65e5\u6d4b\u8bd5\u5546\u54c12","product_id":"55627cc5499619ed1c8b45b3","start_time":{"sec":1432656000,"usec":0},"end_time":{"sec":1432915200,"usec":0},"is_valid":true,"pay_time":{"sec":1433088000,"usec":0}}', Json::TYPE_ARRAY));
        return $this->response;
    }
}
