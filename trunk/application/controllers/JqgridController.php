<?php
class JqgridController extends Zend_Controller_Action
{
    function init()
    {
        $this->db = Zend_Registry::get ( 'db' );
        // enable debug
        Bvb_Grid_Deploy_JqGrid::$debug = true;
        // enable JQuery - should be part of bootstrap
        ZendX_JQuery::enableView($this->view);
    }
    /**
     * Show the source code for this controller
     *
     */
    function codeAction()
    {
    }
    function doAction()
    {
        print_r($this->getRequest()->getParams());
        die();
    }    
    function indexAction()
    {
        // construct JqGrid and let it configure
        $grid1 = new Bvb_Grid_Deploy_JqGrid('jqGrid Example');
        $this->configG1($grid1);
        
        // construct HTML Table Grid and let it configure in the same way
        $grid1_html = new Bvb_Grid_Deploy_Table();
        $this->configG1($grid1_html);
        
        // pass grids to view and deploy() them there 
        $this->view->g1 = $grid1->deploy();       
        $this->view->g1_html = $grid1_html->deploy();        
    }
    
    public function g1ActionBar($id) {
        $helper = new Zend_View_Helper_Url();
        $actions = array(
            array('url'=>$helper->url(array('action'=>'do', 'what'=>'view', 'id'=>$id)), 'caption'=>'View', 'class'=>'ui-icon ui-icon-zoomin'),        
            array('url'=>$helper->url(array('action'=>'do', 'what'=>'edit', 'id'=>$id)), 'caption'=>'Edit', 'class'=>'ui-icon ui-icon-pencil'),
            array('url'=>$helper->url(array('action'=>'do', 'what'=>'delete', 'id'=>$id)), 'caption'=>'Delete', 'class'=>'ui-icon ui-icon-cancel')            
        );
        return Bvb_Grid_Deploy_JqGrid::formatterActionBar($actions);
    }

    function configG1($grid)
    {
        ////////////////// 1. define select
        $select = $this->db->select()
            ->from('City')
            ->order('Name')
            ->columns(array('IsBig'=>new Zend_Db_Expr('IF(Population>500000,1,0)')))
            // TODO big problem             
            // ->columns(array('_action'=>new Zend_Db_Expr('ID')))            
            // ->columns(array('test'=>'ID'))
        ;
        $grid->query($select);

        ////////////////// 2. update column options
        ////////////////// see Bvb documentation
        ////////////////// and for jqg array see http://www.trirand.com/jqgridwiki/doku.php?id=wiki:colmodel_options           
        //$grid->updateColumn('ID', array('title'=>'#ID','width'=>20, 'hide'=>true));
        $grid->updateColumn('ID', array('title'=>'Action','width'=>40, 'callback'=>array('function'=>array($this,'g1ActionBar'), 'params'=>array('{{ID}}'))));        
        //$grid->updateColumn('_action', array('callback'=>array('function'=>array($this,'g1ActionIcons'))));
        $grid->updateColumn('Name', array('title'=>'City name','width'=>260));
        $grid->updateColumn('Population', array(
            'jqg' => array(
                'formatter'=>'integer', 
                'align'=>'right'
            )
        ));
        $grid->updateColumn('IsBig', array(
            'width'=>40,
            'title' => 'Is Big City', 
            'jqg' => array(
                'formatter'=>'checkbox', 
                'align'=>'center',
                'stype'=>'select',
                'searchoptions'=>array('defaultValue'=>'1', 'value'=>array(0=>'No', 1=>'Yes'))
            )
        ));

        ////////////////// 3. set Bvb grid behaviour        
        //$grid->noFilters(1);        
        //$grid->noOrder(1);
        
        ////////////////// 4. set jqGrid options 
        ////////////////// for setJqgOptions see http://www.trirand.com/jqgridwiki/doku.php?id=wiki:options
        ////////////////// see also other Bvb_Grid_Deploy_JqGrid::setJqg*() and Bvb_Grid_Deploy_JqGrid::jqg*() methods    
        $grid->setJqgOptions(array(
            'forceFit'=>true,
            'viewrecords'=>false,
        ));
        $grid->setJqgOnInit('console.log("jqGrid initiated ! If data are remote they are not loaded at this point.");');
        
        ////////////////// 5. set ajax ID and process response if requested 
        $grid->ajax(get_class($grid));
    }
}