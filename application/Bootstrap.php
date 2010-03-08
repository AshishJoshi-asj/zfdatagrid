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

        $view->addHelperPath(AP . '/views/helpers', 'App_View_Helper');
        $view->addHelperPath('App/View/Helper', 'App_View_Helper');
        $view->addHelperPath("ZendX/JQuery/View/Helper", "ZendX_JQuery_View_Helper");

        // Add it to the ViewRenderer
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
        $viewRenderer->setView($view);

        $view->headTitle('ZFDatagrid.com');

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
                               'App_Controller_Plugin_Debug_Plugin_Doctrine',
                               'Memory',
                               'Time',
                               'Registry',
                               'Exception')
        );

        $debug = new App_Controller_Plugin_Debug($options);

        $this->bootstrap('frontController');
        $frontController = $this->getResource('frontController');
        $frontController->registerPlugin($debug);
    }
}
