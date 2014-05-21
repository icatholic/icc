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
use My\Common\MongoCollection;

class UploadController extends Action
{

    private $_file;

    public function init()
    {
        $this->_file = $this->model("Idatabase\Model\File");
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
        $collection_id = $this->params()->fromQuery('collection_id', null);
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
        $width = isset($_REQUEST["width"]) ? intval($_REQUEST["width"]) : 0;
        $height = isset($_REQUEST["height"]) ? intval($_REQUEST["height"]) : 0;
        $imageSrc = isset($_REQUEST["image"]) ? ($_REQUEST["image"]) : '';
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
        $config = Zend_Registry::get('config');
        
        $formId = $this->getRequest()->getParam('formId');
        $limit = isset($_REQUEST["limit"]) ? intval($_REQUEST["limit"]) : 10;
        $start = isset($_REQUEST["start"]) ? intval($_REQUEST["start"]) : 0;
        $query = isset($_REQUEST["query"]) ? $_REQUEST["query"] : 0;
        
        $cursor = $this->_gfs->find(array(
            'meta.idb_form_id' => $formId
        ));
        $cursor->sort(array(
            '_id' => - 1
        ))
            ->skip($start)
            ->limit($limit);
        while ($cursor->hasNext()) {
            $row = $cursor->getNext();
            $image = $config['uma']['server'] . 'soa/image/get/id/' . $row->file['_id']->__toString();
            $results[] = array(
                '_id' => $row->file['_id']->__toString(),
                'fullname' => $row->file['fileName'],
                'name' => $row->file['fileName'],
                'src' => $image,
                'thumbSrc' => $image . '/size/64x64'
            );
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
        $formId = $this->getRequest()->getParam('formId');
        $image = isset($_REQUEST["image"]) ? stripslashes($_REQUEST["image"]) : "";
        $this->_gfs->remove(array(
            'meta.idb_form_id' => $formId,
            '_id' => new MongoId($image)
        ));
        return array(
            'success' => true,
            'message' => 'Success',
            'data' => '',
            'total' => 1,
            'errors' => ''
        );
    }
}