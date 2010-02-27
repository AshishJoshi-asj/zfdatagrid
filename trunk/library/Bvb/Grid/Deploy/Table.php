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

class Bvb_Grid_Deploy_Table extends Bvb_Grid_Data implements Bvb_Grid_Deploy_Interface
{

    const OUTPUT = 'table';

    /**
     * Hold definitions from configurations
     * @var array
     */
    public $deploy = array();

    protected $_deployOptions = null;

    /**
     * Information about the template
     *
     * @var array|empty
     */

    public $templateInfo;

    /**
     * If the form has been submited
     *
     * @var bool
     */
    protected $formPost = 0;

    /**
     * Deploy options
     *
     * @var string
     */
    protected $options = array();

    /**
     * The output type
     *
     * @var string
     */
    protected $output = 'table';

    /**
     * Permission to add records
     *
     * @var array
     */
    private $allowAdd = null;

    /**
     * Permission to edit records
     *
     * @var array
     */
    private $allowEdit = null;

    /**
     * Permission to delete records
     *
     * @var array
     */
    private $allowDelete = null;

    /**
     * Template data
     *
     * @var array
     */
    public $template;

    /**
     * Override the form presentation
     *
     * @var bool
     */
    protected $_editNoForm;

    /**
     * Images url for export
     *
     * @var string
     */
    public $imagesUrl;

    /**
     * If we are allowed to add records to the database if we
     * show two tables (the form and the grid) or just one
     *
     * @var bool
     */
    protected $double_tables = 0;

    /**
     * Set if form vaidation failed
     *
     * @var bool
     */
    protected $_failedValidation;


    /**
     * Template
     *
     * @var object
     */
    public $temp;

    /**
     * Callback to be called after crud operation update
     * @var unknown_type
     */
    protected $_callbackAfterUpdate = null;

    /**
     * Callback to be called after crud operation delete
     * @var unknown_type
     */
    protected $_callbackAfterDelete = null;

    /**
     * Callback to be called after crud operation insert
     * @var unknown_type
     */
    protected $_callbackAfterInsert = null;

    /**
     * Callback to be called Before crud operation update
     * @var unknown_type
     */
    protected $_callbackBeforeUpdate = null;

    /**
     * Callback to be called Before crud operation delete
     * @var unknown_type
     */
    protected $_callbackBeforeDelete = null;

    /**
     * Callback to be called Before crud operation insert
     * @var unknown_type
     */
    protected $_callbackBeforeInsert = null;

    /**
     * Contains result of deploy() function.
     *
     * @var string
     */
    protected $_deploymentContent = null;

    /**
     * Url param with the information about removing records
     *
     * @var string
     */
    protected $_comm;


    /**
     *
     * @var Zend_Form
     */
    protected $_form;


    /**
     *
     * @var Zend_Session_Abstract
     */
    protected $_gridSession = null;


    /**
     * Print class based on conditions
     * @var array
     */
    protected $_classRowCondition = array();

    protected $_classRowConditionResult = array();

    protected $_classCellCondition = array();

    protected $_extraRows = array();

    protected $_render = array();

    protected $_renderDeploy = array();


    /**
     * To edit, add, or delete records, a user must be authenticated, so we instanciate
     * it here.
     *
     * @param array $data
     */
    function __construct ($options)
    {

        $this->_gridSession = new Zend_Session_Namespace('Bvb_Grid');
        $this->_setRemoveHiddenFields(true);

        parent::__construct($options);

        $this->addTemplateDir('Bvb/Grid/Template/Table', 'Bvb_Grid_Template_Table', 'table');

    }


    /**
     * @param string $var
     * @param string $value
     */

    function __set ($var, $value)
    {
        parent::__set($var, $value);
    }


    /**
     *
     * Process all information forms related
     * First we check for permissions to add, edit, delete
     * And then the request->isPost. If true we process the data
     *
     */

    protected function _processForm ()
    {

        if ( ! $this->getSource()->hasCrud() ) {
            return false;
        }

        if ( isset($this->info['add']['allow']) && $this->info['add']['allow'] == 1 ) {
            $this->allowAdd = 1;
        }

        if ( isset($this->info['delete']['allow']) && $this->info['delete']['allow'] == 1 ) {
            $this->allowDelete = 1;
        }

        if ( isset($this->info['edit']['allow']) && $this->info['edit']['allow'] == 1 ) {
            $this->allowEdit = 1;
        }

        // IF a user can edit or delete data we must instanciate the crypt classe.
        // This is an extra-security step.
        if ( $this->allowEdit == 1 || $this->allowDelete ) {
            $dec = isset($this->ctrlParams['comm' . $this->_gridId]) ? $this->ctrlParams['comm' . $this->_gridId] : '';
            $this->_comm = $dec;
        }

        /**
         * Remove if there is something to remove
         */
        if ( $this->allowDelete ) {
            self::_deleteRecord($dec);
        }


        if ( $this->allowAdd == 1 || $this->allowEdit == 1 ) {
            $opComm = isset($this->ctrlParams['comm' . $this->_gridId]) ? $this->ctrlParams['comm' . $this->_gridId] : '';
            $op_query = self::_convertComm($opComm);


            $mode = isset($this->ctrlParams['edit' . $this->_gridId]) ? 'edit' : 'add';

            $queryUrl = $this->_getPkFromUrl();


            if ( ! Zend_Controller_Front::getInstance()->getRequest()->isPost() ) {

                foreach ( array_keys($this->_form->getElements()) as $element ) {
                    if ( isset($this->_gridSession->errors[$element]) ) {
                        $this->_form->getElement($element)->setErrors($this->_gridSession->errors[$element]);
                    }
                }

                if ( isset($this->ctrlParams['add' . $this->_gridId]) && $this->ctrlParams['add' . $this->_gridId] == 1 ) {
                    $this->_willShow['form'] = true;
                    $this->_willShow['formAdd'] = true;
                }

                if ( $mode == 'edit' ) {

                    $this->_willShow['form'] = true;
                    $this->_willShow['formEdit'] = true;
                    $this->_willShow['formEditId'] = $this->_getPkFromUrl();

                    $r = $this->getSource()->getRecord($this->_crudTable, $this->_getPkFromUrl());

                    if ( $r === false ) {
                        $this->_gridSession->message = $this->__('Record Not Found');
                        $this->_gridSession->_noForm = 1;
                        $this->_gridSession->correct = 1;
                    }

                    if ( is_array($r) ) {
                        foreach ( $r as $key => $value ) {
                            $isField = $this->_form->getElement($key);

                            if ( isset($isField) ) {

                                $fieldType = $this->getSource()->getFieldType($this->data['fields'][$key]['field']);

                                if ( $fieldType == 'set' ) {
                                    $value = explode(',', $value);
                                }

                                if ( isset($this->_gridSession->post) && is_array($this->_gridSession->post) ) {
                                    if ( isset($this->_gridSession->post[$key]) ) {
                                        $this->_form->getElement($key)->setValue($this->_gridSession->post[$key]);
                                    }
                                } else {
                                    $this->_form->getElement($key)->setValue($value);
                                }

                            }
                        }
                    }
                }
            }
        }


        //Check if the request method is POST
        if ( Zend_Controller_Front::getInstance()->getRequest()->isPost() && Zend_Controller_Front::getInstance()->getRequest()->getPost('_form_edit' . $this->_gridId) == 1 ) {


            if ( $this->_form->isValid($_POST) ) {

                $post = array();

                foreach ( $this->_form->getElements() as $el ) {
                    $post[$el->getName()] = is_array($el->getValue()) ? implode(',', $el->getValue()) : $el->getValue();
                }

                unset($post['form_submit' . $this->_gridId]);
                unset($post['_form_edit' . $this->_gridId]);
                unset($post['form_reset' . $this->_gridId]);

                $param = Zend_Controller_Front::getInstance()->getRequest();


                // Process data
                if ( $mode == 'add' ) {

                    try {

                        $sendCall = array(&$post, $this->getSource());

                        if ( null !== $this->_callbackBeforeInsert ) {
                            call_user_func($this->_callbackBeforeInsert, $sendCall);
                        }


                        $this->getSource()->insert($this->_crudTable, $post);


                        if ( null !== $this->_callbackAfterInsert ) {
                            call_user_func($this->_callbackAfterInsert, $sendCall);
                        }

                        $this->_gridSession->message = $this->__('Record saved');
                        $this->_gridSession->messageOk = true;
                        $this->_gridSession->_comm = false;
                        $this->_gridSession->_noForm = 1;

                        $this->_gridSession->correct = 1;

                        unset($this->_gridSession->post);

                        $this->_removeFormParams($post, array('add' . $this->_gridId));


                        $this->_redirect($this->getUrl());

                    }
                    catch (Zend_Exception $e) {
                        $this->_gridSession->messageOk = FALSE;
                        $this->_gridSession->message = $this->__('Error saving record: ') . $e->getMessage();
                        $this->_gridSession->formSuccess = 0;
                        $this->_gridSession->formPost = 1;
                        $this->_gridSession->_noForm = 0;

                        $this->_gridSession->correct = 0;
                    }

                }

                // Process data
                if ( $mode == 'edit' ) {

                    try {

                        $sendCall = array(&$post, $this->getSource());

                        if ( null !== $this->_callbackBeforeUpdate ) {
                            call_user_func($this->_callbackBeforeUpdate, $sendCall);
                        }

                        $this->getSource()->update($this->_crudTable, $post, $queryUrl);


                        if ( null !== $this->_callbackAfterUpdate ) {
                            call_user_func($this->_callbackAfterUpdate, $sendCall);
                        }

                        $this->_gridSession->message = $this->__('Record saved');
                        $this->_gridSession->messageOk = true;

                        $this->_gridSession->_comm = false;
                        $this->_gridSession->_noForm = 1;

                        $this->_gridSession->correct = 1;

                        unset($this->_gridSession->post);

                        $this->_removeFormParams($post, array('comm' . $this->_gridId, 'edit' . $this->_gridId));

                        $this->_redirect($this->getUrl());

                    }
                    catch (Zend_Exception $e) {
                        $this->_gridSession->messageOk = FALSE;
                        $this->_gridSession->message = $this->__('Error updating record: ') . $e->getMessage();
                        $this->_gridSession->formSuccess = 0;
                        $this->_gridSession->formPost = 1;
                        $this->_gridSession->_noForm = 0;

                        $this->_gridSession->correct = 0;

                        $this->_removeFormParams($post);
                        $this->_redirect($this->getUrl());
                    }
                }

            } else {

                $this->_gridSession->post = $_POST;
                $this->_gridSession->errors = $this->_form->getMessages();

                $this->_gridSession->message = $this->__('Validation failed');
                $this->_gridSession->messageOk = false;
                $this->_gridSession->formSuccess = 0;
                $this->_gridSession->formPost = 1;
                $this->_gridSession->_noForm = 0;
                $this->_gridSession->correct = 0;
                $this->_removeFormParams($_POST);

                $this->_redirect($this->getUrl());
            }

        }

    }


    protected function _redirect ($url, $code = 302)
    {
        $response = Zend_Controller_Front::getInstance()->getResponse();
        $response->setRedirect($url, $code);
        $response->sendResponse();
        die();
    }


    protected function _removeFormParams ($post, $extra = array())
    {

        if ( count($extra) > 0 ) $post = array_merge($post, array_combine($extra, $extra));


        foreach ( $post as $key => $value ) {
            unset($this->ctrlParams[$key]);
        }

        unset($this->ctrlParams['form_submit' . $this->_gridId]);
        unset($this->ctrlParams['_form_edit' . $this->_gridId]);


        return true;
    }


    /**
     * Remove the record from the table
     * Don't forget to see if the user as set an "extra" WHERE.
     *
     * @param string $sql
     * @param string $user
     * @return string
     */
    protected function _deleteRecord ($sql)
    {

        @$param = explode(";", $sql);

        foreach ( $param as $value ) {
            $dec = explode(":", $value);
            @$final[$dec[0]] = $dec[1];
        }

        if ( @$final['mode'] != 'delete' ) {
            return 0;
        }

        if ( isset($this->info['delete']['where']) && is_array($this->info['delete']['where']) ) {
            $condition = array_merge($this->info['delete']['where'], $this->_getPkFromUrl());
        } else {
            $condition = $this->_getPkFromUrl();
        }

        try {

            $pkParentArray = $this->_getPrimaryKey();
            $pkParent = $pkParentArray[0];

            $sendCall = array(&$condition, $this->getSource());

            if ( null !== $this->_callbackBeforeDelete ) {
                call_user_func($this->_callbackBeforeDelete, $sendCall);
            }


            $resultDelete = $this->getSource()->delete($this->_crudTable, $condition);


            if ( $resultDelete == 1 ) {
                if ( null !== $this->_callbackAfterDelete ) {
                    call_user_func($this->_callbackAfterDelete, $sendCall);
                }
            }

            $this->_gridSession->messageOk = true;
            $this->_gridSession->message = $this->__('Record deleted');
            $this->_gridSession->correct = 1;

            $this->_redirect($this->getUrl('comm'));

        }
        catch (Zend_Exception $e) {
            $this->_gridSession->messageOk = FALSE;
            $this->_gridSession->message = $this->__('Error deleting record =>') . $e->getMessage();
        }

        unset($this->ctrlParams['comm' . $this->_gridId]);

        return true;
    }


    /**
     * Build the first line of the table (Not the TH )
     *
     * @return string
     */
    protected function _buildHeader ()
    {

        $url = $this->getUrl(array('comm', 'edit', 'filters', 'order'));

        $final = '';

        if ( $this->getSource()->hasCrud() ) {
            $this->_render['addButton'] = "<div class=\"addRecord\" ><a href=\"$url/add" . $this->_gridId . "/1\">" . $this->__('Add Record') . "</a></div>";
            if ( ($this->getInfo('doubleTables') == 0 && @$this->ctrlParams['add' . $this->_gridId] != 1 && @$this->ctrlParams['edit' . $this->_gridId] != 1) && $this->_getPrimaryKey() && @$this->info['add']['allow'] == 1 && @$this->info['add']['button'] == 1 && @$this->info['add']['no_button'] != 1 ) {
                $this->_renderDeploy['addButton'] = $this->_render['addButton'];
            }
        }

        /**
         * We must check if there is a filter set or an order, to show the extra th on top
         */


        if ( isset($this->ctrlParams['filters' . $this->_gridId]) || isset($this->ctrlParams['order' . $this->_gridId]) ) {

            $url = $this->getUrl('filters', 'nofilters');
            $url2 = $this->getUrl(array('order', 'noOrder'));
            $url3 = $this->getUrl(array('filters', 'order', 'noFilters', 'noOrder'));

            if ( is_array($this->_defaultFilters) ) {
                $url .= '/nofilters/1';
                $url3 .= '/nofilters/1';
            }

            if ( is_array($this->getSource()->getSelectOrder()) ) {

                $url3 .= '/noOrder/1';
                $url2 .= '/noOrder/1';
            }

            $this->temp['table']->hasExtraRow = 1;

            //Filters and order
            if ( isset($this->ctrlParams['filters' . $this->_gridId]) and isset($this->ctrlParams['order' . $this->_gridId]) && ! isset($this->ctrlParams['noOrder' . $this->_gridId]) ) {
                if ( isset($this->info['ajax']) && $this->info['ajax'] !== false ) {

                    $final1 = "<a href=\"javascript:gridAjax('{$this->info['ajax']}','" . $url . "')\">" . $this->__('Remove Filters') . "</a> | <a href=\"javascript:gridAjax('{$this->info['ajax']}','" . $url2 . "')\">" . $this->__('Remove Order') . "</a> | <a href=\"javascript:gridAjax('{$this->info['ajax']}','" . $url3 . "')\">" . $this->__('Remove Filters &amp; Order') . "</a>";

                } else {
                    $final1 = "<a href=\"$url\">" . $this->__('Remove Filters') . "</a> | <a href=\"$url2\">" . $this->__('Remove Order') . "</a> | <a href=\"$url3\">" . $this->__('Remove Filters &amp; Order') . "</a>";
                }
                //Only filters
            } elseif ( isset($this->ctrlParams['filters' . $this->_gridId]) && (! isset($this->ctrlParams['order' . $this->_gridId]) || isset($this->ctrlParams['noOrder' . $this->_gridId])) ) {


                if ( isset($this->info['ajax']) && $this->info['ajax'] !== false ) {

                    $final1 = "<a href=\"javascript:gridAjax('{$this->info['ajax']}','" . $url . "') \">" . $this->__('Remove Filters') . "</a>";

                } else {
                    $final1 = "<a href=\"$url\">" . $this->__('Remove Filters') . "</a>";
                }

            //Only order
            } elseif ( ! isset($this->ctrlParams['filters' . $this->_gridId]) && (isset($this->ctrlParams['order' . $this->_gridId]) && ! isset($this->ctrlParams['noOrder' . $this->_gridId])) ) {

                if ( isset($this->info['ajax']) && $this->info['ajax'] !== false ) {

                    $final1 = "<a href=\"javascript:gridAjax('{$this->info['ajax']}','" . $url2 . "') \">" . $this->__('Remove Order') . "</a>";

                } else {
                    $final1 = "<a href=\"$url2\">" . $this->__('Remove Order') . "</a>";
                }
            }


            //Replace values
            if ( count($this->_filtersValues) > 0 || (isset($this->ctrlParams['order' . $this->_gridId]) && ! isset($this->ctrlParams['noOrder' . $this->_gridId])) ) {
                $this->_render['extra'] = str_replace("{{value}}", $final1, $this->temp['table']->extra());
                $this->_renderDeploy['extra'] = str_replace("{{value}}", $final1, $this->temp['table']->extra());
            }


            $this->_render['titles'] = self::_buildExtraRows('beforeHeader');
            $this->_renderDeploy['titles'] = self::_buildExtraRows('beforeHeader');
            //close cycle
        }

        return;
    }


    /**
     *
     * Build filters.
     *
     * We receive the information from an array
     * @param array $filters
     * @return unknown
     */
    protected function _buildFiltersTable ($filters)
    {

        //There are no filters.
        if ( ! is_array($filters) ) {
            $this->temp['table']->hasFilters = 0;
            return '';
        }

        //Start the template
        $grid = $this->temp['table']->filtersStart();

        foreach ( $filters as $filter ) {

            //Check extra fields
            if ( $filter['type'] == 'extraField' && $filter['position'] == 'left' ) {
                //Replace values
                $filterValue = isset($filter['value']) ? $filter['value'] : '';

                $grid .= str_replace('{{value}}', $filterValue . '&nbsp;', $this->temp['table']->filtersLoop());
            }

            $hRowField = isset($this->info['hRow']['field']) ? $this->info['hRow']['field'] : '';

            //Check if we have an horizontal row
            if ( (@$filter['field'] != $hRowField && isset($this->info['hRow']['title'])) || ! isset($this->info['hRow']['title']) ) {

                if ( $filter['type'] == 'field' ) {
                    //Replace values
                    $grid .= str_replace('{{value}}', $this->_formatField($filter['field']), $this->temp['table']->filtersLoop());
                }
            }

            //Check extra fields from the right
            if ( $filter['type'] == 'extraField' && $filter['position'] == 'right' ) {
                @ $grid .= str_replace('{{value}}', $filter['value'], $this->temp['table']->filtersLoop());
            }

        }

        //Close template
        $grid .= $this->temp['table']->filtersEnd();

        return $grid;
    }


    /**
     * Buil Table titles.
     *
     * @param array $titles
     * @return string
     */
    protected function _buildTitlesTable ($titles)
    {
        $orderField = null;

        if ( is_array($this->order) ) {
            //We must now the field that is being ordered. So we can grab the image
            $order = array_keys($this->order);
            $order2 = array_keys(array_flip($this->order));

            //The field that is being ordered
            $orderField = $order[0];

            //The oposite order
            $order = strtolower($order2[0]);
        }

        //Lets get the images for defining the order
        $images = $this->temp['table']->images($this->imagesUrl);

        //Iniciate titles template
        $grid = $this->temp['table']->titlesStart();

        if ( $orderField === null ) {
            //Lets get the default order using in the query (Zend_Db)
            $queryOrder = $this->getSource()->getSelectOrder();

            if ( count($queryOrder) > 0 ) {
                $order = strtolower($queryOrder[1]) == 'asc' ? 'desc' : 'asc';
                $orderField = $queryOrder[0];
            }
        }

        if ( isset($this->ctrlParams['noOrder' . $this->_gridId]) ) {
            $orderField = null;
        }

        foreach ( $titles as $title ) {

            //deal with extra field and template
            if ( $title['type'] == 'extraField' && $title['position'] == 'left' ) {
                $grid .= str_replace('{{value}}', $title['value'], $this->temp['table']->titlesLoop());
            }

            $hRowTitle = isset($this->info['hRow']['field']) ? $this->info['hRow']['field'] : '';

            if ( (@$title['field'] != $hRowTitle && isset($this->info['hRow']['title'])) || ! isset($this->info['hRow']['title']) ) {

                if ( $title['type'] == 'field' ) {


                    $noOrder = isset($this->info['noOrder']) ? $this->info['noOrder'] : '';

                    if ( $noOrder == 1 ) {

                        //user set the noOrder(1) method
                        $grid .= str_replace('{{value}}', $this->__($title['value']), $this->temp['table']->titlesLoop());

                    } else {

                        if ( ! isset($this->data['fields'][$title['field']]['order']) ) {
                            $this->data['fields'][$title['field']]['order'] = true;
                        }

                        if ( isset($this->info['ajax']) && $this->info['ajax'] !== false ) {


                            $link1 = "<a  href=\"javascript:gridAjax('{$this->info['ajax']}','{$title['simpleUrl']}/order{$this->_gridId}/{$title['field']}_DESC')\">{$images['desc']}</a>";
                            $link2 = "<a  href=\"javascript:gridAjax('{$this->info['ajax']}','{$title['simpleUrl']}/order{$this->_gridId}/{$title['field']}_ASC')\">{$images['asc']}</a>";

                            if ( ($orderField == $title['field'] && $order == 'asc') || $this->data['fields'][$title['field']]['order'] == 0 ) {
                                $link1 = '';
                            }

                            if ( ($orderField == $title['field'] && $order == 'desc') || $this->data['fields'][$title['field']]['order'] == 0 ) {
                                $link2 = '';
                            }

                            $grid .= str_replace('{{value}}', $link2 . $title['value'] . $link1, $this->temp['table']->titlesLoop());

                        } else {
                            //Replace values in the template
                            if ( ! array_key_exists('url', $title) ) {
                                $grid .= str_replace('{{value}}', $title['value'], $this->temp['table']->titlesLoop());
                            } else {

                                $link1 = "<a  href='" . $title['simpleUrl'] . "/order{$this->_gridId}/{$title['field']}_DESC'>{$images['desc']}</a>";
                                $link2 = "<a  href='" . $title['simpleUrl'] . "/order{$this->_gridId}/{$title['field']}_ASC'>{$images['asc']}</a>";

                                if ( ($orderField == $title['field'] && $order == 'asc') || $this->data['fields'][$title['field']]['order'] == 0 ) {
                                    $link1 = '';
                                }

                                if ( ($orderField == $title['field'] && $order == 'desc') || $this->data['fields'][$title['field']]['order'] == 0 ) {
                                    $link2 = '';
                                }

                                $grid .= str_replace('{{value}}', $link2 . $title['value'] . $link1, $this->temp['table']->titlesLoop());
                            }
                        }
                    }
                }
            }

            //Deal with extra fields
            if ( $title['type'] == 'extraField' && $title['position'] == 'right' ) {
                $grid .= str_replace('{{value}}', $title['value'], $this->temp['table']->titlesLoop());
            }

        }

        //End template
        $grid .= $this->temp['table']->titlesEnd();

        return $grid;

    }


    /**
     * Convert url  params
     *
     * @return array
     */
    protected function _convertComm ()
    {

        $t = explode(";", $this->_comm);

        foreach ( $t as $value ) {
            $value = explode(":", $value);
            @$final[$value[0]] = $value[1];
        }

        return $final;
    }


    /**
     * Buil the table
     *
     * @param array $grids | db results
     * @return unknown
     */
    protected function _buildGridTable ($grids)
    {

        $i = 0;
        $grid = '';

        //We have an extra td for the text to remove filters and order
        if ( isset($this->ctrlParams['filters' . $this->_gridId]) || isset($this->ctrlParams['order' . $this->_gridId]) ) {
            $i ++;
        }

        if ( isset($this->info['hRow']['title']) && $this->_totalRecords > 0 ) {

            $bar = $grids;

            $hbar = trim($this->info['hRow']['field']);

            $p = 0;

            foreach ( $grids[0] as $value ) {


                if ( isset($value['field']) && $value['field'] == $hbar ) {
                    $hRowIndex = $p;
                }

                $p ++;
            }
            $aa = 0;
        }

        $aa = 0;
        $class = 0;
        $fi = array();
        foreach ( $grids as $value ) {

            unset($fi);
            // decorators
            $search = $this->_finalFields;
            foreach ( $search as $key => $final ) {
                if ( $final['type'] == 'extraField' ) {
                    unset($search[$key]);
                }
            }

            $search = array_keys($search);

            foreach ( $value as $tia ) {

                if ( isset($tia['field']) ) {
                    $fi[] = $tia['value'];
                }
            }


            if ( $this->getSource()->hasCrud() ) {

                if ( isset($search[0]) && ($search[0] === 'D' || $search[0] === 'E' || $search[0] === 'V') ) {
                    unset($search[0]);
                }

                if ( isset($search[1]) && ($search[1] === 'D' || $search[1] === 'E') ) {
                    unset($search[1]);
                }

                if ( isset($search[2]) && ($search[2] === 'D' || $search[2] === 'E') ) {
                    unset($search[2]);
                }
            } else {
                if ( isset($search[0]) && $search[0] === 'V' ) {
                    unset($search[0]);
                }
            }

            $search = $this->_reset_keys($search);


            $finalFields = array_combine($search, $fi);

            //horizontal row
            if ( isset($this->info['hRow']['title']) ) {

                if ( $bar[$aa][$hRowIndex]['value'] != @$bar[$aa - 1][$hRowIndex]['value'] ) {
                    $i ++;

                    $grid .= str_replace(array("{{value}}", "{{class}}"), array($bar[$aa][$hRowIndex]['value'], @$value['class']), $this->temp['table']->hRow($finalFields));
                }
            }

            $i ++;

            //loop tr
            $grid .= $this->temp['table']->loopStart(isset($this->_classRowConditionResult[$class]) ? $this->_classRowConditionResult[$class] : '');

            $set = 0;
            foreach ( $value as $final ) {

                $finalField = isset($final['field']) ? $final['field'] : '';
                $finalHrow = isset($this->info['hRow']['field']) ? $this->info['hRow']['field'] : '';

                if ( ($finalField != $finalHrow && isset($this->info['hRow']['title'])) || ! isset($this->info['hRow']['title']) ) {

                    $set ++;

                    $grid .= str_replace(array("{{value}}", "{{class}}", "{{style}}"), array($final['value'], $final['class'], $final['style']), $this->temp['table']->loopLoop($finalFields));

                }
            }

            $set = null;
            $grid .= $this->temp['table']->loopEnd($finalFields);

            @$aa ++;
            $class ++;
        }

        if ( $this->_totalRecords == 0 ) {
            $grid = str_replace("{{value}}", $this->__('No records found'), $this->temp['table']->noResults());
        }

        return $grid;

    }


    /**
     * Biuild the table that handles the query result from sql expressions
     *
     * @param array $sql
     * @return unknown
     */
    protected function _buildSqlexpTable ($sql)
    {

        $grid = '';
        if ( is_array($sql) ) {
            $grid .= $this->temp['table']->sqlExpStart();

            foreach ( $sql as $exp ) {
                if ( ! isset($this->info['hRow']['field']) || $exp['field'] != @$this->info['hRow']['field'] ) {
                    $grid .= str_replace(array("{{value}}", '{{class}}'), array($exp['value'], $exp['class']), $this->temp['table']->sqlExpLoop());
                }
            }
            $grid .= $this->temp['table']->sqlExpEnd();

        } else {
            return false;
        }

        return $grid;

    }


    /**
     * Build pagination
     *
     * @return string
     */
    protected function _pagination ()
    {

        $f = '';

        $url = $this->getUrl(array('start'));

        $actual = (int) isset($this->ctrlParams['start' . $this->_gridId]) ? $this->ctrlParams['start' . $this->_gridId] : 0;

        $ppagina = $this->pagination;
        $result2 = '';

        $pa = $actual == 0 ? 1 : ceil($actual / $ppagina) + 1;

        // Calculate the number of pages
        if ( $this->pagination > 0 ) {
            $npaginas = ceil($this->_totalRecords / $ppagina);
            $actual = floor($actual / $ppagina) + 1;
        } else {
            $npaginas = 0;
            $actual = 0;
        }

        if ( isset($this->info['ajax']) && $this->info['ajax'] !== false ) {
            $pag = ($actual == 1) ? '<strong>1</strong>' : "<a href=\"javascript:gridAjax('{$this->info['ajax']}','$url/star{$this->_gridId}t/0')\">1</a>";
        } else {
            $pag = ($actual == 1) ? '<strong>1</strong>' : "<a href=\"$url/start{$this->_gridId}/0\">1</a>";

        }

        $pag .= ($actual > 5) ? " ... " : "  ";

        if ( $npaginas > 5 ) {
            $in = min(max(1, $actual - 4), $npaginas - 5);
            $fin = max(min($npaginas, $actual + 4), 6);

            for ( $i = $in + 1; $i < $fin; $i ++ ) {
                if ( isset($this->info['ajax']) && $this->info['ajax'] !== false ) {
                    $pag .= ($i == $actual) ? "<strong> $i </strong>" : " <a href=javascript:gridAjax('{$this->info['ajax']}','$url/start{$this->_gridId}/" . (($i - 1) * $ppagina) . "')> $i </a>";
                } else {
                    $pag .= ($i == $actual) ? "<strong> $i </strong>" : " <a href='$url/start{$this->_gridId}/" . (($i - 1) * $ppagina) . "'> $i </a>";
                }

            }

            $pag .= ($fin < $npaginas) ? " ... " : "  ";
        } else {

            for ( $i = 2; $i < $npaginas; $i ++ ) {
                if ( isset($this->info['ajax']) && $this->info['ajax'] !== false ) {

                    $pag .= ($i == $actual) ? "<strong> $i </strong>" : " <a href=\"javascript:gridAjax('{$this->info['ajax']}','" . $url . "/start{$this->_gridId}/" . (($i - 1) * $ppagina) . "')\">$i</a> ";

                } else {

                    $pag .= ($i == $actual) ? "<strong> $i </strong>" : " <a href=\"" . $url . "/start{$this->_gridId}/" . (($i - 1) * $ppagina) . "\">$i</a> ";

                }

            }
        }

        if ( isset($this->info['ajax']) && $this->info['ajax'] !== false ) {
            $pag .= ($actual == $npaginas) ? "<strong>" . $npaginas . "</strong>" : " <a href=\"javascript:gridAjax('{$this->info['ajax']}','$url/start{$this->_gridId}/" . (($npaginas - 1) * $ppagina) . "')\">$npaginas</a> ";

        } else {
            $pag .= ($actual == $npaginas) ? "<strong>" . $npaginas . "</strong>" : " <a href=\"$url/start{$this->_gridId}/" . (($npaginas - 1) * $ppagina) . "\">$npaginas</a> ";

        }

        if ( $actual != 1 ) {

            if ( isset($this->info['ajax']) && $this->info['ajax'] !== false ) {
                $pag = " <a href=\"javascript:gridAjax('{$this->info['ajax']}','$url/start{$this->_gridId}/0')\">" . $this->__('First') . "</a>&nbsp;&nbsp;<a href=\"javascript:agridAjax('{$this->info['ajax']}','$url/start/" . (($actual - 2) * $ppagina) . "')\">" . $this->__('Previous') . "</a>&nbsp;&nbsp;" . $pag;

            } else {

                $pag = " <a href=\"$url/start/0\">" . $this->__('First') . "</a>&nbsp;&nbsp;<a href=\"$url/start{$this->_gridId}/" . (($actual - 2) * $ppagina) . "\">" . $this->__('Previous') . "</a>&nbsp;&nbsp;" . $pag;
            }

        }

        if ( $actual != $npaginas ) {
            if ( isset($this->info['ajax']) && $this->info['ajax'] !== false ) {

                $pag .= "&nbsp;&nbsp;<a href=\"javascript:gridAjax('{$this->info['ajax']}','$url/start{$this->_gridId}/" . ($actual * $ppagina) . "')\">" . $this->__('Next') . "</a> <a href=\"javascript:gridAjax('{$this->info['ajax']}','$url/start{$this->_gridId}/" . (($npaginas - 1) * $ppagina) . "')\">" . $this->__('Last') . "&nbsp;&nbsp;</a>";
            } else {

                $pag .= "&nbsp;&nbsp;<a href=\"$url/start{$this->_gridId}/" . ($actual * $ppagina) . "\">" . $this->__('Next') . "</a>&nbsp;&nbsp;<a href=\"$url/start{$this->_gridId}/" . (($npaginas - 1) * $ppagina) . "\">" . $this->__('Last') . "</a>";
            }

        }

        if ( $npaginas > 1 && isset($this->info['limit']) && (int) @$this->info['limit'] == 0 ) {

            if ( $npaginas < 50 ) {
                // Buil the select form element
                if ( isset($this->info['ajax']) && $this->info['ajax'] !== false ) {
                    $f = "<select id=\"idf\" onchange=\"javascript:gridAjax('{$this->info['ajax']}','{$url}/start{$this->_gridId}/'+this.value)\">";
                } else {
                    $f = "<select id=\"idf\" onchange=\"window.location='{$url}/start{$this->_gridId}/'+this.value\">";
                }

                for ( $i = 1; $i <= $npaginas; $i ++ ) {
                    $f .= "<option ";
                    if ( $pa == $i ) {
                        $f .= " selected ";
                    }
                    $f .= " value=\"" . (($i - 1) * $ppagina) . "\">$i</option>\n";
                }
                $f .= "</select>";

            } else {
                #$f ='<input type="text" size="3" style="width:40px !important;">';
                $f = '';
            }

        }

        if ( $npaginas > 1 || count($this->export) > 0 ) {

            //get actual record
            if ( $actual <= 1 ) {
                $registoActual = 1;
                $registoFinal = $this->_totalRecords > $ppagina ? $ppagina : $this->_totalRecords;
            } else {
                $registoActual = $actual * $ppagina - $ppagina;

                if ( $actual * $ppagina > $this->_totalRecords ) {
                    $registoFinal = $this->_totalRecords;
                } else {
                    $registoFinal = $actual * $ppagina;
                }

            }

            $images = $this->temp['table']->images($this->imagesUrl);

            $exp = '';


            foreach ( $this->getExports() as $export ) {

                $export['newWindow'] = isset($export['newWindow']) ? $export['newWindow'] : true;
                $class = isset($export['cssClass']) ? 'class="' . $export['cssClass'] . '"' : '';

                $blank = $export['newWindow'] == false ? '' : "target='_blank'";

                if ( isset($this->imagesUrl) ) {
                    $export['img'] = $this->imagesUrl . $export['caption'] . '.gif';

                }

                if ( isset($export['img']) ) {
                    $exp .= "<a title='{$export['caption'] }' $class $blank href='$url/_exportTo{$this->_gridId}/{$export['caption']}'><img alt='{$export['caption']}' src='{$export ['img']}' border='0'></a>";
                } else {
                    $exp .= "<a title='{$export['caption'] }'  $class $blank href='$url/_exportTo{$this->_gridId}/{$export['caption']}'>" . $export['caption'] . "</a>";
                }
            }

            if ( isset($this->info['limit']) && (int) @$this->info['limit'] > 0 ) {
                $result2 = str_replace(array('{{export}}', '{{pagination}}', '{{numberRecords}}'), array($exp, '', (int) $this->info['limit']), $this->temp['table']->pagination());

            } elseif ( $npaginas > 1 && count($this->export) > 0 ) {

                if ( $this->pagination == 0 ) {
                    $pag = '';
                    $f = '';
                }

                $result2 = str_replace(array('{{export}}', '{{pagination}}', '{{numberRecords}}'), array($exp, $pag, $registoActual . ' ' . $this->__('to') . ' ' . $registoFinal . ' ' . $this->__('of') . '  ' . $this->_totalRecords), $this->temp['table']->pagination());

            } elseif ( $npaginas < 2 && count($this->export) > 0 ) {

                if ( $this->pagination == 0 ) {
                    $pag = '';
                    $f = '';
                }
                $result2 .= str_replace(array('{{export}}', '{{pagination}}', '{{numberRecords}}'), array($exp, '', $this->_totalRecords), $this->temp['table']->pagination());

            } elseif ( count($this->export) == 0 ) {

                if ( $this->pagination == 0 ) {
                    $pag = '';
                    $f = '';
                }
                $result2 .= str_replace(array('{{export}}', '{{pagination}}', '{{numberRecords}}'), array('', $pag, $this->_totalRecords), $this->temp['table']->pagination());

            }

        } else {
            return '';
        }

        return $result2;
    }


    /**
     * Here we go....
     *
     * @return string
     */
    function deploy ()
    {

        $this->_view = $this->getView();

        $this->_processForm();

        parent::deploy();

        $this->_applyConfigOptions(array(), true);


        if ( ! $this->temp['table'] instanceof Bvb_Grid_Template_Table_Table ) {
            $this->setTemplate('table', 'table', $this->_templateParams);
        } else {
            $this->setTemplate($this->temp['table']->options['name'], 'table', $this->_templateParams);
        }


        // The extra fields, they are not part of database table.
        // Usefull for adding links (a least for me :D )
        $this->_printScript();


        $images = $this->temp['table']->images($this->imagesUrl);

        if ( $this->allowDelete == 1 || $this->allowEdit == 1 || (is_array($this->_detailColumns) && $this->_isDetail == false) ) {
            $pkUrl = $this->_getPrimaryKey();
            $urlFinal = '';

            if ( is_array($pkUrl) ) {
                foreach ( $pkUrl as $value ) {
                    $urlFinal .= $value . ':{{' . $value . '}}-';
                }
            }

            $urlFinal = trim($urlFinal, '-');

        }

        if ( $this->allowEdit == 1 ) {
            if ( ! is_array($this->extra_fields) ) {
                $this->extra_fields = array();
            }
            // Remove the unnecessary URL params
            #$removeParams = array ('filters', 'add' );
            $removeParams = array('add', 'edit', 'comm');

            $url = $this->getUrl($removeParams);

            if ( $this->allowEdit == 1 && isset($this->info['ajax']) && $this->info['ajax'] !== false ) {
                $urlEdit = $this->_baseUrl . '/' . str_replace("/gridmod" . $this->_gridId . "/ajax", "", $url);
            } else {
                $urlEdit = $url;
            }

            array_unshift($this->extra_fields, array('position' => 'left', 'name' => 'E', 'decorator' => "<a href=\"$urlEdit/edit" . $this->_gridId . "/1/comm" . $this->_gridId . "/" . "mode:edit;[" . $urlFinal . "]\" > " . $images['edit'] . "</a>", 'edit' => true));

        }


        if ( $this->allowDelete ) {
            if ( ! is_array($this->extra_fields) ) {
                $this->extra_fields = array();
            }

            if ( count($this->_gridColumns) > 0 ) {
                array_unshift($this->extra_fields, array('position' => 'left', 'name' => 'D', 'decorator' => "<a href=\"$url/comm" . $this->_gridId . "/" . "mode:view;[" . $urlFinal . "]/gridDetail" . $this->_gridId . "/1/gridRemove" . $this->_gridId . "/1\" > " . $images['delete'] . "</a>", 'delete' => true));
            } else {
                array_unshift($this->extra_fields, array('position' => 'left', 'name' => 'D', 'decorator' => "<a href=\"#\" onclick=\"_" . $this->_gridId . "confirmDel('" . $this->__('Are you sure?') . "','$url/comm" . $this->_gridId . "/" . "mode:delete;[" . $urlFinal . "]');\" > " . $images['delete'] . "</a>", 'delete' => true));
            }

        }


        if ( is_array($this->_detailColumns) && $this->_isDetail == false ) {
            if ( ! is_array($this->extra_fields) ) {
                $this->extra_fields = array();
            }

            $removeParams = array('add', 'edit', 'comm');
            $url = $this->getUrl($removeParams);

            array_unshift($this->extra_fields, array('position' => 'left', 'name' => 'V', 'decorator' => "<a href=\"$url/gridDetail" . $this->_gridId . "/1/comm" . $this->_gridId . "/" . "mode:view;[" . $urlFinal . "]/\" >" . $images['detail'] . "</a>", 'detail' => true));
        }


        if ( $this->allowAdd == 0 && $this->allowDelete == 0 && $this->allowEdit == 0 ) {
            $this->_gridSession->unsetAll();
        }

        if ( ! in_array('add' . $this->_gridId, array_keys($this->ctrlParams)) && ! in_array('edit' . $this->_gridId, array_keys($this->ctrlParams)) ) {

            if ( $this->_gridSession->correct === NULL || $this->_gridSession->correct === 0 ) {
                $this->_gridSession->unsetAll();
            }
        }

        if ( strlen($this->_gridSession->message) > 0 ) {
            $this->_render['message'] = str_replace("{{value}}", $this->_gridSession->message, $this->temp['table']->formMessage($this->_gridSession->messageOk));
            $this->_renderDeploy['message'] = $this->_render['message'];
        }


        $this->_render['form'] = $this->_form;
        if ( ((isset($this->ctrlParams['edit' . $this->_gridId]) && $this->ctrlParams['edit' . $this->_gridId] == 1) || (isset($this->ctrlParams['add' . $this->_gridId]) && $this->ctrlParams['add' . $this->_gridId] == 1) || (isset($this->info['doubleTables']) && $this->info['doubleTables'] == 1)) ) {

            if ( ($this->allowAdd == 1 || $this->allowEdit == 1) && ($this->_gridSession->_noForm == 0 || (isset($this->info['doubleTables']) && $this->info['doubleTables'] == 1)) ) {

                // Remove the unnecessary URL params
                $removeParams = array('filters', 'add');

                $url = $this->getUrl($removeParams);

                $this->_renderDeploy['form'] = $this->_form;

                $this->_showsForm = true;
            }
        }


        //Template start
        $this->_render['start'] = $this->temp['table']->globalStart();
        $this->_renderDeploy['start'] = $this->_render['start'];

        if ( ((! isset($this->ctrlParams['edit' . $this->_gridId]) || $this->ctrlParams['edit' . $this->_gridId] != 1) && (! isset($this->ctrlParams['add' . $this->_gridId]) || $this->ctrlParams['add' . $this->_gridId] != 1)) || $this->_gridSession->_noForm == 1 || (isset($this->info['doubleTables']) && $this->info['doubleTables'] == 1) ) {

            if ( $this->_isDetail == true ) {

                $columns = parent::_buildGrid();

                $this->_willShow['detail'] = true;
                $this->_willShow['detailId'] = $this->_getPkFromUrl();

                $this->_render['detail'] = $this->temp['table']->globalStart();

                foreach ( $columns[0] as $value ) {
                    if ( ! isset($value['field']) ) {
                        continue;
                    }

                    if ( isset($this->data['fields'][$value['field']]['title']) ) {
                        $value['field'] = $this->data['fields'][$value['field']]['title'];
                    } else {
                        $value['field'] = ucwords(str_replace('_', ' ', $value['field']));
                    }

                    $this->_render['detail'] .= str_replace(array('{{field}}', '{{value}}'), array($value['field'], $value['value']), $this->temp['table']->detail());
                }

                if ( isset($this->ctrlParams['gridRemove' . $this->_gridId]) && $this->ctrlParams['gridRemove' . $this->_gridId] == 1 ) {

                    $localCancel = $this->getUrl(array('comm','gridDetail','gridRemove'));

                    $localDelete = $this->getUrl(array('gridRemove', 'gridDetail', 'comm'));
                    $localDelete .= "/comm" . $this->_gridId . "/" . str_replace("view", 'delete', $this->ctrlParams['comm' . $this->_gridId]);

                    $buttonRemove = $this->getView()->formButton('delRecordGrid', $this->__('Remove Record'), array('onclick' => "window.location='$localDelete'"));
                    $buttonCancel =  $this->getView()->formButton('delRecordGrid', $this->__('Cancel'), array('onclick' => "window.location='$localCancel'"));

                    $this->_render['detail'] .= str_replace('{{button}}', $buttonRemove.' '.$buttonCancel, $this->temp['table']->detailDelete());
                } else {
                    $this->_render['detail'] .= str_replace(array('{{url}}','{{return}}'), array($this->getUrl(array('gridDetail', 'comm')),$this->__('Return')), $this->temp['table']->detailEnd());
                }

                $this->_render['detail'] .= $this->temp['table']->globalEnd();

                $this->_renderDeploy['detail'] = $this->_render['detail'];

            } else {

                $this->_willShow['grid'] = true;

                $bHeader = self::_buildHeader();
                $bHeader .= self::_buildExtraRows('afterHeader');
                $bTitles = self::_buildExtraRows('beforeTitles');
                $bTitles .= self::_buildTitlesTable(parent::_buildTitles());
                $bTitles .= self::_buildExtraRows('afterTitles');
                $bFilters = self::_buildExtraRows('beforeFilters');
                $bFilters .= self::_buildFiltersTable(parent::_buildFilters());
                $bFilters .= self::_buildExtraRows('afterFilters');
                $bGrid = self::_buildGridTable(parent::_buildGrid());
                $bSqlExp = self::_buildExtraRows('beforeSqlExpTable');
                $bSqlExp .= self::_buildSqlexpTable(parent::_buildSqlExp());
                $bSqlExp .= self::_buildExtraRows('afterSqlExpTable');
                $bPagination = self::_buildExtraRows('beforePagination');
                $bPagination .= self::_pagination();
                $bPagination .= self::_buildExtraRows('afterPagination');

                $this->_renderDeploy['header'] = $bHeader;
                $this->_renderDeploy['titles'] = $bTitles;
                $this->_renderDeploy['filters'] = $bFilters;
                $this->_renderDeploy['grid'] = $bGrid;
                $this->_renderDeploy['sqlExp'] = $bSqlExp;
                $this->_renderDeploy['pagination'] = $bPagination;

                $this->_render['header'] = $bHeader;
                $this->_render['titles'] = $bTitles;
                $this->_render['filters'] = $bFilters;
                $this->_render['grid'] = $bGrid;
                $this->_render['sqlExp'] = $bSqlExp;
                $this->_render['pagination'] = $bPagination;

            }

            $this->_showsGrid = true;
        }

        $this->_render['end'] = $this->temp['table']->globalEnd();
        $this->_renderDeploy['end'] = $this->_render['end'];


        $gridId = $this->_gridId;

        if ( isset($this->ctrlParams['gridmod' . $this->_gridId]) && $this->ctrlParams['gridmod' . $this->_gridId] == 'ajax' && $this->info['ajax'] !== false ) {

            $response = Zend_Controller_Front::getInstance()->getResponse();
            $response->clearBody();
            $response->setBody(implode($this->_renderDeploy));
            $response->sendResponse();
            die();
        }

        if ( isset($this->info['ajax']) && $this->info['ajax'] !== false ) {
            $gridId = $this->info['ajax'];
        }

        $grid = "<div id='{$gridId}'>" . implode($this->_renderDeploy) . "</div>";

        if ( $this->_gridSession->correct > 0 ) {
            if ( $this->_gridSession->correct == 1 ) {
                $this->_gridSession->unsetAll();
            } else {
                $this->_gridSession->correct ++;
            }
        }

        $this->_deploymentContent = implode($this->_renderDeploy);
        return $this;
    }


    /**
     * Render parts os the grid
     * @param $part
     * @param $appendGlobal
     */
    function render ($part, $appendGlobal = false)
    {
        $result = '';
        if ( $appendGlobal === true ) {
            $result .= $this->_render['start'];
        }

        if ( isset($this->_render[$part]) ) {
            $result .= $this->_render[$part];
        }

        if ( $appendGlobal === true ) {
            $result .= $this->_render['end'];
        }

        return $result;
    }


    function __toString ()
    {
        if ( is_null($this->_deploymentContent) ) {
            die('You must explicity call the deploy() method before printing the object');
            # self::deploy();
        }
        return $this->_deploymentContent;
    }


    protected function _printScript ()
    {

        if ( $this->getInfo('ajax') !== false ) {
            $useAjax = 1;
        } else {
            $useAjax = 0;
        }

        $script = "";

        if ( $this->allowDelete == 1 ) {

            $script .= " function _" . $this->_gridId . "confirmDel(msg, url)
        {
            if(confirm(msg))
            {
            ";
            if ( $useAjax == 1 ) {
                $script .= "window.location = '" . $this->_baseUrl . "/'+url.replace('/gridmod" . $this->_gridId . "/ajax','');";
            } else {
                $script .= "window.location = url;";
            }

            $script .= "

            }else{
                return false;
            }
        }";

        }
        if ( $useAjax == 1 ) {
            $script .= "function gridAjax(ponto,url) {

    var xmlhttp;
    try
    {
        xmlhttp=new XMLHttpRequest();
    }
    catch (e)
    {
        try
        {
            xmlhttp=new ActiveXObject(\"Msxml2.XMLHTTP\");
        }
        catch (e)
        {
            try
            {
                xmlhttp=new ActiveXObject(\"Microsoft.XMLHTTP\");
            }
            catch (e)
            {
                alert(\"Your browser does not suppor AJAX!\");
                return false;
            }
        }
    }
    xmlhttp.open(\"GET\", '" . $this->_baseUrl . "/'+url,true);

    xmlhttp.onreadystatechange=function () {

        if (xmlhttp.readyState==4) {
            texto=xmlhttp.responseText;
            document.getElementById(ponto).innerHTML=texto;
        }else{

        }
    }
    xmlhttp.send(null);
}
";
        }

        if ( ! isset($this->info['noFilters']) || $this->info['noFilters'] != 0 ) {

            $script .= "function _" . $this->_gridId . "gridChangeFilters(fields,url,Ajax)
{
    var Ajax = \"1\";
    var fieldsArray = fields.split(\",\");
    var filtro = new Array;

    for (var i = 0; i < fieldsArray.length -1; i++)
    {
        value = document.getElementById(fieldsArray[i]).value;\n";
            $script .= " value = value.replace(/[\"]/,''); ";
            $script .= " value = value.replace(/[\\\]/,''); ";
            $script .= " fieldsArray[i] = fieldsArray[i].replace(/filter_" . $this->_gridId . "/,'filter_'); ";
            $script .= "\nfiltro[i] = '\"'+encodeURIComponent(fieldsArray[i])+'\":\"'+encodeURIComponent(value)+'\"';
    }

    filtro = \"{\"+filtro+\"}\";
    ";

            if ( $useAjax == 1 ) {
                $script .= "gridAjax('{$this->info['ajax']}',url+'/filters{$this->_gridId}/'+filtro);";
            } else {
                $script .= "window.location=url+'/filters{$this->_gridId}/'+filtro;";
            }
        }
        $script .= "
    }
        ";

        $this->getView()->headScript()->appendScript($script);

        return;
    }


    /**
     *
     *@var Bvb_Grid_Form
     * @return unknown
     */
    function setForm ($crud)
    {

        $form = $this->getSource()->buildForm($crud);

        $crud->form->setDecorators(array('FormElements', array('HtmlTag', array('tag' => 'table', 'style' => 'width:98%')), 'Form'));
        $crud->form->setOptions($form);

        if ( count($crud->form->getElements()) > 0 ) {
            foreach ( $crud->form->getElements() as $key => $value ) {
                $value->setDecorators($crud->elementDecorators);
            }
        }

        $crud->form->setDecorators($crud->formDecorator);

        $crud->form->addElement('submit', 'form_submit' . $this->_gridId, array('label' => 'Submit', 'class' => 'submit', 'decorators' => $crud->buttonHidden));
        $crud->form->addElement('hidden', '_form_edit' . $this->_gridId, array('value' => 1, 'decorators' => $crud->buttonHidden));

        $url = $this->getUrl(array_merge(array('add', 'edit', 'comm', 'form_reset'), array_keys($crud->form->getElements())));

        $crud->form->addElement('button', 'form_reset' . $this->_gridId, array('onclick' => "window.location='$url'", 'label' => 'Cancel', 'class' => 'reset', 'decorators' => $crud->buttonHidden));
        $crud->form->addDisplayGroup(array('form_submit' . $this->_gridId, 'form_reset' . $this->_gridId), 'buttons', array('decorators' => $crud->groupDecorators));

        $crud->setAction($this->getUrl(array_keys($crud->form->getElements())));

        $this->_form = $crud->form;

        if ( isset($crud->options['callbackBeforeDelete']) ) {
            $this->_callbackBeforeDelete = $crud->options['callbackBeforeDelete'];
        }

        if ( isset($crud->options['callbackBeforeInsert']) ) {
            $this->_callbackBeforeInsert = $crud->options['callbackBeforeInsert'];
        }

        if ( isset($crud->options['callbackBeforeUpdate']) ) {
            $this->_callbackBeforeUpdate = $crud->options['callbackBeforeUpdate'];
        }

        if ( isset($crud->options['callbackAfterDelete']) ) {
            $this->_callbackAfterDelete = $crud->options['callbackAfterDelete'];
        }

        if ( isset($crud->options['callbackAfterInsert']) ) {
            $this->_callbackAfterInsert = $crud->options['callbackAfterInsert'];
        }

        if ( isset($crud->options['callbackAfterUpdate']) ) {
            $this->_callbackAfterUpdate = $crud->options['callbackAfterUpdate'];
        }

        $crud = $this->_object2array($crud);


        $options = $crud['options'];

        if ( isset($options['table']) ) {
            $this->_crudTable = trim($options['table']);
        }

        $this->info['doubleTables'] = isset($options['doubleTables']) ? $options['doubleTables'] : '';

        if ( isset($options['delete']) ) {
            if ( $options['delete'] == 1 ) {
                $this->delete = array('allow' => 1);
                if ( isset($options['onDeleteAddWhere']) ) {
                    $this->info['delete']['where'] = $options['onDeleteAddWhere'];
                }
            }
        }

        @$this->info['delete']['cascadeDelete'] = $crud['cascadeDelete'];

        if ( isset($options['add']) && $options['add'] == 1 ) {
            if ( ! isset($options['addButton']) ) {
                $options['addButton'] = 0;
            }

            $this->add = array('allow' => 1, 'button' => $options['addButton']);
        }

        if ( isset($options['edit']) && $options['edit'] == 1 ) {
            $this->edit = array('allow' => 1);
        }

        return $this;
    }


    /**
     * Field type on the filters area. If the field type is enum, build the options
     * Also, we first need to check if the user has defined values to presente.
     * If set, this values override the others
     *
     * @param string $campo
     * @param string $valor
     * @return string
     */
    protected function _formatField ($campo)
    {

        $valor = $campo;

        if ( isset($this->data['fields'][$valor]['search']) && $this->data['fields'][$valor]['search'] == false ) {
            return '';
        }

        //check if we need to load  fields for filters
        if ( isset($this->filters[$valor]['distinct']) && is_array($this->filters[$valor]['distinct']) && isset($this->filters[$valor]['distinct']['field']) ) {

            $distinctField = $this->filters[$valor]['distinct']['field'];
            $distinctValue = $this->filters[$valor]['distinct']['name'];

            if ( isset($this->data['fields'][$distinctField]['field']) ) {
                $distinctField = $this->data['fields'][$distinctField]['field'];
            }

            $final = $this->getSource()->getDistinctValuesForFilters($distinctField, $distinctValue);

            $this->filters[$valor]['values'] = $final;
        }


        //Remove unwanted url params
        $url = $this->getUrl(array('filters', 'start', 'comm', '_exportTo'));

        $fieldsSemAsFinal = $this->data['fields'];

        if ( isset($fieldsSemAsFinal[$campo]['searchField']) ) {
            $nkey = $fieldsSemAsFinal[$campo]['searchField'];
            @$this->_filtersValues[$campo] = $this->_filtersValues[$nkey];
        }


        $help_javascript = '';

        $i = 0;
        foreach ( array_keys($this->filters) as $value ) {

            if ( ! isset($this->data['fields'][$value]['search']) ) {
                $this->data['fields'][$value]['search'] = true;
            }

            $hRow = isset($this->data['fields'][$value]['hRow']) ? $this->data['fields'][$value]['hRow'] : '';

            if ( $this->_displayField($value) && $hRow != 1 && $this->data['fields'][$value]['search'] != false ) {
                $help_javascript .= "filter_" . $this->_gridId . $value . ",";
            }
        }

        $help_javascript = str_replace(".", "bvbdot", $help_javascript);
        $attr['onChange'] = "_" . $this->_gridId . "gridChangeFilters('$help_javascript','$url');";

        $opcoes = array();

        if ( isset($this->filters[$campo]) ) {
            $opcoes = $this->filters[$campo];
        }

        if ( isset($opcoes['style']) ) {
            $attr['style'] = $opcoes['style'];
        } else {
            $attr['style'] = " width:95% ";
        }

        $attr['id'] = "filter_" . $this->_gridId . $campo;

        $selected = null;

        if ( isset($this->filters[$valor]['values']) && is_array($this->filters[$valor]['values']) ) {
            $hasValues = false;
        } else {
            $hasValues = $this->getSource()->getFilterValuesBasedOnFieldDefinition($this->data['fields'][$campo]['field']);
        }

        if ( is_array($hasValues) ) {
            $opcoes = array();
            $tipo = 'text';
            $opcoes['values'] = $hasValues;
        } else {
            $tipo = 'text';
        }

        if ( isset($opcoes['values']) && is_array($opcoes['values']) ) {
            $tipo = 'invalid';
            $values = array();
            $values[''] = '--' . $this->__('All') . '--';

            $avalor = $opcoes['values'];

            foreach ( $avalor as $key => $value ) {
                if ( isset($this->_filtersValues[$campo]) && $this->_filtersValues[$campo] == $key ) {
                    $selected = $key;
                }

                $values[$key] = $this->__($value);
            }

            $valor = $this->_view->formSelect($campo, $selected, $attr, $values);

        }

        if ( $tipo != 'invalid' ) {
            $this->_filtersValues[$campo] = isset($this->_filtersValues[$campo]) ? $this->_filtersValues[$campo] : '';
            $valor = $this->_view->formText($campo, @$this->_filtersValues[$campo], $attr);
        }

        return $valor;
    }


    /**
     * Apply config options
     * @param $options
     */
    protected function _applyConfigOptions ($options, $final = false)
    {

        if ( $final == false ) {
            $this->_deployOptions = $options;

            if ( isset($this->_deployOptions['templateDir']) ) {

                $this->_deployOptions['templateDir'] = (array) $this->_deployOptions['templateDir'];

                foreach ( $this->_deployOptions['templateDir'] as $templates ) {
                    $temp = $templates;
                    $temp = str_replace('_', '/', $temp);
                    $this->addTemplateDir($temp, $templates, 'table');
                }
            }

        } else {

            if ( isset($this->_deployOptions['imagesUrl']) ) {
                $this->imagesUrl = $this->_deployOptions['imagesUrl'];
            }

            if ( isset($this->_deployOptions['template']) ) {
                $this->setTemplate($this->_deployOptions['template'], 'table');
            }


        }

        return true;
    }


    /**
     * Returns form instance
     */
    function getForm ()
    {
        return $this->_form;
    }


    /**
     * Adds a row class based on a condition
     * @param $column
     * @param $condition
     * @param $class
     */
    function addClassRowCondition ($column, $condition, $class)
    {
        $this->_classRowCondition[$column][] = array('condition' => $condition, 'class' => $class);
        return $this;
    }


    /**
     * Adds a cell class based on a condition
     * @param $column
     * @param $condition
     * @param $class
     */
    function addClassCellCondition ($column, $condition, $class)
    {
        $this->_classCellCondition[$column][] = array('condition' => $condition, 'class' => $class);
        return $this;
    }


    /**
     * Sets a row class based on a condition
     * @param $column
     * @param $condition
     * @param $class
     */
    function setClassRowCondition ($column, $condition, $class)
    {
        $this->_classRowCondition = array();
        $this->_classRowCondition[$column][] = array('condition' => $condition, 'class' => $class);
        return $this;
    }


    /**
     * Set a cell class based on a condition
     * @param $column
     * @param $condition
     * @param $class
     */
    function setClassCellCondition ($column, $condition, $class)
    {
        $this->_classCellCondition = array();
        $this->_classCellCondition[$column][] = array('condition' => $condition, 'class' => $class);
        return $this;
    }


    function addExtraRows (Bvb_Grid_ExtraRows $rows)
    {
        $rows = $this->_object2array($rows);
        $this->_extraRows = $rows['_rows'];

        return $this;
    }


    function _buildExtraRows ($position)
    {

        if ( count($this->_extraRows) == 0 ) {

            return false;
        }

        $start = '<tr>';
        $middle = '';
        $end = '';
        $hasReturn = false;

        if ( count($this->_getExtraFields('left')) > 0 ) {
            $start .= " <td colspan='" . count($this->_getExtraFields('left')) . "'></td>";
        }

        if ( count($this->_getExtraFields('right')) > 0 ) {
            $end .= " <td colspan='" . count($this->_getExtraFields('left')) . "'></td>";
        }

        foreach ( $this->_extraRows as $key => $value ) {

            if ( $value['position'] != $position ) continue;

            foreach ( $value['values'] as $final ) {
                $colspan = isset($final['colspan']) ? "colspan='" . $final['colspan'] . "'" : '';
                $class = isset($final['class']) ? "class='" . $final['class'] . "'" : '';
                if ( ! isset($final['content']) ) {
                    $final['content'] = '';
                }

                $middle .= "<td $colspan $class >{$final['content']}</td>";

                $hasReturn = true;
            }
        }

        if ( $hasReturn === false ) {
            return false;
        }

        $end .= '</tr>';

        return $start . $middle . $end;

    }


}
