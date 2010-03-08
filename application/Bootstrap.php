<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initAutoload()
    {
        $autoloader = new Zend_Application_Module_Autoloader(array(
            'namespace' => '',
            'basePath'  => dirname(__FILE__),
        ));

        return $autoloader;
    }

    protected function _initDoctrineConnection()
    {
        $this->bootstrap('doctrine');
    }

    protected function _initSession()
    {
        Zend_Session::start();
    }
    
    protected function _initRoutes() {
    	$controller = Zend_Controller_Front::getInstance();
    	$router = $controller->getRouter();
    	$router->addRoute(
    		'sidewinder',
	    	new Zend_Controller_Router_Route(
	    		'sidewinder/:request',
	    		array(
	    			'controller' => 'sidewinder',
	    			'action' => 'index'
	    		)
	    	)
    	);
    }

    /**
     * Initializes the locale, view, helper path, base title and registers the initializer
     * plugin
     *
     * @return N/A
     */
    protected function _initView()
    {
        $controller = Zend_Controller_Front::getInstance();

        // Initialize view
        $view = new Zend_View();
        $view->doctype('XHTML1_STRICT');

        $view->addHelperPath(AP.'/views/helpers', 'App_View_Helper');
        $view->addHelperPath('App/View/Helper', 'App_View_Helper');
        $view->addHelperPath('Cb/View/Helper', 'Cb_View_Helper');
        $view->addHelperPath("ZendX/JQuery/View/Helper", "ZendX_JQuery_View_Helper");

        $navigation = new Zend_Config_Ini(AP . '/configs/navigation.ini', null, array('allowModifications' => true));

        $auth = Zend_Auth::getInstance();

        if ($auth->hasIdentity()) {
            $identity = $auth->getIdentity();

            // TODO this will need to be solidified after Bob completes the Auth.
            $id = 1;//$identity->getUserId();

            $prefs = new Model_Preferences;
            $userpref = json_decode(stripslashes(utf8_encode($prefs->getUserPrefs($id))), true);

            if (!empty($userpref)) {
                foreach($navigation as $nav) {
                    if (isset($userpref['sidebarnav'][$nav->__get('id')]['order'])) {
                        $nav->__set('order', $userpref['sidebarnav'][$nav->__get('id')]['order']);
                    }

                    if (isset($userpref['sidebarnav'][$nav->__get('id')]['state']) && $userpref['sidebarnav'][$nav->__get('id')]['state'] == 0) {
                        $nav->__set('class', 'navbox_closed');
                    }
                }
            }
        }

        $view->getHelper('navigation')->setContainer(new Zend_Navigation($navigation));

        $view->navigation()->menu()->setPartial(array('sidenav.phtml', 'default'));
        $view->navigation()->breadcrumbs()
            ->setPartial('_breadcrumbs.phtml')
            ->setSeparator('<span class="separator breadcrumb>&rarr;</span>');

        // Add it to the ViewRenderer
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
        $viewRenderer->setView($view);

        $view->headTitle('Clickbooth.com');

        // Return it, so that it can be stored by the bootstrap
        return $view;
    }

    protected function _initZFDebug()
    {
        $options = $this->getOptions();

        if (!isset($options['ZFDebug']) || $options['ZFDebug'] != true) {
            return false;
        }

        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->registerNamespace('ZFDebug');

        $options = array(
            'plugins' => array('Variables',
                               'Cb_Controller_Plugin_Debug_Plugin_Doctrine',
                               'Memory',
                               'Time',
                               'Registry',
                               'Exception')
        );

        $debug = new Cb_Controller_Plugin_Debug($options);

        $this->bootstrap('frontController');
        $frontController = $this->getResource('frontController');
        $frontController->registerPlugin($debug);
    }

    protected function _initLogger()
    {
        $options = $this->getOptions();

        $logger = Cb_Log::getInstance();

        // FireBug
        $firebug = new Zend_Log_Writer_Firebug();
        $logger->addWriter($firebug);

        // SysLog
        $syslog = new Zend_Log_Writer_Syslog(array('application' => 'CB Acuity'));
        $logger->addWriter($syslog);

        // Log file - make sure we can write to the file first
        if (is_writable($options['log']['file'])) {
            $file = new Zend_Log_Writer_Stream($options['log']['file']);
            $logger->addWriter($file);
        }
    }

    protected function _initACL()
    {
    	// Hack around to see if it all works

        $opts = $this->getOptions();
        $backendOpts = $opts['cache'];
        $frontendOpts = array(
            'cached_entity' => $this,
            'cache_by_default' => false,
            'cached_methods' => array('_getACL')
        );

        $cache = Zend_Cache::factory(
            'Class', $backendOpts['type'], $frontendOpts, $backendOpts
        );

        Zend_Registry::set('acl', $cache->_getACL());
    }

    public function _getACL()
    {
    	$groupIds = Cb_Query::create()
    	    ->select('group_id')
    	    ->from('Model_Group')
    	    ->execute(array(), DOCTRINE::HYDRATE_ARRAY);

    	// Role Ids are simply group ids here
    	$acl = new Zend_Acl();
    	foreach($groupIds as $row) {
    		$acl->addRole("group_{$row['group_id']}");
    	}

    	/*
    	 * Use the rule list to populate the resource tree
    	 *
    	 * The reason for this is because the only resources worth bothering with are the ones
    	 * with rules.  The ACL will be whitelist only, denying all resources to all roles by
    	 * default.  Any resources not in the ACL will fall back to the default rule. (Usually
    	 * deny)
    	 */
    	$resources = array();
        $rules = Doctrine::getTable('Model_Acl_Rule')->findAll();
        foreach($rules as $rule) {
        	/*
        	 * Take apart the 'resource' portion, so a parent/child relationship can be
        	 * established
        	 */
        	$resource = $rule->resource == '*' ? null : $rule->resource;
        	$privilege = $rule->privilege == '*' ? null : $rule->privilege;
        	if ($resource) {
	        	$resourceParts = explode('/', $rule->resource);
	        	$numResources = count($resourceParts);

	        	// Build/add on to the resource tree the parts that are relevant to this rule
	        	$parent = null;
	        	for ($i = 1; $i <= $numResources; $i++) {
	        		$resource = implode('/', array_slice($resourceParts, 0, $i));
	        		if (!isset($resources[$resource])) {
	        			$resources[$resource] = true;
	        			$acl->addResource($resource, $parent);
	        		}
	        		$parent = $resource;
	        	}
        	}

        	// Finally, register the rule
        	$acl->allow("group_{$rule->group_id}", $resource, $privilege);
        }
        return $acl;
    }
}
