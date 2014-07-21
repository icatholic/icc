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
        fb($action, 'LOG');
        if ($action === 'config') {
            echo '{"imageActionName":"uploadimage","imageFieldName":"upfile","imageMaxSize":2048000,"imageAllowFiles":[".png",".jpg",".jpeg",".gif",".bmp"],"imageCompressEnable":true,"imageCompressBorder":1600,"imageInsertAlign":"none","imageUrlPrefix":"","imagePathFormat":"\/ueditor\/php\/upload\/image\/{yyyy}{mm}{dd}\/{time}{rand:6}","scrawlActionName":"uploadscrawl","scrawlFieldName":"upfile","scrawlPathFormat":"\/ueditor\/php\/upload\/image\/{yyyy}{mm}{dd}\/{time}{rand:6}","scrawlMaxSize":2048000,"scrawlUrlPrefix":"","scrawlInsertAlign":"none","snapscreenActionName":"uploadimage","snapscreenPathFormat":"\/ueditor\/php\/upload\/image\/{yyyy}{mm}{dd}\/{time}{rand:6}","snapscreenUrlPrefix":"","snapscreenInsertAlign":"none","catcherLocalDomain":["127.0.0.1","localhost","img.baidu.com"],"catcherActionName":"catchimage","catcherFieldName":"source","catcherPathFormat":"\/ueditor\/php\/upload\/image\/{yyyy}{mm}{dd}\/{time}{rand:6}","catcherUrlPrefix":"","catcherMaxSize":2048000,"catcherAllowFiles":[".png",".jpg",".jpeg",".gif",".bmp"],"videoActionName":"uploadvideo","videoFieldName":"upfile","videoPathFormat":"\/ueditor\/php\/upload\/video\/{yyyy}{mm}{dd}\/{time}{rand:6}","videoUrlPrefix":"","videoMaxSize":102400000,"videoAllowFiles":[".flv",".swf",".mkv",".avi",".rm",".rmvb",".mpeg",".mpg",".ogg",".ogv",".mov",".wmv",".mp4",".webm",".mp3",".wav",".mid"],"fileActionName":"uploadfile","fileFieldName":"upfile","filePathFormat":"\/ueditor\/php\/upload\/file\/{yyyy}{mm}{dd}\/{time}{rand:6}","fileUrlPrefix":"","fileMaxSize":51200000,"fileAllowFiles":[".png",".jpg",".jpeg",".gif",".bmp",".flv",".swf",".mkv",".avi",".rm",".rmvb",".mpeg",".mpg",".ogg",".ogv",".mov",".wmv",".mp4",".webm",".mp3",".wav",".mid",".rar",".zip",".tar",".gz",".7z",".bz2",".cab",".iso",".doc",".docx",".xls",".xlsx",".ppt",".pptx",".pdf",".txt",".md",".xml"],"imageManagerActionName":"listimage","imageManagerListPath":"\/ueditor\/php\/upload\/image\/","imageManagerListSize":20,"imageManagerUrlPrefix":"","imageManagerInsertAlign":"none","imageManagerAllowFiles":[".png",".jpg",".jpeg",".gif",".bmp"],"fileManagerActionName":"listfile","fileManagerListPath":"\/ueditor\/php\/upload\/file\/","fileManagerUrlPrefix":"","fileManagerListSize":20,"fileManagerAllowFiles":[".png",".jpg",".jpeg",".gif",".bmp",".flv",".swf",".mkv",".avi",".rm",".rmvb",".mpeg",".mpg",".ogg",".ogv",".mov",".wmv",".mp4",".webm",".mp3",".wav",".mid",".rar",".zip",".tar",".gz",".7z",".bz2",".cab",".iso",".doc",".docx",".xls",".xlsx",".ppt",".pptx",".pdf",".txt",".md",".xml"]}';
            return $this->response;
        } elseif ($action === 'listimage' || $action === 'listfile') {
            $size = (int) $this->params()->fromQuery('size', 10);
            $start = (int) $this->params()->fromQuery('start', 0);
            
            $fs = $this->_file->getGridFS();
            $query = array(
                'project_id' => $project_id,
                'collection_id' => $collection_id
            );
            $cursor = $fs->find($query);
            $total = $cursor->count();
            $cursor->skip($start)->limit($size);
            $list = array();
            while ($cursor->hasNext()) {
                $row = $cursor->getNext();
                $list[] = DOMAIN . '/file/' . $row->file['_id']->__toString();
            }
            echo json_encode(array(
                "state" => "SUCCESS",
                "list" => $list,
                "start" => $start,
                "total" => $total
            ));
            return $this->response;
        } elseif ($action === 'catchimage') {
            echo json_encode(array(
                'state' => "不支持远程图片抓取功能",
                'url' => '',
                'title' => '',
                'original' => ''
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
}