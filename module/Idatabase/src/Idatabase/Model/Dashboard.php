<?php
namespace Idatabase\Model;

use My\Common\Model\Mongo;
use My\Common\Stdlib\SplPriorityQueue;

class Dashboard extends Mongo
{

    protected $collection = IDATABASE_DASHBOARD;
    
    private $_collection;
    
    private $_project;
    
    private $_mapping;
    
    private $_statistic;
    
    public function init()
    {
        $this->_collection = new Collection($this->config);
        $this->_project = new Project($this->config);
        $this->_mapping = new Mapping($this->config);
        $this->_statistic = new Statistic($this->config);
    }

    /**
     * 根据集合的名称获取集合的_id
     *
     * @param string $project_id         
     * @throws \Exception or string
     */
    public function getAllStatisticsByProject($project_id)
    {
        $dashboard = new SplPriorityQueue();
        $cursor = $this->_collection->find(array(
            'project_id' => $project_id
        ));
        while ($cursor->hasNext()) {
            $row = $cursor->getNext();
            $collection_id = $row['_id']->__toString();
            $statisticInfos = $this->_statistic->findAll(array(
                'collection_id' => $collection_id
            ));
            if (! empty($statisticInfos)) {
                foreach ($statisticInfos as $statisticInfos) {
                    $dashboard->insert($statisticInfos, $statisticInfos['priority']);
                }
            }
        }
        
        $statistics = array();
        if (! $dashboard->isEmpty()) {
            $dashboard->top();
            while ($dashboard->valid()) {
                $statistics[] = $dashboard->current();
                $dashboard->next();
            }
        }
        return $statistics;
    }
    
}