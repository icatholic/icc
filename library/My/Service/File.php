<?php
namespace My\Common;

class File
{

    private $_project_id;

    private $_file;

    /**
     * 初始化
     *
     * @param Config $config            
     */
    public function __construct(Config $config)
    {
        $this->_config = $config;
        $this->_key = new MongoCollection($config, IDATABASE_KEYS);
        $this->_file = new MongoCollection($config, IDATABASE_FILES);
    }

    /**
     * 身份认证，请在SOAP HEADER部分请求该函数进行身份校验
     * 签名算法:md5($project_id.$rand.$sign) 请转化为长度为32位的16进制字符串
     *
     * @param string $project_id            
     * @param string $rand            
     * @param string $sign            
     * @param string $key_id            
     * @throws \SoapFault
     * @return boolean
     */
    public function authenticate($project_id, $rand, $sign, $key_id = null)
    {
        if (strlen($rand) < 8) {
            throw new \Exception(411, '随机字符串长度过短，为了安全起见至少8位');
        }
        $this->_project_id = $project_id;
        $key_id = ! empty($key_id) ? $key_id : null;
        $keyInfo = $this->getKeysInfo($project_id, $key_id);
        if (md5($project_id . $rand . $keyInfo['key']) !== strtolower($sign)) {
            throw new \Exception(401, '身份认证校验失败');
        }
        return true;
    }

    /**
     * 获取密钥信息
     *
     * @param string $project_id            
     * @param string $key_id            
     * @throws \SoapFault
     * @return array
     */
    private function getKeysInfo($project_id, $key_id)
    {
        $query = array();
        $query['project_id'] = $project_id;
        if ($key_id !== null) {
            $query['_id'] = myMongoId($key_id);
        } else {
            $query['default'] = true;
        }
        $query['expire'] = array(
            '$gte' => new \MongoDate()
        );
        $query['active'] = true;
        $rst = $this->_key->findOne($query, array(
            'key' => true
        ));
        if ($rst === null)
            throw new \Exception(404, '授权密钥无效');
        return $rst;
    }

    /**
     * 上传文件
     *
     * @param string $fieldName            
     * @param array $meta            
     */
    public function upload($fieldName, $meta = array())
    {
        if (! isset($_FILES[$fieldName]))
            throw new \Exception("上传文件无效");
        
        return $this->_file->storeToGridFS($fieldName, array(
            'project_id' => $this->_project_id
        ));
    }
}