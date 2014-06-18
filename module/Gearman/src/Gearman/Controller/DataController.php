<?php
/**
 * Gearman方式同步data数据处理插件
 *
 * @author young 
 * @version 2014.06.16
 * 
 */
namespace Gearman\Controller;

use My\Common\Controller\Action;

class DataController extends Action
{

    private $_worker;

    private $_data;

    public function init()
    {
        $this->_worker = $this->gearman()->worker();
        $this->_data = $this->model('Idatabase\Model\Data');
    }

    /**
     * 导出数据
     */
    public function exportAction()
    {
        try {
            $cache = $this->cache();
            $this->_worker->addFunction("dataExport", function (\GearmanJob $job) use($cache)
            {
                $job->handle();
                $workload = $job->workload();
                $exportKey = md5($workload);
                $params = unserialize($workload);
                $scope = $params['scope'];
                $collection_id = $params['collection_id'];
                $query = $params['query'];
                $fields = $params['fields'];
                $this->_data->setCollection(iCollectionName($collection_id));
                $this->_data->setReadPreference(\MongoClient::RP_SECONDARY_PREFERRED);
                $cursor = $this->_data->find($query, $fields);
                $datas = iterator_to_array($cursor, false);
                // 在导出数据的情况下，将关联数据显示为关联集合的显示字段数据
                
                foreach ($scope->_rshCollection as $_id => $detail) {
                    $_id = $this->getCollectionIdByAlias($_id);
                    $model = $this->collection()->secondary(iCollectionName($_id));
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
                    $rshData[$detail['collectionField']] = $datas;
                }
                
                // 结束
                convertToPureArray($datas);
                array_walk($datas, function (&$value, $key) use($rshData)
                {
                    ksort($value);
                    array_walk($value, function (&$cell, $field)
                    {
                        if (isset($rshData[$field])) {
                            $cell = isset($rshData[$field][$cell]) ? $rshData[$field][$cell] : '';
                        }
                    });
                });
                
                $excel = array(
                    'title' => array_values($scope->_title),
                    'result' => $datas
                );
                arrayToExcel($excel);
            });
            
            while ($this->_worker->work()) {
                if ($this->_worker->returnCode() != GEARMAN_SUCCESS) {
                    echo "return_code: " . $this->_worker->returnCode() . "\n";
                }
            }
            
            return $this->response;
        } catch (\Exception $e) {
            var_dump(exceptionMsg($e));
            $job->sendException(exceptionMsg($e));
            return false;
        }
    }
}
