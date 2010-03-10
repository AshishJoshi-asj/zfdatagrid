<?php
// Connection Component Binding
Doctrine_Manager::getInstance()->bindComponent('Model_City', 'master');

/**
 * Model_Base_City
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property integer $id
 * @property string $name
 * @property string $countrycode
 * @property string $district
 * @property integer $population
 * @property Model_Country $Country
 * 
 * @package    Doctrine
 * @subpackage 
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 6820 2009-11-30 17:27:49Z jwage $
 */
abstract class Model_Base_City extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('City');
        $this->hasColumn('id', 'integer', 4, array(
             'type' => 'integer',
             'fixed' => 0,
             'unsigned' => false,
             'primary' => true,
             'autoincrement' => true,
             'length' => '4',
             ));
        $this->hasColumn('name', 'string', 35, array(
             'type' => 'string',
             'fixed' => 1,
             'unsigned' => false,
             'primary' => false,
             'default' => '',
             'notnull' => true,
             'autoincrement' => false,
             'length' => '35',
             ));
        $this->hasColumn('countrycode', 'string', 3, array(
             'type' => 'string',
             'fixed' => 1,
             'unsigned' => false,
             'primary' => false,
             'default' => '',
             'notnull' => true,
             'autoincrement' => false,
             'length' => '3',
             ));
        $this->hasColumn('district', 'string', 20, array(
             'type' => 'string',
             'fixed' => 1,
             'unsigned' => false,
             'primary' => false,
             'default' => '',
             'notnull' => true,
             'autoincrement' => false,
             'length' => '20',
             ));
        $this->hasColumn('population', 'integer', 4, array(
             'type' => 'integer',
             'fixed' => 0,
             'unsigned' => false,
             'primary' => false,
             'default' => '0',
             'notnull' => true,
             'autoincrement' => false,
             'length' => '4',
             ));
    }

    public function setUp()
    {
        parent::setUp();
        $this->hasOne('Model_Country as Country', array(
             'local' => 'countrycode',
             'foreign' => 'code'));
    }
}