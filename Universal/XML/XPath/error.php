<?php
// {{{ license

// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2001 The PHP Group                                |
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

// $Id: error.php,v 1.4 2002/06/07 00:13:15 dallen Exp $

// }}}
// {{{ description

// Xpath/DOM XML Error interface to PEAR::Error

// }}}

// {{{ class XML_XPath_Error

/**
 * Error class for the XML_XPath interface, which just prepares some variables and spawns PEAR_Error
 *
 * @version  Revision: 1.1
 * @author   Dan Allen <dan@mojavelinux.com>
 * @access   public
 * @since    PHP 4.2.1
 * @package  XML_XPath
 */

// }}}
class XML_XPath_Error extends PEAR_Error {
    // {{{ properties 

    /**
     * Message in front of the error message
     * @var string $error_message_prefix
     */
    var $error_message_prefix = 'XML_XPath Error: ';
    
    // }}}
    // {{{ constructor

    /**
     * Creates an XML_XPath error object, extending the PEAR_Error class
     *
     * @param int   $code the xpath error code
     * @param int   $mode the reaction to the error, either return, die or trigger/callback
     * @param int   $level intensity of the error (PHP error code)
     * @param mixed $debuginfo any information that can inform user as to nature of the error
     *
     * @access private
     */
    function XML_XPath_Error($code = XML_XPATH_ERROR, $mode = PEAR_ERROR_RETURN, 
                         $level = E_USER_NOTICE, $debuginfo = null) 
    {
        if (is_int($code)) {
            $this->PEAR_Error(XML_XPath::errorMessage($code), $code, $mode, $level, $debuginfo);
        } 
        else {
            $this->PEAR_Error("Invalid error code: $code", XML_XPATH_ERROR, $mode, $level, $debuginfo);
        }
    }
    
    // }}}
}
?>
