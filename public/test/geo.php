<?php
try {
    $options = array();
    $options['connectTimeoutMS'] = 60000;
    $options['socketTimeoutMS'] = 60000;
    $options['w'] = 1;
    $options['wTimeout'] = 60000;
    $connect = new \MongoClient("mongodb://10.0.0.31:57017", $options);
    $connect->setReadPreference(\MongoClient::RP_PRIMARY_PREFERRED);
    
    $db = $connect->selectDB('ICCv1');
    // $collection = new MongoCollection($db, 'idatabase_collection_5372fccc49961910538b4570');
    $collection = new MongoCollection($db, 'idatabase_collection_5372fccc49961910538b4570');
    
    $collection->ensureIndex(array(
        'location' => '2d'
    ));
    // $cursor = $collection->find(array(
    // '$and' => array(
    // 0 => array(
    // '__REMOVED__' => FALSE
    // ),
    // 1 => array(
    // '$and' => array(
    // 0 => array(
    // 'location' => array(
    // '$near' => array(
    // 0 => 123,
    // 1 => 123
    // ),
    // '$maxDistance' => 0.089992800575954
    // )
    // )
    // )
    // )
    // )
    // ));
    
    $cursor = $collection->count(array(
        'location' => array(
            '$near' => array(
                0 => 123,
                1 => 123
            ),
            '$maxDistance' => 0.089992800575954
        ),
        '__REMOVED__' => false
    ));
    var_dump($cursor);
    //var_dump($cursor->count());
    //var_dump(iterator_to_array($cursor, false));
} catch (Exception $e) {
    var_dump($e);
}