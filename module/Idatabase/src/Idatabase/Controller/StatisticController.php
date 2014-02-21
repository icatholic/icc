<?php
/**
 * iDatabase项目内数据集合管理
 *
 * @author young 
 * @version 2013.11.19
 * 
 */
namespace Idatabase\Controller;

use My\Common\Controller\Action;
use Zend\Json\Json;

class StatisticController extends Action
{

    private $_collection;

    private $_collection_id;

    private $_project_id;

    private $_statistic;

    private $_statistic_id;

    private $_seriesType = array(
        'column',
        'line',
        'pie'
    );

    public function init()
    {
        $this->_project_id = isset($_REQUEST['__PROJECT_ID__']) ? trim($_REQUEST['__PROJECT_ID__']) : '';
        $this->_collection_id = isset($_REQUEST['__COLLECTION_ID__']) ? trim($_REQUEST['__COLLECTION_ID__']) : '';
        $this->_statistic_id = isset($_REQUEST['__STATISTIC_ID__']) ? trim($_REQUEST['__STATISTIC_ID__']) : '';
        
        if (empty($this->_project_id))
            throw new \Exception('$this->_project_id值未设定');
        
        if (empty($this->_collection_id))
            throw new \Exception('$this->_collection_id值未设定');
        
        $this->_statistic = $this->model('Idatabase\Model\Statistic');
    }

    /**
     * 读取统计列表
     *
     * @author young
     * @name 读取统计列表
     * @version 2014.01.25 young
     */
    public function indexAction()
    {
        $query = array(
            'project_id' => $this->_project_id,
            'collection_id' => $this->_collection_id
        );
        $datas = $this->_statistic->findAll($query);
        return $this->rst($datas, 0, true);
    }

    /**
     * 查询某一条统计信息
     *
     * @author young
     * @name 查询某一条统计信息
     * @version 2014.01.29 young
     */
    public function getAction()
    {
        $cursor = $this->_statistic->find(array(
            '_id' => myMongoId($this->_statistic_id)
        ));
        $datas = iterator_to_array($cursor, false);
        return $this->rst($datas, 0, true);
    }

    /**
     * 添加统计信息
     *
     * @author young
     * @name 添加统计信息
     * @version 2014.01.25 young
     */
    public function addAction()
    {
        $project_id = trim($this->params()->fromPost('__PROJECT_ID__', ''));
        $collection_id = trim($this->params()->fromPost('__COLLECTION_ID__', ''));
        $name = trim($this->params()->fromPost('name', ''));
        $yAxisTitle = trim($this->params()->fromPost('yAxisTitle', '')); // Y轴名称
        $yAxisType = trim($this->params()->fromPost('yAxisType', '')); // Y轴统计方法
        $yAxisField = trim($this->params()->fromPost('yAxisField', '')); // Y轴统计字段
        $xAxisTitle = trim($this->params()->fromPost('xAxisTitle', ''));
        $xAxisType = trim($this->params()->fromPost('xAxisType', ''));
        $xAxisField = trim($this->params()->fromPost('xAxisField', ''));
        $seriesType = trim($this->params()->fromPost('seriesType', ''));
        $seriesField = trim($this->params()->fromPost('seriesField', '')); // 用于pie
        $maxShowNumber = intval($this->params()->fromPost('maxShowNumber', 100)); // 显示最大数量，防止饼状图太多
        $isDashboard = filter_var($this->params()->fromPost('isDashboard', null), FILTER_VALIDATE_BOOLEAN); // 是否显示在控制面板
        $dashboardQuery = trim($this->params()->fromPost('dashboardQuery', '')); // 控制面板附加查询条件
        $statisticPeriod = intval($this->params()->fromPost('statisticPeriod', 24 * 3600)); // 控制面板显示周期
        $colspan = intval($this->params()->fromPost('colspan', 1)); // 行显示是否合并
        $priority = intval($this->params()->fromPost('priority', 0)); // 优先级
        $interval = intval($this->params()->fromPost('interval', 3600)); // 统计执行间隔
        
        if ($name == null) {
            return $this->msg(false, '请填写统计名称');
        }
        
        if ($interval < 300) {
            return $this->msg(false, '统计时间的间隔不得少于300秒');
        }
        
        if (! in_array($seriesType, $this->_seriesType, true)) {
            return $this->msg(false, '请设定统计图表类型');
        }
        
        if ($seriesType !== 'pie') {
            if (empty($yAxisTitle)) {
                return $this->msg(false, '请设定Y轴统计名称');
            }
            
            if (empty($yAxisType)) {
                return $this->msg(false, '请设定Y轴统计类型');
            }
            
            if (empty($yAxisField)) {
                return $this->msg(false, '请设定Y轴统计字段');
            }
            
            if (empty($xAxisTitle)) {
                return $this->msg(false, '请设定X轴统计名称');
            }
            
            if (empty($xAxisType)) {
                return $this->msg(false, '请设定X轴统计类型');
            }
            
            if (empty($xAxisField)) {
                return $this->msg(false, '请设定X轴统计字段');
            }
        } else {
            if (empty($seriesField)) {
                return $this->msg(false, '请设定饼形图统计属性');
            }
        }
        
        if ($dashboardQuery !== '') {
            if (isJson($dashboardQuery)) {
                try {
                    $dashboardQuery = Json::decode($dashboardQuery, Json::TYPE_ARRAY);
                } catch (\Exception $e) {
                    return $this->msg(false, '统计条件的json格式错误');
                }
            } else {
                return $this->msg(false, '统计条件的json格式错误');
            }
        }
        
        $datas = array();
        $datas['project_id'] = $project_id;
        $datas['collection_id'] = $collection_id;
        $datas['name'] = $name;
        $datas['yAxisTitle'] = $yAxisTitle; // title string
        $datas['yAxisType'] = $yAxisType; // [Numeric]
        $datas['yAxisField'] = $yAxisField; // array()
        $datas['xAxisTitle'] = $xAxisTitle; // title string
        $datas['xAxisType'] = $xAxisType; // [Category|Time]
        $datas['xAxisField'] = $xAxisField; // array()
        $datas['seriesType'] = $seriesType; // [line|column|pie]
        $datas['seriesField'] = $seriesField; // pie
        
        $datas['seriesXField'] = $xAxisField; // 用于x轴显示
        $datas['seriesYField'] = $yAxisField; // 用于y轴显示
        $datas['maxShowNumber'] = $maxShowNumber;
        
        $datas['isDashboard'] = $isDashboard;
        $datas['dashboardQuery'] = $dashboardQuery;
        $datas['statisticPeriod'] = $statisticPeriod;
        $datas['colspan'] = $colspan;
        $datas['priority'] = $priority;
        $datas['interval'] = $interval;
        $datas['dashboardOut'] = '';
        $datas['lastExecuteTime'] = new \MongoDate(0);
        $datas['resultExpireTime'] = new \MongoDate(0 + $interval);
        $datas['isRunning'] = false;
        
        $this->_statistic->insert($datas);
        return $this->msg(true, '添加统计成功');
    }

    /**
     * 编辑统计信息
     *
     * @author young
     * @name 编辑统计信息
     * @version 2014.01.25 young
     */
    public function editAction()
    {
        $_id = trim($this->params()->fromPost('_id', ''));
        $project_id = trim($this->params()->fromPost('__PROJECT_ID__', ''));
        $collection_id = trim($this->params()->fromPost('__COLLECTION_ID__', ''));
        $name = trim($this->params()->fromPost('name', ''));
        $yAxisTitle = trim($this->params()->fromPost('yAxisTitle', '')); // Y轴名称
        $yAxisType = trim($this->params()->fromPost('yAxisType', '')); // Y轴统计方法
        $yAxisField = trim($this->params()->fromPost('yAxisField', '')); // Y轴统计字段
        $xAxisTitle = trim($this->params()->fromPost('xAxisTitle', ''));
        $xAxisType = trim($this->params()->fromPost('xAxisType', ''));
        $xAxisField = trim($this->params()->fromPost('xAxisField', ''));
        $seriesType = trim($this->params()->fromPost('seriesType', ''));
        $seriesField = trim($this->params()->fromPost('seriesField', '')); // 用于pie
        $maxShowNumber = intval($this->params()->fromPost('maxShowNumber', 100)); // 显示最大数量，防止饼状图太多
        $isDashboard = filter_var($this->params()->fromPost('isDashboard', null), FILTER_VALIDATE_BOOLEAN); // 是否显示在控制面板
        $dashboardQuery = trim($this->params()->fromPost('dashboardQuery', '')); // 控制面板附加查询条件
        $statisticPeriod = intval($this->params()->fromPost('statisticPeriod', 24 * 3600)); // 控制面板显示周期
        $colspan = intval($this->params()->fromPost('colspan', 1)); // 行显示是否合并
        $priority = intval($this->params()->fromPost('priority', 0)); // 优先级
        $interval = intval($this->params()->fromPost('interval', 3600)); // 统计执行间隔
        
        if ($name == null) {
            return $this->msg(false, '请填写统计名称');
        }
        
        if ($interval < 300) {
            return $this->msg(false, '统计时间的间隔不得少于300秒');
        }
        
        if (! in_array($seriesType, $this->_seriesType, true)) {
            return $this->msg(false, '请设定统计图表类型');
        }
        
        if ($seriesType !== 'pie') {
            if (empty($yAxisTitle)) {
                return $this->msg(false, '请设定Y轴统计名称');
            }
            
            if (empty($yAxisType)) {
                return $this->msg(false, '请设定Y轴统计类型');
            }
            
            if (empty($yAxisField)) {
                return $this->msg(false, '请设定Y轴统计字段');
            }
            
            if (empty($xAxisTitle)) {
                return $this->msg(false, '请设定X轴统计名称');
            }
            
            if (empty($xAxisType)) {
                return $this->msg(false, '请设定X轴统计类型');
            }
            
            if (empty($xAxisField)) {
                return $this->msg(false, '请设定X轴统计字段');
            }
        } else {
            if (empty($seriesField)) {
                return $this->msg(false, '请设定饼形图统计属性');
            }
        }
        
        if ($dashboardQuery !== '') {
            if (isJson($dashboardQuery)) {
                try {
                    $dashboardQuery = Json::decode($dashboardQuery, Json::TYPE_ARRAY);
                } catch (\Exception $e) {
                    return $this->msg(false, '统计条件的json格式错误');
                }
            } else {
                return $this->msg(false, '统计条件的json格式错误');
            }
        }
        
        $datas = array();
        $datas['project_id'] = $project_id;
        $datas['collection_id'] = $collection_id;
        $datas['name'] = $name;
        $datas['yAxisTitle'] = $yAxisTitle; // title string
        $datas['yAxisType'] = $yAxisType; // [Numeric]
        $datas['yAxisField'] = $yAxisField; // array()
        $datas['xAxisTitle'] = $xAxisTitle; // title string
        $datas['xAxisType'] = $xAxisType; // [Category|Time]
        $datas['xAxisField'] = $xAxisField; // array()
        $datas['seriesType'] = $seriesType; // [line|column|pie]
        $datas['seriesField'] = $seriesField; // pie
        
        $datas['seriesXField'] = $xAxisField; // 用于x轴显示
        $datas['seriesYField'] = $yAxisField; // 用于y轴显示
        $datas['maxShowNumber'] = $maxShowNumber;
        
        $datas['isDashboard'] = $isDashboard;
        $datas['dashboardQuery'] = $dashboardQuery;
        $datas['statisticPeriod'] = $statisticPeriod;
        $datas['colspan'] = $colspan;
        $datas['priority'] = $priority;
        $datas['interval'] = $interval;
        $datas['lastExecuteTime'] = new \MongoDate(0);
        $datas['resultExpireTime'] = new \MongoDate(0 + $interval);
        $datas['isRunning'] = false;
        
        $this->_statistic->update(array(
            '_id' => myMongoId($_id)
        ), array(
            '$set' => $datas
        ));
        return $this->msg(true, '编辑统计成功');
    }

    /**
     * 批量编辑统计信息
     *
     * @author young
     * @name 批量编辑统计信息
     * @version 2014.01.26 young
     */
    public function saveAction()
    {
        return $this->msg(false, '本功能不支持批量编辑');
    }

    /**
     * 删除统计信息
     *
     * @author young
     * @name 删除统计信息
     * @version 2014.01.25 young
     */
    public function removeAction()
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
            $this->_statistic->remove(array(
                '_id' => myMongoId($row),
                'project_id' => $this->_project_id,
                'collection_id' => $this->_collection_id
            ));
        }
        return $this->msg(true, '删除统计信息成功');
    }
}
