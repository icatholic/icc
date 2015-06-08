<?php
/**
* iDatabase测试控制器
*
* @author young
* @version 2014.01.22
*
*/
namespace Idatabase\Controller;

use My\Common\Controller\Action;
use My\Common\MongoCollection;

class TestController extends Action
{

    public function init()
    {}

    public function indexAction()
    {
        $modelPlugin = $this->getServiceLocator()->get('Idatabase\Model\Plugin');
        if ($modelPlugin instanceof MongoCollection) {
            echo 'OK';
            var_dump($modelPlugin->findAll(array()));
        } else {
            var_dump($modelPlugin->findAll(array()));
        }
        
        return $this->response; 
    }

    public function testDoPostAction()
    {
        var_dump(doPost('http://140613fg0260demo.umaman.com/index/check-story', array(
            'a' => 123,
            'v' => time()
        )));
        
        $response = doPost('http://140613fg0260demo.umaman.com/index/check-story', array(
            'a' => 123,
            'v' => time()
        ), true);
        
        var_dump($response->getStatusCode());
        var_dump($response->getHeaders());
        var_dump($response->getContent());
        var_dump($response->getBody());
        
        
        return $this->response;
    }
}