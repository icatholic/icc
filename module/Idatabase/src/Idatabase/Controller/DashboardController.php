<?php
/**
 * iDatabase仪表盘控制，显示宏观统计视图
 *
 * @author young 
 * @version 2014.02.10
 * 
 */
namespace Idatabase\Controller;

use My\Common\Controller\Action;
use My\Common\Queue;
use Zend\Json\Json;

class DashboardController extends Action
{

    private $_dashboard;

    private $_statistic;

    private $_project;

    private $_collection;

    private $_project_id;

    private $_mapping;

    private $_structure;

    private $_rshData = array();

    public function init()
    {
        $this->_project_id = isset($_REQUEST['__PROJECT_ID__']) ? trim($_REQUEST['__PROJECT_ID__']) : '';
        $this->_dashboard = $this->model('Idatabase\Model\Dashboard');
        $this->_collection = $this->model('Idatabase\Model\Collection');
        $this->_statistic = $this->model('Idatabase\Model\Statistic');
        $this->_mapping = $this->model('Idatabase\Model\Mapping');
        $this->_structure = $this->model('Idatabase\Model\Structure');
    }

    /**
     * IDatabase仪表盘显示界面
     *
     * @author young
     * @name IDatabase仪表盘显示界面
     * @version 2013.11.11 young
     */
    public function indexAction()
    {
        $rst = array();
        $statistics = $this->_dashboard->getAllStatisticsByProject($this->_project_id);
        foreach ($statistics as $statistic) {
            if (! empty($statistic['dashboardOut'])) {
                $model = $this->collection($statistic['dashboardOut'], DB_MAPREDUCE, DEFAULT_CLUSTER);
                $model->setNoAppendQuery(true);
                $datas = $model->findAll(array(), array(
                    '$natural' => 1
                ), 0, $statistic['maxShowNumber']);
                $statistic['__DATAS__'] = $datas;
                $rst[] = $statistic;
            }
        }
        echo Json::encode($rst);
        return $this->response;
    }

    /**
     * 逐一统计所有需要统计的脚本信息
     * 脚本执行方法: php index.php dashboard run
     *
     * @throws \Exception
     */
    public function runAction()
    {
        $logError = function ($statisticInfo, $rst)
        {
            $this->_statistic->update(array(
                '_id' => $statisticInfo['_id']
            ), array(
                '$set' => array(
                    'dashboardOut' => '',
                    'dashboardError' => is_string($rst) ? $rst : Json::encode($rst)
                )
            ));
        };
        
        $statistics = $this->_statistic->findAll(array(
            'isDashboard' => true,
            'resultExpireTime' => array(
                '$lte' => new \MongoDate()
            )
        ));
        
        if (empty($statistics)) {
            echo 'empty';
            return $this->response;
        }
        
        foreach ($statistics as $statisticInfo) {
            try {
                if (! empty($statisticInfo['dashboardOut'])) {
                    $oldDashboardOut = $this->collection($statisticInfo['dashboardOut'], DB_MAPREDUCE, DEFAULT_CLUSTER);
                    $oldDashboardOut->physicalDrop();
                }
                
                // 检查是否存在映射关系
                $mapCollection = $this->_mapping->findOne(array(
                    'collection_id' => $statisticInfo['collection_id'],
                    'active' => true
                ));
                if ($mapCollection != null) {
                    $dataModel = $this->collection()->secondary($mapCollection['collection'], $mapCollection['database'], $mapCollection['cluster']);
                } else {
                    $dataModel = $this->collection()->secondary(iCollectionName($statisticInfo['collection_id']));
                }
                
                $query = array();
                if (! empty($statisticInfo['dashboardQuery'])) {
                    $query['$and'][] = $statisticInfo['dashboardQuery'];
                }
                $query['$and'][] = array(
                    '__CREATE_TIME__' => array(
                        '$gte' => new \MongoDate(time() - $statisticInfo['statisticPeriod'])
                    )
                );
                
                $out = 'dashboard_' . $statisticInfo['_id']->__toString();
                $rst = mapReduce($out, $dataModel, $statisticInfo, $query, 'replace');
                
                if ($rst instanceof \MongoCollection) {
                    $outCollectionName = $rst->getName(); // 输出集合名称
                    $this->_statistic->update(array(
                        '_id' => $statisticInfo['_id']
                    ), array(
                        '$set' => array(
                            'dashboardOut' => $outCollectionName,
                            'lastExecuteTime' => new \MongoDate(),
                            'resultExpireTime' => new \MongoDate(time() + $statisticInfo['interval'])
                        )
                    ));
                } else {
                    $logError($statisticInfo, $rst);
                }
                
                // 替换统计结果中的数据为人可读数据开始
                if (isset($statisticInfo['xAxisType']) && $statisticInfo['xAxisType'] === 'value') {
                    $rshDatas = $this->dealRshData($statisticInfo['collection_id'], $statisticInfo['xAxisField']);
                    if (! empty($rshDatas)) {
                        $rstModel = $this->collection($out, DB_MAPREDUCE, DEFAULT_CLUSTER);
                        $rstModel->setNoAppendQuery(true);
                        $tmpModel = $this->qw($out . '_tmp', DB_MAPREDUCE, DEFAULT_CLUSTER);
                        $tmpModel->setNoAppendQuery(true);
                        while ($cursor->hasNext()) {
                            $row = $cursor->getNext();
                            $_id = $row['_id'];
                            $tmpModel->insert(array(
                                '_id' => isset($rshDatas[$_id]) ? $rshDatas[$_id] : $_id,
                                'value' => $row['value']
                            ));
                        }
                        $rstModel->physicalDrop();
                        $tmpModel->copyTo($out);
                        $tmpModel->physicalDrop();
                    }
                }
                // 替换统计结果中的数据为人可读数据结束
            } catch (\Exception $e) {
                $logError($statisticInfo, $e->getMessage());
            }
        }
        
        echo 'OK';
        return $this->response;
    }

    /**
     * 处理数据中的关联数据
     */
    private function dealRshData($collection_id, $field)
    {
        try {
            $rshData = array();
            $rsh = $this->_structure->getRshFields($collection_id);
            if (! empty($rsh) && isset($rsh[$field])) {
                $rshCollection = $this->getCollectionIdByAlias($rsh[$field]);
                // 获取被关联集合的结构
                $rshKeyValue = $this->_structure->getComboboxKeyValueField($rshCollection);
                $model = $this->secondary(iCollectionName($rshCollection));
                $cursor = $model->find(array(), array(
                    $rshKeyValue['rshCollectionKeyField'] => true,
                    $rshKeyValue['rshCollectionValueField'] => true
                ));
                
                while ($cursor->hasNext()) {
                    $row = $cursor->getNext();
                    $key = $row[$rshKeyValue['rshCollectionValueField']];
                    $value = isset($row[$rshKeyValue['rshCollectionKeyField']]) ? $row[$rshKeyValue['rshCollectionKeyField']] : '';
                    if ($key instanceof \MongoId) {
                        $key = $key->__toString();
                    }
                    if (! empty($key)) {
                        $rshData[$key] = $value;
                    }
                }
            }
            
            return $rshData;
        } catch (\Exception $e) {
            fb(exceptionMsg($e), 'LOG');
        }
    }
}
