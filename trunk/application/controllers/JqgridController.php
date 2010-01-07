<?php
class JqgridController extends Zend_Controller_Action
{
    function indexAction()
    {
        $db = Zend_Registry::get ( 'db' );
        
        $select = $db->select('title_pag')
            ->from('city' /*, array('ID', 'Name', 'ident', 'days_after')*/)
            ->order('Name')
        ;
        
        $grid1 = new Bvb_Grid_Deploy_JqGrid( $db, 'Grid Example', 'media/temp', array ('download' ) );
        $grid1->query($select);

        $grid1->updateColumn('title_pag', array('title' => 'Division name','width' => '260'));
        
        $grid1->ajax('divisions');
        
        $this->view->g1 = $grid1;       
    }    
}