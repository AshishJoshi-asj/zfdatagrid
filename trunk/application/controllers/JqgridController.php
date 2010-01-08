<?php
class JqgridController extends Zend_Controller_Action
{
    function init()
    {
        $this->db = Zend_Registry::get ( 'db' );
        // enable debug
        Bvb_Grid_Deploy_JqGrid::$debug = true; 
    }
    function indexAction()
    {
        $grid1 = new Bvb_Grid_Deploy_JqGrid($this->db, 'jqGrid Example', 'media/temp', array('download'));
        $this->configG1($grid1);
        
        $grid1_html = new Bvb_Grid_Deploy_Table($this->db, 'HTML Grid Example', 'media/temp', array('download'));
        $this->configG1($grid1_html);
        
        $this->view->g1 = $grid1;       
        $this->view->g1_html = $grid1_html;        
    }

    function configG1($grid)
    {
        $select = $this->db->select()
            ->from('City')
            ->order('Name')
            ->columns(array('IsBig'=>new Zend_Db_Expr('IF(Population>500000,1,0)')))
            // TODO big problem ->columns(array('test'=>'ID'))
        ;
        
        $grid->query($select);

        $grid->updateColumn('ID', array('title'=>'#ID','width'=>20, 'hide'=>true));        
        $grid->updateColumn('Name', array('title'=>'City name','width'=>260));
        $grid->updateColumn('Population', array(
            'jqg' => array('formatter'=>'integer', 'align'=>'right')
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
        //$grid->noFilters(1);        
        //$grid->noOrder(1);
        $grid->setJsOptions(array('forceFit'=>true));
        
        $grid->ajax(get_class($grid));
    }
}