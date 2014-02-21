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
            if ($this->_model instanceof \MongoCollection)
                echo '$this->_model instanceof \MongoCollection';
            else
                echo 'error';
            var_dump($this->_model->insertByFindAndModify(array(
                'a.b' => time()
            )));
            //var_dump($this->_model->findOne());
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
    
    public function irAction() {
        $o = $this->model('test');
        $o->insertByFindAndModify(array('a.b'=>'a.b','c'=>'c'));
        echo '<pre>';
        var_dump($o->findAll(array(),array('_id'=>-1),0,0,array('a.b'=>true)));
        return $this->response;
    }
    
    public function saveAction() {
        $o = $this->collection('test');
        $data = array('a'=>123);
        $o->saveRef($data);
        var_dump($data);
        return $this->response;
    }
}
