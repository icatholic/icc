<?php
namespace Application\Controller;

use Zend\View\Model\ViewModel;
use My\Common\Controller\Action;

class GearmanWorkerController extends Action
{

    private $_data;

    public function init()
    {
        $this->_data = $this->model('Idatabase\Model\Data');
        //$this->_data->setCollection(iCollectionName($_id));
    }

    /**
     * 将所有的map reduce任务放在gearman中完成,防止因为数据量过大，统计时间过长导致的问题。
     */
    public function mrAction()
    {
        
    }

    /**
     * 将所有的excel表格生成任务放在work中完成
     */
    public function exportAction()
    {
        
    }
}