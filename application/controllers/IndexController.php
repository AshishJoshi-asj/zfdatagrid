<?php

class IndexController extends Zend_Controller_Action
{
    public function init()
    {
      // setting the controller and action name as title segments:
      $request = Zend_Controller_Front::getInstance()->getRequest();
      $this->view->headTitle(ucwords($request->getControllerName()))
           ->headTitle(ucwords($request->getActionName()));
       
      // setting a separator string for segments:
      $this->view->headTitle()->setSeparator(' / ');
    }
    
    public function indexAction()
    {
        /**
         * This is a basic Doctrine_Query example.
         * Alternatively to you could do one of the following:
         * 
         * <code>
         * $q = new Model_Country();
         * $q = 'Model_Country';
         * </code>
         * 
         * And will work just the same
         * 
         * @var Doctrine_Query
         */
        $q = Doctrine_Query::create()->from('Model_Country');
        
        $grid = $this->_getGrid($q);
        $grid->setGridColumns(array('code', 'name', 'continent'));
        $grid->updateColumn('name', array('title' => 'Country'));
        
        $this->view->grid = $grid->deploy();
    }
    
    public function advancedAction()
    {
        $q = Doctrine_Query::create()
            ->select('co.code, co.name AS country_name, co.continent, ci.name AS city_name')
            ->from('Model_Country co')
            ->leftJoin('co.City ci');
        
        $grid = $this->_getGrid($q);
        
        $grid->setGridColumns(array('code', 'country_name', 'continent', 'city_name'));
        $grid->updateColumn('country_name', array('title' => 'Country'));
        
        $filters = new Bvb_Grid_Filters();
        $filters->addFilter('country_name', array('distinct' => array('field' => 'name', 'name' => 'name')));
        $filters->addFilter('continent', array('distinct' => array('field' => 'continent', 'name' => 'continent')));
        $filters->addFilter('city_name', array('search' => true));

        $grid->addFilters($filters);
        
        $extraColumn = new Bvb_Grid_Extra_Column();
        $extraColumn->position('right')
                    ->name('Box')
                    ->helper(array(
                        'name'   =>'formCheckbox',
                        'params' => array('toDelete')
                    ));
        
        $grid->addExtraColumns($extraColumn);
        
        $grid->setSqlExp(array(
            'country_name' => array(
                'functions' => array('COUNT'),
                'value'     => 'name',
                'decorator' => ''
            )
        ));
        
        $this->view->grid = $grid->deploy();
        return $this->render('index');
    }
    
    public function crudAction()
    {
        $q = Doctrine_Query::create()->from('Model_Crud');
        $grid = $this->_getGrid($q);
        
        $grid->setGridColumns(array('firstname', 'lastname'));
        
        $form = new Bvb_Grid_Form();
        $form->setAdd(1)
             ->setEdit(1)
             ->setDelete(1)
             ->setAddButton(1)
             ->setDisallowedFields(array('date_added'));
             
        $grid->setForm($form);
        
        $this->view->grid = $grid->deploy();
        
        return $this->render('index');
    }
    
    public function jqgridAction()
    {
        ZendX_JQuery::enableView($this->view);
        Bvb_Grid_Deploy_JqGrid::$defaultJqGridLibPath = '/js/jqgrid';
        
        $this->_helper->layout->setLayout('blank');
        
        $select[] = 'co.code';
        $select[] = 'co.name AS country_name';
        $select[] = 'co.continent AS continent';
        $select[] = 'ci.name AS city_name';
        $select[] = 'co.code AS _action';
        
        $q = Doctrine_Query::create()->select(implode(", ", $select))
                                     ->from('Model_Country co')
                                     ->leftJoin('co.City ci');
        
        $grid = $this->_getJqGrid($q);
        
        $grid->ajax();
        
        $this->view->grid = $grid->deploy();
    }
    
    public function debugAction()
    {
        $q = Doctrine_Query::create()
            ->select('co.code, co.name AS country_name, co.continent, COUNT(ci.name) AS city_total')
            ->from('Model_Country co')
            ->leftJoin('co.City ci ON ci.countrycode = co.code')
            ->groupBy('co.name');
            
        $grid = $this->_getGrid($q);
        
        $grid->updateColumn('city_total', array('searchType' => '>'));
        
        $this->view->grid = $grid->deploy();
        return $this->render('index');
    }
    
    public function viewAction()
    {
        $id = $this->_getParam('id');
        
        $data = Doctrine_Query::create()->from('Model_Country')
                                        ->where('code = ?', $id)
                                        ->fetchOne(array(), Doctrine::HYDRATE_ARRAY);
                                        
        $this->view->data = $data;
    }
    
    /**
     * Just grab and setup our JqGrid grid
     * 
     * @param mixed $query
     */
    protected function _getJqGrid($query)
    {
        $config = new Zend_Config_Ini(AP . '/configs/grid.ini', AE);
        $grid = Bvb_Grid::factory('Bvb_Grid_Deploy_JqGrid', $config);
        
        $grid->setSource(new Bvb_Grid_Source_Doctrine($query));
        
        $grid->setEscapeOutput(false);
        $grid->setGridColumns(array('co_code', 'country_name', 'continent', 'city_name', '_action'));
        
        $grid->updateColumn('co_code', array(
            'title' => 'Country Code',
            'align' => 'center'
        ));
        
        $grid->updateColumn('country_name', array(
            'title' => 'Country',
            'align' => 'center'
        ));
        
        $grid->updateColumn('continent', array('align' => 'center'));
        $grid->updateColumn('city_name', array('align' => 'center'));
        
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
        
        $grid->setJqgParams(array(
            'caption'     => 'jqGrid Example',
            'forceFit'    => true,
            'viewrecords' => true, // show/hide record count right bottom in navigation bar
            'rowList'     => array(10, 15, 50), // show row number per page control in navigation bar
            'altRows'     => true, // rows will alternate color
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
        $grid = Bvb_Grid::factory('Bvb_Grid_Deploy_Table', $config);
        
        $grid->setSource(new Bvb_Grid_Source_Doctrine($query));
        
        $grid->setEscapeOutput(false);
        
        $grid->setImagesUrl('/images/');
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
