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

    /**
     * 处理上传文件
     *
     * @return \Zend\Stdlib\ResponseInterface
     */
    public function uploadAction()
    {
        $action = $this->params()->fromQuery('action', 'config');
        $project_id = $this->params()->fromQuery('__PROJECT_ID__', NULL);
        $collection_id = $this->params()->fromQuery('__COLLECTION_ID__', NULL);
        
        if ($action === 'config') {
            
            return $this->response;
        } elseif ($action === 'list') {
            $this->_file->findAll(array());
            echo json_encode(array(
                "state" => "SUCCESS",
                "list" => $list,
                "start" => $start,
                "total" => count($files)
            ));
        } else {
            if (! isset($_FILES['upfile']) || $_FILES['upfile']['error'] !== 0) {
                echo json_encode(array(
                    'state' => "upload file fail or no file upload",
                    'url' => '',
                    'title' => '',
                    'original' => ''
                ));
                return $this->response;
            }
            
            $gridFsInfo = $this->_file->storeToGridFS('upfile', array(
                'project_id' => $project_id,
                'collection_id' => $collection_id
            ));
            
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

    public function listAction()
    {}
}