<?php
//
// +---------------------------------------------------------------------------+
// | PEAR :: XML :: Transformer :: DocBook Namespace Handler                   |
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
// $Id: DocBook.php,v 1.28 2004/11/19 07:18:57 sebastian Exp $
//

require_once 'XML/Transformer/Namespace.php';
require_once 'XML/Util.php';

/**
 * DocBook Namespace Handler.
 *
 * This namespace handler provides transformations to render a subset of
 * the popular DocBook/XML markup (http://www.docbook.org/) into HTML.
 *
 * Transformations for the following DocBook tags are implemented:
 *
 *   - <artheader>
 *   - <article>
 *   - <author>
 *   - <book>
 *   - <chapter>
 *   - <emphasis>
 *   - <example>
 *   - <figure>
 *   - <filename>
 *   - <firstname>
 *   - <function>
 *   - <graphic>
 *   - <itemizedlist>
 *   - <listitem>
 *   - <orderedlist>
 *   - <para>
 *   - <programlisting>
 *   - <section>
 *   - <surname>
 *   - <title>
 *   - <ulink>
 *   - <xref>
 *
 * Example
 *
 * <code>
 * <?php
 * require_once 'XML/Transformer/Driver/OutputBuffer.php';
 * $t = new XML_Transformer_Driver_OutputBuffer(
 *   array(
 *     'autoload' => 'DocBook'
 *   )
 * );
 * ?>
 * <article>
 *   <artheader>
 *     <title>An Article</title>
 *
 *     <author>
 *       <firstname>Sebastian</firstname>
 *       <surname>Bergmann</surname>
 *     </author>
 *   </artheader>
 *
 *   <section id="foo">
 *     <title>Section One</title>
 *   </section>
 *
 *   <section id="bar">
 *     <title>Section Two</title>
 *
 *     <para>
 *       <xref linkend="foo" />
 *     </para>
 *   </section>
 * </article>
 * </code>
 *
 * Output
 *
 * <code>
 * <html>
 *   <head>
 *     <title>
 *       Sebastian Bergmann: An Article
 *     </title>
 *   </head>
 *   <body>
 *     <h1 class="title">
 *       Sebastian Bergmann: An Article
 *     </h1>
 *     <div class="section">
 *       <a id="foo"></a>
 *       <h2 class="title">
 *         1. Section One
 *       </h2>
 *     </div>
 *     <div class="section">
 *       <a id="bar"></a>
 *       <h2 class="title">
 *         2. Section Two
 *       </h2>
 *       <p>
 *         <a href="#foo">
 *           1. Section One
 *         </a>
 *       </p>
 *     </div>
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
class XML_Transformer_Namespace_DocBook extends XML_Transformer_Namespace {
    // {{{ Members

    /**
    * @var    string
    * @access public
    */
    var $defaultNamespacePrefix = '&MAIN';

    /**
    * @var    boolean
    * @access public
    */
    var $secondPassRequired = TRUE;

    /**
    * @var    string
    * @access private
    */
    var $_author = '';

    /**
    * @var    array
    * @access private
    */
    var $_context = array();

    /**
    * @var    string
    * @access private
    */
    var $_currentExampleNumber = '';

    /**
    * @var    string
    * @access private
    */
    var $_currentFigureNumber = '';

    /**
    * @var    string
    * @access private
    */
    var $_currentSectionNumber = '';

    /**
    * @var    array
    * @access private
    */
    var $_examples = array();

    /**
    * @var    array
    * @access private
    */
    var $_figures = array();

    /**
    * @var    array
    * @access private
    */
    var $_highlightColors = array(
      'bg'      => '#ffffff',
      'comment' => '#ba8370',
      'default' => '#113d73',
      'html'    => '#000000',
      'keyword' => '#005500',
      'string'  => '#550000'
    );

    /**
    * @var    array
    * @access private
    */
    var $_ids = array();

    /**
    * @var    boolean
    * @access private
    */
    var $_roles = array();

    /**
    * @var    array
    * @access private
    */
    var $_secondPass = FALSE;

    /**
    * @var    array
    * @access private
    */
    var $_sections = array();

    /**
    * @var    string
    * @access private
    */
    var $_title = '';

    /**
    * @var    array
    * @access private
    */
    var $_xref = '';

    // }}}
    // {{{ function XML_Transformer_Namespace_DocBook($parameters = array())

    /**
    * @param  array
    * @access public
    */
    function XML_Transformer_Namespace_DocBook($parameters = array()) {
        if (isset($parameters['highlightColors'])) {
            $this->_highlightColors = $parameters['highlightColors'];
        }

        foreach ($this->_highlightColors as $highlight => $color) {
            ini_set('highlight.' . $highlight, $color);
        }
    }

    // }}}
    // {{{ function start_artheader($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_artheader($attributes) {
        if (!$this->_secondPass) {
            return sprintf(
              '<artheader%s>',
              XML_Util::attributesToString($attributes)
            );
        }
    }

    // }}}
    // {{{ function end_artheader($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_artheader($cdata) {
        if (!$this->_secondPass) {
            $cdata = $cdata . '</artheader>';

            return array(
              $cdata,
              FALSE
            );
        }
    }

    // }}}
    // {{{ function start_article($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_article($attributes) {
        return $this->_startDocument('article', $attributes);
    }

    // }}}
    // {{{ function end_article($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_article($cdata) {
        return $this->_endDocument('article', $cdata);
    }

    // }}}
    // {{{ function start_author($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_author($attributes) {}

    // }}}
    // {{{ function end_author($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_author($cdata) {
        $this->_author = trim(str_replace("\n", '', $cdata));
    }

    // }}}
    // {{{ function start_book($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_book($attributes) {
        return $this->_startDocument('book', $attributes);
    }

    // }}}
    // {{{ function end_book($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_book($cdata) {
        return $this->_endDocument('book', $cdata);
    }

    // }}}
    // {{{ function start_chapter($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_chapter($attributes) {
        $id = $this->_startSection(
          'chapter',
          isset($attributes['id']) ? $attributes['id'] : ''
        );

        return '<div class="chapter">' . $id;
    }

    // }}}
    // {{{ function end_chapter($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_chapter($cdata) {
        $this->_endSection('chapter');

        return $cdata . '</div>';
    }

    // }}}
    // {{{ function start_emphasis($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_emphasis($attributes) {
        $emphasisRole = isset($attributes['role']) ? $attributes['role'] : '';

        switch($emphasisRole) {
            case 'bold':
            case 'strong': {
                $this->_roles['emphasis'] = 'b';
            }
            break;

            default: {
                $this->_roles['emphasis'] = 'i';
            }
        }

        return '<' . $this->_roles['emphasis'] . '>';
    }

    // }}}
    // {{{ function end_emphasis($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_emphasis($cdata) {
        $cdata = sprintf(
          '%s</%s>',
          $cdata,
          $this->_roles['emphasis']
        );

        $this->_roles['emphasis'] = '';

        return $cdata;
    }

    // }}}
    // {{{ function start_example($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_example($attributes) {
        $id = $this->_startSection(
          'example',
          isset($attributes['id']) ? $attributes['id'] : ''
        );

        return '<div class="example">' . $id;
    }

    // }}}
    // {{{ function end_example($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_example($cdata) {
        $this->_endSection('example');

        return $cdata . '</div>';
    }

    // }}}
    // {{{ function start_figure($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_figure($attributes) {
        $id = $this->_startSection(
          'figure',
          isset($attributes['id']) ? $attributes['id'] : ''
        );

        return '<div class="figure">' . $id;
    }

    // }}}
    // {{{ function end_figure($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_figure($cdata) {
        $this->_endSection('figure');

        return $cdata . '</div>';
    }

    // }}}
    // {{{ function start_filename($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_filename($attributes) {
        return '<tt>';
    }

    // }}}
    // {{{ function end_filename($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_filename($cdata) {
        return trim($cdata) . '</tt>';
    }

    // }}}
    // {{{ function start_firstname($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_firstname($attributes) {}

    // }}}
    // {{{ function end_firstname($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_firstname($cdata) {
        return trim($cdata);
    }

    // }}}
    // {{{ function start_function($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_function($attributes) {
        return '<code><b>';
    }

    // }}}
    // {{{ function end_function($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_function($cdata) {
        return array(
          trim($cdata) . '</b></code>',
          FALSE
        );
    }

    // }}}
    // {{{ function start_graphic($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_graphic($attributes) {
        return sprintf(
          '<img alt="%s" border="0" src="%s"%s%s/>',

          isset($attributes['srccredit']) ? $attributes['srccredit']                  : '',
          isset($attributes['fileref'])   ? $attributes['fileref']                    : '',
          isset($attributes['width'])     ? ' width="'  . $attributes['width']  . '"' : '',
          isset($attributes['height'])    ? ' height="' . $attributes['height'] . '"' : ''
        );
    }

    // }}}
    // {{{ function end_graphic($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_graphic($cdata) {
        return $cdata;
    }

    // }}}
    // {{{ function start_itemizedlist($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_itemizedlist($attributes) {
        return '<ul>';
    }

    // }}}
    // {{{ function end_itemizedlist($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_itemizedlist($cdata) {
        return $cdata . '</ul>';
    }

    // }}}
    // {{{ function start_listitem($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_listitem($attributes) {
        return '<li>';
    }

    // }}}
    // {{{ function end_listitem($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_listitem($cdata) {
        return $cdata . '</li>';
    }

    // }}}
    // {{{ function start_orderedlist($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_orderedlist($attributes) {
        return '<ol>';
    }

    // }}}
    // {{{ function end_orderedlist($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_orderedlist($cdata) {
        return $cdata . '</ol>';
    }

    // }}}
    // {{{ function start_para($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_para($attributes) {
        return '<p>';
    }

    // }}}
    // {{{ function end_para($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_para($cdata) {
        return $cdata . '</p>';
    }

    // }}}
    // {{{ function start_programlisting($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_programlisting($attributes) {
        $this->_roles['programlisting'] = isset($attributes['role']) ? $attributes['role'] : '';

        switch ($this->_roles['programlisting']) {
            case 'php': {
                return '';
            }
            break;

            default: {
                return '<code>';
            }
        }
    }

    // }}}
    // {{{ function end_programlisting($cdata)

    /**
    * @param  string
    * @return mixed
    * @access public
    */
    function end_programlisting($cdata) {
        switch ($this->_roles['programlisting']) {
            case 'php': {
                $cdata = array(
                  str_replace(
                    '&nbsp;',
                    ' ',
                    highlight_string($cdata, 1)
                  ),
                  FALSE
                );
            }
            break;

            default: {
                $cdata = array(
                  $cdata . '</code>',
                  FALSE
                );
            }
        }

        $this->_roles['programlisting'] = '';

        return $cdata;
    }

    // }}}
    // {{{ function start_section($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_section($attributes) {
        $id = $this->_startSection(
          'section',
          isset($attributes['id']) ? $attributes['id'] : ''
        );

        return '<div class="section">' . $id;
    }

    // }}}
    // {{{ function end_section($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_section($cdata) {
        $this->_endSection('section');

        return $cdata . '</div>';
    }

    // }}}
    // {{{ function start_surname($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_surname($attributes) {}

    // }}}
    // {{{ function end_surname($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_surname($cdata) {
        return trim($cdata);
    }

    // }}}
    // {{{ function start_title($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_title($attributes) {
        switch ($this->_context[sizeof($this->_context)-1]) {
            case 'chapter':
            case 'section': {
                return '<h2 class="title">' . $this->_currentSectionNumber . '. ';
            }
            break;

            case 'example': {
                return '<h3 class="title">Example ' . $this->_currentExampleNumber;
            }
            break;

            case 'figure': {
                return '<h3 class="title">Figure ' . $this->_currentFigureNumber;
            }
            break;
        }
    }

    // }}}
    // {{{ function end_title($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_title($cdata) {
        $cdata = trim($cdata);

        if (!empty($this->_ids[sizeof($this->_ids)-1])) {
            $this->_xref[$this->_ids[sizeof($this->_ids)-1]] = strip_tags($cdata);
        }

        switch ($this->_context[sizeof($this->_context)-1]) {
            case 'article':
            case 'book': {
                $this->_title = $cdata;
            }
            break;

            case 'chapter':
            case 'section': {
                return $cdata . '</h2>';
            }
            break;

            case 'example':
            case 'figure': {
                return $cdata . '</h3>';
            }
            break;

            default: {
                return $cdata;
            }
        }
    }

    // }}}
    // {{{ function start_ulink($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_ulink($attributes) {
        return '<a href="' . $attributes['url'] . '">';
    }

    // }}}
    // {{{ function end_ulink($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_ulink($cdata) {
        return $cdata . '</a>';
    }

    // }}}
    // {{{ function start_xref($attributes)

    /**
    * @param  array
    * @return string
    * @access public
    */
    function start_xref($attributes) {
        if ($this->_secondPass) {
            return sprintf(
              '<a href="#%s">%s</a>',

              isset($attributes['linkend'])               ? $attributes['linkend']               : '',
              isset($this->_xref[$attributes['linkend']]) ? $this->_xref[$attributes['linkend']] : ''
            );
        } else {
            return sprintf(
              '<xref%s>',
              XML_Util::attributesToString($attributes)
            );
        }
    }

    // }}}
    // {{{ function end_xref($cdata)

    /**
    * @param  string
    * @return string
    * @access public
    */
    function end_xref($cdata) {
        if (!$this->_secondPass) {
            $cdata = $cdata . '</xref>';
        }

        return array(
          $cdata,
          FALSE
        );
    }

    // }}}
    // {{{ function _startDocument($type, $attributes)

    /**
    * @param  string
    * @param  array
    * @return string
    * @access private
    */
    function _startDocument($type, $attributes) {
        if (!$this->_secondPass) {
            $id = $this->_startSection(
              $type,
              isset($attributes['id']) ? $attributes['id'] : ''
            );

            return sprintf(
              '<%s>%s',

              $type,
              $id
            );
        } else {
            return sprintf(
              '<html><head><title>%s: %s</title><body><h1 class="title">%s: %s</h1>',

              $this->_author,
              $this->_title,
              $this->_author,
              $this->_title
            );
        }
    }

    // }}}
    // {{{ function _endDocument($type, $cdata)

    /**
    * @param  string
    * @param  string
    * @return string
    * @access private
    */
    function _endDocument($type, $cdata) {
        if (!$this->_secondPass) {
            $this->_endSection($type);

            $this->_secondPass = TRUE;

            $cdata = sprintf(
              '%s</%s>',

              $cdata,
              $type
            );
        } else {
            $cdata = $cdata . '</body></html>';
        }

        return array(
          $cdata,
          FALSE
        );
    }

    // }}}
    // {{{ function _startSection($type, $id)

    /**
    * @param  string
    * @return string
    * @access private
    */
    function _startSection($type, $id) {
        array_push($this->_context, $type);
        array_push($this->_ids,     $id);

        switch ($type) {
            case 'article':
            case 'book':
            case 'chapter':
            case 'section': {
                $this->_currentSectionNumber = '';

                if (!isset($this->_sections[$type]['open'])) {
                    $this->_sections[$type]['open'] = 1;
                } else {
                    $this->_sections[$type]['open']++;
                }

                if (!isset($this->_sections[$type]['id'][$this->_sections[$type]['open']])) {
                    $this->_sections[$type]['id'][$this->_sections[$type]['open']] = 1;
                } else {
                    $this->_sections[$type]['id'][$this->_sections[$type]['open']]++;
                }

                for ($i = 1; $i <= $this->_sections[$type]['open']; $i++) {
                    if (!empty($this->_currentSectionNumber)) {
                        $this->_currentSectionNumber .= '.';
                    }

                    $this->_currentSectionNumber .= $this->_sections[$type]['id'][$i];
                }
            }
            break;

            case 'example': {
                if (!isset($this->_examples[$this->_currentSectionNumber])) {
                    $this->_examples[$this->_currentSectionNumber] = 1;
                } else {
                    $this->_examples[$this->_currentSectionNumber]++;
                }

                $this->_currentExampleNumber =
                $this->_currentSectionNumber . '.' . $this->_examples[$this->_currentSectionNumber];
            }
            break;

            case 'figure': {
                if (!isset($this->_figures[$this->_currentFigureNumber])) {
                    $this->_figures[$this->_currentSectionNumber] = 1;
                } else {
                    $this->_figures[$this->_currentSectionNumber]++;
                }

                $this->_currentFigureNumber =
                $this->_currentSectionNumber . '.' . $this->_figures[$this->_currentSectionNumber];
            }
            break;
        }

        if (!empty($id)) {
            $id = '<a id="' . $id . '" />';
        }

        return $id;
    }

    // }}}
    // {{{ function _endSection($type)

    /**
    * @param  string
    * @access private
    */
    function _endSection($type) {
        array_pop($this->_context);

        switch ($type) {
            case 'article':
            case 'book':
            case 'chapter':
            case 'section': {
                $this->_sections[$type]['open']--;
            }
            break;
        }
    }

    // }}}
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */
?>
