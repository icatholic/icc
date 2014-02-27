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
use OAuth\Common\Exception\Exception;

class DatabaseController extends Action
{

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
        if(empty($project_id)) {
            throw new \Exception('无效的项目编号');
        }
        
        $objFile = $this->collection(IDATABASE_FILES);
        $rst = array();
        if (! empty($_FILES)) {
            foreach ($_FILES as $field => $file) {
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $rst[$field] = $objFile->storeToGridFS($field, array(
                        'project_id' => $project_id
                    ));
                }
            }
        } else {
            $rst = array(
                'ok' => 0,
                'err_code' => '404',
                'err' => '未发现有效的上传文件'
            );
        }
        return json_encode($rst, JSON_UNESCAPED_UNICODE);
    }
}

