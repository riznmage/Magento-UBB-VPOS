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

// $Id: common.php,v 1.22 2003/08/18 04:04:45 dallen Exp $

// }}}
// {{{ description

// Core DOM and internal pointer methods for the Xpath/DOM XML manipulation and query interface.

// }}}
// {{{ functions

/**
 * is_a is only defined in php for user functions, 
 * so I implemented its functionality for php objects
 * until they fix (or never fix) this problem
 *
 * @param  object  $class the class to check
 * @param  string  $match class name you are looking for
 *
 * @access public
 * @return boolean whether the class is of the class type or a descendent of the class
 */
function is_a_php_class($class, $match) 
{
    if (empty($class)) {
        return false;
    }

    $class = is_object($class) ? get_class($class) : $class;
    if (strtolower($class) == strtolower($match)) {
        return true;
    }

    return is_a_php_class(get_parent_class($class), $match);
}

// }}}

// {{{ class XML_XPath_common

/**
 * The XML_XPath_common class contains the DOM functions used to manipulate
 * and maneuver through the xml tree.  The main thing to understand is
 * that all operations work around a single pointer.  This pointer is your
 * place holder within the document.  Each function you run assumes the
 * node in reference is your pointer.  However, every function can take
 * an xpath query or DOM object reference, so that the pointer can be set
 * before working on the node, and can retain this position if specified.
 * Every DOM function call has a init() and shutdown() call.  This function
 * prepares the pointer to the requested location in the tree if an xpath query
 * or pointer object is provided.  In addition, the init() function checks to
 * see that the node type is acceptable for the method, and if not throws an
 * XML_XPath_Error exception.  If you want to execute a function and then remain
 * in the location of your query, then you specify that you want to move the pointer.
 * For the DOM step functions, this is the default action.
 *
 * Note: All offsets in the CharacterData interface start from 0.
 *
 * The object model of XML_XPath is as follows (indentation means inheritance):
 *
 * XML_XPath_common The main functionality of the XML_XPath class is here.  This 
 * |            holds all the DOM functions for manipulating and maneuvering
 * |            through the DOM tree.
 * |
 * +-XML_XPath      The frontend for the XML_XPath implementation.  Provides default
 * |            functions for preparing the main document, running xpath queries
 * |            and handling errorMessages.
 * |
 * +-Result     Extended from the XML_XPath_common class, this object is returned when
 *              an xpath query is executed and can be used to cycle through the
 *              result nodeset or data
 *
 * @version  Revision: 1.1
 * @author   Dan Allen <dan@mojavelinux.com>
 * @access   public
 * @since    PHP 4.2.1
 * @package  XML_XPath
 */

// }}}
class XML_XPath_common {
    // {{{ properties

    /**
     * domxml node of the current location in the xml document
     * @var object $pointer
     */
    var $pointer;

    /**
     * domxml node bookmark used for holding a place in the xml document
     * @var object $bookmark 
     */
    var $bookmark;

    /**
     * when working with the xml document, ignore the presence of blank nodes (white space)
     * @var boolean $skipBlanks
     */
    var $skipBlanks = true;

    /**
     * path to xmllint used for reformating the xml output
     * [!] should be using System_Command for this [!]
     * @var string $xmllint
     */
    var $xmllint = 'xmllint';

    // }}}
    // {{{ string  nodeName()

    /**
     * Return the name of this node, depending on its type, according to the DOM recommendation
     *
     * @param  string  $in_xpathQuery (optional) quick xpath query
     * @param  boolean $in_movePointer (optional) move internal pointer with quick xpath query
     *
     * @access public
     * @return string name of node corresponding to DOM recommendation {or XML_XPath_Error exception}
     */
    function nodeName($in_xpathQuery = null, $in_movePointer = false) 
    {
        if (!$this->pointer) {
            return PEAR::raiseError(null, XML_XPATH_NULL_POINTER, null, E_USER_WARNING, '', 'XML_XPath_Error', true);  
        }

        if ($hasQuery = !is_null($in_xpathQuery) && XML_XPath::isError($result = $this->_quick_evaluate_init($in_xpathQuery, $in_movePointer))) {
            return $result;
        }

        $nodeName = $this->pointer->node_name();

        if ($hasQuery && !$in_movePointer) {
            $this->_restore_bookmark();
        }

        return $nodeName;
    }

    // }}}
    // {{{ int     nodeType()

    /**
     * Returns the integer value constant corresponding to the DOM node type
     *
     * @param  string  $in_xpathQuery (optional) quick xpath query
     * @param  boolean $in_movePointer (optional) move internal pointer with quick xpath query
     *
     * @access public
     * @return int DOM type of the node {or XML_XPath_Error exception}
     */
    function nodeType($in_xpathQuery = null, $in_movePointer = false)
    {
        if (!$this->pointer) {
            return PEAR::raiseError(null, XML_XPATH_NULL_POINTER, null, E_USER_WARNING, '', 'XML_XPath_Error', true);  
        }

        if ($hasQuery = !is_null($in_xpathQuery) && XML_XPath::isError($result = $this->_quick_evaluate_init($in_xpathQuery, $in_movePointer))) {
            return $result;
        }

        $nodeType = $this->pointer->node_type();

        if ($hasQuery && !$in_movePointer) {
            $this->_restore_bookmark();
        }

        return $nodeType;
    }

    // }}}
    // {{{ object  childNodes()

    /**
     * Retrieves the child nodes from the element node as an XML_XPath_result object
     *
     * Similar to an xpath query, this function will grab all the first descendant child
     * nodes of the element node at the current position and will create an XML_XPath_result
     * object of type nodeset with each of the child nodes as the nodes.
     * DOM query functions do not take an xpathQuery argument
     *
     * @access public
     * @return object XML_XPath_result object of type nodeset
     * [!] important note: since we had to hack the result object a bit, you cannot sort the
     * result object when generated in this manner right now [!]
     */
    function &childNodes()
    {
        if (!$this->pointer) {
            return PEAR::raiseError(null, XML_XPATH_NULL_POINTER, null, E_USER_WARNING, '', 'XML_XPath_Error', true);  
        }

        $nodeset = array();
        foreach($this->pointer->child_nodes() as $childNode) {
            // if this is a blank node and we are skipping blank nodes...skip to next child
            if ($childNode->is_blank_node() && $this->skipBlanks) {
                continue;
            }
            $nodeset[] = $childNode;
        }
        return new XML_XPath_result($nodeset, XPATH_NODESET, array($this->pointer, '/*'), $this->ctx, $this->xml);
    }

    // }}}
    // {{{ object  getElementsByTagName()

    /**
     * Create an XML_XPath_result object with the elements with the specified tagname
     *
     * DOM query functions do not take an xpathQuery argument
     *
     * @param  string $in_tagName
     *
     * @return object XML_XPath_result object of matching nodes
     * @access public
     */
    function getElementsByTagName($in_tagName)
    {
        // since we can't do an actual xpath query, we need to create a pseudo xpath result
        $nodeset = $this->xml->get_elements_by_tagname($in_tagName);
        return new XML_XPath_result($nodeset, XPATH_NODESET, array(null, '//' . $in_tagName), $this->ctx, $this->xml);
    }

    // }}}
    // {{{ boolean documentElement()

    /**
     * Move to the document element
     *
     * @param  boolean  $in_movePointer (optional) move the internal pointer or return reference 
     *
     * @access public
     * @return boolean whether pointer was moved or object pointer to document element
     */
    function documentElement($in_movePointer = true)
    {
        if (!$this->pointer) {
            return PEAR::raiseError(null, XML_XPATH_NULL_POINTER, null, E_USER_WARNING, '', 'XML_XPath_Error', true);  
        }
        
        $documentElement = $this->xml->document_element();
        if ($in_movePointer) {
            $this->pointer = $documentElement;
            return true;
        }
        else {
            return $documentElement;
        }
    }

    // }}}
    // {{{ boolean parentNode()

    /**
     * Moves the internal pointer to the parent of the current node or returns the pointer.
     * Step functions do not take an xpathQuery argument
     *
     * @param  boolean  $in_movePointer (optional) move the internal pointer or return reference 
     *
     * @access public
     * @return boolean whether pointer was moved or object pointer to parent
     */
    function parentNode($in_movePointer = true)
    {
        if (!$this->pointer) {
            return PEAR::raiseError(null, XML_XPATH_NULL_POINTER, null, E_USER_WARNING, '', 'XML_XPath_Error', true);  
        }

        $parent = $this->pointer->parent_node(); 
        if ($parent) {
            if ($in_movePointer) {
                $this->pointer = $parent;
                return true;
            }
            else {
                return $parent;
            }
        }
        else {
            return false;
        }
    }

    // }}}
    // {{{ boolean nextSibling()

    /**
     * Moves the internal pointer to the next sibling of the current node, or returns the pointer.
     * If the flag is on to skip blank nodes then the first non-blank node is used.
     * Step functions do not take an xpathQuery argument
     *
     * @param  boolean  $in_movePointer (optional) move the internal pointer or return reference 
     *
     * @access public
     * @return boolean whether the pointer was moved or object pointer to next sibling
     */
    function nextSibling($in_movePointer = true)
    {
        if (!$this->pointer) {
            return PEAR::raiseError(null, XML_XPATH_NULL_POINTER, null, E_USER_WARNING, '', 'XML_XPath_Error', true);  
        }

        if (!$this->pointer->next_sibling()) {
            return false;
        }

        $next = $this->pointer->next_sibling();
        if ($this->skipBlanks) {
            while (true) {
                // make sure we are not already at the end
                if (!$next) {
                    $next = false;
                    break;
                }
                // we have found a non-blank node
                elseif (!$next->is_blank_node()) {
                    break;
                }
                // we found a blank node at the very end
                elseif (!$next = $next->next_sibling()) {
                    $next = false;
                    break;
                }
            }
        }
        if ($next) {
            if ($in_movePointer) {
                $this->pointer = $next;
                return true;
            }
            else {
                return $next;
            }
        }
        else {
            return false;
        }
    }

    // }}}
    // {{{ boolean previousSibling()

    /**
     * Moves the internal pointer to the previous sibling 
     * of the current node or returns the pointer.
     * If the flag is on to skip blank nodes then the first non-blank node is used.
     * Step functions do not take an xpathQuery argument
     *
     * @param  boolean  $in_movePointer (optional) move the internal pointer or return reference 
     *
     * @access public
     * @return boolean whether the pointer was moved or object pointer to previous sibling
     */
    function previousSibling($in_movePointer = true)
    {
        if (!$this->pointer) {
            return PEAR::raiseError(null, XML_XPATH_NULL_POINTER, null, E_USER_WARNING, '', 'XML_XPath_Error', true);  
        }

        if (!$this->pointer->previous_sibling()) {
            return false;
        }

        $previous = $this->pointer->previous_sibling();
        if ($this->skipBlanks) {
            while (true) {
                // we have found a non-blank node
                if (!$previous->is_blank_node()) {
                    break;
                }
                // we have arrived at the beginning
                elseif (!$previous = $previous->previous_sibling()) {
                    $previous = false;
                    break;
                }
            }
        }
        if ($previous) {
            if ($in_movePointer) {
                $this->pointer = $previous;
                return true;
            }
            else {
                return $previous;
            }
        }
        else {
            return false;
        }
    }

    // }}}
    // {{{ boolean firstChild()

    /**
     * Moves the pointer to the first child of this node or returns the first node.  
     * If the flag is on to skip blank nodes then the first non-blank node is used.
     * Step functions do not take an xpathQuery argument
     *
     * @param  boolean  $in_movePointer (optional) move the internal pointer or return reference 
     *
     * @access public
     * @return boolean whether the pointer was moved to the first child or returns the first child
     */
    function firstChild($in_movePointer = true)
    {
        if (!$this->pointer) {
            return PEAR::raiseError(null, XML_XPATH_NULL_POINTER, null, E_USER_WARNING, '', 'XML_XPath_Error', true);  
        }

        if (!$this->pointer->has_child_nodes()) {
            return false;
        }

        $first = $this->pointer->first_child();
        if ($this->skipBlanks) {
            while (true) {
                // we have found a non-blank node
                if (!$first->is_blank_node()) {
                    break;
                }
                // we have arrived at the end
                elseif (!$first = $first->next_sibling()) {
                    $first = false;
                    break;
                }
            }
        }
        if ($first) {
            if ($in_movePointer) {
                $this->pointer = $first;
                return true;
            }
            else {
                return $first;
            }
        }
        else {
            return false;
        }
    }

    // }}}
    // {{{ boolean lastChild()

    /**
     * Moves the pointer to the last child of this node or returns the last child.
     * If the flag is on to skip blank nodes then the first non-blank node is used.
     * Step functions do not take an xpathQuery argument
     *
     * @param  boolean  $in_movePointer (optional) move the internal pointer or return reference 
     *
     * @access public
     * @return boolean whether the pointer was moved to the last child or returns the last child
     */
    function lastChild($in_movePointer = true)
    {
        if (!$this->pointer) {
            return PEAR::raiseError(null, XML_XPATH_NULL_POINTER, null, E_USER_WARNING, '', 'XML_XPath_Error', true);  
        }

        if (!$this->pointer->has_child_nodes()) {
            return false;
        }

        $last = $this->pointer->last_child();
        if ($this->skipBlanks) {
            while (true) {
                // we have found a non-blank node
                if (!$last->is_blank_node()) {
                    break;
                }
                // we have arrived at the beginning
                elseif (!$last = $last->previous_sibling()) {
                    $last = false;
                    break;
                }
            }
        }
        if ($last) {
            if ($in_movePointer) {
                $this->pointer = $last;
                return true;
            }
            else {
                return $last;
            }
        }
        else {
            return false;
        }
    }

    // }}}
    // {{{ boolean hasChildNodes()

    /**
     * Returns whether this node has any children.
     *
     * @param  string  $in_xpathQuery (optional) quick xpath query
     * @param  boolean $in_movePointer (optional) move internal pointer
     *
     * @access public
     * @return boolean has child nodes {or XML_XPath_Error exception}
     */
    function hasChildNodes($in_xpathQuery = null, $in_movePointer = false)
    {
        if (!$this->pointer) {
            return PEAR::raiseError(null, XML_XPATH_NULL_POINTER, null, E_USER_WARNING, '', 'XML_XPath_Error', true);  
        }

        if ($hasQuery = !is_null($in_xpathQuery) && XML_XPath::isError($result = $this->_quick_evaluate_init($in_xpathQuery, $in_movePointer))) {
            return $result;
        }

        $hasChildNodes = $this->pointer->has_child_nodes();

        if ($hasQuery && !$in_movePointer) {
            $this->_restore_bookmark();
        }

        return $hasChildNodes;
    }

    // }}}
    // {{{ boolean hasAttributes()

    /**
     * Returns whether this node has any attributes.
     *
     * @param string  $in_xpathQuery (optional) quick xpath query
     * @param boolean $in_movePointer (optional) move internal pointer
     *
     * @access public
     * @return boolean attributes exist {or XML_XPath_Error exception}
     */
    function hasAttributes($in_xpathQuery = null, $in_movePointer = false)
    {
        if (!$this->pointer) {
            return PEAR::raiseError(null, XML_XPATH_NULL_POINTER, null, E_USER_WARNING, '', 'XML_XPath_Error', true);  
        }

        if ($hasQuery = !is_null($in_xpathQuery) && XML_XPath::isError($result = $this->_quick_evaluate_init($in_xpathQuery, $in_movePointer))) {
            return $result;
        }

        $hasAttributes = $this->pointer->has_attributes();

        if ($hasQuery && !$in_movePointer) {
            $this->_restore_bookmark();
        }

        return $hasAttributes;
    }

    // }}}
    // {{{ boolean hasAttribute()

    /**
     * Returns true when an attribute with a given name is specified on this element 
     * false otherwise.
     *
     * @param string  $in_name name of attribute
     * @param string  $in_xpathQuery (optional) quick xpath query
     * @param boolean $in_movePointer (optional) move internal pointer
     *
     * @access public
     * @return boolean existence of attribute {or XML_XPath_Error exception}
     */
    function hasAttribute($in_name, $in_xpathQuery = null, $in_movePointer = false)
    {
        if (!$this->pointer) {
            return PEAR::raiseError(null, XML_XPATH_NULL_POINTER, null, E_USER_WARNING, '', 'XML_XPath_Error', true);  
        }

        if (XML_XPath::isError($result = $this->_quick_evaluate_init($in_xpathQuery, $in_movePointer, array(XML_ELEMENT_NODE)))) {
            return $result;
        }

        $hasAttribute = $this->pointer->has_attribute($in_name) ? true : false;

        if (!is_null($in_xpathQuery) && !$in_movePointer) {
            $this->_restore_bookmark();
        }

        return $hasAttribute;
    }

    // }}}
    // {{{ array   getAttributes()

    /**
     * Return an associative array of attribute names as the keys and attribute values as the
     * values.  This is not a DOM function, but is a convenient addition.
     *
     * @param string  $in_xpathQuery (optional) quick xpath query
     * @param boolean $in_movePointer (optional) move internal pointer
     *
     * @access public
     * @return array associative array of attributes {or XML_XPath_Error exception}
     */
    function getAttributes($in_xpathQuery = null, $in_movePointer = false) 
    {
        if (!$this->pointer) {
            return PEAR::raiseError(null, XML_XPATH_NULL_POINTER, null, E_USER_WARNING, '', 'XML_XPath_Error', true);  
        }

        if ($hasQuery = !is_null($in_xpathQuery) && XML_XPath::isError($result = $this->_quick_evaluate_init($in_xpathQuery, $in_movePointer))) {
            return $result;
        }

        $return = array();
        if (is_array($attributeNodes = $this->pointer->attributes())) {
            foreach($attributeNodes as $attributeNode) {
                $return[$attributeNode->name] = $attributeNode->value;
            }
        }

        if ($hasQuery && !$in_movePointer) {
            $this->_restore_bookmark();
        }

        return $return;
    }
 
    // }}}
    // {{{ string  getAttribute()

    /**
     * Retrieves an attribute value by name from the element node at the current pointer.
     *
     * Grab the attribute value if it exists and return it.  If the attribute does not
     * exist, this function will return the boolean 'false', so be sure to check properly
     * if the value is "" or if the attribute doesn't exist at all.  This function is the
     * only attribute function which allows you to step onto the attribute node.
     *
     * @param string  $in_name Name of the attribute
     * @param string  $in_xpathQuery (optional) quick xpath query
     * @param boolean $in_movePointer (optional) move the internal pointer with quick xpath query
     *
     * @access public
     * @return string value of attribute or false if attribute DNE {or XML_XPath_Error exception}
     */
    function getAttribute($in_name, $in_xpathQuery = null, $in_movePointer = false) 
    {
        if (!$this->pointer) {
            return PEAR::raiseError(null, XML_XPATH_NULL_POINTER, null, E_USER_WARNING, '', 'XML_XPath_Error', true);  
        }

        if (XML_XPath::isError($result = $this->_quick_evaluate_init($in_xpathQuery, $in_movePointer, array(XML_ELEMENT_NODE)))) {
            return $result;
        }

        $result = $this->pointer->get_attribute($in_name);
        // if we found the attribute, move to it if we are moving the pointer
        if ($result && $in_movePointer) {
            $this->pointer = $this->pointer->get_attribute_node($in_name);
        }

        if (!is_null($in_xpathQuery) && !$in_movePointer) {
            $this->_restore_bookmark();
        }

        return $result;
    }

    // }}}
    // {{{ boolean setAttribute()

    /**
     * Adds a new attribute. If an attribute with that name is already present 
     * in the element, its value is changed to be that of the value parameter.
     * Invalid characters are escaped.
     *
     * @param string  $in_name name of the attribute to be set
     * @param string  $in_value new attribute value
     * @param string  $in_xpathQuery (optional) quick xpath query
     * @param boolean $in_movePointer (optional) move internal pointer
     *
     * @access public
     * @return boolean success {or XML_XPath_Error exception}
     */
    function setAttribute($in_name, $in_value, $in_xpathQuery = null, $in_movePointer = false)
    {
        if (!$this->pointer) {
            return PEAR::raiseError(null, XML_XPATH_NULL_POINTER, null, E_USER_WARNING, '', 'XML_XPath_Error', true);  
        }

        if (XML_XPath::isError($result = $this->_quick_evaluate_init($in_xpathQuery, $in_movePointer, array(XML_ELEMENT_NODE)))) {
            return $result;
        }

        $result = $this->pointer->set_attribute($in_name, $in_value);

        if (!is_null($in_xpathQuery) && !$in_movePointer) {
            $this->_restore_bookmark();
        }

        return $result;
    }

    // }}}
    // {{{ void    removeAttribute()

    /**
     * Remove the attribute by name.
     *
     * @param  string  $in_name name of the attribute
     * @param  string  $in_xpathQuery (optional) quick xpath query
     * @param  boolean $in_movePointer (optional) move internal pointer
     *
     * @access public
     * @return boolean success {or XML_XPath_Error exception}
     */
    function removeAttribute($in_name, $in_xpathQuery = null, $in_movePointer = false)
    {
        if (!$this->pointer) {
            return PEAR::raiseError(null, XML_XPATH_NULL_POINTER, null, E_USER_WARNING, '', 'XML_XPath_Error', true);  
        }

        if (XML_XPath::isError($result = $this->_quick_evaluate_init($in_xpathQuery, $in_movePointer, array(XML_ELEMENT_NODE)))) {
            return $result;
        }

        $result = $this->pointer->remove_attribute($in_name);

        if (!is_null($in_xpathQuery) && !$in_movePointer) {
            $this->_restore_bookmark();
        }
        
        return $result;
    }

    // }}}
    // {{{ string  substringData()

    /**
     * Extracts a range of data from the node.  Takes an offset and a count, which are optional
     * and will default to retrieving the whole string.  If an XML_ELEMENT_NODE provided, then
     * it first concats all the adjacent text nodes recusively and works on those.
     * ??? implement wholeText() which concats all text nodes adjacent to a text node ???
     *
     * @param int  $in_offset offset of substring to extract
     * @param int  $in_count length of substring to extract
     * @param string $in_xpathQuery (optional) quick xpath query
     * @param boolean $in_movePointer (optional) move internal pointer
     *
     * @access public
     * @return string substring of the character data {or XML_XPath_Error exception}
     */
    function substringData($in_offset = 0, $in_count = 0, $in_xpathQuery = null, $in_movePointer = false) 
    {
        if (!$this->pointer) {
            return PEAR::raiseError(null, XML_XPATH_NULL_POINTER, null, E_USER_WARNING, '', 'XML_XPath_Error', true);  
        }

        if (XML_XPath::isError($result = $this->_quick_evaluate_init($in_xpathQuery, $in_movePointer, array(XML_TEXT_NODE, XML_ELEMENT_NODE, XML_CDATA_SECTION_NODE, XML_COMMENT_NODE)))) {
            return $result;
        }

        if (!is_int($in_count) || $in_count < 0) {
            $return = PEAR::raiseError(null, XML_XPATH_INDEX_SIZE, null, E_USER_WARNING, "Count: $in_offset", 'XML_XPath_Error', true);
        }
        elseif (!is_int($in_offset) || $in_offset < 0 || $in_offset > strlen($content = $this->pointer->get_content())) {
            $return = PEAR::raiseError(null, XML_XPATH_INDEX_SIZE, null, E_USER_WARNING, "Offset: $in_offset", 'XML_XPath_Error', true);
        }
        else {
            $return = $in_count ? substr($content, $in_offset, $in_count) : 
                                  substr($content, $in_offset);
        }

        if (!is_null($in_xpathQuery) && !$in_movePointer) {
            $this->_restore_bookmark();
        }

        return $return;
    }

    // }}}
    // {{{ void    insertData()

    /**
     * Will insert data at offset for a text node.
     *
     * @param string  $in_content content to be inserted
     * @param int     $in_offset offset to insert data
     * @param string  $in_xpathQuery (optional) quick xpath query
     * @param boolean $in_movePointer (optional) move internal pointer
     *
     * @access public
     * @return void {or XML_XPath_Error exception}
     */
    function insertData($in_content, $in_offset = 0, $in_xpathQuery = null, $in_movePointer = false)
    {
        return $this->_set_content($in_content, $in_xpathQuery, $in_movePointer, false, $in_offset);
    }

    // }}}
    // {{{ void    deleteData()

    /**
     * Will delete data at offset and for count for a text node.
     *
     * @param int     $in_offset (optional) offset to delete data
     * @param int     $in_count (optional) number of characters to delete
     * @param string  $in_xpathQuery (optional) quick xpath query
     * @param boolean $in_movePointer (optional) move internal pointer
     *
     * @access public
     * @return void {or XML_XPath_Error exception}
     */
    function deleteData($in_offset = 0, $in_count = 0, $in_xpathQuery = null, $in_movePointer = false)
    {
        return $this->_set_content(null, $in_xpathQuery, $in_movePointer, true, $in_offset, $in_count);
    }

    // }}}
    // {{{ void    replaceData()

    /**
     * Will replace data at offset and for count with content
     *
     * @param string  $in_content content to insert
     * @param int     $in_offset (optional) offset to replace data
     * @param int     $in_count (optional) number of characters to replace
     * @param string  $in_xpathQuery (optional) quick xpath query
     * @param boolean $in_movePointer (optional) move internal pointer
     *
     * @access public
     * @return void {or XML_XPath_Error exception}
     */
    function replaceData($in_content, $in_offset = 0, $in_count = 0, $in_xpathQuery = null, $in_movePointer = false)
    {
        return $this->_set_content($in_content, $in_xpathQuery, $in_movePointer, true, $in_offset, $in_count);
    }

    // }}}
    // {{{  void    appendData()

    /**
     * Will append data to end of text node.
     *
     * @param string  $in_content content to append
     * @param string  $in_xpathQuery (optional) quick xpath query
     * @param boolean $in_movePointer (optional) move internal pointer
     *
     * @access public
     * @return void {or XML_XPath_Error exception}
     */
    function appendData($in_content, $in_xpathQuery = null, $in_movePointer = false)
    {
        return $this->_set_content($in_content, $in_xpathQuery, $in_movePointer, false, null);
    }

    // }}}
    // {{{ object  replaceChild()

    /**
     * Replaces the old child with the new child.  If the new child is already in the document,
     * it is first removed (not implemented yet).  If the new child is a document fragment, then
     * all of the nodes are inserted in the location of the old child.
     *
     * @param mixed   $in_xmlData document fragment or node
     * @param string  $in_xpathQuery (optional) quick xpath query
     * @param boolean $in_movePointer (optional) move internal pointer
     *
     * @access public
     * @return object pointer to old node {or XML_XPath_Error exception} 
     */
    function replaceChild($in_xmlData, $in_xpathQuery = null, $in_movePointer = false)
    {
        if (!$this->pointer) {
            return PEAR::raiseError(null, XML_XPATH_NULL_POINTER, null, E_USER_WARNING, '', 'XML_XPath_Error', true);  
        }

        if (XML_XPath::isError($result = $this->_quick_evaluate_init($in_xpathQuery, $in_movePointer, array(XML_ELEMENT_NODE, XML_TEXT_NODE, XML_COMMENT_NODE, XML_CDATA_SECTION_NODE, XML_PI_NODE)))) {
            return $result;
        }

        if (XML_XPath::isError($importedNodes = $this->_build_fragment($in_xmlData))) {
            if (!is_null($in_xpathQuery) && !$in_movePointer) {
                $this->_restore_bookmark();
            }

            return $importedNodes;
        }

        $parent = $this->pointer->parent_node();
        $lastNodeIndex = sizeOf($importedNodes) - 1;
        // run through all the new nodes...on the last new node, run replace_child()
        foreach($importedNodes as $index => $importedNode) {
            if ($index == $lastNodeIndex) {
                $oldNode = $parent->replace_child($importedNode, $this->pointer);
            }
            else {
                $parent->insert_before($importedNode, $this->pointer);
            }
        }

        if (!is_null($in_xpathQuery) && !$in_movePointer) {
            $this->_restore_bookmark();
        }

        return new XML_XPath_result(array($oldNode), XPATH_NODESET, null, $this->ctx, $this->xml);
    }

    // }}}
    // {{{ object  appendChild()

    /**
     * Adds the node or document fragment to the end of the list of children.  If the node
     * is already in the tree it is first removed (not sure if this works yet)
     *
     * @param mixed   $in_xmlData string document fragment or node
     * @param string  $in_xpathQuery (optional) quick xpath query
     * @param boolean $in_movePointer move internal pointer
     *
     * @access public
     * @return object pointer to the first of the nodes appended {or XML_XPath_Error exception}
     */
    function appendChild($in_xmlData, $in_xpathQuery = null, $in_movePointer = false) 
    {
        if (!$this->pointer) {
            return PEAR::raiseError(null, XML_XPATH_NULL_POINTER, null, E_USER_WARNING, '', 'XML_XPath_Error', true);  
        }

        if (XML_XPath::isError($result = $this->_quick_evaluate_init($in_xpathQuery, $in_movePointer, array(XML_ELEMENT_NODE, XML_DOCUMENT_NODE)))) {
            return $result;
        }

        // if this is a document node, make sure no root exists
        if ($this->pointer->node_type() == XML_DOCUMENT_NODE && $this->xml->document_element()) {
            if (!is_null($in_xpathQuery) && !$in_movePointer) {
                $this->_restore_bookmark();
            }

            return PEAR::raiseError(null, XML_DUPLICATE_ROOT, null, E_USER_WARNING, null, 'XML_XPath_Error', true);
        }

        if (XML_XPath::isError($importedNodes = $this->_build_fragment($in_xmlData))) {
            return $importedNodes;
        }

        foreach($importedNodes as $index => $importedNode) {
            $node = $this->pointer->append_child($importedNode);
            if ($index == 0) {
                $newNode = $node;
            }
        }

        if (!is_null($in_xpathQuery) && !$in_movePointer) {
            $this->_restore_bookmark();
        }

        return $newNode;
    }

    // }}}
    // {{{ object  insertBefore()

    /**
     * Inserts the node before the current pointer.
     *
     * @param mixed   $in_xmlData either a document fragment xml string or a node
     * @param string  $in_xpathQuery (optional) quick xpath query
     * @param boolean $in_movePointer (optional) move internal pointer
     *
     * @access public
     * @return object pointer to the first of the new inserted nodes
     */
    function insertBefore($in_xmlData, $in_xpathQuery = null, $in_movePointer = false)
    {
        if (!$this->pointer) {
            return PEAR::raiseError(null, XML_XPATH_NULL_POINTER, null, E_USER_WARNING, '', 'XML_XPath_Error', true);  
        }

        if (XML_XPath::isError($result = $this->_quick_evaluate_init($in_xpathQuery, $in_movePointer, array(XML_ELEMENT_NODE)))) {
            return $result;
        }

        // we do some fance stuff here to make this general...make a fake node
        $importedNodes = $this->_build_fragment($in_xmlData);
        if (XML_XPath::isError($importedNodes)) {
            return $importedNodes;
        }

        $parent = $this->pointer->parent_node();
        foreach($importedNodes as $index => $importedNode) {
            $node = $parent->insert_before($importedNode, $this->pointer);
            if ($index == 0) {
                $newNode = $node;
            }
        }

        if (!is_null($in_xpathQuery) && !$in_movePointer) {
            $this->_restore_bookmark();
        }

        return $newNode;
    }

    // }}}
    // {{{ object  removeChild()

    /**
     * Removes the child node at the current pointer and returns it.
     *
     * This function will remove a child from the list of children and will
     * move the pointer to the parent node.
     *
     * @param string  $in_xpathQuery (optional) quick xpath query
     * @param boolean $in_movePointer (optional) move internal pointer
     *
     * @access public
     * @return object cloned node of the removed node, ready to be put in another document
     */
    function removeChild($in_xpathQuery = null, $in_movePointer = false)
    {
        if (!$this->pointer) {
            return PEAR::raiseError(null, XML_XPATH_NULL_POINTER, null, E_USER_WARNING, '', 'XML_XPath_Error', true);  
        }

        if (XML_XPath::isError($result = $this->_quick_evaluate_init($in_xpathQuery, $in_movePointer, array(XML_ELEMENT_NODE, XML_TEXT_NODE, XML_COMMENT_NODE, XML_PI_NODE, XML_CDATA_SECTION_NODE)))) {
            return $result;
        }

        // get the parent
        $parent = $this->pointer->parent_node();
        // remove the child
        $removedNode = $parent->remove_child($this->pointer);
        // set pointer to the parent
        $this->pointer = $parent;

        if (!is_null($in_xpathQuery) && !$in_movePointer) {
            $this->_restore_bookmark();
        }
       
        return new XML_XPath_result(array($removedNode), XPATH_NODESET, null, $this->ctx, $this->xml);
    }

    // }}}
    // {{{ object  cloneNode()

    /**
     * Clones the node and return the node as a result object
     *
     * @param bool    $in_deep (optional) clone node children
     * @param string  $in_xpathQuery (optional) quick xpath query
     * @param boolean $in_movePointer (optional) move internal pointer
     *
     * @access public
     * @return object cloned node of the current node, ready to be put in another document
     */
    function cloneNode($in_deep = false, $in_xpathQuery = null, $in_movePointer = false)
    {
        if (!$this->pointer) {
            return PEAR::raiseError(null, XML_XPATH_NULL_POINTER, null, E_USER_WARNING, '', 'XML_XPath_Error', true);  
        }

        if (XML_XPath::isError($result = $this->_quick_evaluate_init($in_xpathQuery, $in_movePointer))) {
            return $result;
        }
        
        $clonedNode = $this->pointer->clone_node($in_deep);

        if (!is_null($in_xpathQuery) && !$in_movePointer) {
            $this->_restore_bookmark();
        }

        return new XML_XPath_result(array($clonedNode), XPATH_NODESET, null, $this->ctx, $this->xml);
    }
    // }}}
    // {{{ void    replaceChildren()

    /** 
     * Not in the DOM specification, but certainly a convenient function.  Allows you to pass
     * in an xml document fragment which will be parsed into an xml object and merged into the
     * xml document, replacing all the previous children of the node.  It does this by shallow
     * cloning the node, restoring the attributes and then adding the parsed children.
     *
     * @param string  $in_fragment xml fragment which will be merged into tree
     * @param string  $in_xpathQuery (optional) quick xpath query
     * @param boolean $in_movePointer (optional) move internal pointer
     *
     * @access public
     * @return void {or XML_XPath_Error exception}
     */
    function replaceChildren($in_xmlData, $in_xpathQuery = null, $in_movePointer = false)
    {
        if (!$this->pointer) {
            return PEAR::raiseError(null, XML_XPATH_NULL_POINTER, null, E_USER_WARNING, '', 'XML_XPath_Error', true);  
        }

        if (XML_XPath::isError($result = $this->_quick_evaluate_init($in_xpathQuery, $in_movePointer, array(XML_ELEMENT_NODE)))) {
            return $result;
        }

        if (XML_XPath::isError($importedNodes = $this->_build_fragment($in_xmlData))) {
            if (!is_null($in_xpathQuery) && !$in_movePointer) {
                $this->_restore_bookmark();
            }

            return $importedNodes;
        }

        // nix all the children by overwriting node and fixing attributes
        $attributes = $this->pointer->has_attributes() ? $this->pointer->attributes() : array();
        $this->pointer->replace_node($clone = $this->pointer->clone_node());
        $this->pointer = $clone;

        foreach($attributes as $attributeNode) {
           // waiting on set_attribute_node() to work here
           $this->pointer->set_attribute($attributeNode->node_name(), $attributeNode->value());
        }
        
        foreach ($importedNodes as $importedNode) {
            $this->pointer->append_child($importedNode);
        }

        if (!is_null($in_xpathQuery) && !$in_movePointer) {
            $this->_restore_bookmark();
        }
    } 

    // }}}
    // {{{ string  dumpChildren()

    /**
     * Returns all the contents of an element node, regardless of type, as is.
     *
     * @param string  $in_xpathQuery quick xpath query
     * @param boolean $in_movePointer move internal pointer
     *
     * @access public
     * @return string xml string, a concatenation of all the children of the element node
     */
    function dumpChildren($in_xpathQuery = null, $in_movePointer = false, $in_format = true) 
    {
        if (!$this->pointer) {
            return PEAR::raiseError(null, XML_XPATH_NULL_POINTER, null, E_USER_WARNING, '', 'XML_XPath_Error', true);  
        }

        if (XML_XPath::isError($result = $this->_quick_evaluate_init($in_xpathQuery, $in_movePointer, array(XML_ELEMENT_NODE)))) {
            return $result;
        }

        $xmlString = trim($this->xml->dump_node($this->pointer, $in_format));
        $xmlString = substr($xmlString,strpos($xmlString,'>')+1,-(strlen($this->nodeName())+3));
        
        if (!is_null($in_xpathQuery) && !$in_movePointer) {
            $this->_restore_bookmark();
        }

        return $xmlString;
    }

    // }}}
    // {{{ void    toFile()

    /**
     * Exports the xml document to a file.  Only works for the whole document right now.
     *
     * @param  file  $in_file file to export the xml to
     * @param  int   $in_compression (optional) ratio of compression using zlib (0-9)
     *
     * @access public
     * @return void {or XML_XPath_Error exception}
     */
    function toFile($in_file, $in_compression = 0)
    {
        // If the file does not exist, make sure we can write in this directory
        if (!file_exists($in_file)) {
            if (!is_writable(dirname($in_file))) {
                return PEAR::raiseError(null, XML_XPATH_FILE_NOT_WRITABLE, null, E_USER_WARNING, "File: $in_file", 'XML_XPath_Error', true);
            }
        }
        // If the file exists, make sure we can overwrite it
        else {
            if (!is_writable($in_file)) {
                return PEAR::raiseError(null, XML_XPATH_FILE_NOT_WRITABLE, null, E_USER_WARNING, "File: $in_file", 'XML_XPath_Error', true);
            }
        }

        if (!(is_int($in_compression) && $in_compression >= 0 && $in_compression <= 9)) {
            $in_compression = 0;
        }
        $this->xml->dump_mem_file($in_file, $in_compression);
        return true;
    }

    // }}}
    // {{{ string  toString()

    /**
     * Export the xml document to a string, beginning from the pointer.
     *
     * @param string  $in_xpathQuery quick xpath query
     * @param boolean $in_movePointer move internal pointer
     * @param boolean $in_format reformat using xmllint --format
     *
     * @access public
     * @return string xml string, starting at pointer
     */
    function toString($in_xpathQuery = null, $in_movePointer = false, $in_format = true) 
    {
        if (!$this->pointer) {
            return PEAR::raiseError(null, XML_XPATH_NULL_POINTER, null, E_USER_WARNING, '', 'XML_XPath_Error', true);  
        }

        if ($hasQuery = !is_null($in_xpathQuery) && XML_XPath::isError($result = $this->_quick_evaluate_init($in_xpathQuery, $in_movePointer))) {
            return $result;
        }
         
        if ($this->nodeType() == XML_DOCUMENT_NODE) {
            $xmlString = $this->xml->dump_mem($in_format);
        }
        else {
            $xmlString = $this->xml->dump_node($this->pointer, $in_format);
        }
        /*
        if ($in_format) {
            $xmlString = escapeshellarg($xmlString);
            $xmlString = `echo $xmlString | {$this->xmllint} --format - 2>&1`;
        }*/

        if ($hasQuery && !$in_movePointer) {
            $this->_restore_bookmark();
        }
     
        return $xmlString;
    }

    // }}}
    // {{{ object  getPointer()

    /**
     * Get the current pointer in the xml document.
     *
     * @access public
     * @return object current pointer object
     */
    function getPointer() 
    {
        return $this->pointer;
    }

    // }}}
    // {{{ object  setPointer()

    /**
     * Set the pointer in the xml document
     *
     * @param  object  $in_node node to move to in the xml document
     *
     * @access public
     * @return void {or XML_XPath_Error exception}
     */
    function setPointer($in_node) 
    {
        // if this is an error object, just return it
        if (XML_XPath::isError($in_node)) {
            return $in_node;
        }
        elseif (!$this->_is_dom_node($in_node)) {
            return PEAR::raiseError(null, XML_XPATH_NODE_REQUIRED, null, E_USER_WARNING, $in_node, 'XML_XPath_Error', true);
        }
        else {
            // we are okay, set the node and return true
            $this->pointer = $in_node;
            return true;
        }
    }
    
    // }}}
    // {{{ string  getNodePath()

    /**
     * Resolve the xpath location of the current node
     *
     * @param  string $in_xpathQuery (optional)
     * @param  boolean $in_movePointer (optional)
     *
     * @return string xpath location query
     * @access public
     */
    function getNodePath($in_node)
    {
        if (!$in_node) {
            return null;
        }
        elseif (!$this->_is_dom_node($in_node)) {
            return PEAR::raiseError(null, XML_XPATH_NODE_REQUIRED, null, E_USER_WARNING, $in_node, 'XML_XPath_Error', true);
        }

        $buffer = '';
        $cur = $in_node;
        do {
            $name = '';
            $sep = '/';
            $occur = 0;
            if (($type = $cur->node_type()) == XML_DOCUMENT_NODE) {
                if ($buffer[0] == '/') {
                    break;
                }
    
                $next = false;
            }
            else if ($type == XML_ATTRIBUTE_NODE) {
                $sep .= '@';
                $name = $cur->node_name();
                $next = $cur->parent_node();
            }
            else {
                $name = $cur->node_name();
                $next = $cur->parent_node();
    
                // now figure out the index
                $tmp = $cur->previous_sibling();
                while ($tmp != false) {
                    if ($name == $tmp->node_name()) {
                        $occur++; 
                    }
                    $tmp = $tmp->previous_sibling();
                }
    
                $occur++;
    
                if ($type == XML_ELEMENT_NODE) {
                    // this is a hack and only works for some cases
                    // we can have to nodes with the same name but different namespace,
                    // so this should actually go above
                    if (($prefix = $cur->prefix()) != '') { 
                        $name = $prefix . ':' . $name;
                    }
                }
                // fix the names for those nodes where xpath query and dom node name don't match
                elseif ($type == XML_COMMENT_NODE) {
                    $name = 'comment()';
                }
                elseif ($type == XML_PI_NODE) {
                    $name = 'processing-instruction()';
                }
                elseif ($type == XML_TEXT_NODE) {
                    $name = 'text()';
                }
                // anything left here has not been coded yet (cdata is broken)
                else {
                    $name = '';
                    $sep = '';
                    $occur = 0;
                }
            }

            if ($occur == 0) {
                $buffer = $sep . $name . $buffer;
            }
            else {
                $buffer = $sep . $name . '[' . $occur . ']' . $buffer;
            }
    
            $cur = $next;
    
        } while ($cur != false);
    
        return $buffer;
    }

    // }}}
    // {{{ mixed   getOne()

    /**
     * A quick version of the evaluate, where the results are returned immediately. This
     * function is equivalent to xsl:value-of select in every way.
     *
     * @param  string  $in_xpathQuery (optional) quick xpath query
     * @param  boolean $in_movePointer (optional) move internal pointer
     *
     * @access public
     * @return mixed number of nodes or value of scalar result {or XML_XPath_Error exception}
     */
    function getOne($in_xpathQuery, $in_movePointer = false)
    {
        // Execute the xpath query and return the results, then reset the result index
        if (XML_XPath::isError($result = $this->evaluate($in_xpathQuery, $in_movePointer))) {
            return $result;
        }

        return $result->getData();
    }
  
    // }}}
    // {{{ void    evaluate()

    /**
     * Evaluate the xpath expression on the loaded xml document.
     *
     * The xpath query provided is evaluated and either an XML_XPath_result object is
     * returned, or, if the pointer is being moved, it acts like a glorified step function
     * and moves the pointer to the specified node (or first node if it is a set) and returns
     * a boolean success
     *
     * @param  string  $in_xpathQuery xpath query
     * @param  boolean $in_movePointer (optional) move internal pointer
     *
     * @access public
     * @return mixed result object or boolean success (for move pointer)
     * @throws XML_XPath_error XML_XPATH_NOT_LOADED
     */
    function &evaluate($in_xpathQuery, $in_movePointer = false) 
    {
        // Make sure we have loaded an xml document and were able to create an xpath context
        if (strtolower(get_class($this->ctx)) != 'xpathcontext') {
            return PEAR::raiseError(null, XML_XPATH_NOT_LOADED, null, E_USER_ERROR, null, 'XML_XPath_Error', true);
        }

        // enable relative xpath queries (I don't check a valid dom object yet)
        settype($in_xpathQuery, 'array');
        if (isset($in_xpathQuery[1])) {
            $sep = '/';
            // those double slashes cause an anomally
            if (substr($in_xpathQuery[1], 0, 2) == '//') {
                $sep = '';
            }
            
            if ($in_xpathQuery[0] == 'current()' || $in_xpathQuery[0] == '.') {
                $in_xpathQuery[0] = $this->getNodePath($this->pointer);
            }
            elseif ($in_xpathQuery[0] == 'parent::node()' || $in_xpathQuery[0] == '..') {
                if ($this->pointer->node_type() != XML_DOCUMENT_NODE) {
                    $in_xpathQuery[0] = $this->getNodePath($this->pointer->parent_node());
                }
                else {
                    $in_xpathQuery[0] = $this->getNodePath($this->pointer);
                }
            }
            else {
                $in_xpathQuery[0] = $this->getNodePath($in_xpathQuery[0]);
            }

            // handle or statements and construct query
            $parts = explode('|', $in_xpathQuery[1]);
            $xpathQuery = $in_xpathQuery[0] . $sep . implode('|' . $in_xpathQuery[0] . $sep, $parts);
        }
        else {
            $xpathQuery = reset($in_xpathQuery);
        }

        // we don't care if this messes up, we will let the result object handle no results
        $result = @xpath_eval($this->ctx, $xpathQuery);

        // if we are moving the pointer, return boolean success just like the step functions
        if ($in_movePointer) {
            if ($result->type == XPATH_NODESET && !empty($result->nodeset)) {
                $this->pointer = reset($result->nodeset);
                return true;
            }
            else {
                return false;
            }
        }

        return new XML_XPath_result($result->type == XPATH_NODESET ? $result->nodeset : $result->value, $result->type, $xpathQuery, $this->ctx, $this->xml);
    }

    // }}}
    // {{{ _build_fragment()

    /**
     * For functions which take a document fragment I have a general way to import the data
     * into a nodeset and then I return the nodeset.  If the xml data was already a node, I
     * just cast it to a single element array so the return type is consistent.
     *
     * @param mixed  $in_xmlData either document fragment string or dom node
     *
     * @access private
     * @return array nodeset array
     */
    function _build_fragment($in_xmlData)
    {
        if ($this->_is_dom_node($in_xmlData)) {
            $fakeChildren = array($in_xmlData);
        }
        else {
            $fake = @domxml_open_mem('<fake>'.$in_xmlData.'</fake>');
            if (!$fake) {
                return PEAR::raiseError(null, XML_PARSE_ERROR, null, E_USER_WARNING, $in_xmlData, 'XML_XPath_Error', true);
            }
            $fakeRoot = $fake->document_element();
            $fakeChildren = $fakeRoot->has_child_nodes() ? $fakeRoot->child_nodes() : array();
        }
        return $fakeChildren;
    }

    // }}}
    // {{{ _set_content()
    
    /**
     * Generic function to handle manipulation of a data string based on manipulation parameters.
     *
     * @param string  $in_content data to be added
     * @param boolean $in_replace method of manipulation
     * @param int     $in_offset offset of manipulation
     * @param int     $in_count length of manipulation
     *
     * @access private
     * @return object XML_XPath_Error on fail
     */
    function _set_content($in_content, $in_xpathQuery, $in_movePointer, $in_replace, $in_offset = 0, $in_count = 0)
    {
        if (!$this->pointer) {
            return PEAR::raiseError(null, XML_XPATH_NULL_POINTER, null, E_USER_WARNING, '', 'XML_XPath_Error', true);  
        }

        if (XML_XPath::isError($result = $this->_quick_evaluate_init($in_xpathQuery, $in_movePointer, array(XML_ELEMENT_NODE, XML_TEXT_NODE, XML_CDATA_SECTION_NODE, XML_COMMENT_NODE, XML_PI_NODE)))) {
            return $result;
        }

        $data = $this->pointer->get_content();
        // little hack to get appendData to use this function here...special little exception
        $in_offset = is_null($in_offset) ? strlen($data) : $in_offset;
        if (!is_int($in_offset) || $in_offset < 0 || $in_offset > strlen($data)) {
            $return = PEAR::raiseError(null, XML_XPATH_INDEX_SIZE, null, E_USER_WARNING, "Offset: $in_offset", 'XML_XPath_Error', true);
        }
        elseif (!is_int($in_count) || $in_count < 0) {
            $return = PEAR::raiseError(null, XML_XPATH_INDEX_SIZE, null, E_USER_WARNING, "Count: $in_offset", 'XML_XPath_Error', true);
        }
        else {
            if ($in_replace) {
                $data = $in_count ? substr($data, 0, $in_offset) . $in_content . substr($data, $in_offset + $in_count) : substr($data, 0, $in_offset) . $in_content;
            }
            else {
                $data = substr($data, 0, $in_offset) . $in_content . substr($data, $in_offset);
            }

            if ($this->pointer->node_type() == XML_ELEMENT_NODE) {
                $this->replaceChildren($data);
            }
            else {
                $this->pointer->replace_node($this->xml->create_text_node($data));
            }

            $return = null;
        }

        if (!is_null($in_xpathQuery) && !$in_movePointer) {
            $this->_restore_bookmark();
        }

        return $return;
    }

    // }}}
    // {{{ _is_dom_node()

    /**
     * Determines if the provided object is a domnode descendent.
     *
     * @param  object  $in_object object in question
     *
     * @access private
     * @return boolean whether the object is a domnode
     */
    function _is_dom_node($in_object)
    {
        return (is_object($in_object) && is_a_php_class($in_object, 'domnode'));
    }

    // }}}
    // {{{ _restore_bookmark()

    /**
     * Restore the internal pointer after a quick query operation
     *
     * @access private
     * @return void
     */
    function _restore_bookmark()
    {
        $this->pointer = $this->bookmark;
    }

    // }}}
    // {{{ _quick_evaluate_init()

    /**
     * The function will allow an on the quick xpath query to move the internal pointer before
     * invoking the xmldom function.  The requirements are that the xpath query must return
     * an XPATH_NODESET and have at least one node.  If not, an XML_XPath_Error will be returned
     * ** In addition this function does a check on the correct nodeType to run the caller method
     *
     * @param string  $in_xpathQuery optional xpath query to move the internal pointer
     * @param boolean $in_movePointer move the pointer temporarily or permanently
     * @param array   $in_nodeTypes required nodeType list for the caller method
     *
     * @access private
     * @return boolean true on success, XML_XPath_Error on error
     */
    function _quick_evaluate_init($in_xpathQuery = null, $in_movePointer = false, $in_nodeTypes = null) 
    {
        // we don't need to check for null, since false or 0 is not a valid query anyway
        if ($in_xpathQuery) {
            if (!is_object($in_xpathQuery)) {
                // doing the following manually (without evaluate()) is critical for speed
                
                // Make sure we have an xpath context (mildly costly)
                if (strtolower(get_class($this->ctx)) != 'xpathcontext') {
                    return PEAR::raiseError(null, XML_XPATH_NOT_LOADED, null, E_USER_ERROR, null, 'XML_XPath_Error', true);
                }

                // enable relative xpath queries (I don't check a valid dom object yet)
                settype($in_xpathQuery, 'array');
                if (isset($in_xpathQuery[1])) {
                    $sep = '/';
                    // those double slashes cause an anomally
                    if (substr($in_xpathQuery[1], 0, 2) == '//') {
                        $sep = '';
                    }
                    
                    if ($in_xpathQuery[0] == 'current()' || $in_xpathQuery[0] == '.') {
                        $in_xpathQuery[0] = $this->getNodePath($this->pointer);
                    }
                    elseif ($in_xpathQuery[0] == 'parent::node()' || $in_xpathQuery[0] == '..') {
                        if ($this->pointer->node_type() != XML_DOCUMENT_NODE) {
                            $in_xpathQuery[0] = $this->getNodePath($this->pointer->parent_node());
                        }
                        else {
                            $in_xpathQuery[0] = $this->getNodePath($this->pointer);
                        }
                    }
                    else {
                        $in_xpathQuery[0] = $this->getNodePath($in_xpathQuery[0]);
                    }

                    $xpathQuery = $in_xpathQuery[0] . $sep . $in_xpathQuery[1];
                }
                else {
                    $xpathQuery = reset($in_xpathQuery);
                }

                if (!$result = @xpath_eval($this->ctx, $xpathQuery)) {
                    return PEAR::raiseError(null, XML_XPATH_INVALID_QUERY, null, E_USER_WARNING, "XML_XPath query: $xpathQuery", 'XML_XPath_Error', true);
                }
                
                if (empty($result->nodeset) || $result->type != XPATH_NODESET) {
                    return PEAR::raiseError(null, XML_XPATH_INVALID_NODESET, null, E_USER_WARNING, "XML_XPath query: $xpathQuery", 'XML_XPath_Error', true);
                }

                // this takes the first result (too bad if you had more)
                $tmpPointer = reset($result->nodeset);
            }
            // a bit costly, so we put it second
            elseif ($this->_is_dom_node($in_xpathQuery)) {
                $tmpPointer = $in_xpathQuery;
            } 
            else {
                return PEAR::raiseError(null, XML_XPATH_INVALID_QUERY, null, E_USER_WARNING, "XML_XPath query: $in_xpathQuery", 'XML_XPath_Error', true);
            }

            // if we are moving the internal pointer, then do it now
            if ($in_movePointer) {
                $this->pointer = $tmpPointer;
            }
            // set the bookmark if we only want to temporarily move the pointer
            // this area is too critical to call class methods...we have to be nasty
            else {
                $this->bookmark = $this->pointer;
                $this->pointer = $tmpPointer;
            }
        }

        // see if we have a restricted nodeType requirement (negligible time)
        if (is_array($in_nodeTypes) && !in_array($nodeType = $this->pointer->node_type(), $in_nodeTypes)) {
            if (!is_null($in_xpathQuery) && !$in_movePointer) {
                $this->_restore_bookmark();
            }

            return PEAR::raiseError(null, XML_XPATH_INVALID_NODETYPE, null, E_USER_WARNING, "Required type: ".implode(" or ", $in_nodeTypes).", Provided type: ".$nodeType, 'XML_XPath_Error', true);
        }
        else {
            return true;
        }
    }

    // }}}
}
?>
