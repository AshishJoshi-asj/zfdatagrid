<?php

/**
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license
 * It is  available through the world-wide-web at this URL:
 * http://www.petala-azul.com/bsd.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to geral@petala-azul.com so we can send you a copy immediately.
 *
 * @package    Bvb_Grid
 * @copyright  Copyright (c)  (http://www.petala-azul.com)
 * @license    http://www.petala-azul.com/bsd.txt   New BSD License
 * @version    $Id$
 * @author     Bento Vilas Boas <geral@petala-azul.com >
 */

class Bvb_Grid_Form extends Zend_Form
{

    public $options;

    public $fields;

    public $cascadeDelete;

    protected $_model;

    public $groupDecorators = array('FormElements', array('HtmlTag', array('tag' => 'td', 'colspan' => '2', 'class' => 'buttons')), 'DtDdWrapper');

    public $elementDecorators = array(
                                    'ViewHelper',
                                    'Description',
                                    'Errors',
                                    array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
                                    array(array('label' => 'Label'), array('tag' => 'td')),
                                    array(array('row' => 'HtmlTag'), array('tag' => 'tr')));

    public $buttonHidden = array('ViewHelper');

    public $formDecorator = array('FormElements', array('HtmlTag', array('tag' => 'table', 'style' => 'width:98%','class'=>'borders')), 'Form');


    function __call ($name, $args)
    {
        if (substr(strtolower($name), 0, 3) == 'set') {
            $name = substr($name, 3);
            $name[0] = strtolower($name[0]);
            $this->options[$name] = $args[0];

            return $this;
        }

        parent::__call($name, $args);

    }

    function setCallbackBeforeDelete ($callback)
    {

        if (! is_callable($callback)) {
            throw new Exception($callback . ' not callable');
        }
        $this->options['callbackBeforeDelete'] = $callback;

        return $this;
    }

    function setCallbackBeforeUpdate ($callback)
    {

        if (! is_callable($callback)) {
            throw new Exception($callback . ' not callable');
        }

        $this->options['callbackBeforeUpdate'] = $callback;

        return $this;
    }

    function setCallbackBeforeInsert ($callback)
    {

        if (! is_callable($callback)) {
            throw new Exception($callback . ' not callable');
        }

        $this->options['callbackBeforeInsert'] = $callback;

        return $this;
    }

    function setCallbackAfterDelete ($callback)
    {

        if (! is_callable($callback)) {
            throw new Exception($callback . ' not callable');
        }

        $this->options['callbackAfterDelete'] = $callback;

        return $this;
    }

    function setCallbackAfterUpdate ($callback)
    {

        if (! is_callable($callback)) {
            throw new Exception($callback . ' not callable');
        }

        $this->options['callbackAfterUpdate'] = $callback;

        return $this;
    }

    function setCallbackAfterInsert ($callback)
    {

        if (! is_callable($callback)) {
            throw new Exception($callback . ' not callable');
        }

        $this->options['callbackAfterInsert'] = $callback;

        return $this;
    }

    function onDeleteCascade ($options)
    {
        $this->cascadeDelete[] = $options;
        return $this;

    }

    /**
     *
     * @param Zend_Db_Table_Abstract $model
     */
    public function setModel ($model)
    {
        $form = $model->buildForm($this);

        $this->_model = $form;

        $this->setDecorators(array('FormElements', array('HtmlTag', array('tag' => 'table', 'style' => 'width:98%')), 'Form'));

        $this->setOptions($form);


        return $this;
    }

    /**
     * @var Zend_From
     */
    function getModel ()
    {
        return $this->_model;
    }

}