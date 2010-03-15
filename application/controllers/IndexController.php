<?php

class IndexController extends Zend_Controller_Action
{
    public function indexAction()
    {
        /**
         * This is a basic Doctrine_Query example.
         * Alternatively to you could do the following:
         * 
         * <code>
         * $q = new Model_Country();
         * </code>
         * 
         * And will work just the same
         * 
         * @var Doctrine_Query
         */
        $q = Doctrine_Query::create()->from('Model_Country c');
        
        $grid = $this->_getGrid($q);
        $grid->setGridColumns(array('code', 'name', 'continent'));
        $grid->updateColumn('name', array('title' => 'Country'));
        
        $this->view->grid = $grid->deploy();
    }
    
    public function crudAction()
    {
        $q = Doctrine_Query::create()->from('Model_Crud');
        $grid = $this->_getGrid($q);
        
        $form = new Bvb_Grid_Form();
        $form->setAdd(1)->setEdit(1)->setDelete(1)->setAddButton(1);
        
        $grid->setForm($form);
        
        $this->view->grid = $grid->deploy();
    }
    
    public function jqgridAction()
    {
        ZendX_JQuery::enableView($this->view);
        Bvb_Grid_Deploy_JqGrid::$defaultJqGridLibPath = '/js/jqgrid';
        
        $this->_helper->layout->setLayout('blank');
        
        $q = Doctrine_Query::create()->select('code, name, continent, code AS _action')
                                     ->from('Model_Country');
        
        $grid = $this->_getJqGrid($q);
        
        $grid->ajax();
        
        $this->view->grid = $grid->deploy();
    }
    
    public function viewAction()
    {
        $id = $this->_getParam('id');
        
        $data = Doctrine_Query::create()->from('Model_Country')
                                        ->where('code = ?', $id)
                                        ->fetchOne(array(), Doctrine::HY);
                                        
        $this->view->data = $data;
    }
    
    protected function _getJqGrid($query)
    {
        $config = new Zend_Config_Ini(AP . '/configs/grid.ini', AE);
        $grid = Bvb_Grid_Data::factory('Bvb_Grid_Deploy_JqGrid', $config);
        
        $grid->setSource(new Bvb_Grid_Source_Doctrine($query));
        
        $grid->setEscapeOutput(false);
        $grid->setGridColumns(array('code', 'name', 'continent', '_action'));
        
        $grid->updateColumn('code', array('align' => 'center'));
        
        $grid->updateColumn('name', array(
            'title' => 'Country',
            'align' => 'center'
        ));
        
        $grid->updateColumn('continent', array('align' => 'center'));
        
        $grid->updateColumn('_action', array(
            'search'   => false, //this will disable search on this field
            'order'    => 1,
            'title'    => 'Action',
            'width'    => 100,
            'class'    => 'bvb_action bvb_first',
            'callback' => array(
                'function' => array($this,'formatColumnAction'),
                'params'   => array('{{code}}')
            ),
            'jqg'      => array('fixed' => true, 'search' => false)
        ));
        
        $grid->imagesUrl = '/images/';
        //$grid->setDetailColumns();
        
        $grid->setJqgParams(array(
            'caption' => 'jqGrid Example',
            'forceFit' => true,
            'viewrecords' => true, // show/hide record count right bottom in navigation bar
            'rowList' => array(10, 15, 50), // show row number per page control in navigation bar
            'altRows' => true, // rows will alternate color
        ));
        
        return $grid;
    }
    
    /**
     * Just grab a grid with basics setup
     * 
     * @param mixed $query
     * @return Bvb_Grid_Data
     */
    protected function _getGrid($query)
    {
        $config = new Zend_Config_Ini(AP . '/configs/grid.ini', AE);
        $grid = Bvb_Grid_Data::factory('Bvb_Grid_Deploy_Table', $config);
        
        $grid->setSource(new Bvb_Grid_Source_Doctrine($query));
        
        $grid->setEscapeOutput(false);
        
        $grid->imagesUrl = '/images/';
        $grid->setDetailColumns();
        //$grid->addTemplateDir('My/Template/Table', 'My_Template_Table', 'table');
        //$grid->addFormatterDir('My/Formatter', 'My_Formatter');
        //$grid->cache = array('use' => 0, 'instance' => Zend_Registry::get('cache'), 'tag' => 'grid');
        
        return $grid;
    }
    
    public function formatColumnAction($id) {
        
        $helper = new Zend_View_Helper_Url();
        $actions = array(
            array('href'=>$helper->url(array('action'=>'view', 'id'=>$id)), 'caption'=>'View', 'class'=>'{view}'),
            array('href'=>$helper->url(array('action'=>'edit', 'id'=>$id)), 'caption'=>'Edit', 'class'=>'{edit} fixedClass'),
            array('href'=>$helper->url(array('action'=>'delete', 'id'=>$id)), 'caption'=>'Delete', 'class'=>'{delete}'),
            array('onclick'=>new Zend_Json_Expr('alert("you clicked on ID: "+jQuery(this).closest("tr").attr("id"));'), 'caption'=>'Alert Me')
        );
        
        return Bvb_Grid_Deploy_JqGrid::formatterActionBar($actions);
    }
}
