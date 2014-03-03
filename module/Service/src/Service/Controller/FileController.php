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
        $download = $this->params()->fromRoute('d', false);
        $resize = $this->params()->fromRoute('r', false);
        $thumbnail = $this->params()->fromRoute('t', false);
        $adpter = $this->params()->fromRoute('a', false);
        $width = intval($this->params()->fromRoute('w', 0));
        $height = intval($this->params()->fromRoute('h', 0));
        $quality = intval($this->params()->fromRoute('q', 100));
        
        if ($id == null) {
            header("HTTP/1.1 404 Not Found");
            return $this->response;
        }
        
        $gridFsFile = $this->_file->getGridFsFileById($id);
        if ($gridFsFile instanceof \MongoGridFSFile) {
            if (strpos(strtolower($gridFsFile->file['mime']), 'image') !== false) {
                // 图片处理
                $fileInfo = $gridFsFile->file;
                $fileName = $fileInfo['filename'];
                $fileMime = $fileInfo['mime'];
                
                $imagick = new \Imagick();
                $resource = $gridFsFile->getResource();
                $imagick->readImageFile($resource);
                if ($adpter) {
                    $imagick->cropThumbnailImage($width, $height);
                } elseif ($thumbnail) {
                    $imagick->thumbnailImage($width, $height);
                } else {
                    $geo = $imagick->getImageGeometry();
                    $sizeWidth = $geo['width'];
                    $sizeHeight = $geo['height'];
                    if ($width > 0 && $height > 0) {
                        if ($sizeWidth / $width > $sizeHeight / $height) {
                            $height = 0;
                        } else {
                            $width = 0;
                        }
                        $imagick->thumbnailImage($width, $height);
                    } else 
                        if ($width > 0 || $height > 0) {
                            $imagick->thumbnailImage($width, $height);
                        }
                }
                if ($quality < 100) {
                    $imagick->setImageCompression(\Imagick::COMPRESSION_JPEG);
                    $imagick->setImageCompressionQuality($quality);
                    $fileName .= '.jpg';
                    $fileMime = 'image/jpg';
                }
                $imagick->stripImage();
                $data = $imagick->getimageblob();
                $imagick->destroy();
                
                setHeaderExpires();
                if (isset($fileMime)) {
                    header('Content-Type: ' . $fileMime . ';');
                }
                if ($download)
                    header('Content-Disposition:attachment;filename="' . $fileName . '"');
                else
                    header('Content-Disposition:filename="' . $fileName . '"');
                echo $data;
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

