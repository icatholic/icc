<?php
/**
 * iDatabase服务
 *
 * @author young 
 * @version 2014.02.12
 * 
 */
namespace Service\Controller;

use My\Common\Controller\Action;
use My\Service\Database;

class DatabaseController extends Action
{

    /**
     * 提供数据集合的操作服务
     *
     * @see \Zend\Mvc\Controller\AbstractActionController::indexAction()
     */
    public function indexAction()
    {
        $uri = DOMAIN . '/service/database/index';
        $className = '\My\Service\Database';
        $config = $this->getServiceLocator()->get('mongos');
        echo $this->soap($uri, $className, $config);
        return $this->response;
    }

    /**
     * 接受上传文件的处理
     *
     * @return string json
     */
    public function uploadAction()
    {
        $project_id = $this->params()->fromQuery('project_id', '');
        if (empty($project_id)) {
            throw new \Exception('无效的项目编号');
        }
        
        $objFile = $this->collection(IDATABASE_FILES);
        $rst = array();
        if (! empty($_FILES)) {
            foreach ($_FILES as $field => $file) {
                if ($file['error'] === UPLOAD_ERR_OK) {
                    if (filesize($file['tmp_name']) > 0) {
                        $rst[$field] = $objFile->storeToGridFS($field, array(
                            'project_id' => $project_id
                        ));
                    } else {
                        $rst[$field] = array(
                            'ok' => 0,
                            'err_code' => '500',
                            'err' => '上传文件Size为0字节，请检查上传文件'
                        );
                    }
                } else {
                    switch ($file['error']) {
                        case UPLOAD_ERR_INI_SIZE:
                            $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
                            break;
                        case UPLOAD_ERR_FORM_SIZE:
                            $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            $message = "The uploaded file was only partially uploaded";
                            break;
                        case UPLOAD_ERR_NO_FILE:
                            $message = "No file was uploaded";
                            break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                            $message = "Missing a temporary folder";
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            $message = "Failed to write file to disk";
                            break;
                        case UPLOAD_ERR_EXTENSION:
                            $message = "File upload stopped by extension";
                            break;
                        default:
                            $message = "Unknown upload error";
                            break;
                    }
                    
                    $rst[$field] = array(
                        'ok' => 0,
                        'err_code' => $file['error'],
                        'err' => $message
                    );
                }
            }
        } else {
            $rst = array(
                'ok' => 0,
                'err_code' => '404',
                'err' => '未发现有效的上传文件'
            );
        }
        echo json_encode($rst, JSON_UNESCAPED_UNICODE);
        return $this->response;
    }

    /**
     * 用于服务器端调试的方法
     *
     * @return \Zend\Stdlib\ResponseInterface
     */
    public function testAction()
    {
        $config = $this->getServiceLocator()->get('mongos');
        $obj = new Database($config);
        $obj->authenticate('52dce281489619e902452b46', '687797961627269ff11a3f2f41ae90b014589fde', 'e3d47b82de94098cfc16966cbea8d917', '53059145489619c06a3dc01f');
        $obj->setCollection('test_data_type');
        // var_dump($obj->findAll(serialize(array()), serialize(array()), serialize(array())));
        var_dump($obj->getGridFsFileById(serialize('542a4dd0499619c8548b45bc')));
        return $this->response;
    }
}

