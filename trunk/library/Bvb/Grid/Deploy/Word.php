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



class Bvb_Grid_Deploy_Word extends Bvb_Grid_Data implements Bvb_Grid_Deploy_Interface
{

    const OUTPUT = 'word';

    protected $options = array ();

    public $deploy;


    function __construct( $options )
    {

        if (! in_array ( self::OUTPUT, $this->export ))
        {
            echo $this->__ ( "You dont' have permission to export the results to this format" );
            die ();
        }

        $this->_setRemoveHiddenFields(true);
        parent::__construct ($options  );

        $this->addTemplateDir ( 'Bvb/Grid/Template/Word', 'Bvb_Grid_Template_Word', 'word' );

    }

    function deploy()
    {

        $this->setPagination ( 0 );

        parent::deploy ();


        if (! $this->temp['word'] instanceof Bvb_Grid_Template_Word_Word) {
            $this->setTemplate('word', 'word');
        }

        $titles = parent::_buildTitles ();

        #$nome = reset($titles);
        $wsData = parent::_buildGrid ();
        $sql = parent::_buildSqlExp ();

        $xml = $this->temp ['word']->globalStart ();

        $xml .= $this->temp ['word']->titlesStart ();

        foreach ( $titles as $value )
        {
            if (($value ['field'] != @$this->info ['hRow'] ['field'] && @$this->info ['hRow'] ['title'] != '') || @$this->info ['hRow'] ['title'] == '')
            {
                $xml .= str_replace ( "{{value}}", $value ['value'], $this->temp ['word']->titlesLoop () );
            }
        }
        $xml .= $this->temp ['word']->titlesEnd ();


        if (is_array ( $wsData ))
        {
            /////////////////
            if (@$this->info ['hRow'] ['title'] != '')
            {
                $bar = $wsData;

                $hbar = trim ( $this->info ['hRow'] ['field'] );

                $p = 0;
                foreach ( $wsData [0] as $value )
                {
                    if ($value ['field'] == $hbar)
                    {
                        $hRowIndex = $p;
                    }

                    $p ++;
                }
                $aa = 0;
            }

            //////////////
            //////////////
            //////////////

            $i = 1;
            $aa = 0;
            foreach ( $wsData as $row )
            {
                ////////////
                //A linha horizontal
                if (@$this->info ['hRow'] ['title'] != '')
                {

                    if (@$bar [$aa] [$hRowIndex] ['value'] != @$bar [$aa - 1] [$hRowIndex] ['value'])
                    {
                        $xml .= str_replace ( "{{value}}", @$bar [$aa] [$hRowIndex] ['value'], $this->temp ['word']->hRow () );
                    }
                }
                ////////////

                $xml .= $this->temp ['word']->loopStart ();
                $a = 1;
                foreach ( $row as $value )
                {
                    $value ['value'] = strip_tags ( $value ['value'] );

                    if ((@$value ['field'] != @$this->info ['hRow'] ['field'] && @$this->info ['hRow'] ['title'] != '') || @$this->info ['hRow'] ['title'] == '')
                    {
                        $xml .= str_replace ( "{{value}}", $value ['value'], $this->temp ['word']->loopLoop ( 2 ) );
                    }
                    $a ++;
                }
                $xml .= $this->temp ['word']->loopEnd ();
                $aa ++;
                $i ++;
            }
        }


        if (is_array ( $sql ))
        {
            $xml .= $this->temp ['word']->sqlExpStart ();
            foreach ( $sql as $value )
            {
                $xml .= str_replace ( "{{value}}", $value ['value'], $this->temp ['word']->sqlExpLoop () );
            }
            $xml .= $this->temp ['word']->sqlExpEnd ();
        }


        $xml .= $this->temp ['word']->globalEnd ();


        if (! isset($this->deploy['save'])) {
            $this->deploy['save'] = false;
        }

        if (! isset($this->deploy['download'])) {
            $this->deploy['download'] = false;
        }


        if ($this->deploy['save'] != 1 && $this->deploy['download'] != 1) {
            throw new Exception('Nothing to do. Please specify download&&|save options');
        }

        if (empty($this->deploy['name'])) {
            $this->deploy['name'] = date('H_m_d_H_i_s');
        }

        if (substr($this->deploy['name'], - 4) == '.doc') {
            $this->deploy['name'] = substr($this->deploy['name'], 0, - 4);
        }

        $this->deploy['dir'] = rtrim($this->deploy['dir'], '/') . '/';

        if (! is_dir($this->deploy['dir'])) {
            throw new Bvb_Grid_Exception($this->deploy['dir'] . ' is not a dir');
        }

        if (! is_writable($this->deploy['dir'])) {
            throw new Bvb_Grid_Exception($this->deploy['dir'] . ' is not writable');
        }

        file_put_contents($this->deploy['dir'] . $this->deploy['name'] . ".doc", $xml);


        if ($this->deploy['download'] == 1) {
            header ( 'Content-type: application/word' );
            header('Content-Disposition: attachment; filename="' . $this->deploy['name'] . '.doc"');
            readfile($this->deploy['dir'] . $this->deploy['name'] . '.doc');
        }


        if ($this->deploy['save'] != 1) {
            unlink($this->deploy['dir'] . $this->deploy['name'] . '.doc');
        }

        die ();
    }

}



