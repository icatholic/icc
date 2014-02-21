<?php
namespace My\Common\Factory;

use Traversable;
use Zend\Stdlib\ArrayUtils;
use Zend\Config\Config;
use Zend\ServiceManager\FactoryInterface;

/**
 * $cfg = array(
 * 'options'=>array('key'=>'value'),
 * 'servers'=>array(
 * 'default'=>array(
 * array('server','port')
 * ),
 * 'analyze'=>array(
 * array('server','port')
 * )
 * )
 * );
 *
 * @author Young
 *        
 */
abstract class Mongo
{

    public static function factory($cfg)
    {
        if ($cfg instanceof Traversable) {
            $cfg = ArrayUtils::iteratorToArray($cfg);
        }
        
        if (! is_array($cfg)) {
            throw new \Exception('配置信息未设定');
        }
        
        if (! isset($cfg['cluster']) || empty($cfg['cluster'])) {
            throw new \Exception('配置信息中缺少cluster参数');
        }
        
        $options = array();
        $options['connectTimeoutMS'] = 60000;
        $options['socketTimeoutMS'] = 60000;
        $options['w'] = 1;
        $options['wTimeout'] = 60000;
        
        if (isset($cfg['options']) && ! empty($cfg['options'])) {
            $options = array_merge($options, $cfg['options']);
        }
        
        if (! isset($cfg['cluster']['default']) || empty($cfg['cluster']['default'])) {
            throw new \Exception('配置信息中缺少cluster.default参数');
        }
        
        $cluster = array();
        foreach ($cfg['cluster'] as $clusterName => $clusterInfo) {
            try {
                shuffle($clusterInfo['servers']);
                $dnsString = 'mongodb://' . join(',', $clusterInfo['servers']);
                if (class_exists('\MongoClient')) {
                    $connect = new \MongoClient($dnsString, $options);
                    // $connect->setReadPreference(\MongoClient::RP_PRIMARY_PREFERRED);
                    $cluster[$clusterName]['connect'] = $connect;
                } else {
                    throw new \Exception('请安装PHP的Mongo1.4+版本的扩展');
                }
            } catch (\Exception $e) {
                if ($clusterName == 'default')
                    throw new \Exception('无法与Mongodb建立连接' . $e->getMessage());
                else
                    break;
            }
            
            try {
                if (is_array($clusterInfo['dbs']) && ! empty($clusterInfo['dbs']) && $connect instanceof \MongoClient) {
                    foreach ($clusterInfo['dbs'] as $db) {
                        $cluster[$clusterName]['dbs'][$db] = $connect->selectDB($db);
                    }
                } else {
                    throw new \Exception('请设定cluster.name.dbs');
                }
            } catch (\Exception $e) {
                throw new \Exception('已经建立连接，但是无法访问指定的数据库');
            }
            unset($connect);
        }
        
        return new Config($cluster);
    }
}






