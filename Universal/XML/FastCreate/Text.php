<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Text driver for the XML_FastCreate object.
 *
 * This file contains the default driver class 'Text' for XML_FastCreate.
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
 * @version    CVS: $Id: Text.php,v 1.3 2005/03/31 14:37:50 guillaume Exp $
 * @link       http://pear.php.net/package/XML_FastCreate
 * @see        XML_Tree
 */

require_once 'XML/FastCreate.php';

// {{{ XML_FastCreate_Text

/**
 * Text driver for the XML_FastCreate object.
 *
 * This is the default driver to use, all XML is a string value.
 * ex:  $x =& XML_FastCreate::factory('Text');
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
 * @version    CVS: $Id: Text.php,v 1.3 2005/03/31 14:37:50 guillaume Exp $
 * @link       http://pear.php.net/package/XML_FastCreate
 * @see        XML_Tree
 */
class XML_FastCreate_Text extends XML_FastCreate
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
    // {{{ XML_FastCreate_Text()
    
    /** 
     *  Make an instance of the XML_FastCreate_Text driver.
     *
     *  @param array $list      List of options. See XML_FastCreate:factory()
     *
     *  @return object          An XML_FastCreate_Text instance
     *  @access public
     */
    function XML_FastCreate_Text($options = array())
    {
        $this->XML_FastCreate($options);
        $this->_driver = 'Text';
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
        if (!isSet($options['expand'])) {
            $this->_options['expand'] = false;
        }
        if (!isSet($options['singleAttribute'])) {
            $this->_options['singleAttribute'] = false;
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
     * @return string           The XML String
     * @access public
     */
    function makeXML($tag, $attribs = array(), $contents = array())
    {
        $attTxt = '';
        foreach ($attribs as $attrib => $value) {
            if (is_bool($value) && $value 
            && $this->_options['singleAttribute']) {
                if ($this->_options['quote']) {
                    $attrib = $this->_quoteEntities($attrib);
                }
                $attTxt .= " $attrib";
            } else {
                if ($this->_options['quote']) {
                    $attrib = $this->_quoteEntities($attrib);
                    $value = $this->_quoteEntities($value);
                }
                $attTxt .= ' '.$attrib.'="'.$value.'"';
            }
        }
        if (count($contents) > 0) {
            $element = '<'.$tag.$attTxt.'>';
            foreach ($contents as $content) {
                if ($this->_options['quote']) {
                    $content = $this->quote($content);
                }
                $element .= $content;
            }
            $element .= "</$tag>";
        } else {
            if ($this->_options['expand']) {
                $element = "<$tag$attTxt></$tag>";
            } else {
                $element = "<$tag$attTxt />";
            }
        }
        $this->xml = $this->cr.$this->_quoted($element);
        return $this->xml;
    }
    // }}}
    // {{{ comment()
    
    /**
     * Make an XML comment
     *
     * @param mixed $content    Content to comment
     * 
     * @return string           The XML content commented
     * @access public
     */
    function comment($content)
    {
        return $this->_quoted('<!-- '.$content.' -->');
    }
    // }}}
    // {{{ cdata()
    
    /**
     * Make a CDATA section <![CDATA[ (...) ]]>
     *
     * @param mixed $content    Content of the section
     * 
     * @return string           The XML cdata content
     * @access public
     */
    function cdata($content)
    {
        return  $this->_quoted('/*<![CDATA[*/'
                .$this->cr.$content
                .$this->cr.'/*]]>*/');
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
        return $header.$this->_unquote($this->xml);
    }
    // }}}
    // {{{ importXML()

    /**
     * Import XML text to driver data
     *
     * @param string $xml       The XML text
     * 
     * @return string           The XML text (correctly quoted)
     * @access public
     */
    function importXML($xml) 
    {
        return $this->_quoted($xml);
    }
    // }}}
    // {{{ exportXML()
    
    /**
     * Export driver data to XML text
     *
     * @param string $xml       The XML data (from XML_FastCreate_Text)
     * 
     * @return string           The XML text output
     * @access public
     */
    function exportXML($data = array()) 
    {
        $xml = '';
        foreach ($data as $str) {
            $xml .= $str;
        }
        return $this->cr.$xml.$this->cr;
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
    function quote($str)
    {
        if (is_string($str)) {
            $len = strlen($str);
            $new = $toQuote = '';
            $waitEnd  = false;
            for ($i=0; $i < $len; $i++) {
                if ($str{$i} == '<') {
                    if (($str{$i+1} == '_') && ($str{$i+2} == '>')) {
                        $new .= $this->_quoteEntities($toQuote);
                        $toQuote = '';
                        $waitEnd = true;
                        $i += 3;
                    }
                }
                if ($waitEnd && ($str{$i} == '<')) {
                    if (($str{$i+1} == '/') && ($str{$i+2} == '_') 
                        && ($str{$i+3} == '>')) {
                        $waitEnd = false;
                        $i += 4;
                        if ($i > $len) { 
                            $i--;
                        }
                    }
                }
                if ($waitEnd) {
                    $new .= $str{$i};
                } else {
                    if ($i < $len) {
                        $toQuote .= $str{$i};
                    }
                }
            }
            $new = '<_>'.$new.$this->_quoteEntities($toQuote).'</_>';
        }
        return $new;
    }
    // }}}
    // {{{ noquote()

    /**
     * Don't quote this content.
     *
     * To use only if the 'quoteContents' is true
     * 
     * @param string $content   Content to escape quoting
     *
     * @return string           The content not quoted
     * @access public
     */
    function noquote($str) 
    {
        return '<_>'.$str.'</_>';
    }
    // }}}
    // {{{ _unquote()

    /**
     *  Remove all "quoted tags"
     *
     *  @param string $str      Content
     *
     *  @return string          Content without "quoted tags"
     *  @access private 
     */
    function _unquote($str) 
    {
        return str_replace(array('<_>', '</_>'), array('', ''), $str);
    }
    // }}}
    // {{{ _quoted()

    /** 
     *  Define this content 'quoted'
     *
     *  @param string $content  Content to declare quoted
     *
     *  @return string          Content quoted
     *  @access private
     */
    function _quoted($content) 
    {
        if ($this->_options['quote']) {
            return '<_>'.$this->_unquote($content).'</_>';
        }
        return $content;
    }
    // }}}

}
// }}}

?>
