<?php
//
// +---------------------------------------------------------------------------+
// | PEAR :: XML :: Transformer :: Anchor Namespace Handler                    |
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
// $Id: Anchor.php,v 1.18 2004/11/20 07:35:07 sebastian Exp $
//

require_once 'XML/Transformer/Namespace.php';
require_once 'XML/Util.php';

 /**
 * Handler for the Anchor Namespace.
 *
 * This namespace maintains an anchor database, a database of
 * named links. These links can be referenced using the iref
 * tag within this namespace.
 *
 * This allows for a central storage of links, changing links
 * need only be changed in one locations. Designers can reference
 * the link through the symbolic name.
 *
 * Example:
 *
 * <code>
 * <?php
 * $n = XML_Transformer_Namespace_Anchor;
 * $t->overloadNamespace("anchor", $n);
 *
 * $n->setDatabase(
 *       array(
 *         "pear" => array(
 *           "href"  => "http://pear.php.net",
 *           "title" => "PEAR Homepage"
 *         )
 *       )
 * );
 * ?>
 * <p>The <anchor:iref iref="pear">PEAR Homepage</anchor:iref> is now online.</p>
 * </code>
 *
 * Output:
 *
 * <code>
 * <p>The <a href="http://www.pear.net" title="PEAR Homepage">PEAR
 * Homepage</a> is now online.</p>
 * </code>
 *
 * @author      Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @author      Kristian Köhntopp <kris@koehntopp.de>
 * @copyright   Copyright &copy; 2002-2004 Sebastian Bergmann <sb@sebastian-bergmann.de> and Kristian Köhntopp <kris@koehntopp.de>
 * @license     http://www.php.net/license/3_0.txt The PHP License, Version 3.0
 * @category    XML
 * @package     XML_Transformer
 */
class XML_Transformer_Namespace_Anchor extends XML_Transformer_Namespace {
    // {{{ Members

    /**
    * @var    boolean
    * @access public
    */
    var $defaultNamespacePrefix = 'anchor';

    /**
    * @var    array
    * @access private
    */
    var $_anchorDatabase = array();

    /**
    * @var    array
    * @access private
    */
    var $_irefAttributes = array();

    // {{{ function setDatabase($db)

    /**
    * Install a complete link database array.
    *
    * @param  array
    * @return boolean
    * @access public
    */
    function setDatabase($db) {
        $this->_anchorDatabase = $db;

        return TRUE;
    }

    // }}}
    // {{{ function getDatabase($db)

    /**
    * Return the link database array.
    *
    * @return array
    * @access public
    */
    function getDatabase() {
        return $this->_anchorDatabase;
    }

    // }}}
    // {{{ function addItem($item, $attr)

    /**
    * Add an item $item with the attributes $attr to the link database array.
    *
    * @param  string
    * @param  array
    * @return boolean
    * @access public
    */
    function addItem($item, $attr) {
        $this->_anchorDatabase[$item] = $attr;

        return TRUE;
    }

    // }}}
    // {{{ function dropItem($item)

    /**
    * Drop an item $item drom the link database array.
    *
    * @param  string
    * @return boolean
    * @access public
    */
    function dropItem($item) {
        if (!isset($this->_anchorDatabase[$item]))
            return FALSE;

        unset($this->_anchorDatabase[$item]);

        return TRUE;
    }

    // }}}
    // {{{ function getItem($item)

    /**
    * Get an item $item from the link database array.
    *
    * @param  string
    * @return mixed
    * @access public
    */
    function getItem($item) {
        if (!isset($this->_anchorDatabase[$item])) {
            return FALSE;
        }

        return $this->_anchorDatabase[$item];
    }

    // }}}
    // {{{ function start_iref($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_iref($attributes) {
        $this->_irefAttributes = $attributes;

        return '';
    }

    // }}}
    // {{{ function end_iref($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_iref($cdata) {
        if (!isset($this->_irefAttributes['iref']))
            return '';

        $name = $this->_irefAttributes['iref'];
        if (!isset($this->_anchorDatabase[$name]))
            return sprintf('<span>(undefined reference %s)%s</span>',
                $name,
                $cdata
            );

        return sprintf('<a %s>%s</a>',
            XML_Util::attributesToString($this->_anchorDatabase[$name]),
            $cdata
        );
    }

    // }}}
    // {{{ function start_random($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_random($attributes) {
        return '';
    }

    // }}}
    // {{{ function end_random($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_random($cdata) {
        srand((double)microtime()*1000000);

        $keys = array_keys($this->_anchorDatabase);
        $pos  = rand(0, count($keys)-1);
        $name = $keys[$pos];

        return sprintf('<a %s>%s</a>',
            XML_Util::attributesToString($this->_anchorDatabase[$name]),
            $cdata
        );
    }

    // }}}
    // {{{ function start_link($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_link($attributes) {
        if (!isset($attributes['name']))
            return '';

        $name = $attributes['name'];
        unset($attributes['name']);

        $this->addItem($name, $attributes);
        return '';
    }

    // }}}
    // {{{ function end_link($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_link($cdata) {
        return '';
    }

    // }}}
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */
?>
