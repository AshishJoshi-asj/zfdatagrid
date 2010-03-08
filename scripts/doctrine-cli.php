#!/usr/bin/env php

<?php
    define('AE', 'development');

    require dirname(__FILE__).'/../application/setup.php';
    
    require_once AP.'/../library/Doctrine/Doctrine/Parser/sfYaml/sfYaml.php';
    
    // bootstrap Doctrine and the Autoloader
    $application->bootstrap('autoload');

    // set aggressive loading to make sure migrations are working
    Doctrine_Manager::getInstance()->setAttribute(
        Doctrine::ATTR_MODEL_LOADING,
        Doctrine_Core::MODEL_LOADING_AGGRESSIVE
    );

    Doctrine_Manager::getInstance()->setAttribute(Doctrine_Core::ATTR_QUOTE_IDENTIFIER, true);
    
    $options = $application->getBootstrap()->getOptions();
    
    Doctrine_Manager::getInstance()->openConnection($options['resources']['doctrine']['master'], 'master');
    
    $cli = new Doctrine_Cli($options['resources']['doctrine']);
    $cli->run($_SERVER['argv']);
