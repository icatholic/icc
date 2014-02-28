<?php
include "iDatabase.php";
include "Zend/Loader/Autoloader.php";
$autoloader = Zend_Loader_Autoloader::getInstance();

$collectionAlias = 'test_data_type';
$project_id = '52dce281489619e902452b46';
$password = '11111111';
$key_id = '53059145489619c06a3dc01f';


    $obj = new iDatabase($project_id, $password, $key_id);
    //$obj->setDebug(true);
    $obj->setCollection($collectionAlias);
    try {
		var_dump($obj->uploadBytes('1.jpg',file_get_contents('./1.jpg')));
//        $obj->findAll(array());
//         $datas = array(
//             'textfield' => '123'
//         );
//         var_dump($obj->save($datas));
    } catch (Exception $e) {
        var_dump($e);
    }

