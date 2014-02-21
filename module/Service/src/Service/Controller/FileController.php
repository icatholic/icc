<?php
/**
 * File下载处理函数
 *
 * @author young 
 * @version 2014.02.17
 * 
 */
namespace Service\Controller;

use My\Common\Controller\Action;

class FileController extends Action
{

    private $_file;

    public function init()
    {
        $this->_file = $this->model('Idatabase\Model\File');
    }

    /**
     * 提供外部文件下载服务
     */
    public function indexAction()
    {
        $id = $this->params()->fromRoute('id', null);
        $download = $this->params()->fromRoute('download', null);
        
        if ($id == null) {
            header("HTTP/1.1 404 Not Found");
            return $this->response;
        }
        
        $gridFsFile = $this->_file->getGridFsFileById($id);
        if ($gridFsFile instanceof \MongoGridFSFile) {
            $this->_file->output($gridFsFile, true, $download == null ? false : true);
            return $this->response;
        } else {
            header("HTTP/1.1 404 Not Found");
            return $this->response;
        }
    }
}

