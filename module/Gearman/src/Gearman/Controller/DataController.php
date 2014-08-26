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

    public function init()
    {
        $this->_worker = $this->gearman()->worker();
        $this->_data = $this->model('Idatabase\Model\Data');
        $this->_collection = $this->model('Idatabase\Model\Collection');
        $this->_mapping = $this->model('Idatabase\Model\Mapping');
        $this->_file = $this->model('Idatabase\Model\File');
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
                
                if (count($excel['result']) > 5000) {
                    arrayToCVS($excel, null, $temp);
                } else {
                    arrayToExcel($excel, $exportKey, $temp);
                }
                
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
        ini_set("auto_detect_line_endings", true);
        ini_set('memory_limit', '4096M');
        $cache = $this->cache();
        $this->_worker->addFunction("dataImport", function (\GearmanJob $job) use($cache)
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
            $this->_data->setCollection(iCollectionName($collection_id));
            
            if ($physicalDrop) {
                // 导出数据为bson
                $exportCmd = $mongoBin . "mongodump --host {$host} --port {$port} -d $dbName -c idatabase_collection_{$collection_id} -o {$out}";
                $fp = popen($exportCmd, 'r');
                pclose($fp);
                
                // echo "\n";
                
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
            
            // 获取导入字段
            $arr = $this->csv2arr($csv);
            if (empty($arr)) {
                echo '$arr is empty';
                $job->sendFail();
                return false;
            }
            
            $title = array_shift($arr);
            array_walk($title, function (&$items)
            {
                $items = trim($items);
            });
            $fields = join(',', $title);
            
            // 创建临时文件用于导入csv使用
            $tempNoIconv = tempnam(sys_get_temp_dir(), 'csv_import_');
            $temp = tempnam(sys_get_temp_dir(), 'csv_import_');
            $handle = fopen($tempNoIconv, 'w');
            foreach ($arr as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
            
            //进行编码转换,强制转化为UTF-8
            $iconvCmd = $iconvBin.'iconv -t UTF-8 ' . $tempNoIconv . ' -o ' . $temp;
            $fp = popen($iconvCmd,'r');
            pclose($fp);
            unlink($tempNoIconv);
            
            // 执行导入脚本
            $importCmd = $mongoBin . "mongoimport -host {$host} --port {$port} -d {$dbName} -c idatabase_collection_{$collection_id} -f {$fields} --ignoreBlanks --file {$temp} --type csv";
            $fp = popen($importCmd, 'r');
            pclose($fp);
            unlink($temp);
            
            // 增加一些系统默认参数
            $now = new \MongoDate();
            $this->_data->physicalUpdate(array(), array(
                '$set' => array(
                    '__REMOVED__' => false,
                    '__CREATE_TIME__' => $now,
                    '__MODIFY_TIME__' => $now
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

    /**
     * 转化为数组
     *
     * @param string $CsvString            
     * @return array
     */
    private function csv2arr($CsvString)
    {
        $Data = str_getcsv($CsvString, "\n"); // parse the rows
        foreach ($Data as &$Row)
            $Row = str_getcsv($Row, ",");
        return $Data;
    }
}
