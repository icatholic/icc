<?php
/**
 * 缓存插件，默认使用文本缓存，在集群环境请默认使用memcached缓存
 * 
 * @author Young 
 *
 */
namespace My\Common\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class Cache extends AbstractPlugin
{

    private $_cache;

    private $_key;

    public function __invoke($key = null)
    {
        $this->_cache = $this->getController()
            ->getServiceLocator()
            ->get(CACHE_ADAPTER);
        
        if ($key === null) {
            return $this;
        }
        return $this->load($key);
    }

    public function load($key)
    {
        $this->_key = $key;
        return $this->_cache->getItem($key);
    }

    public function save($datas, $key = null)
    {
        if ($key === null)
            $key = $this->_key;
        
        return $this->_cache->setItem($key, $datas);
    }

    public function remove($key)
    {
        if ($key === null)
            $key = $this->_key;
        return $this->_cache->removeItem($key);
    }
}