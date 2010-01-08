<?php
/** Zend_Json */
require_once 'Zend/Json.php';

/** Zend_Controller_Front */
require_once 'Zend/Controller/Front.php';

// TODO see also http://www.datatables.net/examples/ and http://www.flexigrid.info/
class Bvb_Grid_Deploy_JqGrid extends Bvb_Grid_DataGrid
{
    /**
     * URL path to place where JqGrid library resides
     * TODO add static variable which will initialize this
     * TODO build get/set methods
     */
    protected $_jqgridLibPath = "/public/scripts/jqgrid";
        
    /**
     * Default options for JqGrid 
     * 
     * @var array
     */
    private $_jqgDefaultOptions = array(
        'mtype' => 'POST', // GET will not work because of our parsing
        'height' => 'auto',
        'autowidth' => true,
        'rownumbers' => true,
        'gridview' => true,
        'multiselect' => false,
        'viewrecords' => true,     	
        'imgpath' => "themes/basic/images",
        'caption' => '', 	
        'loadError' => 'function(xhr,st,err) { if (xhr.status!=200) {alert(xhr.statusText);} }',   
    );
    
    private $_jqgOptions = array();
    
    private $_jqgOnInit = array(1);
    
    /**
     * List of commands to execute after the jqGrid object is initiated
     *  
     * @var array
     */
    private $_postCommands = array();
    /**
     * List of custon buttons to be shown on navigation bar
     * 
     * @var array
     */
    private $_buttons = array();
    
    /**
     * Constructor
     * 
     * @param Zend_Db $db false if Zend_Db will not be used
     */
    function __construct ($db = false, $tableCaption = false)
    {
        $this->initLogger();
        
        parent::__construct($db);
        // see http://code.google.com/p/zfdatagrid/issues/detail?id=94
        if (false!==$tableCaption) {
            // set caption to grid
            $this->_jqgDefaultOptions['caption'] = $tableCaption; 
        } 
        
        // TODO move this to prepareOptions ?
        // change parameters from jqGrid to fit Bvb_Grid_DataGrid
        // setPagination
        if (isset($this->ctrlParams ['rows'])) {
            $this->setPagination($this->ctrlParams['rows']);
        }
        // start
        if (isset($this->ctrlParams['page'])) {
            $page = $this->ctrlParams['page'];
        } else {
            $this->ctrlParams['page'] = 1;
            $page = 1;
        }
        $this->ctrlParams['start'] = $this->pagination * ($page-1);
        // order
        $sidx = isset($this->ctrlParams['sidx']) ? $this->ctrlParams['sidx'] : ""; 
        $sord = isset($this->ctrlParams['sord']) ? $this->ctrlParams['sord'] : "asc";
        if ($sidx!=="") {
            $this->ctrlParams ['order'] = $sidx . '_' . strtoupper($sord);
        }
        // filters
        if ( isset($this->ctrlParams ['_search']) && $this->ctrlParams ['_search']) {
            // TODO support search operators
            $flts = new stdClass();
            foreach ($this->ctrlParams as $filter=>$val) {
                $flts->$filter = $val;
            }
            $this->ctrlParams['filters'] = urlencode(Zend_Json::encode($flts));
            //print_r($this->ctrlParams['filters']);die;            
        }
    }
    /**
     * Call this in controller (before any output) to dispatch Ajax requests.
     * 
     * @param string $gridId ID to recognize the request from multiple tables ajax request will be ignored if FALSE
     *  
     * @return void
     */
    function ajax($gridId)
    {
        $this->setId($gridId);
        // if request is Ajax we should only return data
        if (false!==$gridId && $this->isAjaxRequest()) {
            // prepare data          
            parent::deploy();
            // set data in JSON format
            $response = Zend_Controller_Front::getInstance()->getResponse();
            if (!self::$debug) {
                $response->setHeader('Content-Type', 'application/json');
            }
            $response->setBody($this->renderPartData());
            // send logged messages to FirePHP
            Zend_Wildfire_Channel_HttpHeaders::getInstance()->flush();
            // send the response now and end request processing
            $response->sendResponse();
            exit;                                            
        }
    }
    /**
     * Set jQuery Grid options
     * 
     * @param array $options set JqGrid options (@see http://www.trirand.com/jqgridwiki/doku.php?id=wiki:options)  
     * 
     * @return void
     *
     */
    public function setJqgOptions(array $options)
    {
        // TODO bad name, use the same as in ZendX_Jquery
        // TODO also dangerouse that it will call set functions for general Bvb class
        $this->_jqgOptions = array(); //$this->_jqgDefaultOptions;
        
        $methods = get_class_methods($this);
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (in_array($method, $methods)) {
                $this->$method($value);
            } else {
                $this->_jqgOptions[$key] = $value;
            }
        }
    }
    /**
     * Add export action buttons to grid
     *  
     * @param array $types names of deploy classes
     * 
     * @return void
     */
    protected function addExportButtons(array $types)
    {
        // TODO not ok links
        foreach ($types as $export=>$url) {
            $this->jqgAddNavButton(
                array(
                    'caption' => $export,
                    'buttonicon' => "ui-icon-extlink",
                    'onClickButton' => new Zend_Json_Expr(<<<JS
function() { 
    newwindow = window.open("$url",'$export Export',''); 
    if (window.focus) {
        newwindow.focus();
    } 
    return false; 
}
JS
                    ),
                    'position' => "last"
                )
            );
        }
    }
    /**
     * Build grid. Will output HTML definition for grid and add js/css to view.
     * 
     * @return string output this sting to place in view where you want to display the grid
     */
    function deploy()
    {      
        // prepare internal Bvb data
        parent::deploy();
        // prepare access to view
        $view = $this->getView();
        // defines ID property of html tags related to this jqGrid
        $id = $this->getId();
        
        // initialize jQuery
        $this->jqInit();
        // prepare options used to build jqGrid element
        $this->prepareOptions();
        // build definition of columns, which will manipulate _options
        $this->_jqgOptions['colModel'] = $this->jqgGetColumnModel();
        // build final JavaScript code and return HTML code to display
        $this->jqAddOnLoad($this->renderPartJavascript());
        return $this->renderPartHtml();      
    }
    /**
     * Return javascript part of grid
     * 
     * @return string
     */
    public function renderPartJavascript()
    {
        // ! this should be the last commands (it is not chainable anymore)
        foreach ($this->_buttons as $btn) {
            $this->_postCommands[] = sprintf("navButtonAdd('#%s', %s)", $this->jqgGetIdPager(), self::encodeJson($btn));
        }
        if (true) {
            // first data will be loaded via ajax call  
            $data = array();
            $this->_jqgOptions['datatype'] = "json";
        } else {
            // set first data without ajax request
            $data = $this->renderPartData();
            $this->_jqgOptions['datatype'] = "local";            
            $this->_postCommands[] = 'setGridParam({datatype:"json"})';                
            $this->_postCommands[] = 'jqGrid()[0].addJSONData(myData)';        
        }
        // combine the post commands into JavaScrip string
        if (count($this->_postCommands)) {
            $postCommands = '.' . implode("\n.", $this->_postCommands);
        }
        // convert options to javascript
        $options = self::encodeJson($this->_jqgOptions);
        // build javascript text
        $idtable = $this->jqgGetIdTable();
        $idpager = $this->jqgGetIdPager();        
        $js = <<<EOF
var myData = $data;     
jQuery("#$idtable").jqGrid(
$options
)
$postCommands
;
EOF;
        // TODO add users javascript code, something like ready event
        if (count($this->_jqgOnInit)>0) {
            $js .= PHP_EOL . <<<JS
jQuery("#$idtable").each(function () {
    var grid = $(this).jqGrid();
    var pager = grid.navGrid('#$idpager');
    console.log(grid);
});
JS;
        }
        return $js;
    }
    /**
     * Return html part of grid
     * 
     * @return string
     */
    public function renderPartHtml()
    {
        $idtable = $this->jqgGetIdTable();
        $idpager = $this->jqgGetIdPager();
        $html = <<<HTML
<table id="$idtable">
    <tr><td></td></tr>
</table>
<div id="$idpager"></div>
HTML;
        return $html;
    }
    /**
     * Return data in JSON format
     * 
     * @return string
     */
    function renderPartData()
    {      
        // clarify the values
        $page = $this->ctrlParams ['page']; // get the requested page 
        $limit = $this->pagination; // get how many rows we want to have into the grid 
        $count =  $this->_totalRecords;
        // build rows
        $data = new stdClass();
        $data->rows = array();
        foreach (parent::buildGrid() as $row) {
            $d = array();
            foreach ( $row as $key=>$val ) {
                $d[] = $val['value'];
            }
            $cell = new stdClass();
            // TODO how to add PK as ID into JSON data ?            
            $cell->cell = $d;
            $data->rows[] = $cell;       
        }
        // set some other information
        if ($count >0) {
            $totalPages = ceil($count/$limit); 
        } else { 
            $totalPages = 0; 
        }         
        $data->page = $page; 
        $data->total = $totalPages; 
        $data->records = $count;
            
        return Zend_Json::encode($data); 
    }    
    /**
     * Consolidate all settings to know how to render the grid
     * 
     * Options are set on grid level by:
     * 1. javascript options passed to jqGrid (?)
     * 2. special Bvb_Grid_Deploy_JqGrid options (jqg array)
     * 3. standard Bvb settings
     * 
     * Options are set on column level by:
     * 1. javascript options passed to columns (?)
     * 2. special Bvb_Grid_Deploy_JqGrid options (jqg array)
     * 3. standard Bvb settings
     * 4. formaters (?)
     * 
     * @return void
     */
    public function prepareOptions()
    {
        $id = $this->getId();
        // build URL where to receive data from
        $url = $this->getView()->serverUrl(true) . "?q=$id";
        
        // initialize table with default options
        ////////////////////////////////////////
        $this->_jqgOptions += $this->_jqgDefaultOptions;
        // prepare navigation 
        $this->_postCommands[] = sprintf("navGrid('#%s',{edit:false,add:false,del:false})", $this->jqgGetIdPager());
        
        // override with options explicitly set by user
        ///////////////////////////////////////////////
       
        // override with options defined on Bvb_Grid_DataGrid level
        ///////////////////////////////////////////////////////////
        $this->_jqgOptions['url'] = $url;
        $this->_jqgOptions['pager'] = new Zend_Json_Expr(sprintf("jQuery('#%s')", $this->jqgGetIdPager()));
        $this->_jqgOptions['rowNum'] = $this->pagination;

        if (!$this->getInfo('noFilters', false)) {
            // add filter toolbar to grid - if not set $grid->noFilters(1);            
            $this->_postCommands[] = 'filterToolbar()';
            $this->jqgAddNavButton(array(
                'caption' => $this->__("Quick Search"),
                'title' => $this->__("Toggle Search Toolbar"), 
                'buttonicon' => 'ui-icon-pin-s', 
                'onClickButton' => new Zend_Json_Expr("function(){ $(this)[0].toggleToolbar(); }")        
            ));
        }

        if ($this->getInfo('noOrder', false)) {
            // dissable sorting on columns - if set $grid->noOrder(1);            
            $this->_jqgOptions['viewsortcols'] = array(false,'vertical',false); 
        }                
        // add export buttons
        $this->addExportButtons($this->export);       
    }
    /**
     * Encode Json that may include javascript expressions.
     *
     * Take care of using the Zend_Json_Encoder to alleviate problems with the json_encode
     * magic key mechanism as of now.
     *
     * @see Zend_Json::encode
     * @param  mixed $value
     * @return mixed
     */    
    public static function encodeJson($value)
    {
        return Zend_Json::encode($value, false, array('enableJsonExprFinder' => true));
    }
    /**
     *  Loads jQuery related libraries needed to display jqGrid.
     * 
     *  ZendX_Jquery is used as default, but this could be overriden.
     * 
     *  @return Bvb_Grid_Deploy_JqGrid
     */
    public function jqInit()
    {
        $this->_view->jQuery()
            ->enable()        
            ->uiEnable()
            ->addStylesheet($this->_jqgridLibPath . "/css/ui.jqgrid.css")
            // TODO locale should be configurable
            ->addJavascriptFile($this->_jqgridLibPath . '/js/i18n/grid.locale-en.js')
            ->addJavascriptFile($this->_jqgridLibPath . '/js/jquery.jqGrid.min.js');
        return $this;
    }    
    /**
     * Add JavaScript code to be executed when jQuery ready event
     * 
     * ZendX_Jquery is used as default, but this could be overriden. 
     * 
     * @param string $js
     * 
     * @return Bvb_Grid_Deploy_JqGrid
     */
    public function jqAddOnLoad($js)
    {
        $this->_view->jQuery()->addOnLoad($js);
        return $this;        
    }
    /////////////////////
    /**
     * Add action button to navigation bar  
     * 
     * @param array $button options for JqGrid custom button
     * 
     * @return Bvb_Grid_Deploy_JqGrid
     */
    public function jqgAddNavButton($button)
    {
        $this->_buttons[] = $button; 
        return $this;
    }
    /**
     * 
     */
    public function jqgGetColumnModel()
    {
        $model = array();
        
        //BVB grid options
        $skipOptions = array(
            'title',     // handled in parent::buildTitles()
            'hide',      // handled in parent::buildTitles()
            'sqlexp',
            'hRow',
            'eval', 
            'class', 
            'searchType', 
            'format',
            'jqg' // we handle this separately
        );
        
        $titles = $this->buildTitles();
        // TODO need fix of #101
        $fields = $this->removeAsFromFields();
/*        $this->log($this->data['fields']);
        $fields = $this->removeAsFromFields();
        $this->log($fields);
        $this->log($titles);*/        

        foreach ($titles as $key=>$title) {
            // basic options
            $options = array("name" => $title['name'], "label" => $title['value']);
            // add defined options
            if (isset($fields[$key])) {
                foreach ($fields[$key] as $name=>$value) {
                    if ( in_array($name, $skipOptions)) {
                        continue ;
                    }
                    $options[$name] = $value;
                }
                if (isset($fields[$key]['jqg'])) {
                    // we apply jqg options after all other options
                    // see http://www.trirand.com/jqgridwiki/doku.php?id=wiki:colmodel_options
                    foreach ($fields[$key]['jqg'] as $name=>$value) {
                        $options[$name] = $value;
                    }
                }
            } else {
                $this->log("why there is no key $key in fields ?");
            }
            // add field to model
            $model[] = $options;
        } 

        return $model;
    }
    /**
     * Return ID for pager HTML element 
     */
    public function jqgGetIdPager()
    {
        return "jqg_pager_" . $this->getId();
    }
    /**
     * Return ID for pager HTML element 
     */
    public function jqgGetIdTable()
    {
        return "jqg_" . $this->getId();
    }
    ///////////////////////////////////////////////// Following functions could go to Bvb_Grid_DataGrid
    /////////////////////////////////////////////////
    // @codingStandardsIgnoreStart    
    /**
     * Not defined in Bvb_Grid_DataGrid, but used there
     *   
     * @var string
     */
    protected $output = 'jqgrid';
    // @codingStandardsIgnoreEnd 
    /**
     * @see Bvb_Grid_DataGrid::$export
     */
    public $export = array();    
    /**
     * @var Zend_View_Interface
     */    
    protected $_view = null;
    /**
     * Ajax ID
     * @var string
     */
    protected $_id = 0;
    /**
     * 
     * @var unknown_type
     */
    protected $_logger = null;
    /**
     * Set to true if you want to debug this class
     * 
     * @var unknown_type
     */
    public static $debug = false;
    
    /**
     * Set view object
     *
     * @param  Zend_View_Interface $view
     * 
     * @return Bvb_Grid_Deploy_JqGrid
     */
    public function setView(Zend_View_Interface $view = null)
    {
        $this->_view = $view;
        return $this;
    }

    /**
     * Retrieve view object
     *
     * If none registered, attempts to pull from ViewRenderer.
     *
     * @return Zend_View_Interface|null
     */
    public function getView()
    {
        if (null === $this->_view) {
            require_once 'Zend/Controller/Action/HelperBroker.php';
            $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
            $this->setView($viewRenderer->view);
        }

        return $this->_view;
    }
    /**
     * Use to detect if we should return plain JSON data or full table definition
     *
     * @return boolean
     */    
    protected function isAjaxRequest()
    {
        return Zend_Controller_Front::getInstance()->getRequest()->isXmlHttpRequest() 
            || isset($this->ctrlParams['_search']);
    }
    /**
     * 
     * @return string
     */
    public function getId()
    {
        return $this->_id;
    }
    /**
     * 
     * @param string $id
     * 
     * @return Bvb_Grid_Deploy_JqGrid 
     */
    public function setId($id)
    {
        $this->_id = $id;
        return $this;
    }
    /**
     * Return variable stored in info. Return default if value is not stored.  
     *
     * @param string $param
     * @param mixed  $default
     * 
     * @return mixed
     */    
    public function getInfo($param, $default = false) {
        if (isset($this->info[$param])) {
            return $this->info[$param];
        } else {
            return $default;
        }
    }

    protected function initLogger()
    {
        if (self::$debug) {
            // send messages to FirePHP
            $writter = new Zend_Log_Writer_Firebug();
        } else {
            // we need to have at least dummy instance of Zend_Log            
            $writter = new Zend_Log_Writer_Null();    
        }
        $this->_logger = new Zend_Log($writter);
    }
    protected function log($message, $priority = 7)
    {
        $this->_logger->log($message, $priority);
    }
}