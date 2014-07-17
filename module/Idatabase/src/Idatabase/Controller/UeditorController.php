<?php

/**
 * iDatabase整合UEditor
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

    public function configAction()
    {
        echo '{
    "imageUrl": "http://localhost/ueditor/php/controller.php?action=uploadimage",
    "imagePath": "/ueditor/php/",
    "imageFieldName": "upfile",
    "imageMaxSize": 2048,
    "imageAllowFiles": [".png", ".jpg", ".jpeg", ".gif", ".bmp"]
    "其他配置项...": "其他配置值..."}';
        return $this->response;
    }

    /**
     * 处理上传文件
     *
     * @return \Zend\Stdlib\ResponseInterface
     */
    public function uploadAction()
    {
        if (! isset($_FILES['upfile']) || $_FILES['upfile']['error'] !== 0) {
            echo json_encode(array(
                'state' => "upload file fail or no file upload",
                'url' => '',
                'title' => '',
                'original' => ''
            ));
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

    public function listAction()
    {}
}