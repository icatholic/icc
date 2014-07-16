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

    /**
     * 处理上传文件
     * @return \Zend\Stdlib\ResponseInterface
     */
    public function uploadAction()
    {
        if (! isset($_FILES['upfile']) || $_FILES['upfile']['error'] !== 0) {
            echo "upload file fail or no file upload";
            return $this->response;
        }
        
        $gridFsInfo = $this->_file->storeToGridFS('upfile');
        
        $url = DOMAIN . '/file/' . $gridFsInfo['_id']->__toString();
        $fileName = $_FILES['upfile']['name'];
        echo json_encode(array(
            'state' => 'SUCCESS',
            'url' => $url,
            'title' => $fileName,
            'original' => $fileName
        ));
        return $this->response;
    }
}