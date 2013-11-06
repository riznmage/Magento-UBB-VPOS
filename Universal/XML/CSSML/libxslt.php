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

// $Id: libxslt.php,v 1.6 2005/10/12 12:38:13 toggg Exp $

// }}}
// {{{ description

// XML_CSSML is a CSSML to CSS xslt parser

// }}}

// {{{ class XML_CSSML_domxml

/**
 * The XML_CSSML_domxml is a container class which
 * provides the libxslt xsl functions to parse a CSSML
 * document into a stylesheet with the ability to output
 * to a file or return
 *
 * @author   Dan Allen <dan@mojavelinux.com>
 * @version  Revision: 0.1
 * @access   public
 * @package  XML_CSSML
 */

// }}}
class XML_CSSML_libxslt extends XML_CSSML
{
    // {{{ constructor

    function XML_CSSML_libxslt($in_CSSML = null, $in_type = 'string', $in_params = null)
    {
        if (!function_exists('domxml_version')) {
            $this = PEAR::raiseError(null, XML_CSSML_ERROR, null, E_USER_ERROR,
             'This driver needs the domxml extension to run', 'XML_CSSML_Error', true);
            return;
        }
        $this->loaded = false;
        if (!is_null($in_CSSML)) {
            $this->load($in_CSSML, $in_type);
        }

        if (!is_null($in_params)) {
            $this->setParams($in_params);
        }

        $this->stylesheetDoc = domxml_xslt_stylesheet_file(dirname(__FILE__) . '/libxslt.xsl');
    }

    // }}}
    // {{{ process()

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
            'output'        => $this->output,
        );

        // Run the transformation and return the result (empty if stream is file)
        $result = $this->stylesheetDoc->process($this->CSSMLDoc, $params);

        // If stream is STDOUT then create string and return
        if ($this->output == 'STDOUT') {
            $resultData = $result->document_element();
            $output = $resultData->get_content();
        }

        return isset($output) ? $output : true;
    }

    // }}}
    // {{{ load()

    function load($in_CSSML, $in_type = 'string')
    {
        if (parent::isError($load = parent::load())) {
            return $load;
        }

        // If the CSSML data is already a DOM object (can tell by checking for root)
        if ($in_type == 'object' && get_class($in_CSSML) == 'DomDocument') {
            $this->CSSMLDoc = $in_CSSML;
        }
        // If this is a data file, then make it an DOM object with the file function
        elseif ($in_type == 'file' && @file_exists($in_CSSML)) {
            $this->CSSMLDoc = domxml_open_file($in_CSSML);
        }
        // If we were given a string, then make it a DOM object with the string function
        elseif ($in_type == 'string' && is_string($in_CSSML)) {
            $this->CSSMLDoc = domxml_open_mem($in_CSSML);
        }
        // We need to die here because we have no data or it cannot be xml
        else {
            return PEAR::raiseError(null, XML_CSSML_INVALID_DATA, null, E_USER_WARNING, "Request data: $in_CSSML", 'XML_CSSML_Error', true);
        }

        if (!is_a($this->CSSMLDoc, 'DomDocument')) {
            return PEAR::raiseError(null, XML_CSSML_INVALID_DOCUMENT, null, E_USER_WARNING, "Request data: $in_CSSML", 'XML_CSSML_Error', true);
        }

        $this->loaded = true;
    }

    // }}}
}
?>
