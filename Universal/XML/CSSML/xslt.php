<?php
// {{{ license

// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Dan Allen <dan@mojavelinux.com>                             |
// +----------------------------------------------------------------------+

// $Id: xslt.php,v 1.4 2005/10/12 12:38:13 toggg Exp $

// }}}
// {{{ description

// XML_CSSML is a CSSML to CSS xslt parser

// }}}

// {{{ class XML_CSSML_xslt

/**
 * The XML_CSSML_xslt is a container class which
 * provides the sablotron xsl functions to parse a CSSML
 * document into a stylesheet with the ability to output
 * to a file or return
 *
 * @author   Dan Allen <dan@mojavelinux.com>
 * @version  Revision: 0.1
 * @access   public
 * @package  XML_CSSML
 */

// }}}
class XML_CSSML_xslt extends XML_CSSML
{
    // {{{ properties

    /**
     * The sabltron extension can use xml strings as arguments for the
     * processor, but must do so when calling xslt_process.  This variable
     * holds those parameters.
     * @var array $arguments
     */
    var $arguments = array();

    // }}}
    // {{{ constructor

    function XML_CSSML_xslt($in_CSSML = null, $in_type = 'string', $in_params = null)
    {
        if (!function_exists('xslt_create')) {
            $this = PEAR::raiseError(null, XML_CSSML_ERROR, null, E_USER_ERROR,
             'This driver needs the xslt extension to run', 'XML_CSSML_Error', true);
            return;
        }
        $this->loaded = false;
        if (!is_null($in_CSSML)) {
            $this->load($in_CSSML, $in_type);
        }

        if (!is_null($in_params)) {
            $this->setParams($in_params);
        }

        $this->stylesheetDoc = dirname(__FILE__) . '/xslt.xsl';
    }

    // }}}
    // {{{ process()

    // I need some error checking in here
    function process()
    {
        if (parent::isError($process = parent::process())) {
            return $process;
        }

        // Prepare the params for passing to the stylesheet
        $params = array(
            'filter'        => $this->filter,
            'browser'       => $this->browser,
            'comment'       => $this->comment,
        );

        $xh = xslt_create();

        $result = xslt_process($xh, $this->CSSMLDoc, $this->stylesheetDoc, null, $this->arguments, $params);

        if ($this->output != 'STDOUT') {
            $fp = fopen($this->output, 'w');
            fwrite($fp, $result);
            fclose($fp);
            $result = true;
        }

        return $result;
    }

    // }}}
    // {{{ load()

    // I need some more error checking in here
    function load($in_CSSML, $in_type = 'string')
    {
        if (parent::isError($load = parent::load())) {
            return $load;
        }

        if ($in_type == 'file' && @file_exists($in_CSSML)) {
            $this->CSSMLDoc = $in_CSSML;
        } elseif ($in_type == 'string' && is_string($in_CSSML)) {
            $this->CSSMLDoc = 'arg:/_xml';
            $this->arguments = array('/_xml' => $in_CSSML);
        } else {
            return PEAR::raiseError(null, XML_CSSML_INVALID_DATA, null, E_USER_WARNING, "Request data: $in_CSSML", 'XML_CSSML_Error', true);
        }

        $this->loaded = true;
    }

    // }}}
}
?>
