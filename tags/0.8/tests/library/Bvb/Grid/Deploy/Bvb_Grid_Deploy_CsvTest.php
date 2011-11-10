<?php

/**
 * Test class for Bvb_Grid_Deploy_Csv.
 * Generated by PHPUnit on 2011-04-04 at 05:28:21.
 */
class Bvb_Grid_Deploy_CsvTest extends Bvb_GridTestHelper {

    public function setUp()
    {

        parent::setUp();
        $this->grid = Bvb_Grid::factory('csv');
        $this->grid->setParam('module', 'default');
        $this->grid->setParam('controller', 'site');
        $this->grid->setView(new Zend_View(array()));
        $this->grid->setExport(array('csv'));
    }

    public function testSaveFile()
    {
        $this->grid->setDeployOption('name', 'barcelos');
        $this->grid->setDeployOption('save', '1');
        $this->grid->setDeployOption('dir', $this->_temp);

        $this->grid->setSource(new Bvb_Grid_Source_Zend_Table(new Bugs()));
        $this->grid->deploy();

        $this->assertTrue(file_exists($this->_temp . 'barcelos.csv'));
        unlink($this->_temp . 'barcelos.csv');
    }

    public function testNotSaveFile()
    {
        $this->grid->setDeployOption('name', 'barcelos');
        $this->grid->setDeployOption('save', '0');
        $this->grid->setDeployOption('display', '1');
        $this->grid->setDeployOption('dir', $this->_temp);

        $this->grid->setSource(new Bvb_Grid_Source_Zend_Table(new Bugs()));
        $this->grid->deploy();

        $this->assertFalse(file_exists($this->_temp . 'barcelos.csv'));
    }

    public function testDisplayAndSave()
    {
        $this->grid->setDeployOption('name', 'barcelos');
        $this->grid->setDeployOption('save', '1');
        $this->grid->setDeployOption('display', '1');
        $this->grid->setDeployOption('dir', $this->_temp);

        $this->grid->setSource(new Bvb_Grid_Source_Zend_Table(new Bugs()));
        $this->grid->deploy();
        die();

        $this->assertTrue(file_exists($this->_temp . 'barcelos.csv'));
        unlink($this->_temp . 'barcelos.csv');
    }

}