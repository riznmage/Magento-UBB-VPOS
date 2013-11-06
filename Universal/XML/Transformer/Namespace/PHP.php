<?php
//
// +---------------------------------------------------------------------------+
// | PEAR :: XML :: Transformer :: PHP Namespace Handler                       |
// +---------------------------------------------------------------------------+
// | Copyright (c) 2002-2004 Sebastian Bergmann <sb@sebastian-bergmann.de> and |
// |                         Kristian Köhntopp <kris@koehntopp.de>.            |
// +---------------------------------------------------------------------------+
// | This source file is subject to version 3.00 of the PHP License,           |
// | that is available at http://www.php.net/license/3_0.txt.                  |
// | If you did not receive a copy of the PHP license and are unable to        |
// | obtain it through the world-wide-web, please send a note to               |
// | license@php.net so we can mail you a copy immediately.                    |
// +---------------------------------------------------------------------------+
//
// $Id: PHP.php,v 1.20 2004/11/20 07:35:07 sebastian Exp $
//

require_once 'XML/Transformer/Namespace.php';

/**
 * Handler for the PHP Namespace.
 *
 * Example
 *
 * <code>
 * <?php
 * require_once 'XML/Transformer_OutputBuffer.php';
 * require_once 'XML/Transformer/Namespace/PHP.php';
 *
 * $t = new XML_Transformer_OutputBuffer;
 * $t->overloadNamespace('php', new XML_Transformer_Namespace_PHP);
 * $t->start();
 * ?>
 * <html>
 *   <body>
 *     <dl>
 *       <dd>Current time: <php:expr>time()</php:expr></dd>
 *       <php:setvariable name="foo">bar</php:setvariable>
 *       <dd>foo = <php:getvariable name="foo"/></dd>
 *     </dl>
 *
 *     <php:namespace name="my">
 *       <php:define name="tag">
 *         <h1 align="$align">$content</h1>
 *       </php:define>
 *     </php:namespace>
 *
 *     <my:tag align="center">Some Text</my:tag>
 *   </body>
 * </html>
 * </code>
 *
 * Output
 *
 * <code>
 * <html>
 *   <body>
 *     <dl>
 *       <dd>Current time: 1032158587</dd>
 *       <dd>foo = bar</dd>
 *     </dl>
 *
 *     <h1 align="center">Some Text</h1>
 *   </body>
 * </html>
 * </code>
 *
 * @author      Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @author      Kristian Köhntopp <kris@koehntopp.de>
 * @copyright   Copyright &copy; 2002-2004 Sebastian Bergmann <sb@sebastian-bergmann.de> and Kristian Köhntopp <kris@koehntopp.de>
 * @license     http://www.php.net/license/3_0.txt The PHP License, Version 3.0
 * @category    XML
 * @package     XML_Transformer
 */
class XML_Transformer_Namespace_PHP extends XML_Transformer_Namespace {
    // {{{ Members

    /**
    * @var    boolean
    * @access public
    */
    var $defaultNamespacePrefix = 'php';

    /**
    * @var    string
    * @access private
    */
    var $_defineName;

    /**
    * @var    string
    * @access private
    */
    var $_namespace = 'define';

    /**
    * @var    string
    * @access private
    */
    var $_inNamespace = FALSE;

    /**
    * @var    string
    * @access private
    */
    var $_namespaceClassDefinition = '';

    /**
    * @var    string
    * @access private
    */
    var $_variable = '';

    // }}}
    // {{{ function start_define($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_define($attributes) {
        if ($this->_inNamespace) {
            $this->_defineName = $attributes['name'];
        }
        
        $this->getLock();
        return '';
    }

    // }}}
    // {{{ function end_define($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_define($cdata) {
        if (!$this->_inNamespace)
            return '';

        $this->releaseLock();

        $this->_namespaceClassDefinition .= sprintf('
          var $%s_attributes = array();

          function start_%s($att) {
              $this->%s_attributes = $att;

              return "";
          }

          function end_%s($content) {
              foreach ($this->%s_attributes as $__k => $__v) {
                  $$__k = $__v;
              }
              
              $str = "%s";
              return $str;
          }',

          $this->_defineName,
          $this->_defineName,
          $this->_defineName,
          $this->_defineName,
          $this->_defineName,
          addslashes($cdata)
        );

        return '';
    }

    // }}}
    // {{{ function start_namespace($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_namespace($attributes) {
        $this->_inNamespace = TRUE;
        $this->_namespace   = $attributes['name'];

        $classname = 'PEAR_XML_TRANSFORMER_NAMESPACE_PHP_' . $this->_namespace;

        $this->_namespaceClassDefinition = sprintf(
          'class %s extends XML_Transformer_Namespace {',
          $classname
        );

        return '';
    }

    // }}}
    // {{{ function end_namespace($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_namespace($cdata) {
        $classname = 'PEAR_XML_TRANSFORMER_NAMESPACE_PHP_' . $this->_namespace;

        eval($this->_namespaceClassDefinition . ' };');
        $this->_namespaceClassDefinition = '';

        $this->_transformer->overloadNamespace(
          $this->_namespace,
          new $classname,
          TRUE
        );

        $this->_namespace   = '';
        $this->_inNamespace = FALSE;

        return '';
    }

    // }}}
    // {{{ function start_expr($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_expr($attributes) {}

    // }}}
    // {{{ function end_expr($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_expr($cdata) {
        return eval('return ' . $cdata . ';');
    }

    // }}}
    // {{{ function start_logic($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_logic($attributes) {}

    // }}}
    // {{{ function end_logic($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_logic($cdata) {
        // This does not actually work in PHP 4.2.3,
        // when using XML_Transformer_OutputBuffer.
        // It should, though.
        ob_start();
        eval($cdata);
        $buffer = ob_get_contents();
        ob_end_clean();
        return $buffer;
    }

    // }}}
    // {{{ function start_getparameter($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_getparameter($attributes) {
        return isset($_GET[$attributes['name']]) ? $_GET[$attributes['name']] : '';
    }

    // }}}
    // {{{ function end_getparameter($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_getparameter($cdata) {
        return $cdata;
    }

    // }}}
    // {{{ function start_postparameter($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_postparameter($attributes) {
        return isset($_POST[$attributes['name']]) ? $_POST[$attributes['name']] : '';
    }

    // }}}
    // {{{ function end_postparameter($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_postparameter($cdata) {
        return $cdata;
    }

    // }}}
    // {{{ function start_cookievariable($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_cookievariable($attributes) {
        return isset($_COOKIE[$attributes['name']]) ? $_COOKIE[$attributes['name']] : '';
    }

    // }}}
    // {{{ function end_cookievariable($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_cookievariable($cdata) {
        return $cdata;
    }

    // }}}
    // {{{ function start_servervariable($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_servervariable($attributes) {
        return isset($_SERVER[$attributes['name']]) ? $_SERVER[$attributes['name']] : '';
    }

    // }}}
    // {{{ function end_servervariable($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_servervariable($cdata) {
        return $cdata;
    }

    // }}}
    // {{{ function start_sessionvariable($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_sessionvariable($attributes) {
        return isset($_SESSION[$attributes['name']]) ? $_SESSION[$attributes['name']] : '';
    }

    // }}}
    // {{{ function end_sessionvariable($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_sessionvariable($cdata) {
        return $cdata;
    }

    // }}}
    // {{{ function start_getvariable($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_getvariable($attributes) {
        return isset($GLOBALS[$attributes['name']]) ? $GLOBALS[$attributes['name']] : '';
    }

    // }}}
    // {{{ function end_getvariable($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_getvariable($cdata) {
        return $cdata;
    }

    // }}}
    // {{{ function start_setvariable($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_setvariable($attributes) {
        $this->_variable = isset($attributes['name']) ? $attributes['name'] : '';

        return '';
    }

    // }}}
    // {{{ function end_setvariable($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_setvariable($cdata) {
        if ($this->_variable != '') {
            $GLOBALS[$this->_variable] = $cdata;
            $this->_variable = '';
        }

        return '';
    }

    // }}}
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */
?>
