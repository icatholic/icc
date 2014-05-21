<?php
/**
* iDatabase上传控制器
*
* @author young
* @version 2014.05.21
*
*/
namespace Idatabase\Controller;

use My\Common\Controller\Action;

class UploadController extends Action
{

    private $_file;

    public function init()
    {
        $this->_file = $this->model('Idatabase\Model\File');
    }

    /**
     * 处理上传数据的接口
     *
     * @see \Zend\Mvc\Controller\AbstractActionController::indexAction()
     */
    public function indexAction()
    {
        $action = $this->params()->fromQuery('action', null);
        switch ($action) {
            case 'upload':
                echo (json_encode($this->uploadHtmlEditorImage()));
                break;
            
            case 'resize':
                echo (json_encode($this->resizeImage()));
                break;
            
            case 'imagesList':
                echo (json_encode($this->getImages()));
                break;
            
            case 'delete':
                echo (json_encode($this->deleteImage()));
                break;
        }
        return $this->response;
    }

    /**
     * 处理上传图片
     *
     * @return Ambigous <multitype:boolean string , multitype:boolean string multitype:string >
     */
    private function uploadHtmlEditorImage()
    {
        $collection_id = $this->params('collection_id', null);
        if (isset($_FILES['photo-path']) && $_FILES['photo-path']['error'] == UPLOAD_ERR_OK) {
            
            $fileInfo = $this->_file->storeToGridFS('photo-path', array(
                'collection_id' => $collection_id
            ));
            $url = DOMAIN . '/file/' . $fileInfo['_id'];
            
            $result = array(
                'success' => true,
                'message' => 'Image Uploaded Successfully',
                'data' => array(
                    'src' => $url
                ),
                'total' => '1',
                'errors' => ''
            );
        } else {
            $result = array(
                'success' => false,
                'message' => 'Error',
                'data' => '',
                'total' => '0',
                'errors' => 'Error Uploading Image'
            );
        }
        return $result;
    }

    /**
     * 格式化图片尺寸
     *
     * @return multitype:boolean string number multitype:string
     */
    private function resizeImage()
    {
        $width = $this->params()->fromQuery('width', 0);
        $height = $this->params()->fromQuery('height', 0);
        $imageSrc = $this->params()->fromQuery('image', '');
        return array(
            'success' => true,
            'message' => 'Success',
            'data' => array(
                'src' => $imageSrc . "/w/{$width}/h/{$height}"
            ),
            'total' => 1,
            'errors' => ''
        );
    }

    /**
     * 获取图片信息
     *
     * @return multitype:boolean string number multitype:string NULL
     */
    private function getImages()
    {
        $collection_id = $this->params('collection_id', '');
        $limit = $this->params()->fromQuery('limit', 10);
        $start = $this->params()->fromQuery('start', 0);
        
        $files = $this->_file->getGridFsFileBy(array(
            'collection_id' => $collection_id
        ));
        
        $results = array();
        if (! empty($files) && is_array($files)) {
            foreach ($files as $file) {
                $image = DOMAIN . '/file/' . $file->file['_id']->__toString();
                $results[] = array(
                    '_id' => $file->file['_id']->__toString(),
                    'fullname' => $file->file['fileName'],
                    'name' => $file->file['fileName'],
                    'src' => $image,
                    'thumbSrc' => $image . '/w/64/h/64'
                );
            }
        }
        
        return array(
            'success' => true,
            'message' => 'Success',
            'data' => $results,
            'total' => count($results),
            'errors' => ''
        );
    }

    /**
     * 删除图片
     *
     * @return multitype:boolean string number
     */
    private function deleteImage()
    {
        $image = $this->params()->fromQuery('image', '');
        $this->file->removeFileFromGridFS($image);
        return array(
            'success' => true,
            'message' => 'Success',
            'data' => '',
            'total' => 1,
            'errors' => ''
        );
    }
}