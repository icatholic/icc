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

    private $_collection;

    private $_data;

    public function init()
    {
        $this->_worker = $this->gearman()->worker();
        $this->_data = $this->model('Idatabase\Model\Data');
        $this->_collection = $this->model('Idatabase\Model\Collection');
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
                $params = unserialize($workload);
                $scope = $params['scope'];
                $collection_id = $params['collection_id'];
                $query = $params['query'];
                $fields = $params['fields'];
                $exportKey = md5($workload);
                $exportGearmanKey = md5($scope->_collection_id.serialize($query));
                
                $this->_data->setCollection(iCollectionName($collection_id));
                $this->_data->setReadPreference(\MongoClient::RP_SECONDARY_PREFERRED);
                $cursor = $this->_data->find($query, $fields);
                $datas = array();
                
                // 保持拥有全部的字段名，不存在错乱的想象
                $fieldNames = array_keys($fields);
                while ($cursor->hasNext()) {
                    $row = $cursor->getNext();
                    $diff = array_diff(array_keys($row), $fieldNames);
                    array_walk($diff, function ($items, $key) use(&$row)
                    {
                        $row[$items] = '';
                    });
                    $datas[] = $row;
                }
                
                // 在导出数据的情况下，将关联数据显示为关联集合的显示字段数据
                $rshData = array();
                foreach ($scope->_rshCollection as $_id => $detail) {
                    $_id = $this->getCollectionIdByAlias($scope->_project_id, $_id);
                    $model = $this->collection()
                        ->secondary(iCollectionName($_id));
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
                
                $temp = tempnam(sys_get_temp_dir, 'gearman_export_');
                arrayToExcel($excel, $exportKey, $temp);
                $cache->save(file_get_contents($temp), $exportKey, 60);
                unlink($temp);
                $cache->remove($exportGearmanKey);
                $job->sendComplete('complete');
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

    /**
     * 根据集合的名称获取集合的_id
     *
     * @param string $alias            
     * @throws \Exception or string
     */
    private function getCollectionIdByAlias($project_id, $alias)
    {
        try {
            new \MongoId($alias);
            return $alias;
        } catch (\MongoException $ex) {}
        
        $collectionInfo = $this->_collection->findOne(array(
            'project_id' => $project_id,
            'alias' => $alias
        ));
        
        if ($collectionInfo == null) {
            throw new \Exception('集合名称不存在于指定项目');
        }
        
        return $collectionInfo['_id']->__toString();
    }
}
