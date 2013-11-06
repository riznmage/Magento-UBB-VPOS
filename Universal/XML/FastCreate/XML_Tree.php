<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * XML_Tree driver for the XML_FastCreate object.
 *
 * This file contains the driver class 'XML_Tree' for XML_FastCreate.
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   XML
 * @package    XML_FastCreate
 * @author     Guillaume Lecanu <Guillaume@dev.fr>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: XML_Tree.php,v 1.3 2005/03/31 14:37:50 guillaume Exp $
 * @link       http://pear.php.net/package/XML_FastCreate
 * @see        XML_Tree
 */

require_once 'XML/FastCreate.php';
require_once 'XML/Tree.php';

// {{{ XML_FastCreate_XML_Tree

/**
 * XML_Tree driver for the XML_FastCreate object.
 *
 * This driver offer the possibility to manipulate XML_Tree object.
 * ex:  $x =& XML_FastCreate::factory('XML_Tree');
 * KNOWN BUGS :
 * => Some features don't work in XML_Tree output :
 *      - noquote(), comment() and cdata() are not yet possible with XML_Tree
 *      - singleAttribute option is not possible 
 *      - expand option cannot be disable
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   XML
 * @package    XML_FastCreate
 * @author     Guillaume Lecanu <Guillaume@dev.fr>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: XML_Tree.php,v 1.3 2005/03/31 14:37:50 guillaume Exp $
 * @link       http://pear.php.net/package/XML_FastCreate
 * @see        XML_Tree
 */
class XML_FastCreate_XML_Tree extends XML_FastCreate
{
    // {{{ properties
    
    /**
    * Boolean to known if the end-developper has used the factory() method
    *
    * @var      boolean
    * @access   private
    */
    var $_factory = true;
    
    /**
    * Options list to used with the XML_FastCreate() constructor
    *
    * @var      array
    * @access   private
    */
    var $_options = array();
    
    /**
    * XML generated 
    *
    * @var      string
    * @access   public
    */
    var $xml = '';

    // }}}
    // {{{ XML_FastCreate_XML_Tree()

    /** 
     *  Make an instance of the XML_FastCreate_XML_Tree driver.
     *
     *  @param array $list      List of options. See XML_FastCreate:factory()
     *
     *  @return object          An XML_FastCreate_XML_Tree instance
     *  @access public
     */
    function XML_FastCreate_XML_Tree($options = array())
    {
        $this->XML_FastCreate($options);
        $this->_driver = 'XML_Tree';
        $this->_options = $options;
        if (!isSet($options['version'])) {
            $this->_options['version'] = '1.0';
        }
        if (!isSet($options['encoding'])) {
            $this->_options['encoding'] = 'UTF-8';
        }
        if (!isSet($options['standalone'])) {
            $this->_options['standalone'] = 'no';
        }
        if (!isSet($options['doctype'])) {
            $this->_options['doctype'] = '';
        }
        if (!isSet($options['quote'])) {
            $this->_options['quote'] = true;
        }
    }
    // }}}
    // {{{ makeXML()

    /**
     * Make an XML Tag 
     *
     * @param string $tag       Name of the tag
     * @param array $attribs    List of attributes
     * @param array $contents   List of contents (strings or sub tags)
     * 
     * @return object           The XML into an XML_Tree object
     * @access public
     */
    function makeXML($tag, $attribs = array(), $contents = array())
    {
        foreach ($attribs as $attrib => $value) {
            if (!is_null($value)) {
                if ($this->_options['quote']) {
                    $attribs[$attrib] = $this->quote($value);
                }
            }
        }
        $this->xml = new XML_Tree_Node(''.$tag, '', $attribs);
        $content = null;
        foreach ($contents as $c) {
            if (is_string($c)) {
                $content .= $c;
            }
        }
        if (!is_null($content)) {
            if ($this->_options['quote']) {
                $content = $this->quote($content);
            }
            $this->xml->content = $content;
        }
        foreach ($contents as $c) {
            if (is_object($c)) {
                $this->xml->addChild($c);
            }
        }
        return $this->xml;
    }
    // }}}
    // {{{ quote()

    /**
     * Encode a string to be include in XML tags.
     *
     * To use only if the 'quoteContents' is false
     * Convert :  &      <     >     "       '
     *      To :  &amp;  &lt;  &gt;  &quot;  &apos;
     * 
     * @param string $content   Content to be quoted
     *
     * @return string           The quoted content
     * @access public
     */
    function quote($content)
    {
        return $this->_quoteEntities($content);
    }
    // }}}
    // {{{ noquote()

    /**
     * (Don't quote this content.)
     *
     * Not yet possible with XML_Tree
     * 
     * @param string $content   Content to escape quoting
     *
     * @return string           The content not quoted
     * @access public
     */
    function noquote($content) 
    {
        return $content;
    }
    // }}}
    // {{{ comment()
    
    /**
     * (Make an XML comment)
     *
     * Not yet possible with XML_Tree
     *
     * @param mixed $content    Content to comment
     * 
     * @return object           The XML_Tree content commented
     * @access public
     */
    function comment($content)
    {
        return $content;
    }
    // }}}
    // {{{ cdata()

    /**
     * (Make a CDATA section <![CDATA[ (...) ]]>)
     *
     * Not yet possible with XML_Tree
     *
     * @param mixed $content    Content of the section
     * 
     * @return object           The XML_Tree cdata content
     * @access public
     */
    function cdata($content)
    {
        return $content;
    }
    // }}}
    // {{{ getXML()

    /**
     * Return the current XML text 
     *
     * @return string           The current XML text
     * @access public
     */
    function getXML()
    {
        $header = '<?xml'
                .' version="'.$this->_options['version'].'"'
                .' encoding="'.$this->_options['encoding'].'"'
                .' standalone="'.$this->_options['standalone'].'" ?>';
        if ($this->_options['doctype']) {
            $header .= "\n".$this->_options['doctype'];
        }
        return $header.$this->xml->get();
    }
    // }}}
    // {{{ importXML()

    /**
     * Import XML text to driver data
     *
     * @param string $xml       The XML text
     * 
     * @return object           The XML_Tree content
     * @access public
     */
    function importXML($xml) 
    {
        $tree = new XML_Tree();
        $tree->getTreeFromString($xml);
        return $tree->root;
    }
    // }}}
    // {{{ exportXML()
    
    /**
     * Export driver data to XML text
     *
     * @param object $xml       The XML_Tree data (from XML_FastCreate_XML_Tree)
     * 
     * @return string           The XML text output
     * @access public
     */
    function exportXML($data = array()) 
    {
        $xml = '';
        foreach ($data as $node) {
            if (is_string($node)) {
                $xml .= $node;
            } elseif (is_object($node)) {
                $xml .= $node->get();
            }
        }
        return $xml;
    }
    // }}}

}
// }}}

?>
