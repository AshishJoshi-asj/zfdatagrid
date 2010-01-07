<?php
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
    private $_defaultOptions = array(
        'datatype' =>"local",
        'height' => 250,
        'autowidth' => true,
        'rownumbers' => true,
        'gridview' => true,
        'multiselect' => true,
        'viewrecords' => true, 	
        'viewsortcols' => true, 	     	
        'imgpath' => "themes/basic/images",
        'caption' => '', 	
        'loadError' => 'function(xhr,st,err) { if (xhr.status!=200) {alert(xhr.statusText);} }',   
    );
    
    private $_options = array();
    
    private $_buttons = array();
    
    /**
     * Constructor
     * 
     * @param Zend_Db $db false if Zend_Db will not be used
     */
    function __construct ($db = false)
    {
        parent::__construct($db);       

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
            foreach ($this->ctrlParams as $filter=>$val)
            $flts->$filter = $val;
            $this->ctrlParams ['filters'] = urlencode(Zend_Json::encode($flts));                       
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
        // if request is Ajax we should only return data
        if (false!==$gridId && $this->isAjaxRequest()) {
            // prepare data          
            parent::deploy();
            // set data in JSON format
            $response = Zend_Controller_Front::getInstance()->getResponse();
            $response->setHeader('Content-Type', 'application/json');
            $response->setBody($this->buildJson());
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
     * @param array $options set JqGrid options (@see http://www.trirand.com/jqgridwiki)  
     * 
     * @return void
     *
     */
    public function setJsOptions(array $options)
    {
        $this->_options = $this->_defaultOptions;
        
        $methods = get_class_methods($this);
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (in_array($method, $methods)) {
                $this->$method($value);
            } else {
                $this->_options[$key] = $value;
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
        foreach ($types as $export=>$url) {
            $this->addButton(
                array(
                    'caption' => $export,
                    'buttonicon' => "ui-icon-extlink",
                    // TODO use expression
                    'onClickButton' => <<<JS
function() { 
    newwindow = window.open("$url",'$export Export',''); 
    if (window.focus) {
        newwindow.focus();
    } 
    return false; 
}
JS
                    ,
                    'position' => "last"
                )
            );
        }
    }

    /**
     * Add grid button
     *
     * @param array $options options for JqGrid custom button
     * 
     * @return void
     */
    public function addButton(array $options)
    {
        $button = '{';
        foreach ($options as $name => $value) {
            if (in_array($name, array('onClickButton'))) {
                // parameters that can be js function
                $button .= '"'.$name.'": '.$value.','. PHP_EOL;
            } elseif (is_array($value)) {
                $button .= '"'.$name.'": '.Zend_Json::encode($value).','. PHP_EOL;
            } elseif (is_bool($value)) {
                $button .= '"'.$name.'":'.($value ? 'true':'false').','. PHP_EOL;
            } else {
                $button .= '"'.$name . '": "' . addslashes($value) . '",' . PHP_EOL;
            }
        }
        $button = substr($button, 0, -(strlen(PHP_EOL)+1));
        $button .= '}';
        $this->_buttons[] = $button;
    }
    
    /**
     * Build grid. Will output HTML definition for grid and add js/css to view.
     * 
     * @return string output this sting to place in view where you want to display the grid
     */
    function deploy()
    {
        $html = "";        
        // prepare internal Bvb data
        parent::deploy();
        // prepare access to view
        $view = $this->getView();
        // defines ID property of html tags related to this jqGrid
        $id = "1";
        // build URL where to receive data from
        $url = $view->serverUrl(true) . "?q=$id";
        // build definition of columns
        $colDefs = $this->buildJqColumns();
        // add export buttons
        $this->addExportButtons($this->export);        
        // prepare default values ...
        $rowNum = $this->pagination; 
        // prepare first part of data
        $data = $this->buildJson();        

        // initialize jQuery
        $view->jQuery()
            ->enable()        
            ->uiEnable()
            ->addStylesheet($this->_jqgridLibPath . "/css/ui.jqgrid.css")
            // TODO locale should be configurable
            ->addJavascriptFile($this->_jqgridLibPath . '/js/i18n/grid.locale-en.js')
            ->addJavascriptFile($this->_jqgridLibPath . '/js/jquery.jqGrid.min.js');

        // initialize table with jQuery
        $this->_options += $this->_defaultOptions;
        
        
        $x = Zend_Json::encode($this->_options, false, array('enableJsonExprFinder' => true));

        // add filter toolbar to grid - $grid->noFilters(1);
        $filterToolbar = (isset($this->info['noFilters']) ? $this->info['noFilters'] : false) ? "" : ".filterToolbar()";
        // TODO dissable sorting on columns - $grid->noOrder(1);        
        
        $options = <<<EOF
{
url: "$url",
pager: jQuery('#jqgrid_pager_$id'),
$colDefs,
rowNum:$rowNum,
EOF;
        foreach ($this->_options as $name => $value) {
            if (in_array($name, array('loadError'))) {
                // parameters that can be js function
                $options .= '"'.$name.'": '.$value.','. PHP_EOL;
            } elseif (is_array($value)) {
                $options .= '"'.$name.'": '.Zend_Json::encode($value).','. PHP_EOL;
            } elseif (is_bool($value)) {
                $options .= '"'.$name.'":'.($value ? 'true':'false').','. PHP_EOL;
            } else {
                $options .= '"'.$name . '": "' . addslashes($value) . '",' . PHP_EOL;
            }
        }
        $options = substr($options, 0, -(strlen(PHP_EOL)+1));
        $options .= '}';
        
        $buttons = '';
        if (true) {
            $buttons = <<<JS
.navButtonAdd('#jqgrid_pager_$id', {
    caption:"Quick Search",
    title:"Toggle Search Toolbar", 
    buttonicon :'ui-icon-pin-s', 
    onClickButton:function(){ $(this)[0].toggleToolbar(); } 
});
JS;
        }
        foreach ($this->_buttons as $button) {
            $buttons .= ".navButtonAdd('#jqgrid_pager_$id',$button)";
        }
        
        $js = <<<EOF
var myData = $data;     
jQuery("#jqgrid_$id").jqGrid(
$options
)
.navGrid('#jqgrid_pager_$id',{edit:false,add:false,del:false})
	$filterToolbar
    $buttons;
jQuery("#jqgrid_$id")
	.setGridParam({datatype:"json"})[0]
	.addJSONData(myData);
EOF;

        $view->jQuery()->addOnLoad($js);
        // return html part of grid 
        $html = <<<HTML
<table id="jqgrid_$id" class="scroll" cellpadding="0" cellspacing="0">
    <tr><td></td></tr>
</table>
<div id="jqgrid_pager_$id" class="scroll"></div>
HTML;
        return $html;
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
     * Pack data into JSON format
     * 
     * @return string
     */
    function buildJson()
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
     * Create column definition compatible with jqGrid
     * 
     * @return unknown_type
     */
    function buildJqColumns()
    {
        $cols = array();
        
        //BVB grid options
        $skipOptions = array('title','sqlexp','hide', 'hRow','eval', 'class', 'searchType', 'format');
        
        $titles = parent::buildTitles();

        
        reset($this->data['fields']);
        foreach ($titles as $title) {
            $options = '{"name":"'.$title['name'].'", "label":"'.$title['value'].'", ';
            foreach (current($this->data['fields']) as $name =>$value) {
                if ( in_array($name, $skipOptions)) {
                    continue ;
                }
                if (in_array($name, array('unformat'))) {
                    // parameters that can be js function
                    $options .= '"'.$name.'": '.$value.',';
                } elseif (is_array($value)) {
                    $options .= '"'.$name.'": '.Zend_Json::encode($value).',';
                } elseif (is_bool($value)) {
                    $options .= '"'.$name.'":'.($value ? 'true':'false').',';
                } else {
                    $options .= '"'.$name . '": "' . addslashes($value) . '",';
                }
            }
            $options = substr($options, 0, -1);
            $options = rtrim($options, ', ');
            $options .= '}';
            $cols[] = $options;
            next($this->data['fields']);
        } 
        $cols = implode(",", $cols);
        return 'colModel:[' . $cols . ']';
    }
    
    ///////////////////////////////////////////////// Following functions could go to Bvb_Grid_DataGrid
    /**
     * @var Zend_View_Interface
     */    
    protected $_view = null;
    /**
     * Set view object
     *
     * @param  Zend_View_Interface $view
     * @return Zend_Form
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
    
}
