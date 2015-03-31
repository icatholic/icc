<?php

/**
 * iDatabase数据管理控制器
 *
 * @author young 
 * @version 2013.11.22
 * 
 */
namespace Idatabase\Controller;

use My\Common\Controller\Action;

class ImportController extends Action
{

    /**
     * 读取当前数据集合的mongocollection实例
     *
     * @var object
     */
    private $_data;

    /**
     * 读取数据属性结构的mongocollection实例
     *
     * @var object
     */
    private $_structure;

    /**
     * 读取集合列表集合的mongocollection实例
     *
     * @var object
     */
    private $_collection;

    /**
     * 当前集合所属项目
     *
     * @var string
     */
    private $_project_id = '';

    /**
     * 当前集合所属集合 集合的alias别名或者_id的__toString()结果
     *
     * @var string
     */
    private $_collection_id = '';

    /**
     * 存储数据的物理集合名称
     *
     * @var string
     */
    private $_collection_name = '';

    /**
     * 存储当前集合的结局结构信息
     *
     * @var array
     */
    private $_schema = null;

    /**
     * 存储查询显示字段列表
     *
     * @var array
     */
    private $_fields = array();

    /**
     * Gearman客户端
     *
     * @var object
     */
    private $_gmClient;

    /**
     * 存储文件
     *
     * @var object
     */
    private $_file;

    /**
     * 初始化函数
     *
     * @see \My\Common\ActionController::init()
     */
    public function init()
    {
        resetTimeMemLimit();
        $this->_project_id = isset($_REQUEST['__PROJECT_ID__']) ? trim($_REQUEST['__PROJECT_ID__']) : '';
        
        if (empty($this->_project_id))
            throw new \Exception('$this->_project_id值未设定');
        
        $this->_collection = $this->model('Idatabase\Model\Collection');
        $this->_collection_id = isset($_REQUEST['__COLLECTION_ID__']) ? trim($_REQUEST['__COLLECTION_ID__']) : '';
        if (empty($this->_collection_id))
            throw new \Exception('$this->_collection_id值未设定');
        
        $this->_collection_id = $this->getCollectionIdByName($this->_collection_id);
        $this->_collection_name = 'idatabase_collection_' . $this->_collection_id;
        
        $this->_data = $this->collection($this->_collection_name);
        $this->_structure = $this->model('Idatabase\Model\Structure');
        $this->_file = $this->model('Idatabase\Model\File');
        
        // 建立gearman客户端连接
        $this->_gmClient = $this->gearman()->client();
        
        $this->getSchema();
    }

    /**
     * 将导入数据脚本放置到gearman中进行，加快页面的响应速度
     */
    public function importCsvJobAction()
    {
        // 大数据量导采用mongoimport直接导入的方式导入数据
        $collection_id = trim($this->params()->fromPost('__COLLECTION_ID__', null));
        $physicalDrop = filter_var($this->params()->fromPost('physicalDrop', false), FILTER_VALIDATE_BOOLEAN);
        
        $upload = $this->params()->fromFiles('import');
        if (empty($collection_id) || empty($upload)) {
            return $this->msg(false, '上传文件或者集合编号不能为空');
        }
        
        $uploadFileNameLower = strtolower($upload['name']);
        
        if (strpos($uploadFileNameLower, '.zip') !== false) {
            $bytes = file_get_contents($upload['tmp_name']);
            $fileInfo = $this->_file->storeBytesToGridFS($bytes, 'import.zip');
            $key = $fileInfo['_id']->__toString();
        } else {
            if (strpos($uploadFileNameLower, '.csv') === false) {
                return $this->msg(false, '请上传csv格式的文件');
            }
            
            $bytes = file_get_contents($upload['tmp_name']);
            if (! detectUTF8($bytes)) {
                return $this->msg(false, '请使用文本编辑器将文件转化为UTF-8格式');
            }
            $fileInfo = $this->_file->storeBytesToGridFS($bytes, 'import.csv');
            $key = $fileInfo['_id']->__toString();
        }
        
        $workload = array();
        $workload['key'] = $key;
        $workload['collection_id'] = $collection_id;
        $workload['physicalDrop'] = $physicalDrop;
        // $jobHandle = $this->_gmClient->doBackground('dataImport', serialize($workload), $key);
        $jobHandle = $this->_gmClient->doBackground('bsonImport', serialize($workload), $key);
        $stat = $this->_gmClient->jobStatus($jobHandle);
        if (isset($stat[0]) && $stat[0]) {
            return $this->msg(true, '数据导入任务已经被受理，请稍后片刻若干分钟，导入时间取决于数据量(1w/s)。');
        } else {
            return $this->msg(false, '任务提交失败');
        }
    }

    /**
     * 导入数据到集合内
     */
    public function importAction()
    {
        try {
            $importSheetName = trim($this->params()->fromPost('sheetName', null));
            $file = $this->params()->fromFiles('import', null);
            
            if ($importSheetName == null) {
                return $this->msg(false, '请设定需要导入的sheet');
            }
            
            if ($file == null) {
                return $this->msg(false, '请上传Excel数据表格文件');
            }
            
            if ($file['error'] === UPLOAD_ERR_OK) {
                $fileName = $file['name'];
                $filePath = $file['tmp_name'];
                
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                switch ($ext) {
                    case 'xlsx':
                        $inputFileType = 'Excel2007';
                        break;
                    default:
                        return $this->msg(false, '很抱歉，您上传的文件格式无法识别,格式要求：*.xlsx');
                }
                
                $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
                $objReader->setReadDataOnly(true);
                $objReader->setLoadSheetsOnly($importSheetName);
                
                $objPHPExcel = $objReader->load($filePath);
                if (! in_array($importSheetName, array_values($objPHPExcel->getSheetNames()))) {
                    return $this->msg(false, 'Sheet:"' . $importSheetName . '",不存在，请检查您导入的Excel表格');
                }
                
                $objPHPExcel->setActiveSheetIndexByName($importSheetName);
                $objActiveSheet = $objPHPExcel->getActiveSheet();
                $sheetData = $objActiveSheet->toArray(null, true, true, true);
                $objPHPExcel->disconnectWorksheets();
                unset($objReader, $objPHPExcel, $objActiveSheet);
                
                if (empty($sheetData)) {
                    return $this->msg(false, '请确认表格中未包含有效数据，请复核');
                }
                
                $firstRow = array_shift($sheetData);
                if (count($firstRow) == 0) {
                    return $this->msg(false, '标题行数据为空');
                }
                
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
                    return $this->msg(false, '无匹配的标题或者标题字段，请检查导入数据的格式是否正确');
                }
                
                array_walk($sheetData, function ($row, $rowNumber) use($titles)
                {
                    $insertData = array();
                    foreach ($titles as $col => $colName) {
                        $insertData[$colName] = formatData($row[$col], $this->_fields[$colName]);
                    }
                    $this->_data->insertByFindAndModify($insertData);
                    unset($insertData);
                });
                
                // 用bson文件代替插入数据
                // $bson = '';
                // foreach ($sheetData as $rowNumber=>$row) {
                // $insertData = array();
                // foreach ($titles as $col => $colName) {
                // $insertData[$colName] = formatData($row[$col], $this->_fields[$colName]);
                // }
                // $bson .= bson_encode($insertData);
                // }
                
                unset($sheetData);
                return $this->msg(true, '导入成功');
            } else {
                return $this->msg(false, '上传文件失败');
            }
        } catch (\Exception $e) {
            fb(exceptionMsg($e), \FirePHP::LOG);
            return $this->msg(false, '导入失败，发生异常');
        }
    }

    /**
     * 获取集合的数据结构
     *
     * @return array
     */
    private function getSchema()
    {
        $this->_schema = array();
        $this->_fields = array();
        $cursor = $this->_structure->find(array(
            'collection_id' => $this->_collection_id
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
