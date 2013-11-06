<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Master file that defined the base structure for extended classes (drivers).
 *
 * This is the file included by the end-developper. The driver class needed is 
 * automatically included.
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
 * @version    CVS: $Id: FastCreate.php,v 1.5 2005/03/31 15:14:55 guillaume Exp $
 * @link       http://pear.php.net/package/XML_FastCreate
 * @see        XML_Tree
 */

require_once 'PEAR.php';

// {{{ constants

/**
 * Errors of XML_FastCreate
 */
define('XML_FASTCREATE_ERROR_NO_FACTORY', 1);
define('XML_FASTCREATE_ERROR_NO_DRIVER', 2);
define('XML_FASTCREATE_ERROR_DTD', 3);

/**
 * Default filename for 'file' option
 */
define('XML_FASTCREATE_FILE', '/tmp/XML_FastCreate.xml');

/**
 * Default program for 'exec' option
 */
define('XML_FASTCREATE_EXEC',
    '/usr/bin/xmllint --valid --noout /tmp/XML_FastCreate.xml 2>&1');

/**
 * DocType : XHTML 1.1
 */
define('XML_FASTCREATE_DOCTYPE_XHTML_1_1', 
    '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" '
    .'"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">');

/**
 * DocType : XHTML 1.0 Strict
 */
define('XML_FASTCREATE_DOCTYPE_XHTML_1_0_STRICT',
    '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" '
    .'"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">');

/**
 * DocType : XHTML 1.0 Transitional
 */
define('XML_FASTCREATE_DOCTYPE_XHTML_1_0_TRANSITIONAL',
    '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" '
    .'"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">');

/**
 * DocType : XHTML 1.0 Frameset
 */
define('XML_FASTCREATE_DOCTYPE_XHTML_1_0_FRAMESET',
    '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" '
    .'"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">');

/**
 * DocType : XHTML 4.01 Strict
 */
define('XML_FASTCREATE_DOCTYPE_HTML_4_01_STRICT',
    '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" '
    .'"http://www.w3.org/TR/html4/strict.dtd">');

/**
 * DocType : XHTML 4.01 Transitional
 */
define('XML_FASTCREATE_DOCTYPE_HTML_4_01_Transitional',
    '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" '
    .'"http://www.w3.org/TR/html4/loose.dtd">');

/**
 * DocType : XHTML 4.01 Frameset
 */
define('XML_FASTCREATE_DOCTYPE_HTML_4_01_Frameset',
    '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" '
    .'"http://www.w3.org/TR/html4/frameset.dtd">');

// }}}



/**
 * Make a special class for overloading, depending of the PHP version.
 * The class XML_FastCreate extend this special class 'XML_FastCreate_Overload'
 */
if (isSet($_GLOBALS['XML_FASTCREATE_NO_OVERLOAD']) 
    && $_GLOBALS['XML_FASTCREATE_NO_OVERLOAD']) {
    if (is_array($_GLOBALS['XML_FASTCREATE_NO_OVERLOAD'])) {
        $class = 'class XML_FastCreate_Overload extends PEAR {';
        foreach ($_GLOBALS['XML_FASTCREATE_NO_OVERLOAD'] as $tag) {
            $class .= <<<TEXT
            function $tag() { 
                \$args = func_get_args();
                array_unshift(\$args, '$tag');
                return call_user_func_array(array(&\$this, 'xml'), \$args);
            }
TEXT;
        }
        $class .= ' }';
        eval($class);
    } else {
        class XML_FastCreate_Overload extends PEAR {}
    }
} else {
    if (phpversion() < 5) {
        $class = <<<TEXT
        class XML_FastCreate_Overload extends PEAR {
            function __call(\$method, \$args, &\$return)
            {
                if (\$method != __CLASS__) {
                    \$return = \$this->_call(\$method, \$args);
                }
                return true;
            }
        }
TEXT;
        eval($class);
        if (function_exists('overload')) {
            overload('XML_FastCreate_Overload');
        }
    } else {
        $class = <<<TEXT
        class XML_FastCreate_Overload extends PEAR {
            function __call(\$method, \$args) 
            {
                if (\$method != __CLASS__) {
                    return \$this->_call(\$method, \$args);
                }
            }
        }
TEXT;
        eval($class);
    }
}


// {{{ XML_FastCreate

/**
 * Master class to used the XML_FastCreate application.
 *
 * The end-developper need to call the factory() method to make an instance :
 *      $x =& XML_FastCreate::factory('Text');
 * Simple example to make a valid XHTML page :
 * <code>
 * <?php
 *  require_once 'XML/FastCreate.php';
 *  $x =& XML_FastCreate::factory('Text');
 *  $x->html(
 *     $x->head(
 *          $x->title("A simple XHTML page")
 *     ),
 *     $x->body(
 *         $x->div(
 *             $x->h1('Example'),
 *             $x->br(),
 *             $x->a(array('href' => 'http://pear.php.net'), 'PEAR WebSite')
 *         )
 *     )
 *  );
 *  // Write output
 *  $x->toXML();
 * ?>
 * </code>
 * KNOWN BUGS :
 * - XML_DTD is an alpha version
 *      - Some DTD couln't correctly interpreted (like XHTML 1.1)
 *      - You can use an external program like XMLLINT for check validation
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
 * @version    CVS: $Id: FastCreate.php,v 1.5 2005/03/31 15:14:55 guillaume Exp $
 * @link       http://pear.php.net/package/XML_FastCreate
 * @see        XML_Tree
 */
class XML_FastCreate extends XML_FastCreate_Overload 
{
    // {{{ properties
    
    /**
    * DTD Filename for check validity of XML
    *
    * @var      string
    * @access   private
    */
    var $_dtd;
    
    /**
    * Enable / disable output indentation
    *
    * @var      boolean
    * @access   private
    */
    var $_indent = false;

    /**
    * Write output to a file
    *
    * @var      string
    * @access   private
    */
    var $_file;
    
    /**
    * Run external command after write output into file
    *
    * @var      string
    * @access   private
    */
    var $_exec;
    
    /*
    * Flag to know if the factory is used
    *
    * @var      boolean
    * @access   private
    */
    var $_factory = false;
    
    /*
    * Name of the driver use
    *
    * @var      string
    * @access   private
    */
    var $_driver;
    
    /*
    * List of tags replacements
    *
    * @var      array
    * @access   private
    */
    var $_translate;
    
    /*
    * List of entities to convert
    *
    * @var      array
    * @access   private
    */
    var $_entities = array('&', '<', '>', '"', "'");
    
    /*
    * List of replacement of entities
    *
    * @var      array
    * @access   private
    */
    var $_replaces = array('&amp;', '&lt;', '&gt;', '&quot;', '&apos;');
        
    /*
    * String representation of the carriage return
    *
    * @var      string
    * @access   public
    */
    var $cr;
    
    /*
    * String representation of a tabulation
    *
    * @var      string
    * @access   public
    */
    var $tab;
    
    // }}}
    // {{{ factory()
    
    /**
     * Factory : Make an instance of XML_FastCreate object
     *
     * @param string $driver    Driver to use ("Text", "XML_Tree"..)
     *
     * @param array $options    Hashtable of options :
     *
     *      'dtd' :         Set the DTD file to check validity
     *                      [required the XML_DTD package]
     *
     *      'indent' :      Enable / disable output indentation
     *
     *      'version' :     Set the XML version (default = '1.0')
     *
     *      'encoding' :    Set the encoding charset (default = 'UTF-8')
     *
     *      'standalone' :  Set the standalone attribute (default = 'no')
     *
     *      'doctype'   :   DocType string, set manually or use :
     *          XML_FASTCREATE_DOCTYPE_XHTML_1_1
     *          XML_FASTCREATE_DOCTYPE_XHTML_1_0_STRICT
     *          XML_FASTCREATE_DOCTYPE_XHTML_1_0_FRAMESET
     *          XML_FASTCREATE_DOCTYPE_XHTML_1_0_TRANSITIONAL
     *          XML_FASTCREATE_DOCTYPE_HTML_4_01_STRICT
     *          XML_FASTCREATE_DOCTYPE_HTML_4_01_FRAMESET
     *          XML_FASTCREATE_DOCTYPE_HTML_4_01_TRANSITIONAL
     *
     *      'quote' :       Auto quote attributes & contents (default = true)
     *
     *      'translate' :   Hashtable of tags to translate to anothers :
     *                      'translate' => array(
     *                          'title' => array('<h1 class="title"><span>', 
     *                                          '</span></h1>'),
     *                          'date'  => array('<span class="date">', 
     *                                          '</span>'),
     *                      )
     *      
     *      'exec' :        Use an external tool to valid the document
     *
     *      'file' :        Write the validation output to a file
     *
     *      'expand' :      Return single tag with the syntax : 
     *                      <tag></tag> rather <tag /> (default = false)
     *                      ( set to true if you write HTML )
     *          
     *      'apos' :        Quote apostrophe to &apos; (default = true) 
     *                      <! WARNING !>
     *                      For valid XML, you must let this option to true.
     *                      If you write XHTML, Microsoft Internet Explorer 
     *                      won't recognize this entitie, so you need to turn
     *                      this option to false.
     *
     *      'singleAttribute' : Accept single attributes (default = false)
     *                      ex: $x->input(array('type'=>'checkbox', 
     *                              checked=>true))
     *                      =>  <input type="checkbox" checked />
     *                      <! WARNING !> 
     *                      This syntax is not valid XML.
     *                      For valid XML, don't use this option, use this :
     *                      ex: $x->input(array('type'=>'checkbox', 
     *                              checked=>'checked'))
     *                      =>  <input type="checkbox" checked=>"checked" />
     * 
     * @return object       An XML_FastCreate_<driver> instance
     * @access public
     * @static
     */
    function &factory($driver, $options = array())
    {
        @include_once "FastCreate/{$driver}.php";
        $class = 'XML_FastCreate_'.$driver;
        if (!class_exists($class)) {
            return PEAR::raiseError("Unable to include the XML/FastCreate/"
                .$driver.".php file.", XML_FASTCREATE_ERROR_NO_DRIVER, 
                PEAR_ERROR_DIE);
        }
        $obj = new $class($options);
        return $obj;
    }
    // }}}
    // {{{ XML_FastCreate()
    
    /**
     * Constructor method. Use the factory() method to make an instance 
     * 
     * @param array $options    Hashtable of options. See factory() for details.
     *
     * @return object           An XML_FastCreate instance
     * @access private
     */
    function XML_FastCreate($options = array())
    {
        if ($this->_factory) {
        
            $this->PEAR();
            $this->_dtd = (isSet($options['dtd']) 
                        ? $options['dtd'] : '');
            $this->_file = (isSet($options['file']) 
                        ? $options['file'] : '');
            $this->_exec = (isSet($options['exec']) 
                        ? $options['exec'] : '');
            if (isSet($options['indent'])) {
                $this->_indent = $options['indent'];
            }
            if (isSet($options['translate'])) {
                $this->_translate = $options['translate'];
            }
            if ($this->_dtd) {
                include_once 'XML/DTD/XmlValidator.php';
            }
            if (!isSet($options['apos'])) {
                $options['apos'] = true;
            }
            if (!$options['apos']) {
                array_pop($this->_entities);
                array_pop($this->_replaces);
            }
            $this->cr  = chr(13).chr(10);
            $this->tab = chr(9);

        } else {
            PEAR::raiseError("Use the factory() method please.",
                XML_FASTCREATE_ERROR_NO_FACTORY, 
                PEAR_ERROR_DIE);
        }
    }
    // }}}
    // {{{ _call()
    
    /**
     * Overloading management
     * 
     * @param string $method    Name of the function overloaded
     * @param array $args       List of arguments of the function overloaded
     * 
     * @return mixed 
     * @access private
     */
    function _call(&$method, &$args) 
    {
        array_unshift($args, $method);
        return call_user_func_array(array(&$this, 'xml'), $args);
    }
    // }}}
    // {{{ toXML()
    
    /**
     * Print the current XML to standard output
     *
     * @return mixed    Return true or a PEAR Error object
     * @access public
     */
    function toXML()
    {
        $xml = $this->getXML();
        if ($this->_indent) {
            $xml = $this->indentXML($xml);
        }
        print $xml;
 
        // Check Validity
        if ($this->_dtd) {
            return $this->isValid($xml);
        }

        // Write output to file
        if ($this->_file) {
            $fp = fopen($this->_file, 'w+');
            fwrite($fp, $xml);
            fclose($fp);
        }
        
        // Run an external program
        if ($this->_exec) {
           $return = shell_exec($this->_exec);
           if ($return) {
                return PEAR::raiseError($return, XML_FASTCREATE_ERROR_DTD);
           }
        }

        return true;
    }
    // }}}
    // {{{ isValid()

    /**
     * Check if the XML respect the DTD.
     * Require the XML_DTD package
     *
     * @param string $xml   The XML text to check
     *
     * @return boolean      Return true if valid
     * @access public
     */
    function isValid(&$xml)
    {
        $validator = new XML_DTD_XmlValidator;
        $tree = new XML_Tree();
        $nodes = $tree->getTreeFromString($xml);
        if (PEAR::isError($nodes)) {
            return $nodes;
        }
        $parser =& new XML_DTD_Parser;
        $validator->dtd = $parser->parse($this->_dtd);
        $validator->_runTree($nodes);
        if ($validator->_errors) {
            $errors = $validator->getMessage();
            return PEAR::raiseError($errors, XML_FASTCREATE_ERROR_DTD);
        }
        return true;
    }
    // }}}
    // {{{ _quoteEntities()

    /**
     * Replace XML special characters by their entities
     *
     * Convert :  &      <     >     "       '
     *      To :  &amp;  &lt;  &gt;  &quot;  &apos;
     * 
     * @param string $content   Content to be quoted
     *
     * @return string           The quoted content
     * @access private
     */
    function _quoteEntities($content)
    {
        return str_replace($this->_entities, $this->_replaces, $content);
    }
    // }}}
    // {{{ indentXML()
 
    /**
     * Indent an XML text 
     *
     * @param string $xml   XML text to indent
     * 
     * @return string       The XML text indented
     * @acess public
     */
    function indentXML($xml)
    {
        require_once "XML/Beautifier.php";
        $fmt = new XML_Beautifier();
        $out =& $fmt->formatString($xml);
        return $out;
    }
    // }}}
    // {{{ xml()
    
    /** 
     *  Make an XML tag.
     *
     *  Accept all forms of parameters.
     *
     * @param string $tag       Name of the tag
     * @param array $args       Optional list for attributes
     * @param array $contents   Optional list of contents (strings or sub tags)
     *
     * @return mixed            See the driver specification
     * @access public
     */
    function xml($tag) 
    {
        $attribs = array();
        $args = func_get_args();
        array_shift($args);
        if ((count($args) > 0) && is_array($args[0])) {
            $attribs = $args[0];
            array_shift($args);
        }
        if ($tag{0} == '_') {
            $tag = substr($tag, 1);
        } else {
        
            if (isSet($this->_translate[$tag])) {
                if (isSet($this->_translate[$tag][1])) {
                    $open  =& $this->_translate[$tag][0];
                    $close =& $this->_translate[$tag][1];
                } elseif (isSet($this->_translate[$tag][0])) {
                    $open  = '<'.$this->_translate[$tag][0].'>';
                    $close = '</'.$this->_translate[$tag][0].'>';
                } else {
                    $open  = '<div class="'.$tag.'">';
                    $close = '</div>';
                }
                return $this->importXML($open.$this->exportXML($args).$close);
            } 
        }
        return $this->makeXML($tag, $attribs, $args);
    }
    // }}}
 

    // -------------------------------------------------------- \\
    // --- Abstract methods to be implemented by the driver --- \\
    // -------------------------------------------------------- \\
 
    // {{{ makeXML()
    
    /**
     * Make an XML Tag 
     *
     * @param string $tag       Name of the tag
     * @param array $attribs    List of attributes
     * @param array $contents   List of contents (strings or sub tags)
     * 
     * @return mixed            See the driver specifications
     * @access public
     */
    function makeXML($tag, $attribs = array(), $contents = array()) 
    {
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
    function noquote($content) 
    {
    }
    // }}}
    // {{{ comment()

    /**
     * Make an XML comment
     *
     * @param mixed $content    Content to comment
     * 
     * @return mixed            See the driver specifications
     * @access public
     */
    function comment($content) 
    {
    }
    // }}}
    // {{{ cdata()

    /**
     * Make a CDATA section <![CDATA[ (...) ]]>
     *
     * @param mixed $content    Content of the section
     * 
     * @return mixed            See the driver specifications
     * @access public
     */
    function cdata($content) 
    {
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
    }
    // }}}
    // {{{ importXML()

    /**
     * Import XML text to driver data
     *
     * @param mixed $xml        The XML text
     * 
     * @return mixed            See the driver specifications
     * @access public
     */
    function importXML($xml) 
    {
    }
    // }}}
    // {{{ exportXML()

    /**
     * Export driver data to XML text
     *
     * @param mixed $xml        The XML data driver 
     * 
     * @return string           The XML text
     * @access public
     */
    function exportXML($xml) 
    {
    }
    // }}}

}
// }}}

?>
