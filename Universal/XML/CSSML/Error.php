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

// $Id: Error.php,v 1.2 2003/01/04 11:56:29 mj Exp $

// }}}
// {{{ description

// XML_CSSML is a CSSML to CSS xslt parser

// }}}

// {{{ class XML_CSSML_Error

/**
 * XML_CSSML_Error Class for Error Handling of CSSML
 * @access public
 */

// }}}
class XML_CSSML_Error extends PEAR_Error {
    // {{{ properties

    /**
     * Message in front of the error message
     * @var string $error_message_prefix
     */
    var $error_message_prefix = 'XML_CSSML Error: ';
    
    // }}}
    // {{{ constructor

    /**
    * Creates a XML_CSSML error object, extending the PEAR_Error class
    *
     * @param int   $code the xpath error code
     * @param int   $mode (optional) the reaction either return, die or trigger/callback
     * @param int   $level (optional) intensity of the error (PHP error code)
     * @param mixed $debuginfo (optional) information that can inform user as to nature of error
     *
     * @access private
     */
    function XML_CSSML_Error($code = XML_CSSML_ERROR, $mode = PEAR_ERROR_RETURN, 
                         $level = E_USER_NOTICE, $debuginfo = null) 
    {
        if (is_int($code)) {
            $this->PEAR_Error(XML_CSSML::errorMessage($code), $code, $mode, $level, $debuginfo);
        } 
        else {
            $this->PEAR_Error("Invalid error code: $code", XML_CSSML_ERROR, $mode, $level, $debuginfo);
        }
    }
 
    // }}}
}
?>
