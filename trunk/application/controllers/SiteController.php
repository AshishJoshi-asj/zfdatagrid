<?php

/**
 * @author mascker
 */

include 'application/models/Model.php';

class SiteController extends Zend_Controller_Action
{

    private $_db;

    /**
     * If a action don't exist, just redirect to the basic
     *
     * @param string $name
     * @param array $var
     */
    function __call ($name, $var)
    {
        $this->_redirect('default/site/basic', array('exit' => 1));
        return false;
    }

    /**
     * I think this is needed for something. can't remember
     *
     */
    function init ()
    {

        $this->view->url = Zend_Registry::get('config')->site->url;
        $this->view->action = $this->getRequest()->getActionName();
        header('Content-Type: text/html; charset=ISO-8859-1');
        $this->_db = Zend_Registry::get('db');
        Bvb_Grid_Deploy_Ofc::$url = Zend_Registry::get('config')->site->url;

    }

    /**
     * Same as __call
     *
     */
    function indexAction ()
    {
        $this->_forward('basic');
    }

    /**
     * Show the source code for this controller
     *
     */
    function codeAction ()
    {
        $this->render('code');
    }

    /**
     * Simplify the datagrid creation process
     * @return Bvb_Grid_Deploy_Table
     */
    function grid ($id = '')
    {

        $config = new Zend_Config_Ini('./application/grids/grid.ini', 'production');

        $grid = Bvb_Grid_Data::factory('Bvb_Grid_Deploy_Table', $config,$id);

        $grid->setEscapeOutput(false);
        $grid->addTemplateDir('My/Template/Table', 'My_Template_Table', 'table');
        $grid->addFormatterDir('My/Formatter', 'My_Formatter');
        $grid->imagesUrl = $this->getRequest()->getBaseUrl() . '/public/images/';
        $grid->cache = array('use' => 0, 'instance' => Zend_Registry::get('cache'), 'tag' => 'grid');


        return $grid;
    }


    /**
     * A simple usage of advanced filters. Every time you change a filter, the system automatically
     *runs a query to the others filters, making sure they don't allow you to filter for a record that is not in the database
     *
     *
     * We also use SQL expressions and they will appear on the last line (before pagination)
     * The average of LifeExpectancy and to SUM of Population
     */
    function filtersAction ()
    {

        $grid = $this->grid();

        $grid->query($this->_db->select()->from('Country', array('Name', 'Continent', 'Population', 'LifeExpectancy', 'GovernmentForm', 'HeadOfState')));

        $grid->updateColumn('Name', array('title' => 'Country', 'class' => 'width_200'))->updateColumn('Continent', array('title' => 'Continent'))->updateColumn('Population', array('title' => 'Population', 'class' => 'width_80'))->updateColumn('LifeExpectancy', array('title' => 'Life E.', 'class' => 'width_80'))->updateColumn('GovernmentForm', array('title' => 'Government Form', 'searchType' => '='))->updateColumn('HeadOfState', array('title' => 'Head Of State', 'searchType' => '='));

        $filters = new Bvb_Grid_Filters();
        $filters->addFilter('Name', array('distinct' => array('field' => 'Name', 'name' => 'Name'),'searchType'=>'<>'))->addFilter('Continent', array('distinct' => array('field' => 'Continent', 'name' => 'Continent')))->addFilter('LifeExpectancy', array('distinct' => array('field' => 'LifeExpectancy', 'name' => 'LifeExpectancy')))->addFilter('GovernmentForm', array('distinct' => array('field' => 'GovernmentForm', 'name' => 'GovernmentForm')))->addFilter('HeadOfState')->addFilter('Population');

        $grid->addFilters($filters);

        $this->view->pages = $grid->deploy();


        $this->render('index');
    }

    /**
     * Adding extra columns to a datagrid. They can be at left or right.
     * Also notice that you can use fields values to populate the fields by surrounding the field name with {{}}
     *
     */
    function extraAction ()
    {

        $grid = $this->grid();

        $grid->query($this->_db->select()->from(array('c' => 'Country'), array('country' => 'Name', 'Continent', 'Population', 'GovernmentForm', 'HeadOfState'))->join(array('ct' => 'City'), 'c.Capital = ct.ID', array('city' => 'Name')));

        $grid->updateColumn('country', array('title' => 'Country (Capital)', 'class' => 'hideInput', 'decorator' => '{{country}} <em>({{city}})</em>'));
        $grid->updateColumn('city', array('title' => 'Capital', 'hide' => 1));
        $grid->updateColumn('Continent', array('title' => 'Continent'));
        $grid->updateColumn('Population', array('title' => 'Population', 'class' => 'width_80'));
        $grid->updateColumn('LifeExpectancy', array('title' => 'Life E.', 'class' => 'width_50'));
        $grid->updateColumn('GovernmentForm', array('title' => 'Government Form'));
        $grid->updateColumn('HeadOfState', array('title' => 'Head Of State', 'hide' => 1));

        $extra = new Bvb_Grid_ExtraColumns();
        $extra->position('right')->name('Right')->decorator("<input class='input_p'type='text' value=\"{{Population}}\" size=\"3\" name='number[]'>");

        $esquerda = new Bvb_Grid_ExtraColumns();
        $esquerda->position('left')->name('Left')->decorator("<input  type='checkbox' name='number[]'>");

        $grid->addExtraColumns($extra, $esquerda);

        $this->view->pages = $grid->deploy();
        $this->render('index');
    }


    function csvAction ()
    {

        $grid = $this->grid();
        $grid->setDataFromCsv('media/files/grid.csv');
        $this->view->pages = $grid->deploy();
        $this->render('index');
    }

    function feedAction ()
    {
        $grid = $this->grid();
        $grid->setDataFromXml('http://zfdatagrid.com/feed/', 'channel,item');
        $grid->setPagination(10);
        $this->view->pages = $grid->deploy();
        $this->render('index');
    }

    /**
     * The 'most' basic example.
     */
    function basicAction ()
    {
        $grid = $this->grid();
        $select = $this->_db->select()->from('Country');
        $grid->query($select);

        $grid->setDetailColumns();
        $grid->setGridColumns(array('ID','Name','Continent','Population','LocalName','GovernmentForm'));

        $this->view->pages = $grid->deploy();

        $this->render('index');
    }


    /**
     * The 'most' basic example.
     */
    function multiAction ()
    {


        $grid = $this->grid('2');
        $select = $this->_db->select()->from('City');
        $grid->query($select);

        $form = new Bvb_Grid_Form();
        $form->setAdd(1)->setEdit(1)->setDelete(1)->setButton(1);
        $form->setModel(new Addressbook());
        $grid->addForm($form);



        $grid1 = $this->grid('1');
        $select1 = $this->_db->select()->from('City');
        $grid1->query($select1);

        $form1 = new Bvb_Grid_Form();
        $form1->setAdd(1)->setEdit(1)->setDelete(1)->setButton(1);
        $form1->setModel(new Addressbook());
        $grid1->addForm($form1);

        $this->view->pages = $grid->deploy();
        $this->view->pages1 = $grid1->deploy();

        $this->render('index');
    }



    /**
     * The 'most' basic example.
     */
    function ajaxAction ()
    {

        $grid = $this->grid();

        $grid->query($this->_db->select()->from('City'));
        $grid->setAjax('ajaxGrid');
        $this->view->pages = $grid->deploy();

        $this->render('index');
    }

    /**
     * This demonstrates how easy it is for us to use our own templates (Check the grid function at the page top)
     *
     */
    function templateAction ()
    {

        $grid = $this->grid();
        $grid->query($this->_db->select()->from('City'));
        $grid->setNoFilters(1)->setPagination(14)->setTemplate('outside', 'table');
        $this->view->pages = $grid->deploy();
        $this->render('index');
    }

    /**
     * This example allow you to create an horizontal row, for every distinct value from a field
     *
     */
    function hrowAction ()
    {

        $grid = $this->grid();
        $grid->query($this->_db->select()->from('Country', array('Name', 'Continent', 'Population', 'LifeExpectancy', 'GovernmentForm', 'HeadOfState')));
        $grid->setNoFilters(1);
        $grid->setNoOrder(1);

        $grid->setPagination(1200);

        $grid->updateColumn('Name', array('title' => 'Country'));
        $grid->updateColumn('Continent', array('title' => 'Continent', 'hRow' => 1));
        $grid->updateColumn('Population', array('title' => 'Population', 'class' => 'width_80'));
        $grid->updateColumn('LifeExpectancy', array('title' => 'Life E.', 'class' => 'width_50', 'decorator' => '<b>{{LifeExpectancy}}</b>', 'format' => 'number'));
        $grid->updateColumn('GovernmentForm', array('title' => 'Government Form'));
        $grid->updateColumn('HeadOfState', array('title' => 'Head Of State'));

        $this->view->pages = $grid->deploy();
        $this->render('index');
    }

    /**
     * If you don't like to work with array when adding columns, you can work by dereferencing objects
     *
     */
    function columnAction ()
    {

        $grid = $this->grid();
        $grid->query($this->_db->select()->from(array('c' => 'Country'), array('Country' => 'Name', 'Continent', 'Population', 'GovernmentForm', 'HeadOfState'))->join(array('ct' => 'City'), 'c.Capital = ct.ID', array('Capital' => 'Name')));
        $grid->setPagination(15);

        $cap = new Bvb_Grid_Column('Country');
        $cap->title('Country (Capital)')->class('width_150')->decorator('{{Country}} <em>({{Capital}})</em>');

        $name = new Bvb_Grid_Column('Name');
        $name->title('Capital')->hide(1);

        $continent = new Bvb_Grid_Column('Continent');
        $continent->title('Continent');

        $population = new Bvb_Grid_Column('Population');
        $population->title('Population')->class('width_80');

        $governmentForm = new Bvb_Grid_Column('GovernmentForm');
        $governmentForm->title('Government Form');

        $headState = new Bvb_Grid_Column('HeadOfState');
        $headState->title('Head Of State');

        $grid->updateColumns($cap, $name, $continent, $population, $governmentForm, $headState);

        $this->view->pages = $grid->deploy();
        $this->render('index');
    }


    function crudAction ()
    {
        $grid = $this->grid();
        $grid->setModel(new Bugs());

        $grid->setColumnsHidden(array('bug_id', 'seguinte', 'time', 'bug_status'));
        $grid->updateColumn('date', array( 'title' => 'Date', 'tooltipField' => 'sempre'));

        $form = new Bvb_Grid_Form();
        $form->setAdd(1)->setEdit(1)->setDelete(1)->setDetail(1);
        $grid->addForm($form);

        $grid->export = array();
        $grid->setDetailColumns();
        $this->view->pages = $grid->deploy();
        $this->render('index');
    }

    function ofcAction ()
    {

        $this->view->graphs = $allowedGraphs = array('line', 'bar', 'bar_glass', 'bar_3d', 'bar_filled', 'pie', 'mixed');

        $type = $this->_getParam('type');

        if (! in_array($type, $allowedGraphs)) {
            $type = 'bar_glass';
        }

        $this->getRequest()->setParam('_exportTo', 'ofc');

        $grid = $this->grid();
        $grid->setChartType($type);
        $grid->setChartOptions(array('set_bg_colour' => '#FFFFFF'));
        $grid->setTile('My First Graph');
        $grid->setChartDimensions(900, 400);
        $grid->setFilesLocation(array('js' => $this->getFrontController()->getBaseUrl() . '/public/scripts/swfobject.js', 'flash' => $this->getFrontController()->getBaseUrl() . '/public/flash/open-flash-chart.swf'));

        if ($type == 'pie') {
            $grid->addValues('Population', array('set_colours' => array('#000000', '#999999', '#BBBBBB', '#FFFFFF')));
        } elseif ($type == 'mixed') {
            $grid->addValues('GNP', array('set_colour' => '#FF0000', 'set_key' => 'Gross National Product', 'chartType' => 'Bar_Glass'));
            $grid->addValues('SurfaceArea', array('set_colour' => '#00FF00', 'set_key' => 'Surface', 'chartType' => 'line'));
        } else {
            $grid->addValues('GNP', array('set_colour' => '#00FF00', 'set_key' => 'Gross National Product'));
            $grid->addValues('SurfaceArea', array('set_colour' => '#0000FF', 'set_key' => 'Surface'));
        }

        $grid->setXLabels('Name');

        $grid->query($this->_db->select()->from('Country', array('Population', 'Name', 'GNP', 'SurfaceArea'))->where('Continent=?', 'Europe')->where('Population>?', 5000000)->where(new Zend_Db_Expr('length(Name)<10'))->order(new Zend_Db_Expr('RAND()'))->limit(10));

        $this->view->pages = $grid->deploy();
        $this->render('index');
    }

}