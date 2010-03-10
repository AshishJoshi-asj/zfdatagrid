<?php

class IndexController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $q = Doctrine_Query::create()->from('Model_Country co')
                                     ->leftJoin('co.City ci');
                                     
        $grid = $this->_getGrid();
        $grid->setSource(new Bvb_Grid_Source_Doctrine($q));
        
        $this->view->grid = $grid->deploy();
    }
    
    protected function _getGrid()
    {
        $config = new Zend_Config_Ini(AP . '/configs/grid.ini', AE);
        $grid = Bvb_Grid_Data::factory('Bvb_Grid_Deploy_Table', $config);
        
        $grid->setEscapeOutput(false);
        $grid->setGridColumns(array('code', 'name', 'continent'));
        //$grid->addTemplateDir('My/Template/Table', 'My_Template_Table', 'table');
        //$grid->addFormatterDir('My/Formatter', 'My_Formatter');
        $grid->imagesUrl = '/images/';
        //$grid->cache = array('use' => 0, 'instance' => Zend_Registry::get('cache'), 'tag' => 'grid');
        
        return $grid;
    }
}
