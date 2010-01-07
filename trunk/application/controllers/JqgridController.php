<?php
class JqgridController extends Zend_Controller_Action
{
    function indexAction()
    {
        $db = Zend_Registry::get ( 'db' );
        
        $select = $db->select()
            ->from('City')
            ->order('Name')
        ;
        
        $grid1 = new Bvb_Grid_Deploy_JqGrid( $db, 'Grid Example', 'media/temp', array ('download' ) );
        $grid1->query($select);

        $grid1->updateColumn('ID', array('title' => '#ID','width' => '20'));        
        $grid1->updateColumn('Name', array('title' => 'City name','width' => '260'));
        //$grid1->noFilters(1);        
        //$grid1->noOrder(1);
        
        $grid1->ajax('divisions');
        
        $this->view->g1 = $grid1;       
    }    
}