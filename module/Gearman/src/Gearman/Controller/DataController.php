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

    private $_mapping;

    public function init()
    {
        $this->_worker = $this->gearman()->worker();
        $this->_data = $this->model('Idatabase\Model\Data');
        $this->_collection = $this->model('Idatabase\Model\Collection');
        $this->_mapping = $this->model('Idatabase\Model\Mapping');
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
                $exportGearmanKey = md5($scope->_collection_id . serialize($query));
                
                // 获取映射关系，初始化数据集合model
                $mapCollection = $this->_mapping->findOne(array(
                    'project_id' => $scope->_project_id,
                    'collection_id' => $scope->_collection_id,
                    'active' => true
                ));
                if ($mapCollection != null) {
                    $this->_data->setCollection($mapCollection['collection'], $mapCollection['database'], $mapCollection['cluster']);
                } else {
                    $this->_data->setCollection(iCollectionName($collection_id));
                }
                
                $this->_data->setReadPreference(\MongoClient::RP_SECONDARY_PREFERRED);
                $cursor = $this->_data->find($query, $fields);
                $excelDatas = array();
                // 保持拥有全部的字段名，不存在错乱的想象
                $fieldNames = array_keys($fields);
                while ($cursor->hasNext()) {
                    $row = $cursor->getNext();
                    $tmp = array();
                    foreach ($fieldNames as $key) {
                        $tmp[$key] = isset($row[$key]) ? $row[$key] : '';
                    }
                    $excelDatas[] = $tmp;
                    unset($tmp);
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
                convertToPureArray($excelDatas);
                array_walk($excelDatas, function (&$value, $key) use($rshData, $fields)
                {
                    $loop = function ($value, $tmp)
                    {
                        $new = $value;
                        $len = count($tmp);
                        for ($i = 0; $i < $len; $i ++) {
                            if (isset($new[$tmp[$i]])) {
                                $new = $new[$tmp[$i]];
                            } else {
                                return '';
                            }
                        }
                        return $new;
                    };
                    
                    foreach ($fields as $k => $v) {
                        if (strpos($k, '.') !== false) {
                            $tmp = explode('.', $k);
                            $value[$k] = $loop($value, $tmp);
                        }
                    }
                    
                    ksort($value);
                    array_walk($value, function (&$cell, $field) use($rshData)
                    {
                        if (isset($rshData[$field])) {
                            $cell = isset($rshData[$field][$cell]) ? $rshData[$field][$cell] : '';
                        }
                    });
                });
                
                $title = array();
                ksort($fields);
                foreach (array_keys($fields) as $field) {
                    $title[] = isset($scope->_title[$field]) ? $scope->_title[$field] : $field;
                }
                
                $excel = array(
                    'title' => $title,
                    'result' => $excelDatas
                );
                
                $temp = tempnam(sys_get_temp_dir(), 'gearman_export_');
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
     * 导入数据任务
     */
    public function importAction()
    {
        $cache = $this->cache();
        $this->_worker->addFunction("dataImport", function (\GearmanJob $job) use($cache)
        {
            $job->handle();
            $workload = $job->workload();
            $params = unserialize($workload);
            $key = $params['key'];
            $collection_id = $params['collection_id'];
            // 加载缓存数据
            $csv = $cache->load($key);
            
            // 将检测到的内容格式转换为utf-8
            $fromEncode = mb_detect_encoding($csv);
            $toEncode = 'utf-8';
            $csv = mb_convert_encoding($csv, $toEncode, $fromEncode);
            
            // 获取导入字段
            $arr = str_getcsv($csv);
            $fields = join(',', array_shift($arr));
            
            // 创建临时文件用于导入csv使用
            $temp = tmpfile();
            fputcsv($temp, $arr);
            
            // 执行导入脚本
            $host = '10.0.0.31';
            $port = '57017';
            $dbName = 'ICCv1';
            $fp = popen("/home/mongodb/bin/mongoimport -host {$host} --port {$port} -d {$dbName} -c idatabase_collection_{$collection_id} -f {$fields} --ignoreBlanks --file {$temp} --type csv", 'r');
            pclose($fp);
            
            // 增加一些系统默认参数
            $model = $this->_data->setCollection(iCollectionName($collection_id));
            $model->update(array(), array(
                '$set' => array(
                    '__REMOVED__' => false,
                    '__CREATE_TIME__' => new MongoDate(),
                    '__MODIFY_TIME__' => new MongoDate()
                )
            ));
            $job->sendComplete('complete');
            return true;
        });
        
        while ($this->_worker->work()) {
            if ($this->_worker->returnCode() != GEARMAN_SUCCESS) {
                echo "return_code: " . $this->_worker->returnCode() . "\n";
            }
        }
        return $this->response;
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
