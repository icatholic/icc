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
use Imagine\Imagick\Imagine;
use Imagine\Image\BoxInterface;
use Imagine\Image\Box;

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
        $width = $this->params()->fromRoute('w', null);
        $height = $this->params()->fromRoute('h', null);
        if ($id == null) {
            header("HTTP/1.1 404 Not Found");
            return $this->response;
        }
        
        $gridFsFile = $this->_file->getGridFsFileById($id);
        if ($gridFsFile instanceof \MongoGridFSFile) {
            if (strpos(strtolower($gridFsFile->file['mime']), 'image')) {
                // 图片处理
                $imagick = new \Imagick();
                $resource = $gridFsFile->getResource();
                $imagick->readImageFile($resource);
                $imagick->thumbnailimage($width, $height, false, false);
                echo $imagick->getimageblob();
            } else {
                $this->_file->output($gridFsFile, true, $download == null ? false : true);
            }
            return $this->response;
        } else {
            header("HTTP/1.1 404 Not Found");
            return $this->response;
        }
    }

    public function uploadAction()
    {}
}

