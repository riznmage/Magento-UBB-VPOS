<?php
//
// +---------------------------------------------------------------------------+
// | PEAR :: XML :: Transformer :: Widget Namespace Handler                    |
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
// $Id: Widget.php,v 1.11 2004/11/19 07:18:57 sebastian Exp $
//

require_once 'XML/Transformer/Namespace.php';

/**
 * Handler for the Widget Namespace.
 *
 * Implements <widget:obox /> similar to http://docs.roxen.com/roxen/2.2/creator/text/obox.tag.
 * Implements <widget:oboxtitle> as counterpart to <obox><title>..</title></obox> in Roxen.
 *
 * @author      Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @author      Kristian Köhntopp <kris@koehntopp.de>
 * @copyright   Copyright &copy; 2002-2004 Sebastian Bergmann <sb@sebastian-bergmann.de> and Kristian Köhntopp <kris@koehntopp.de>
 * @license     http://www.php.net/license/3_0.txt The PHP License, Version 3.0
 * @category    XML
 * @package     XML_Transformer
 */
class XML_Transformer_Namespace_Widget extends XML_Transformer_Namespace {
    // {{{ Members
    
    /**
    * @var    boolean
    * @access public
    */
    var $defaultNamespacePrefix = 'widget';

    /**
    * @var    array
    * @access private
    */
    var $_oboxAttributes = array();

    /**
    * @var    string
    * @access private
    */
    var $_oboxUnitPngPath = "";

    /**
    * @var    string
    * @access private
    */
    var $_oboxUnitPngURL  = "/cache/unit.png";

    // }}}
    // {{{ function _makeUnitPngPath()

    /**
    * Create the filesystem pathname for the unitPng
    *
    * @return void
    * @access private
    */
    function _makeUnitPngPath() {
      $this->_oboxUnitPngPath = $_SERVER['DOCUMENT_ROOT']
                              . "/"
                              . $this->_oboxUnitPngURL;

      return;
    }

    // }}}
    // {{{ function _unitPng()

    /**
    * Create the transparent unitPng and return its URL
    *
    * @return string
    * @access private
    */
    function _unitpng() {
        if (file_exists($this->_oboxUnitPngPath)) {
            return $this->_oboxUnitPngURL;
        }

        $im    = ImageCreate(1, 1);
        $trans = ImageColorAllocate($im, 128, 128, 128);

        ImageColorTransparent($im, $trans);
        ImageFilledRectangle($im, 0,0,1,1,$trans);

        $this->_makeUnitPngPath();

        ImagePNG($im, $this->_oboxUnitPngPath);
        ImageDestroy($im);

        return $this->_oboxUnitURL;
    }

    // }}}
    // {{{ function _imagePlaceholder($h = FALSE, $w = FALSE)

    /**
    * Create a placeholder image of $h pixel height and $w pixel width
    *
    * @param  integer
    * @param  integer
    * @return string
    * @access private
    */
    function _imagePlaceholder($h = FALSE, $w = FALSE) {
        if ($h === FALSE) {
            $h = isset($this->_oboxAttributes['outlinewidth']) ? $this->_oboxAttributes['outlinewidth'] : 1;
        }

        if ($w === FALSE) {
            $w = $h;
        }

        return sprintf(
          '<img src="%s" alt="" width="%s" height="%s" />',
          $this->_unitpng(),
          $w,
          $h
        );
    }

    // }}}
    // {{{ function _oboxGetAttr($name)

    /**
    * Return value of $name suitable for attribute printing (name='value')
    * or an empty string ('')
    *
    * @param  string
    * @return string
    * @access private
    */
    function _oboxGetAttr($name) {
        if (isset($this->_oboxAttributes[$name])) {
            return sprintf(
              " %s='%s'",
              $name,
              $this->_oboxAttributes[$name]
            );
        } else {
            return '';
        }
    }

    // }}}
    // {{{ function _oboxGetAttrAs($name, $attributes)

    /**
    * Return value of $name suitable as printable attr $attr (attr='valueofname')
    * or an empty string ('')
    *
    * @param  string
    * @param  string
    * @return string
    * @access private
    */
    function _oboxGetAttrAs($name, $attributes) {
        if (isset($this->_oboxAttributes[$name])) {
            return sprintf(
              " %s='%s'",
              $attributes,
              $this->_oboxAttributes[$name]
            );
        } else {
            return '';
        }
    }

    // }}}
    // {{{ function _oboxGetValueWithDefault($name, $def)

    /**
    * Return value of $name as value or $def, if empty.
    *
    * @param  string
    * @param  string
    * @return string
    * @access private
    */
    function _oboxGetValueWithDefault($name, $def) {
        if (isset($this->_oboxAttributes[$name])) {
            return $this->_oboxAttributes[$name];
        } else {
            return $def;
        }
    }

    // }}}
    // {{{ function _titlebox()

    /**
    * Create the obox titlebox. Ugly.
    *
    * @return string
    * @access private
    */
    function _titlebox() {
        if (!isset($this->_oboxAttributes['title'])) {
            return sprintf(
              " <tr>\n  <td colspan='5'%s>%s</td>\n </tr>\n",
              $this->_oboxGetAttrAs("outlinecolor", "bgcolor"),
              $this->_imagePlaceholder()
            );
        }

        $left      = $this->_oboxGetValueWithDefault('left',      20);
        $right     = $this->_oboxGetValueWithDefault('right',     20);
        $leftskip  = $this->_oboxGetValueWithDefault('leftskip',  10);
        $rightskip = $this->_oboxGetValueWithDefault('rightskip', 10);

        if (!isset($this->_oboxAttributes['titlecolor']) &&
             isset($this->_oboxAttributes['bgcolor'])) {
            $this->_oboxAttributes['titlecolor'] = $this->_oboxAttributes['bgcolor'];
        }

        $r .= sprintf(
          " <tr>\n  <td>%s</td>\n  <td>%s</td>\n  <td nowrap='nowrap' rowspan='3'%s%s%s>%s%s%s</td>\n  <td>%s</td>\n  <td>%s</td>\n </tr>\n",
          $this->_imagePlaceholder(1,1),
          $this->_imagePlaceholder(1, $left),
          $this->_oboxGetAttrAs('titlealign', 'align'),
          $this->_oboxGetAttrAs('titlevalign', 'valign'),
          $this->_oboxGetAttrAs('titlecolor', 'bgcolor'),
          $this->_imagePlaceholder(1, $leftskip),
          $this->_oboxAttributes['title'],
          $this->_imagePlaceholder(1, $rightskip),
          $this->_imagePlaceholder(1, $right),
          $this->_imagePlaceholder(1,1)
        );

        $r .= sprintf(
          " <tr%s>\n  <td colspan='2' height='1'%s>%s</td>\n  <td colspan='2' height='1'%s>%s</td>\n </tr>\n",
          $this->_oboxGetAttrAs("bgcolor", "bgcolor"),
          $this->_oboxGetAttrAs("outlinecolor", "bgcolor"),
          $this->_imagePlaceholder($this->_oboxGetValueWithDefault("outlinewidth", 1), 1),
          $this->_oboxGetAttrAs("outlinecolor", "bgcolor"),
          $this->_imagePlaceholder($this->_oboxGetValueWithDefault("outlinewidth", 1), 1)
        );

        $r .= sprintf(
          " <tr%s>\n  <td%s>%s</td>\n  <td>%s</td>\n  <td>%s</td>\n  <td%s>%s</td>\n </tr>\n",
          $this->_oboxGetAttrAs("bgcolor", "bgcolor"),
          $this->_oboxGetAttrAs('outlinecolor', 'bgcolor'),
          $this->_imagePlaceholder(1, $this->_oboxGetValueWithDefault("outlinewidth", 1)),
          $this->_imagePlaceholder(1, 1),
          $this->_imagePlaceholder(1, 1),
          $this->_oboxGetAttrAs('outlinecolor', 'bgcolor'),
          $this->_imagePlaceholder(1, $this->_oboxGetValueWithDefault("outlinewidth", 1))
        );

        return $r;
    }

    // }}}
    // {{{ function _box($cdata)

    /**
    * Create the actual obox.
    *
    * @param  string
    * @return string
    * @access private
    */
    function _box($cdata) {
        /* Outer container */
        $r  = sprintf(
          "<table border='0' cellpadding='0' cellspacing='0'%s%s>\n",
          $this->_oboxGetAttr("align"),
          $this->_oboxGetAttr("width")
        );

        /* Title */
        $r .= $this->_titlebox();

        /* Content container */
        $r .= sprintf(
          " <tr%s>\n",
          $this->_oboxGetAttr("bgcolor")
        );

        $r .= sprintf(
          "  <td%s%s>%s</td>\n  <td colspan='3'>\n",
          $this->_oboxGetAttrAs("outlinewidth", "width"),
          $this->_oboxGetAttrAs("outlinecolor", "bgcolor"),
          $this->_imagePlaceholder(1, $this->_oboxGetValueWithDefault("outlinewidth", 1))
        );

        $r .= sprintf(
          "<table %s%s border='0' cellspacing='0' cellpadding='%s'><tr><td%s%s>%s</td></tr></table>\n  </td>\n",
          $this->_oboxGetAttrAs("contentwidth", "width"),
          $this->_oboxGetAttrAs("contentheight", "height"),
          $this->_oboxGetValueWithDefault("contentpadding", 0),
          $this->_oboxGetAttrAs("contentalign", "align"),
          $this->_oboxGetAttrAs("contentvalign", "valign"),
          $cdata
        );

        $r .= sprintf(
          "  <td%s%s>%s</td>\n </tr>\n",
          $this->_oboxGetAttrAs("outlinewidth", "width"),
          $this->_oboxGetAttrAs("outlinecolor", "bgcolor"),
          $this->_imagePlaceholder(1, $this->_oboxGetValueWithDefault("outlinewidth", 1))
        );

        /* Footer line */
        $r .= sprintf(
          " <tr>\n  <td colspan='5'%s>%s</td>\n </tr>\n</table>\n",
          $this->_oboxGetAttrAs("outlinecolor", "bgcolor"),
          $this->_imagePlaceholder()
        );

        return $r;
    }

    // }}}
    // {{{ function start_obox($attributes)

    /**
    * <obox /> -- This container creates an outlined box.
    *
    * The outer Table is controlled by
    *   align=...
    *   width=...
    *
    * The title is controlled by
    *   title=...
    *   titlealign=...
    *   titlevalign=...
    *   titlecolor=...
    *
    * The outline is controlled by
    *   outlinecolor=...
    *   outlinewidth=...
    *   left=...
    *   leftskip=...
    *   right=...
    *   rightskip=...
    *
    * The inner table cell is controlled by
    *   contentalign=...
    *   contentvalign=...
    *   contentpadding=...
    *   contentwidth=...
    *   contentheight=...
    *   bgcolor=...
    *
    * @param  string
    * @return string
    * @access public
    */
    function start_obox($attributes) {
        $this->_oboxAttributes = $attributes;

        return '';
    }

    // }}}
    // {{{ function end_obox($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_obox($cdata) {
        return $this->_box($cdata);
    }

    // }}}
    // {{{ function start_oboxtitle($attributes)

    /**
    * <oboxtitle /> -- Alternate method to set the obox title
    *
    * align=...
    * valign=...
    *
    * @param  string
    * @return string
    * @access public
    */
    function start_oboxtitle($attributes) {
        if (isset($attributes['align'])) {
            $this->_oboxAttributes['titlealign'] = $attributes['align'];
        }

        if (isset($attributes['valign'])) {
            $this->_oboxAttributes['titlevalign'] = $attributes['valign'];
        }

        if (isset($attributes['bgcolor'])) {
            $this->_oboxAttributes['titlecolor'] = $attributes['bgcolor'];
        }

        return '';
    }

    // }}}
    // {{{ function end_oboxtitle($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_oboxtitle($cdata) {
        $this->_oboxAttributes['title'] = $cdata;

        return '';
    }

    // }}}
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */
?>
