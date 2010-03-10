<?php

class IndexController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $q = Doctrine_Query::create()->select('co.code, co.name, co.continent AS poop, ci.name')
                                     ->from('Model_Country co')
                                     ->leftJoin('co.City ci');
                                     
        $grid = $this->_getGrid($q);
        
        $this->view->grid = $grid->deploy();
    }
    
    protected function _getGrid($query)
    {
        $config = new Zend_Config_Ini(AP . '/configs/grid.ini', AE);
        $grid = Bvb_Grid_Data::factory('Bvb_Grid_Deploy_Table', $config);
        
        $grid->setSource(new Bvb_Grid_Source_Doctrine($query));
        
        $grid->setEscapeOutput(false);
        $grid->setGridColumns(array('co_code', 'co_name', 'co_poop', 'ci_name'));
        $grid->updateColumn('ci_name', array('title' => 'City Name'));
        $grid->imagesUrl = '/images/';
        //$grid->addTemplateDir('My/Template/Table', 'My_Template_Table', 'table');
        //$grid->addFormatterDir('My/Formatter', 'My_Formatter');
        //$grid->cache = array('use' => 0, 'instance' => Zend_Registry::get('cache'), 'tag' => 'grid');
        
        return $grid;
    }
}
