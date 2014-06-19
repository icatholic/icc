<?php

/**
 * iDatabase文件处理函数
 *
 * @author young 
 * @version 2014.02.16
 * 
 */
namespace Idatabase\Controller;

use My\Common\Controller\Action;

class UeditorController extends Action
{

    private $_file;

    public function init()
    {
        $this->_file = $this->model('Idatabase\Model\File');
    }

    public function uploadAction()
    {

    }
}