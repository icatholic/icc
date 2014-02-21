<?php
/**
 * iDatabase定义数据字典
 *
 * @author young 
 * @version 2013.11.22
 * 
 */
namespace Idatabase\Controller;

use Zend\Json\Json;
use My\Common\Controller\Action;

class StructureController extends Action
{

    private $_project_id;

    private $_collection_id;

    private $_plugin_id;

    private $_structure;
    
    private $_mapping;

    private $_plugin_structure;

    private $_plugin_collection_id;

    private $_model;

    private $_collection;

    private $_fieldRgex = '/^[a-z]{1}[a-z0-9_\.]*$/i';

    public function init()
    {
        if ($this->action != 'filter') {
            $this->_project_id = isset($_REQUEST['__PROJECT_ID__']) ? trim($_REQUEST['__PROJECT_ID__']) : '';
            $this->_collection_id = isset($_REQUEST['__COLLECTION_ID__']) ? trim($_REQUEST['__COLLECTION_ID__']) : '';
            $this->_plugin_id = isset($_REQUEST['__PLUGIN_ID__']) ? trim($_REQUEST['__PLUGIN_ID__']) : '';
            $this->_plugin_collection_id = isset($_REQUEST['__PLUGIN_COLLECTION_ID__']) ? trim($_REQUEST['__PLUGIN_COLLECTION_ID__']) : '';
            
            if (empty($this->_project_id)) {
                throw new \Exception('$this->_project_id值未设定');
            }
            
            if (empty($this->_collection_id)) {
                throw new \Exception('$this->_collection_id值未设定');
            }
        }
        
        $this->_structure = $this->model('Idatabase\Model\Structure');
        $this->_collection = $this->model('Idatabase\Model\Collection');
        $this->_plugin_structure = $this->model('Idatabase\Model\PluginStructure');
        $this->_mapping = $this->model('Idatabase\Model\Mapping');
        $this->_model = $this->_structure;
    }

    /**
     * 读取某个集合的全部字段
     *
     * @author young
     * @name 读取某个集合的全部字段
     * @version 2013.11.22 young
     */
    public function indexAction()
    {
        $rst = array();
        $sort = array(
            'orderBy' => 1,
            '_id' => 1
        );
        
        $query = array(
            'collection_id' => $this->_collection_id
        );
        $cursor = $this->_structure->find($query);
        $cursor->sort($sort);
        while ($cursor->hasNext()) {
            $row = $cursor->getNext();
            if (isset($row['rshCollection']) && $row['rshCollection'] != '') {
                $row = array_merge($row, $this->getRshCollectionInfo($row['rshCollection']));
            }
            $rst[] = $row;
        }
        
        return $this->rst($rst, $cursor->count(), true);
    }

    /**
     * 获取关联集合的信息
     *
     * @param string $collectionName            
     * @return array
     */
    private function getRshCollectionInfo($collectionName)
    {
        $rst = array();
        $cursor = $this->_structure->find(array(
            'collection_id' => $this->_collection->getCollectionIdByAlias($this->_project_id, $collectionName)
        ));
        
        $rst = array(
            'rshCollectionValueField' => '_id',
            'rshCollectionDisplayField' => '',
            'rshCollectionFatherField' => ''
        );
        
        while ($cursor->hasNext()) {
            $row = $cursor->getNext();
            if ($row['rshKey'])
                $rst['rshCollectionDisplayField'] = $row['field'];
            if ($row['rshValue'])
                $rst['rshCollectionValueField'] = $row['field'];
            if ($row['isFatherField'])
                $rst['rshCollectionFatherField'] = $row['field'];
        }
        return $rst;
    }

    /**
     * 添加新的字段
     *
     * @author young
     * @name 添加新的字段
     * @version 2013.11.14 young
     * @return JsonModel
     */
    public function addAction()
    {
        $datas = array();
        $datas['collection_id'] = $this->_collection_id;
        $datas['plugin_collection_id'] = $this->_plugin_collection_id;
        $datas['plugin_id'] = $this->_plugin_id;
        $datas['field'] = $this->params()->fromPost('field', null);
        $datas['label'] = $this->params()->fromPost('label', null);
        $datas['type'] = $this->params()->fromPost('type', null);
        $datas['filter'] = (int) filter_var($this->params()->fromPost('filter', 0), FILTER_SANITIZE_NUMBER_INT);
        $datas['searchable'] = filter_var($this->params()->fromPost('searchable', false), FILTER_VALIDATE_BOOLEAN);
        $datas['main'] = filter_var($this->params()->fromPost('main', false), FILTER_VALIDATE_BOOLEAN);
        $datas['required'] = filter_var($this->params()->fromPost('required', false), FILTER_VALIDATE_BOOLEAN);
        $datas['isFatherField'] = filter_var($this->params()->fromPost('isFatherField', false), FILTER_VALIDATE_BOOLEAN);
        $datas['rshCollection'] = $this->params()->fromPost('rshCollection', '');
        $datas['isBoxSelect'] = filter_var($this->params()->fromPost('isBoxSelect', ''), FILTER_VALIDATE_BOOLEAN);
        $datas['rshType'] = 'combobox';
        $datas['rshKey'] = filter_var($this->params()->fromPost('rshKey', false), FILTER_VALIDATE_BOOLEAN);
        $datas['rshValue'] = filter_var($this->params()->fromPost('rshValue', false), FILTER_VALIDATE_BOOLEAN);
        $datas['showImage'] = filter_var($this->params()->fromPost('showImage', false), FILTER_VALIDATE_BOOLEAN);
        $datas['orderBy'] = (int) filter_var($this->params()->fromPost('orderBy', 0), FILTER_VALIDATE_INT);
        $datas['isQuick'] = filter_var($this->params()->fromPost('isQuick', false), FILTER_VALIDATE_BOOLEAN);
        $datas['quickTargetCollection'] = trim($this->params()->fromPost('quickTargetCollection', ''));
        $datas['rshSearchCondition'] = trim($this->params()->fromPost('rshSearchCondition', ''));
        $datas['isLinkageMenu'] = filter_var($this->params()->fromPost('isLinkageMenu', false), FILTER_VALIDATE_BOOLEAN);
        $datas['linkageClearValueField'] = trim($this->params()->fromPost('linkageClearValueField', ''));
        $datas['linkageSetValueField'] = trim($this->params()->fromPost('linkageSetValueField', ''));
        $datas['cdnUrl'] = trim($this->params()->fromPost('cdnUrl', ''));
        $datas['xTemplate'] = trim($this->params()->fromPost('xTemplate', ''));
        
        if ($datas['type'] !== 'filefield' && ! empty($datas['cdnUrl'])) {
            return $this->msg(false, '只有当输入类型为“文件类型”时，才需要设定文件资源域名');
        }
        
        if ($datas['field'] == null) {
            return $this->msg(false, '请填写字段名称');
        }
        
        if (! $this->checkFieldName($datas['field'])) {
            return $this->msg(false, '字段名必须为以英文字母开始的“字母、数字、下划线”的组合,“点”标注子属性时，子属性必须以字母开始');
        }
        
        if ($datas['label'] == null) {
            return $this->msg(false, '请填写字段描述');
        }
        
        if ($datas['type'] == null) {
            return $this->msg(false, '请选择字段类型');
        }
        
        if ($datas['rshSearchCondition'] !== '') {
            if (isJson($datas['rshSearchCondition'])) {
                try {
                    $datas['rshSearchCondition'] = Json::decode($datas['rshSearchCondition'], Json::TYPE_ARRAY);
                } catch (\Exception $e) {
                    return $this->msg(false, '关联集合约束查询条件的json格式错误');
                }
            } else {
                return $this->msg(false, '关联集合约束查询条件的json格式错误');
            }
        }
        
        if ($datas['isQuick'] === true) {
            if ($datas['type'] !== 'arrayfield') {
                return $this->msg(false, '快速录入字段，输入类型必须是“数组”');
            }
            
            if ($datas['quickTargetCollection'] === '') {
                return $this->msg(false, '请选快速录入的目标集合');
            }
        }
        
        if ($this->checkExist('field', $datas['field'], array(
            'collection_id' => $this->_collection_id
        ))) {
            return $this->msg(false, '字段名称已经存在');
        }
        
        if ($this->checkExist('label', $datas['label'], array(
            'collection_id' => $this->_collection_id
        ))) {
            return $this->msg(false, '字段描述已经存在');
        }
        
        if ($datas['isBoxSelect']) {
            if ($datas['type'] !== 'arrayfield') {
                return $this->msg(false, '启用多选项时，请设定输入类型为“数组”');
            }
            
            if (empty($datas['rshCollection'])) {
                return $this->msg(false, '启用多选项时，必须设定“关联结合”');
            }
        }
        
        if ($datas['isFatherField']) {
            if (empty($datas['rshCollection'])) {
                return $this->msg(false, '复选项，必须设定“关联结合”，且关联结合为自身');
            }
        }
        
        $this->_structure->insert($datas);
        $this->_plugin_structure->sync($datas);
        
        return $this->msg(true, '添加信息成功');
    }

    /**
     * 编辑某些字段
     *
     * @author young
     * @name 编辑某些字段
     * @version 2013.11.14 young
     * @return JsonModel
     */
    public function editAction()
    {
        $_id = $this->params()->fromPost('_id', null);
        $datas = array();
        $datas['collection_id'] = $this->_collection_id;
        $datas['plugin_collection_id'] = $this->_plugin_collection_id;
        $datas['plugin_id'] = $this->_plugin_id;
        $datas['field'] = $this->params()->fromPost('field', null);
        $datas['label'] = $this->params()->fromPost('label', null);
        $datas['type'] = $this->params()->fromPost('type', null);
        $datas['filter'] = (int) filter_var($this->params()->fromPost('filter', 0), FILTER_SANITIZE_NUMBER_INT);
        $datas['searchable'] = filter_var($this->params()->fromPost('searchable', false), FILTER_VALIDATE_BOOLEAN);
        $datas['main'] = filter_var($this->params()->fromPost('main', false), FILTER_VALIDATE_BOOLEAN);
        $datas['required'] = filter_var($this->params()->fromPost('required', false), FILTER_VALIDATE_BOOLEAN);
        $datas['isFatherField'] = filter_var($this->params()->fromPost('isFatherField', false), FILTER_VALIDATE_BOOLEAN);
        $datas['rshCollection'] = $this->params()->fromPost('rshCollection', '');
        $datas['isBoxSelect'] = filter_var($this->params()->fromPost('isBoxSelect', ''), FILTER_VALIDATE_BOOLEAN);
        $datas['rshType'] = 'combobox';
        $datas['rshKey'] = filter_var($this->params()->fromPost('rshKey', false), FILTER_VALIDATE_BOOLEAN);
        $datas['rshValue'] = filter_var($this->params()->fromPost('rshValue', false), FILTER_VALIDATE_BOOLEAN);
        $datas['showImage'] = filter_var($this->params()->fromPost('showImage', false), FILTER_VALIDATE_BOOLEAN);
        $datas['orderBy'] = (int) filter_var($this->params()->fromPost('orderBy', 0), FILTER_VALIDATE_INT);
        $datas['isQuick'] = filter_var($this->params()->fromPost('isQuick', false), FILTER_VALIDATE_BOOLEAN);
        $datas['quickTargetCollection'] = trim($this->params()->fromPost('quickTargetCollection', ''));
        $datas['rshSearchCondition'] = trim($this->params()->fromPost('rshSearchCondition', ''));
        $datas['isLinkageMenu'] = filter_var($this->params()->fromPost('isLinkageMenu', false), FILTER_VALIDATE_BOOLEAN);
        $datas['linkageClearValueField'] = trim($this->params()->fromPost('linkageClearValueField', ''));
        $datas['linkageSetValueField'] = trim($this->params()->fromPost('linkageSetValueField', ''));
        $datas['cdnUrl'] = trim($this->params()->fromPost('cdnUrl', ''));
        $datas['xTemplate'] = trim($this->params()->fromPost('xTemplate', ''));
        
        if ($datas['type'] !== 'filefield' && ! empty($datas['cdnUrl'])) {
            return $this->msg(false, '只有当输入类型为“文件类型”时，才需要设定文件资源域名');
        }
        
        if ($datas['field'] == null) {
            return $this->msg(false, '请填写字段名称');
        }
        
        if (! $this->checkFieldName($datas['field'])) {
            return $this->msg(false, '字段名必须为以英文字母开始的“字母、数字、下划线”的组合,“点”标注子属性时，子属性必须以字母开始');
        }
        
        if ($datas['label'] == null) {
            return $this->msg(false, '请填写字段描述');
        }
        
        if ($datas['type'] == null) {
            return $this->msg(false, '请选择字段类型');
        }
        
        if ($datas['rshSearchCondition'] !== '') {
            if (isJson($datas['rshSearchCondition'])) {
                try {
                    $datas['rshSearchCondition'] = Json::decode($datas['rshSearchCondition'], Json::TYPE_ARRAY);
                } catch (\Exception $e) {
                    return $this->msg(false, '关联集合约束查询条件的json格式错误');
                }
            } else {
                return $this->msg(false, '关联集合约束查询条件的json格式错误');
            }
        }
        
        if ($datas['isQuick'] === true) {
            if ($datas['type'] !== 'arrayfield') {
                return $this->msg(false, '快速录入字段，输入类型必须是“数组”');
            }
            
            if ($datas['quickTargetCollection'] === '') {
                return $this->msg(false, '请选快速录入的目标集合');
            }
        }
        
        $oldStructureInfo = $this->_structure->findOne(array(
            '_id' => myMongoId($_id)
        ));
        
        if ($this->checkExist('field', $datas['field'], array(
            'collection_id' => $this->_collection_id
        )) && $oldStructureInfo['field'] != $datas['field']) {
            return $this->msg(false, '字段名称已经存在');
        }
        
        if ($this->checkExist('label', $datas['label'], array(
            'collection_id' => $this->_collection_id
        )) && $oldStructureInfo['label'] != $datas['label']) {
            return $this->msg(false, '字段描述已经存在');
        }
        
        if ($datas['isBoxSelect']) {
            if ($datas['type'] !== 'arrayfield') {
                return $this->msg(false, '启用多选项时，请设定输入类型为“数组”');
            }
            
            if (empty($datas['rshCollection'])) {
                return $this->msg(false, '启用多选项时，必须设定“关联结合”');
            }
        }
        
        if ($datas['isFatherField']) {
            if (empty($datas['rshCollection'])) {
                return $this->msg(false, '复选项，必须设定“关联结合”，且关联结合为自身');
            }
        }
        
        // 如果修改了字段名称，那么对于数据集合中的对应字段进行重命名操作
        if ($oldStructureInfo['field'] !== $datas['field']) {
            if($this->_mapping->getMapping($this->_collection_id)!==null) {
                return $this->msg(false, '当前集合开启了映射，无法修改字段名');
            }
            $dataCollection = $this->collection(iCollectionName($this->_collection_id));
            if ($dataCollection instanceof \MongoCollection) {
                $rstRename = $dataCollection->update(array(), array(
                    '$rename' => array(
                        $oldStructureInfo['field'] => $datas['field']
                    )
                ));
                $datas['__OLD_FIELD__'] = $oldStructureInfo['field'];
            }
        }
        
        $this->_structure->update(array(
            '_id' => myMongoId($_id)
        ), array(
            '$set' => $datas
        ));
        
        // 同步插件中的数据结构
        $this->_plugin_structure->sync($datas);
        
        return $this->msg(true, '编辑信息成功');
    }

    /**
     * 批量保存字段修改
     *
     * @author young
     * @name 批量保存字段修改
     * @version 2013.12.02 young
     * @return JsonModel
     */
    public function saveAction()
    {
        $updateInfos = $this->params()->fromPost('updateInfos', null);
        try {
            $updateInfos = Json::decode($updateInfos, Json::TYPE_ARRAY);
        } catch (\Exception $e) {
            return $this->msg(false, '无效的json字符串');
        }
        
        if (! is_array($updateInfos)) {
            return $this->msg(false, '更新数据无效');
        }
        
        $rename = array();
        foreach ($updateInfos as $row) {
            $_id = $row['_id'];
            unset($row['_id']);
            
            if ($row['field'] == null) {
                return $this->msg(false, '请填写字段名称');
            }
            
            if (! $this->checkFieldName($row['field'])) {
                return $this->msg(false, '字段名必须为以英文字母开始的“字母、数字、下划线”的组合,“点”标注子属性时，子属性必须以字母开始');
            }
            
            if ($row['label'] == null) {
                return $this->msg(false, '请填写字段描述');
            }
            
            if ($row['type'] == null) {
                return $this->msg(false, '请选择字段类型');
            }
            
            if ($row['rshSearchCondition'] !== '') {
                if (isJson($row['rshSearchCondition'])) {
                    try {
                        $row['rshSearchCondition'] = Json::decode($row['rshSearchCondition'], Json::TYPE_ARRAY);
                    } catch (\Exception $e) {
                        $this->msg(false, '关联集合约束查询条件的json格式错误');
                    }
                } else {
                    return $this->msg(false, '关联集合约束查询条件的json格式错误');
                }
            }
            
            if ($row['isQuick'] === true) {
                if ($row['type'] !== 'arrayfield') {
                    return $this->msg(false, '快速录入字段，输入类型必须是“数组”');
                }
                
                if ($row['quickTargetCollection'] === '') {
                    return $this->msg(false, '请选快速录入的目标集合');
                }
            }
            
            if ($row['isFatherField']) {
                if (empty($row['rshCollection'])) {
                    return $this->msg(false, '复选项，必须设定“关联结合”，且关联结合为自身');
                }
            }
            
            $row['filter'] = (int) $row['filter'];
            
            $oldStructureInfo = $this->_structure->findOne(array(
                '_id' => myMongoId($_id)
            ));
            
            if ($this->checkExist('field', $row['field'], array(
                'collection_id' => $this->_collection_id
            )) && $oldStructureInfo['field'] != $row['field']) {
                return $this->msg(false, '字段名称已经存在');
            }
            
            if ($this->checkExist('label', $row['label'], array(
                'collection_id' => $this->_collection_id
            )) && $oldStructureInfo['label'] != $row['label']) {
                return $this->msg(false, '字段描述已经存在');
            }
            
            if ($row['isBoxSelect']) {
                if ($row['type'] !== 'arrayfield') {
                    return $this->msg(false, '启用多选项时，请设定输入类型为“数组”');
                }
                
                if (empty($row['rshCollection'])) {
                    return $this->msg(false, '启用多选项时，必须设定“关联结合”');
                }
            }
            
            if ($oldStructureInfo['field'] != $row['field']) {
                if($this->_mapping->getMapping($this->_collection_id)!==null) {
                    return $this->msg(false, '当前集合开启了映射，无法修改字段名');
                }
                $rename[$oldStructureInfo['field']] = $row['field'];
                $row['__OLD_FIELD__'] = $oldStructureInfo['field'];
            }
            
            $rst = $this->_structure->update(array(
                '_id' => myMongoId($_id),
                'collection_id' => $this->_collection_id
            ), array(
                '$set' => $row
            ));

            $this->_plugin_structure->sync($row);
        }
        
        // 如果修改了字段名称，那么对于数据集合中的对应字段进行重命名操作
        if (! empty($rename)) {
            $dataCollection = $this->collection(iCollectionName($this->_collection_id));
            if ($dataCollection instanceof \MongoCollection) {
                $dataCollection->update(array(), array(
                    '$rename' => $rename
                ));
            }
        }
        return $this->msg(true, '更新字段属性成功');
    }

    /**
     * 删除某些字段
     *
     * @author young
     * @name 删除某些字段
     * @version 2013.11.22 young
     * @return JsonModel
     */
    public function removeAction()
    {
        $_id = $this->params()->fromPost('_id', null);
        $plugin_id = $this->_plugin_id;
        
        try {
            $_id = Json::decode($_id, Json::TYPE_ARRAY);
        } catch (\Exception $e) {
            return $this->msg(false, '无效的json字符串');
        }
        
        if (! is_array($_id)) {
            return $this->msg(false, '请选择你要删除的项');
        }
        foreach ($_id as $row) {
            $rowInfo = $this->_structure->findOne(array(
                '_id' => myMongoId($row)
            ));
            if ($rowInfo != null) {
                $this->_plugin_structure->removePluginStructure($plugin_id, $rowInfo);
                $this->_structure->remove(array(
                    '_id' => myMongoId($row),
                    'collection_id' => $this->_collection_id
                ));
            }
        }
        return $this->msg(true, '删除字段属性成功');
    }

    /**
     * 获取全部过滤器方法
     *
     * @return multitype:number
     */
    public function filterAction()
    {
        $map = array();
        $map['int'] = '整数验证';
        $map['boolean'] = '是非验证';
        $map['float'] = '浮点验证';
        $map['validate_url'] = '是否URL';
        $map['validate_email'] = '是否Email';
        $map['validate_ip'] = '是否IP地址';
        $map['string'] = '过滤字符串';
        $map['encoded'] = '去除或编码特殊字符';
        $map['special_chars'] = 'HTML转义';
        $map['unsafe_raw'] = '无过滤字符串';
        $map['email'] = '过滤非Email字符';
        $map['url'] = '过滤非URL字符';
        $map['number_int'] = '数字过滤非整型';
        $map['number_float'] = '数字过滤非浮点';
        $map['magic_quotes'] = '转义字符';
        
        $filters = array();
        foreach (filter_list() as $key => $value) {
            if (isset($map[$value])) {
                $filters[] = array(
                    'name' => $map[$value],
                    'val' => filter_id($value)
                );
            }
        }
        
        $filters[] = array(
            'name' => '关闭过滤器',
            'val' => 0
        );
        return $this->rst($filters, null, true);
    }

    /**
     * 检测字段是否已经存在
     *
     * @param string $field            
     * @param string $info            
     * @param array $extra            
     * @param resource $model            
     * @return boolean
     */
    private function checkExist($field, $info, $extra = null, $model = null)
    {
        if ($model == null) {
            if ($this->_model instanceof \MongoCollection) {
                $model = $this->_model;
            } else {
                throw new \Exception('$this->_model未设定');
            }
        }
        
        $query = array();
        if (empty($extra) || ! is_array($extra)) {
            $query = array(
                $field => $info
            );
        } else {
            $query = array(
                '$and' => array(
                    array(
                        $field => $info
                    ),
                    $extra
                )
            );
        }
        $info = $model->findOne($query);
        
        if ($info == null) {
            return false;
        }
        return true;
    }

    /**
     * 检查mongodb的属性名称命名空间
     *
     * @param string $name            
     * @return boolean
     */
    private function checkFieldName($name)
    {
        if (! preg_match($this->_fieldRgex, $name)) {
            return false;
        }
        return true;
    }
}
