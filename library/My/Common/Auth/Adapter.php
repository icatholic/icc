<?php
namespace My\Common\Auth;

use Zend\Authentication\Adapter\AdapterInterface;
use Zend\Authentication\Result;

class Adapter implements AdapterInterface
{
    private $_username;
    
    private $_password;
    
    public function __construct($username, $password)
    {
        $this->_username = trim($username);
        $this->_password = sha1(trim($password));
    }
    
    /**
     * @see \Zend\Authentication\Adapter\AdapterInterface::authenticate()
     * @return \Zend\Authentication\Result
     */
    public function authenticate() {
        
        return new Result();
    }
}