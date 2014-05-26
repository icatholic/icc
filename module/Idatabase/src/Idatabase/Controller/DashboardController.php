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

    private $_rshData = array();

    public function init()
    {
        $this->_project_id = isset($_REQUEST['__PROJECT_ID__']) ? trim($_REQUEST['__PROJECT_ID__']) : '';
        $this->_dashboard = $this->model('Idatabase\Model\Dashboard');
        $this->_collection = $this->model('Idatabase\Model\Collection');
        $this->_statistic = $this->model('Idatabase\Model\Statistic');
        $this->_mapping = $this->model('Idatabase\Model\Mapping');
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
                $rst = mapReduce($out, $dataModel, $statisticInfo, $query, 'reduce');
                
                // 统计完成替换里面的关联变量信息
                $rstModel = $this->collection(iCollectionName($out));
                $result = $rstModel->findAll(array());
                $map = array(
                    '_id' => $statisticInfo['xAxisField']
                );
                $result = $this->replaceRshData($result, $map);
                
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
    private function dealRshData($collection_id)
    {
        foreach ($this->_rshCollection as $_id => $detail) {
            $_id = $this->getCollectionIdByAlias($_id);
            $collectionName = 'idatabase_collection_' . $_id;
            $model = $this->collection($collectionName);
            $cursor = $model->find(array(), array(
                $detail['rshCollectionKeyField'] => true,
                $detail['rshCollectionValueField'] => true
            ));
            
            $datas = array();
            while ($cursor->hasNext()) {
                $row = $cursor->getNext();
                $key = $row[$detail['rshCollectionValueField']];
                $value = isset($row[$detail['rshCollectionKeyField']]) ? $row[$detail['rshCollectionKeyField']] : '';
                if ($key instanceof \MongoId) {
                    $key = $key->__toString();
                }
                if (! empty($key)) {
                    $datas[$key] = $value;
                }
            }
            $this->_rshData[$detail['collectionField']] = $datas;
        }
    }
}
