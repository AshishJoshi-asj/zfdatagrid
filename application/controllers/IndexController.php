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
    
    protected function _getGrid($query)
    {
        $config = new Zend_Config_Ini(AP . '/configs/grid.ini', AE);
        $grid = Bvb_Grid_Data::factory('Bvb_Grid_Deploy_Table', $config);
        
        $grid->setSource(new Bvb_Grid_Source_Doctrine($query));
        
        $grid->setEscapeOutput(false);
        //$grid->setGridColumns(array('code', 'country_name', 'poop', 'city_name'));
        //$grid->updateColumn('city_name', array('title' => 'City Name'));
        
        //$grid->setGridColumns(array('co_code', 'co_name', 'co_continent', 'ci_name'));
        
        $grid->setGridColumns(array('code', 'name', 'continent'));
        
        //$grid->setGridColumns(array('code', 'country_name', 'poop'));
        
        $grid->imagesUrl = '/images/';
        $grid->setDetailColumns();
        //$grid->addTemplateDir('My/Template/Table', 'My_Template_Table', 'table');
        //$grid->addFormatterDir('My/Formatter', 'My_Formatter');
        //$grid->cache = array('use' => 0, 'instance' => Zend_Registry::get('cache'), 'tag' => 'grid');
        
        return $grid;
    }
}
