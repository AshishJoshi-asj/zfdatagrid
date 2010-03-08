#!/usr/bin/env php

<?php
    define('AE', 'development');

    require dirname(__FILE__).'/../application/setup.php';
    
    require_once AP.'/../library/Doctrine/Doctrine/Parser/sfYaml/sfYaml.php';
    
    // bootstrap Doctrine and the Autoloader
    $application = Cb_Application::getInstance()->getApplication();
    $application->getBootstrap()
        ->bootstrap('autoload');

    // set aggressive loading to make sure migrations are working
    Doctrine_Manager::getInstance()->setAttribute(
        Doctrine::ATTR_MODEL_LOADING,
        Doctrine_Core::MODEL_LOADING_AGGRESSIVE
    );

    Doctrine_Manager::getInstance()->setAttribute(Doctrine_Core::ATTR_QUOTE_IDENTIFIER, true);
    
    $options = $application->getBootstrap()->getOptions();
    
    Doctrine_Manager::getInstance()->openConnection($options['resources']['doctrine']['master'], 'master');
    Doctrine_Manager::getInstance()->openConnection($options['resources']['doctrine']['dt'], 'dt');
    
    $cb_generate_yaml_db_path = dirname(dirname(__FILE__)) . '/library/Cb/Doctrine/Task/CbGenerateYamlDb.php';
    require $cb_generate_yaml_db_path;
    
    $cli = new Doctrine_Cli($options['resources']['doctrine']);
    $cli->registerTaskClass('Cb_Doctrine_Task_CbGenerateYamlDb');
    $cli->run($_SERVER['argv']);
