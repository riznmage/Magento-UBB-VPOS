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

// $Id: XPath.php,v 1.19 2005/10/12 14:48:52 toggg Exp $

// }}}
// {{{ description

// Xpath/DOM XML manipulation and query interface.

// }}}
// {{{ error codes

/*
 * Error codes for the XML_XPath interface, which will be mapped to textual messages
 * in the XML_XPath::errorMessage() function.  If you are to add a new error code, be
 * sure to add the textual messages to the XML_XPath::errorMessage() function as well
 */

define('XML_XPATH_OK',                      1);
define('XML_XPATH_ERROR',                  -1);
define('XML_XPATH_ALREADY_EXISTS',         -2);
define('XML_XPATH_INVALID_DOCUMENT',       -3);
define('XML_XPATH_INVALID_QUERY',          -4);
define('XML_XPATH_NO_DOM',                 -5);
define('XML_XPATH_INVALID_INDEX',          -6);
define('XML_XPATH_INVALID_NODESET',        -7);
define('XML_XPATH_NULL_POINTER',           -8);
define('XML_XPATH_NOT_LOADED',             -9);
define('XML_XPATH_INVALID_NODETYPE',      -10);
define('XML_XPATH_FILE_NOT_WRITABLE',     -11);
define('XML_XPATH_NODE_REQUIRED',         -12);
define('XML_XPATH_INDEX_SIZE',            -13);
define('XML_PARSE_ERROR',                 -14);
define('XML_DUPLICATE_ROOT',              -15);

// }}}
// {{{ includes

require_once 'PEAR.php';
require_once 'XPath/common.php';
require_once 'XPath/result.php';
require_once 'XPath/error.php';

// }}}

// {{{ class XML_XPath

/**
 * The main "XML_XPath" class is simply a container class with some methods for
 * creating DOM xml objects and preparing error codes
 *
 * @version  Revision: 1.1
 * @author   Dan Allen <dan@mojavelinux.com>
 * @access   public
 * @since    PHP 4.2.1
 * @package  XML_XPath
 */

// }}}
class XML_XPath extends XML_XPath_common {
    // {{{ properties

    /** @var object xml data object */
    var $xml;

    /** @var object xpath context object for the xml data object */
    var $ctx;

    /** @var object current location in the xml document */
    var $pointer;
 
    /** @var boolean determines if we have loaded a document or not */
    var $loaded = false;

    /** @var boolean error indicator */
    var $error = false;

    // }}}
    // {{{ constructor

    function XML_Xpath($in_xml = null, $in_type = 'string') 
    {
        // load the xml document if passed in here
        // if not defined, require load() to be called
        if (!is_null($in_xml)) {
            if (XML_XPath::isError($result = $this->load($in_xml, $in_type))) {
                $this->error = $result;
            }
        }
    }

    // }}}
    // {{{ void    load()

    /**
     * Load the xml document on which we will execute queries and modifications.  This xml
     * document can be loaded from a previously existing xmldom object, a string or a file.
     * On successful load of the xml document, a new xpath context is created so that queries
     * can be done immediately.
     *
     * @param  mixed   $in_xml xml document, in one of 3 forms (object, string or file)
     *
     * @access public
     * @return void {or XML_XPath_Error exception}
     */
    function load($in_xml, $in_type = 'string') 
    {
        // if we already have a document loaded, then throw a warning
        if ($this->loaded) {
            return PEAR::raiseError(null, XML_XPATH_ALREADY_EXISTS, null, E_USER_WARNING, $this->xml->root(), 'XML_XPath_Error', true);
        }
        // we need to capture errors, since there is not interface for this
        ob_start();
        // in this case, we already have an xmldom object
        // get_class returns a lowercase name
        if ($in_type == 'object' && strtolower(get_class($in_xml)) == 'domdocument') {
            $this->xml = $in_xml;
        }
        // we can read the file, so use xmldocfile to make a xmldom object
        elseif ($in_type == 'file' && (preg_match(';(https?|ftp)://;', $in_xml) || @file_exists($in_xml))) {
            $this->xml = domxml_open_file($in_xml);
        }
        // this is a string, so attempt to make an xmldom object from string
        elseif($in_type == 'string' && is_string($in_xml)) {
            $this->xml = domxml_open_mem($in_xml);
        }
        // not a valid xml instance, so throw error
        else {
            ob_end_clean();
            return PEAR::raiseError(null, XML_XPATH_INVALID_DOCUMENT, null, E_USER_ERROR, "The xml $in_type '$in_xml' could not be parsed to xml dom", 'XML_XPath_Error', true);
        }
        $loadError = ob_get_contents();
        ob_end_clean();
        // make sure a domxml object was created, and if so initialized the state
        // get_class returns a lowercase name        
        if (strtolower(get_class($this->xml)) == 'domdocument') {
            $this->loaded = true;
            $this->ctx = $this->xml->xpath_new_context();    
            $this->pointer = $this->xml->root();
            return true;
        }
        // we could not make a domxml object, so throw an error
        else {
            return PEAR::raiseError(null, XML_XPATH_NO_DOM, null, E_USER_ERROR, "<b>libxml2 Message:</b> $loadError <b>Document:</b> $in_xml", 'XML_XPath_Error', true);
        }
    }
 
    // }}}
    // {{{ void    registerNamespace()

    /**
     * This function is sort of temporary hack for registering namespace prefixes
     *
     * The domxml functions should do this automatically when a domxml object is read in.
     * For now, you can use this function.
     *
     * @param  array $in_namespaces
     *
     * @return void
     * @access public
     */
    function registerNamespace($in_namespaces)
    {
        settype($in_namespaces, 'array');
        foreach($in_namespaces as $localName => $namespace) {
            $this->ctx->xpath_register_ns($localName, $namespace);
        }
    }

    // }}}
    // {{{ boolean isError()

    /**
     * Tell whether a result code from a XML_XPath method is an error.
     *
     * @param  object  $in_value object in question
     *
     * @access public
     * @return boolean whether object is an error object
     */
    function isError($in_value)
    {
        if (is_a($in_value, 'xml_xpath_error')) {
	        return true;
        }
        if (is_a($in_value, 'xml_xpath')) {
	        return (bool) $in_value->error;
        }
        return false;
    }

    // }}}
    // {{{ mixed   errorMessage()

    /**
     * Return a textual error message for an XML_XPath error code.
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
                XML_XPATH_OK                    => 'no error',
                XML_XPATH_ERROR                 => 'unknown error',
                XML_XPATH_ALREADY_EXISTS        => 'xml document already loaded',
                XML_XPATH_INVALID_DOCUMENT      => 'invalid xml document',
                XML_XPATH_INVALID_QUERY         => 'invalid xpath query',
                XML_XPATH_NO_DOM                => 'DomDocument could not be instantiated',
                XML_XPATH_INVALID_INDEX         => 'invalid index',
                XML_XPATH_INVALID_NODESET       => 'requires nodeset and one of appropriate type',
                XML_XPATH_NOT_LOADED            => 'DomDocument has not been loaded',
                XML_XPATH_NULL_POINTER          => 'Null pointer, probably due to empty result object',
                XML_XPATH_INVALID_NODETYPE      => 'invalid nodetype for requested feature',
                XML_XPATH_FILE_NOT_WRITABLE     => 'file could not be written',
                XML_XPATH_NODE_REQUIRED         => 'DomNode required for operation',
                XML_XPATH_INDEX_SIZE            => 'index given out of range',
                XML_PARSE_ERROR             => 'parse error in xml string',
                XML_DUPLICATE_ROOT          => 'root element already exists'
            );  
        }

        // If this is an error object, then grab the corresponding error code
        if (XML_XPath::isError($in_value)) {
            if (is_a($in_value, 'xml_xpath_error')) {
                $in_value = $in_value->getCode();
            } else {
                $in_value = $in_value->error->getCode();
            }
        }
        
        // return the textual error message corresponding to the code
        return isset($errorMessages[$in_value]) ? $errorMessages[$in_value] : $errorMessages[XML_XPATH_ERROR];
    }

    // }}}
    // {{{ void    reset()

    /**
     * Resets the object so it is possible to load another xml document.
     *
     * @access public
     * @return void
     */
    function reset()
    {
        $this->xml = null;
        $this->ctx = null;
        $this->pointer = null;
        $this->loaded = false;
    }

    // }}}
    // {{{ void    free()

    /**
     * Kill the class object to free memory.  Not really sure how necessary this is, but xml
     * documents can be pretty big.  This will kill everything, so only use it when you are done
     *
     * @access public
     * @return void
     */
    function free()
    {
        $this->reset();
    }

    // }}}
}
?>
