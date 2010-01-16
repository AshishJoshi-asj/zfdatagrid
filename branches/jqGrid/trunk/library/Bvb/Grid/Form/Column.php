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
 * @version    0.4   $
 * @author     Bento Vilas Boas <geral@petala-azul.com > 
 */


class Bvb_Grid_Form_Column
{
    public $options;
    
    function __construct($name,  $options = array())
    {
        $this->options['field'] = $name;
        
        if (count($options) > 0) {
            $this->options = array_merge($this->options, $options);
        }
        
        return $this;
    }
    
    function __call($name, $args)
    {
        $this->options[$name] = $args[0];
        return $this;
    }

}
