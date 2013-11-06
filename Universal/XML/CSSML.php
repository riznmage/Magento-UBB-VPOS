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

// $Id: CSSML.php,v 1.8 2005/10/12 12:38:12 toggg Exp $

// }}}
// {{{ description

// XML_CSSML is a CSSML to CSS xslt parser

// }}}
// {{{ error codes

define('XML_CSSML_OK',                 0);
define('XML_CSSML_ERROR',             -1);
define('XML_CSSML_ALREADY_EXISTS',    -2);
define('XML_CSSML_NOT_LOADED',        -3);
define('XML_CSSML_INVALID_DATA',      -4);
define('XML_CSSML_INVALID_DOCUMENT',  -5);
define('XML_CSSML_INVALID_FILE',      -6);

// }}}
// {{{ includes

require_once 'PEAR.php';
require_once 'XML/CSSML/Error.php';

// }}}
// {{{ functions

/**
 * Replace function is_a()
 *
 * @category PHP
 * @package  PHP_Compat
 * @link     http://php.net/function.is_a
 * @author   Aidan Lister <aidan@php.net>
 * @version  $Revision: 1.8 $
 * @since       PHP 4.2.0
 * @require     PHP 4.0.0 (user_error) (is_subclass_of)
 */
if (!function_exists('is_a')) {
    function is_a($object, $class)
    {
        if (!is_object($object)) {
           return false;
        }

        if (get_class($object) == strtolower($class)) {
            return true;
        } else {
            return is_subclass_of($object, $class);
        }
    }
}

// }}}

// {{{ class XML_CSSML

/**
 * The XML_CSSML class provides the xsl functions
 * to parse a CSSML document into a stylesheet
 * with the ability to output to a file or return
 *
 * @author   Dan Allen <dan@mojavelinux.com>
 * @version  Revision: 0.1
 * @access   public
 * @package  XML_CSSML
 */

// }}}
class XML_CSSML {
    // {{{ properties

    /**
     * domxml object which holds the xml document with the css information
     * @var object $CSSMLDoc
     */
    var $CSSMLDoc;

    /**
     * domxml object which holds the xsl document which parses the cssml document
     * @var object $stylesheetDoc
     */
    var $stylesheetDoc;

    /**
     * Redirection method for the output of the cssml (file, stdout)
     * If redirection is a file, it must be absolute
     * @var string $outputMethod
     */
    var $output = 'STDOUT';

    /**
     * Code corresponding to the user agent of the browser,
     * such as is generated with Net_UserAgentDetect
     * @var string $browser
     */
    var $browser = '';

    /**
     * Filter for the entries in the CSSML
     * @var string $filter
     */
    var $filter = '';

    /**
     * Comment to be used at the top of the stylesheet output
     * @var string $comment
     */
    var $comment = '';

    /**
     * Boolean which defines if the CSSML document has been loaded
     * @var boolean $loaded
     */
    var $loaded = false;

    // }}}
    // {{{ constructor

    /**
     * Class constructor, prepare the cssml document object from either a string, file or object
     *
     * @param mixed $in_cssml Optionally the CSSML data can be passed to the constructor
     * @return void
     * @access private
     */
    function XML_CSSML($in_driver, $in_CSSML = null, $in_type = 'string', $in_params = null)
    {
        $this = $this->factory($in_driver, $in_CSSML, $in_type, $in_params);
    }

    // }}}
    // {{{ factory()

    function &factory($in_driver, $in_CSSML = null, $in_type = 'string', $in_params = null)
    {
        $interface_path = 'CSSML/' . $in_driver . '.php';
        $interface_class = 'XML_CSSML_' . $in_driver;

        @include_once $interface_path;

        $obj =& new $interface_class($in_CSSML, $in_type, $in_params);
        return $obj;
    }

    // }}}
    // {{{ load()

    /**
     * Prepare the CSSML document object from either a string, file or object.  This
     * will set the CSSMLDoc class variable which will be parsed by the xsl stylesheet
     * into a CSS stylesheet
     *
     * @param mixed $in_CSSML The CSSML document which contains the information for
     *                         generating the CSS document
     *
     * @return void
     *
     */
    function load()
    {
        if ($this->loaded) {
            return PEAR::raiseError(null, XML_CSSML_ALREADY_EXISTS, null, E_USER_WARNING, $this->CSSMLDoc, 'XML_CSSML_Error', true);
        }
    }

     // }}}
     // {{{ setParams()

    /**
     * Set the params (params) that will be used when calling the stylesheet parser.
     * This pertains particularly to variables such as browser code, image path and
     * the filter.  It works by passing an associative array with any number of the
     * possible parameters for the stylesheet.  If a variable is not set, the default
     * will be used
     *
     * @param array $in_params Associative array of the params
     *
     * @return void
     *
     */
    function setParams($in_params)
    {
        if (isset($in_params['browser'])) {
            $this->browser = $in_params['browser'];
        }

        if (isset($in_params['filter'])) {
            $this->filter = $in_params['filter'];
        }

        if (isset($in_params['comment'])) {
            $this->comment = str_replace(array('/*', '*/'), '', $in_params['comment']);
        }

        if (isset($in_params['output'])) {
            $this->output = $in_params['output'];
            if ($in_params['output'] != 'STDOUT') {
                // check to make sure this is a file...this needs work
                if (!@file_exists($in_params['output']) && !@touch($in_params['output'])) {
                    $this->output = 'STDOUT';
                    return PEAR::raiseError(null, XML_CSSML_INVALID_FILE, PEAR_ERROR_PRINT, E_USER_NOTICE, '', 'XML_CSSML_Error', true);
                }
            }
        }
    }

    // }}}
    // {{{ process()

    /**
     * Run the transformation on the CSSML document using the CSSML xsl stylesheet.  If
     * the output method is to a file, then the function will not return.  If the output
     * is set to STDOUT, the xml string will be returned (really the css document) after
     * some clean up of entities and domxml bugs have been fixed
     *
     * @return css string if output method is STDOUT, else void
     * @access public
     */
    function process()
    {
        if (!$this->loaded) {
            return PEAR::raiseError(null, XML_CSSML_NOT_LOADED, null, E_USER_WARNING, 'use load() function', 'XML_CSSML_Error', true);
        }
    }

    // }}}
    // {{{ isError()

    /**
     * Tell whether a result code from a XML_CSSML method is an error.
     *
     * @param  object  $in_value object in question
     *
     * @access public
     * @return boolean whether object is an error object
     */
    function isError($in_value)
    {
        return is_a($in_value, 'xml_cssml_error');
    }

    // }}}
    // {{{ errorMessage()

    /**
     * Return a textual error message for an XML_CSSML error code.
     *
     * @param  int $in_value error code
     *
     * @access public
     * @return string error message, or false if not error code
     */
    function errorMessage($in_value)
    {
        // make the variable static so that it only has to do the defining on the first call
        static $errorMessages;

        // define the varies error messages
        if (!isset($errorMessages)) {
            $errorMessages = array(
                XML_CSSML_OK                    => 'no error',
                XML_CSSML_ERROR                 => 'unknown error',
                XML_CSSML_ALREADY_EXISTS        => 'cssml document already loaded',
                XML_CSSML_NOT_LOADED            => 'cssml document has not been loaded',
                XML_CSSML_INVALID_DATA          => 'invalid cssml data to parse',
                XML_CSSML_INVALID_DOCUMENT      => 'cssml domdocument could not be created',
                XML_CSSML_INVALID_FILE          => 'output file does not exist',
            );
        }

        // If this is an error object, then grab the corresponding error code
        if (XML_CSSML::isError($in_value)) {
            $in_value = $in_value->getCode();
        }

        // return the textual error message corresponding to the code
        return isset($errorMessages[$in_value]) ? $errorMessages[$in_value] : $errorMessages[XML_CSSML_ERROR];
    }

    // }}}
    // {{{ reset()

    /**
     * Resets the object so it is possible to load another xml document.
     *
     * @access public
     * @return void
     */
    function reset()
    {
        $this->CSSMLDoc = null;
        $this->loaded = false;
    }

    // }}}
    // {{{ free()

    /**
     * Kill the class object to free memory.  Not really sure how necessary this is, but xml
     * documents can be pretty big.  This will kill everything, so only use it when you are done
     *
     * @access public
     * @return void
     */
    function free()
    {
        $vars = get_class_vars('XML_CSSML');

        foreach ($vars as $var => $value) {
            $this->$var = null;
        }
    }

    // }}}
}
?>
