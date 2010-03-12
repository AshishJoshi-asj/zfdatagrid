<?php

class IndexController extends Zend_Controller_Action
{
    public function indexAction()
    {
//        $q = Doctrine_Query::create()->select('co.code AS code, co.name AS country_name, co.continent AS poop, ci.name AS city_name')
//                                     ->from('Model_Country AS co, co.City AS ci');
                                     
//        $q = Doctrine_Query::create()->select('co.code AS code, co.name AS country_name, co.continent AS poop, ci.name AS city_name')
//                                     ->from('Model_Country AS co')
//                                     ->leftJoin('co.City AS ci');
                                     
//        $q = Doctrine_Query::create()->from('Model_Country AS co')
//                                     ->leftJoin('co.City AS ci');

        $q = Doctrine_Query::create()->from('Model_Country');
        
//        $q = Doctrine_Query::create()->select('code AS code, name AS country_name, continent AS poop')
//                                     ->from('Model_Country');
        
        $grid = $this->_getGrid($q);
        
        $this->view->grid = $grid->deploy();
    }
    
    public function crudAction()
    {
        $q = Doctrine_Query::create()->from('Model_Country');
        $grid = $this->_getGrid($q);
        
        $form = new Bvb_Grid_Form();
        $form->setAdd(1)->setEdit(1)->setDelete(1)->setAddButton(1);
        
        $grid->setForm($form);
        
        $this->view->grid = $grid->deploy();
    }
    
    public function jqgridAction()
    {
        $q = Doctrine_Query::create()->from('Model_Country');
        $grid = $this->_getJqGrid($q);
        
    }
    
    protected function _getJqGrid($query)
    {
        $config = new Zend_Config_Ini(AP . '/configs/grid.ini', AE);
        $grid = Bvb_Grid_Data::factory('Bvb_Grid_Deploy_JqGrid', $config);
        
        
    }
    
    /**
     * 
     * @param Doctrine_Query $query
     * @return Bvb_Grid_Data
     */
    protected function _getGrid($query)
    {
        $config = new Zend_Config_Ini(AP . '/configs/grid.ini', AE);
        $grid = Bvb_Grid_Data::factory('Bvb_Grid_Deploy_Table', $config);
        
        $grid->setSource(new Bvb_Grid_Source_Doctrine($query));
        
        $grid->setEscapeOutput(false);
        $grid->setGridColumns(array('code', 'name', 'continent'));
        $grid->updateColumn('name', array('title' => 'Country'));
        
        $grid->imagesUrl = '/images/';
        $grid->setDetailColumns();
        //$grid->addTemplateDir('My/Template/Table', 'My_Template_Table', 'table');
        //$grid->addFormatterDir('My/Formatter', 'My_Formatter');
        //$grid->cache = array('use' => 0, 'instance' => Zend_Registry::get('cache'), 'tag' => 'grid');
        
        return $grid;
    }
}
