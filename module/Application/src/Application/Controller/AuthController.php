<?php
/**
 * 身份认证控制器
 *
 */
namespace Application\Controller;

use Zend\View\Model\ViewModel;
use Gregwar\Captcha\CaptchaBuilder;
use My\Common\Controller\Action;

class AuthController extends Action
{

    private $_account;

    private $_role;

    private $_resource;

    public function init()
    {
        $this->_account = $this->collection(SYSTEM_ACCOUNT);
        $this->_role = $this->collection(SYSTEM_ROLE);
        $this->_resource = $this->collection(SYSTEM_RESOURCE);
    }

    /**
     * 显示登录页面
     *
     * @author young
     * @name 显示登录页面
     * @version 2013.11.07 young
     */
    public function indexAction()
    {
        $failure = $this->params()->fromRoute('failure', false);
        $view = new ViewModel(array(
            'failure' => $failure
        ));
        $view->setTerminal(true);
        return $view;
    }

    /**
     * 处理登录请求
     *
     * @author young
     * @name 处理登录请求
     * @version 2013.11.07 young
     */
    public function loginAction()
    {
        session_unset();
        $username = $this->params()->fromPost('username', null);
        $password = $this->params()->fromPost('password', null);
        
        $accountInfo = $this->_account->findOne(array(
            'username' => $username,
            'password' => sha1($password),
            'expire' => array(
                '$gt' => new \MongoDate()
            )
        ));
        
        if ($accountInfo == null) {
            return $this->redirect()->toRoute('login', array(
                'failure' => true,
                'code'=>500
            ));
        }
        
        $_SESSION['account'] = $accountInfo;
        if($accountInfo['role']!=='root') {
            // 查询用户所具备的权限
            $roleInfo = $this->_role->findOne(array(
                'role' => $accountInfo['role']
            ));
            if (empty($roleInfo['resources'])) {
                return $this->redirect()->toRoute('login', array(
                    'failure' => true,
                    'code'=>501
                ));
            }
            $_SESSION['account']['resources'] = $roleInfo['resources'];
        }
        
        return $this->redirect()->toRoute('home');
    }

    /**
     * 处理注销请求
     *
     * @author young
     * @name 处理注销请求
     * @version 2013.11.07 young
     */
    public function logoutAction()
    {
        session_destroy();
        $this->redirect()->toRoute('login');
    }

    /**
     * 保持登录状态
     */
    public function keepAction()
    {
        if (! isset($_SESSION['account'])) {
            return $this->deny('很抱歉，您的登录已经注销，请重新登录。');
        }
        return $this->msg(true, 'keep is ok');
    }

    /**
     * 生成登录页面的图形验证码
     *
     * @author young
     * @name 生成登录页面的图形验证码
     * @version 2013.11.07 young
     * @return \Zend\Stdlib\ResponseInterface
     */
    public function captchaAction()
    {
        $builder = new CaptchaBuilder();
        $builder->setBackgroundColor(255, 255, 255);
        $builder->setTextColor(255, 0, 255);
        $builder->setPhrase(rand(100000, 999999));
        $_SESSION['phrase'] = $builder->getPhrase();
        $builder->build(150, 40);
        header('Content-type: image/jpeg');
        $this->response->setContent($builder->output(80));
        return $this->response;
    }
}
