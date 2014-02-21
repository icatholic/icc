<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace Application\Controller;

use Zend\View\Model\ViewModel;
use My\Common\Controller\Action;
use Zend\Code\Scanner\DirectoryScanner;
use Zend\Code\Scanner\DocBlockScanner;

class IndexController extends Action
{

    private $_account;

    private $_resource;

    private $_setting;

    public function init()
    {
        $this->_account = $this->collection(SYSTEM_ACCOUNT);
        $this->_resource = $this->collection(SYSTEM_RESOURCE);
        $this->_setting = $this->collection(SYSTEM_SETTING);
    }

    /**
     * IDatabase系统主控制面板
     *
     * @author young
     * @name ICC系统主控制面板
     * @version 2013.11.07 young
     */
    public function indexAction()
    {
        if ($this->_account->findOne(array(
            'username' => 'root'
        )) === null) {
            return $this->redirect()->toRoute('install');
        } else 
            if (! isset($_SESSION['account'])) {
                return $this->redirect()->toRoute('login');
            }
        
        $setting = $this->getSetting();
        $this->layout()->setVariables($setting);
        return new ViewModel();
    }

    /**
     * 手动更新js/css版本
     *
     * @author young
     * @name 手动更新js/css版本
     * @version 2014.01.26
     */
    public function versionAction()
    {
        $this->_setting->update(array(
            'key' => '__VERSION__'
        ), array(
            '$set' => array(
                'value' => date("YmdHis")
            )
        ));
        echo 'ok';
        return $this->response;
    }

    /**
     * 从系统集合中获取全局的配置参数
     *
     * @return array
     */
    private function getSetting()
    {
        $setting = array();
        $cursor = $this->_setting->find(array());
        while ($cursor->hasNext()) {
            $row = $cursor->getNext();
            $setting[$row['key']] = $row['value'];
        }
        return $setting;
    }

    /**
     * 安装icc系统
     */
    public function installAction()
    {
        // 插入系统根用户
        if ($this->_account->findOne(array(
            'username' => 'admin'
        )) == null) {
            $datas = array();
            $this->_account->insert(array(
                'username' => 'root',
                'password' => sha1('yangming1983'),
                'role' => 'root',
                'isProfessional' => true,
                'expire' => new \MongoDate(strtotime('2020-12-31 23:59:59')),
                'active' => true
            ));
            $this->_account->insert(array(
                'username' => 'admin',
                'password' => sha1('yangming1983'),
                'role' => 'admin',
                'isProfessional' => true,
                'expire' => new \MongoDate(strtotime('2020-12-31 23:59:59')),
                'active' => true
            ));
        }
        
        // 获取全部方法列表到数据库中
        $this->addResource();
        
        return $this->redirect()->toRoute('home');
    }

    /**
     * 检索指定目录下的全部资源到数据库中
     */
    private function addResource()
    {
        $this->_resource->remove(array());
        $scaner = new DirectoryScanner();
        $scaner->addDirectory(ROOT_PATH . '/module/Application/src/Application/Controller/');
        $scaner->addDirectory(ROOT_PATH . '/module/Idatabase/src/Idatabase/Controller/');
        foreach ($scaner->getClasses(true) as $classScanner) {
            $className = $classScanner->getName();
            foreach ($classScanner->getMethods(true) as $method) {
                if ($this->endsWith($method->getName(), 'Action')) {
                    $actionName = $method->getName();
                    $docComment = $method->getDocComment();
                    $docBlockScanner = new DocBlockScanner($docComment);
                    $docAtName = $this->getDocNameValue($docBlockScanner->getTags());
                    
                    // 写入数据库
                    $classInfo = $this->parseClassName($className);
                    $this->_resource->insert(array(
                        'name' => $docAtName,
                        'alias' => $className . '\\' . $actionName,
                        'namespace' => $classInfo['namespace'],
                        'controller' => $classInfo['controller'],
                        'action' => $this->parseMethodName($actionName)
                    ));
                }
            }
        }
    }

    /**
     * 判断是否是Action
     *
     * @param string $haystack            
     * @param string $needle            
     * @return boolean
     */
    private function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length === 0) {
            return true;
        }
        return (substr($haystack, - $length) === $needle);
    }

    /**
     * 从tags中获取doc的@name属性的值，如果没有返回空
     *
     * @param array $tags            
     * @return string
     */
    private function getDocNameValue($tags)
    {
        if (! empty($tags)) {
            foreach ($tags as $tag) {
                if (trim($tag['name']) === '@name') {
                    return trim($tag['value']);
                }
            }
        }
        return '';
    }

    /**
     * 解析class名称
     *
     * @param string $className            
     * @return array
     */
    private function parseClassName($className)
    {
        $split = explode('\\', $className);
        return array(
            'namespace' => $split[0],
            'controller' => $this->convert(str_replace('Controller', '', $split[2]))
        );
    }

    /**
     * 解析action名称
     *
     * @param string $methodName            
     * @return string
     */
    private function parseMethodName($methodName)
    {
        return $this->convert(str_replace('Action', '', $methodName));
    }

    /**
     * 将方法名转换为router路径
     *
     * @param string $name            
     * @return string
     */
    private function convert($name)
    {
        return strtolower(preg_replace("/([a-z0-9])([A-Z])/", "$1-$2", $name));
    }
}
