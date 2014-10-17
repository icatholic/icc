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

    private $_file;

    private $_structure;

    private $_schema = array();

    private $_fields = array();

    public function init()
    {
        resetTimeMemLimit(0);
        ini_set("auto_detect_line_endings", true);
        
        $this->_worker = $this->gearman()->worker();
        $this->_data = $this->model('Idatabase\Model\Data');
        $this->_collection = $this->model('Idatabase\Model\Collection');
        $this->_mapping = $this->model('Idatabase\Model\Mapping');
        $this->_file = $this->model('Idatabase\Model\File');
        $this->_structure = $this->model('Idatabase\Model\Structure');
        
        $this->_collection->setReadPreference(\MongoClient::RP_SECONDARY);
        $this->_mapping->setReadPreference(\MongoClient::RP_SECONDARY);
        $this->_file->setReadPreference(\MongoClient::RP_SECONDARY);
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
                
                $this->_data->setReadPreference(\MongoClient::RP_SECONDARY);
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
                
                if (count($excel['result']) > 5000) {
                    arrayToCVS($excel, null, $temp);
                    $outType = 'csv';
                } else {
                    arrayToExcel($excel, $exportKey, $temp);
                    $outType = 'xlsx';
                }
                
                $fileInfo = $this->_data->storeBytesToGridFS(file_get_contents($temp), $temp);
                $fileInfo['outType'] = $outType;
                $cache->save($fileInfo, $exportKey, 60);
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
     * 采用bson文件格式的方式导入
     */
    public function importBsonAction()
    {
        $cache = $this->cache();
        $this->_worker->addFunction("bsonImport", function (\GearmanJob $job) use($cache)
        {
            $iconvBin = '/usr/bin/';
            $mongoBin = '/home/mongodb/bin/';
            $host = '10.0.0.31';
            $port = '57017';
            $dbName = 'ICCv1';
            $backupDbName = 'backup';
            $out = sys_get_temp_dir() . '/';
            
            $job->handle();
            $workload = $job->workload();
            $params = unserialize($workload);
            $key = $params['key'];
            $collection_id = $params['collection_id'];
            $physicalDrop = $params['physicalDrop'];
            $this->getSchema($collection_id); // 获取集合的数据结构
            $this->_data->setCollection(iCollectionName($collection_id));
            
            if ($physicalDrop) {
                // 导出数据为bson
                $exportCmd = $mongoBin . "mongodump --host {$host} --port {$port} -d $dbName -c idatabase_collection_{$collection_id} -o {$out}";
                $fp = popen($exportCmd, 'r');
                pclose($fp);

                // 将bson导入到备份数据库
                $bson = $out . $dbName . '/idatabase_collection_' . $collection_id . '.bson';
                $backupCollection = 'bak_' . date("YmdHis") . '_' . $collection_id;
                $restoreCmd = $mongoBin . "mongorestore --host {$host} --port {$port} -d {$backupDbName} -c {$backupCollection} {$bson}";
                $fp = popen($restoreCmd, 'r');
                pclose($fp);
                
                // 删除导出的bson文件
                unlink($bson);
                
                // drop集合数据库
                $this->_data->physicalDrop();
            }
            
            // 加载csv数据
            $csv = $this->_file->getFileFromGridFS($key);
            $this->_file->removeFileFromGridFS($key);
            
            if (empty($csv)) {
                echo '$csv is empty';
                $job->sendFail();
                return false;
            }

            $arr = $this->csv2arr($csv);
            unset($csv);//释放内存
            
            if (empty($arr)) {
                echo '$arr is empty';
                $job->sendFail();
                return false;
            }
            
            $firstRow = array_shift($arr);
            $titles = array();
            foreach ($firstRow as $col => $value) {
                $value = trim($value);
                if (in_array($value, array_keys($this->_schema), true)) {
                    $titles[$col] = $this->_schema[$value];
                } else 
                    if (in_array($value, array_values($this->_schema), true)) {
                        $titles[$col] = $value;
                    }
            }
            
            if (count($titles) == 0) {
                echo '无匹配的标题或者标题字段，请检查导入数据的格式是否正确';
                $job->sendFail();
                return false;
            }
            
            $bson = '';
            $now = new \MongoDate();
            $temp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bson_' . uniqid() . '.bson';
            $fp = fopen($temp, 'w');
            foreach ($arr as $rowNumber => $row) {
                $insertData = array();
                foreach ($titles as $col => $colName) {
                    $insertData[$colName] = formatData($row[$col], $this->_fields[$colName]);
                }
                $insertData['__REMOVED__'] = false;
                $insertData['__CREATE_TIME__'] = $now;
                $insertData['__MODIFY_TIME__'] = $now;
                fwrite($fp, bson_encode($insertData));
            }
            fclose($fp);
            
            // 执行导入脚本
            echo $importCmd = $mongoBin . "mongorestore -host {$host} --port {$port} -d {$dbName} -c idatabase_collection_{$collection_id} $temp";
            $fp = popen($importCmd, 'r');
            pclose($fp);
            unlink($temp);
            
            echo "\ncomplete";
            
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

    /**
     * 转化为数组
     *
     * @param string $CsvString            
     * @return array
     */
    private function csv2arr($csvString)
    {
        $data = str_getcsv($csvString, "\n"); // parse the rows
        foreach ($data as &$row)
            $row = str_getcsv($row, ",");
        return $data;
    }

    /**
     * 获取集合的数据结构
     *
     * @param string $collection_id
     *            获取集合的数据结构
     * @return array
     */
    private function getSchema($collection_id)
    {
        $this->_schema = array();
        $this->_fields = array();
        $cursor = $this->_structure->find(array(
            'collection_id' => $collection_id
        ));
        while ($cursor->hasNext()) {
            $row = $cursor->getNext();
            $this->_schema[$row['label']] = $row['field'];
            $this->_fields[$row['field']] = $row['type'];
        }
        return true;
    }

    /**
     * 根据集合的名称获取集合的_id
     *
     * @param string $name            
     * @throws \Exception or string
     */
    private function getCollectionIdByName($name)
    {
        try {
            new \MongoId($name);
            return $name;
        } catch (\MongoException $ex) {}
        
        $collectionInfo = $this->_collection->findOne(array(
            'project_id' => $this->_project_id,
            'name' => $name
        ));
        
        if ($collectionInfo == null) {
            throw new \Exception('集合名称不存在于指定项目');
        }
        
        return $collectionInfo['_id']->__toString();
    }
}
