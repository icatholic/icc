<?php
namespace My\Common\Cache;

use Zend\Cache\StorageFactory;
use Zend\Cache\Storage\Adapter\AbstractAdapter;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManagerInterface;

class ListenerAggregate implements ListenerAggregateInterface
{

    protected $cache;

    private $_key = null;

    private $_cache_result = '';
    
    const PRE = 'cache.pre';
    
    const POST = 'cache.post';

    protected $listeners = array();

    public function __construct(AbstractAdapter $cache)
    {
        $this->cache = $cache;
    }

    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(static::PRE, array(
            $this,
            'load'
        ), 100);
        $this->listeners[] = $events->attach(static::POST, array(
            $this,
            'save'
        ), - 100);
    }

    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    private function setKey(EventInterface $e)
    {
        $params = $e->getParams();
        if (is_array($params) && array_key_exists('__RESULT__', $params)) {
            $this->_cache_result = $params['__RESULT__'];
            unset($params['__RESULT__']);
        }
        $this->_key = crc32(get_class($e->getTarget()) . '-' . json_encode($params));
    }

    /**
     *
     * @method 生成缓存key
     * @param EventInterface $e            
     * @return number
     */
    private function getKey(EventInterface $e)
    {
        $this->setKey($e);
        return $this->_key;
    }

    /**
     *
     * @method 处理缓存
     * @param EventInterface $e            
     * @return string
     */
    public function load(EventInterface $e)
    {
        if (NULL !== ($content = $this->cache->getItem($this->getKey($e)))) {
            $e->stopPropagation(true);
            return $content;
        }
    }

    /**
     *
     * @method 保存缓存
     * @param EventInterface $e            
     * @param string $content            
     */
    public function save(EventInterface $e)
    {
        $this->cache->setItem($this->getKey($e), $this->_cache_result);
    }
}