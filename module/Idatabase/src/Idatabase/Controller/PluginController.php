<?php
/**
 * iDatabase项目插件管理
 *
 * @author young 
 * @version 2013.11.26
 * 
 */
namespace Idatabase\Controller;

use Zend\View\Model\JsonModel;
use Zend\Json\Json;
use My\Common\Controller\Action;

class PluginController extends Action
{

    private $_plugin;

    private $_project_plugin;

    private $_project_id;

    public function init()
    {
        $this->_project_id = isset($_REQUEST['__PROJECT_ID__']) ? trim($_REQUEST['__PROJECT_ID__']) : '';
        $this->_plugin = $this->model('Idatabase\Model\Plugin');
        $this->_project_plugin = $this->model('Idatabase\Model\ProjectPlugin');
        
        // 注意这里应该增加检查，该项目id是否符合用户操作的权限范围
    }

    /**
     * 读取某个项目使用的全部插件列表
     *
     * @author young
     * @name 读取某个项目使用的全部插件列表
     * @version 2013.11.27 young
     */
    public function indexAction()
    {
        if (empty($this->_project_id))
            throw new \Exception('$this->_project_id值未设定');
        
        $query = array(
            'project_id' => $this->_project_id
        );
        
        $cursor = $this->_project_plugin->find($query);
        
        $result = array();
        while ($cursor->hasNext()) {
            $row = $cursor->getNext();
            $plugin_id = $row['plugin_id'];
            $pluginInfo = $this->_plugin->findOne(array(
                '_id' => myMongoId($plugin_id)
            ));
            $result[] = array(
                '_id' => $row['_id'],
                'plugin_id' => $row['plugin_id'],
                '__CREATE_TIME__' => $row['__CREATE_TIME__'],
                'name' => $pluginInfo['name'],
                'desc' => $pluginInfo['desc'],
                'xtype' => $pluginInfo['xtype']
            );
        }
        return $this->rst($result, $cursor->count(), true);
    }

    /**
     * 为指定项目添加插件
     *
     * @author young
     * @name 为指定项目添加插件
     * @version 2013.11.26 young
     * @return JsonModel
     */
    public function addAction()
    {
        if (empty($this->_project_id))
            throw new \Exception('$this->_project_id值未设定');
        
        $project_id = $this->_project_id;
        $source_project_id = $this->params()->fromPost('__SOURCE_PROJECT_ID__', '');
        $plugin_id = $this->params()->fromPost('__PLUGIN_ID__', null);
        
        if ($project_id == null) {
            return $this->msg(false, '无效的项目编号');
        }
        
        if ($plugin_id == null) {
            return $this->msg(false, '无效的插件编号');
        }
        
        $datas = array();
        $datas['project_id'] = $project_id;
        $datas['source_project_id'] = $source_project_id;
        $datas['plugin_id'] = $plugin_id;
        $this->_project_plugin->update(array(
            'project_id' => $project_id,
            'plugin_id' => $plugin_id
        ), array(
            '$set' => $datas
        ), array(
            'upsert' => true
        ));
        
        return $this->msg(true, '添加信息成功');
    }

    /**
     * 删除指定项目中的插件
     *
     * @author young
     * @name 删除指定项目中的插件
     * @version 2013.11.27 young
     * @return JsonModel
     */
    public function removeAction()
    {
        if (empty($this->_project_id))
            throw new \Exception('$this->_project_id值未设定');
        
        $_id = $this->params()->fromPost('_id', null);
        try {
            $_id = Json::decode($_id, Json::TYPE_ARRAY);
        } catch (\Exception $e) {
            return $this->msg(false, '无效的json字符串');
        }
        
        if (! is_array($_id)) {
            return $this->msg(false, '请选择你要删除的项');
        }
        foreach ($_id as $row) {
            $this->_project_plugin->remove(array(
                '_id' => myMongoId($row),
                'project_id' => $this->_project_id
            ));
        }
        return $this->msg(true, '删除信息成功');
    }

    /**
     * 列出全部系统插件
     *
     * @author young
     * @name 列出全部系统插件
     * @version 2013.11.27 young
     * @return JsonModel
     */
    public function readPluginAction()
    {
        return $this->findAll(IDATABASE_PLUGINS);
    }

    /**
     * 添加系统插件
     *
     * @author young
     * @name 添加系统插件
     * @version 2013.11.27 young
     * @return JsonModel
     */
    public function addPluginAction()
    {
        $name = $this->params()->fromPost('name', null);
        $desc = $this->params()->fromPost('desc', null);
        $xtype = $this->params()->fromPost('xtype', null);
        if ($name == null) {
            return $this->msg(false, '请填写插件名称');
        }
        
        if ($desc == null) {
            return $this->msg(false, '请填写插件描述');
        }
        
        if ($xtype == null) {
            return $this->msg(false, '请填写插件ExtJS的xtype');
        }
        
        if ($this->checkPluginNameExist($name)) {
            return $this->msg(false, '插件名称已经存在');
        }
        
        $datas = array();
        $datas['name'] = $name;
        $datas['desc'] = $desc;
        $datas['xtype'] = $xtype;
        
        $this->_plugin->insert($datas);
        return $this->msg(true, '添加插件成功');
    }

    /**
     * 编辑系统插件
     *
     * @author young
     * @name 编辑系统插件
     * @version 2013.11.27 young
     * @return JsonModel
     */
    public function editPluginAction()
    {
        $_id = $this->params()->fromPost('_id', null);
        $name = $this->params()->fromPost('name', null);
        $desc = $this->params()->fromPost('desc', null);
        $xtype = $this->params()->fromPost('xtype', null);
        if ($_id == null) {
            return $this->msg(false, '无效的插件_id');
        }
        
        if ($name == null) {
            return $this->msg(false, '请填写插件名称');
        }
        
        if ($desc == null) {
            return $this->msg(false, '请填写插件描述');
        }
        
        if ($xtype == null) {
            return $this->msg(false, '请填写插件ExtJS的xtype');
        }
        
        $oldPluginInfo = $this->_plugin->findOne(array(
            '_id' => myMongoId($_id)
        ));
        
        if ($this->checkPluginNameExist($name) && $oldPluginInfo['name'] != $name) {
            return $this->msg(false, '插件名称已经存在');
        }
        
        $datas = array();
        $datas['name'] = $name;
        $datas['desc'] = $desc;
        $datas['xtype'] = $xtype;
        
        $this->_plugin->update(array(
            '_id' => myMongoId($_id)
        ), array(
            '$set' => $datas
        ));
        
        return $this->msg(true, '添加插件成功');
    }

    /**
     * 删除系统插件
     *
     * @author young
     * @name 删除系统插件
     * @version 2013.11.27 young
     * @return JsonModel
     */
    public function removePluginAction()
    {
        $_id = $this->params()->fromPost('_id', null);
        try {
            $_id = Json::decode($_id, Json::TYPE_ARRAY);
        } catch (\Exception $e) {
            return $this->msg(false, '无效的json字符串');
        }
        
        if (! is_array($_id)) {
            return $this->msg(false, '请选择你要删除的项');
        }
        foreach ($_id as $row) {
            $this->_plugin->remove(array(
                '_id' => myMongoId($row)
            ));
        }
        return $this->msg(true, '删除插件成功');
    }

    /**
     * 检查系统插件是否存在
     *
     * @param string $info            
     * @return bool True/False
     */
    private function checkPluginNameExist($info)
    {
        $check = $this->_plugin->count(array(
            'name' => $info
        ));
        if ($check > 0) {
            return true;
        }
        return false;
    }
}
