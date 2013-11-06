<?php
//
// +---------------------------------------------------------------------------+
// | PEAR :: XML :: Transformer :: Driver :: OutputBuffer                      |
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
// $Id: OutputBuffer.php,v 1.13 2004/11/19 07:18:57 sebastian Exp $
//

require_once 'XML/Transformer.php';

 /**
 * Uses PHP's Output Buffering mechanism to catch the
 * output of a script, transforms it, and outputs the
 * result.
 *
 * Example
 *
 * <code>
 * <?php
 * require_once 'XML/Transformer/Driver/OutputBuffer.php';
 * require_once 'XML/Transformer/Namespace.php';
 *
 * class Main extends XML_Transformer_Namespace {
 *     function start_bold($attributes) {
 *         return '<b>';
 *     }
 *
 *     function end_bold($cdata) {
 *         return $cdata . '</b>';
 *     }
 * }
 *
 * $t = new XML_Transformer_Driver_OutputBuffer(
 *   array(
 *     'overloadedNamespaces' => array(
 *       '&MAIN' => new Main
 *     )
 *   )
 * );
 * ?>
 * <bold>text</bold>
 * </code>
 *
 * Output
 *
 * <code>
 * <b>text</b>
 * </code>
 *
 * @author      Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @author      Kristian Köhntopp <kris@koehntopp.de>
 * @copyright   Copyright &copy; 2002-2004 Sebastian Bergmann <sb@sebastian-bergmann.de> and Kristian Köhntopp <kris@koehntopp.de>
 * @license     http://www.php.net/license/3_0.txt The PHP License, Version 3.0
 * @category    XML
 * @package     XML_Transformer
 */
class XML_Transformer_Driver_OutputBuffer extends XML_Transformer {
    // {{{ Members

    /**
    * @var    boolean
    * @access private
    */
    var $_started = FALSE;

    // }}}
    // {{{ function XML_Transformer_Driver_OutputBuffer($parameters = array())

    /**
    * Constructor.
    *
    * @param  array
    * @access public
    */
    function XML_Transformer_Driver_OutputBuffer($parameters = array()) {
        $this->XML_Transformer($parameters);

        if (!empty($this->_callbackRegistry->overloadedNamespaces)) {
            $this->start();
        }
    }

    // }}}
    // {{{ function start()

    /**
    * Starts the output-buffering,
    * and thus the transformation.
    *
    * @access public
    */
    function start() {
        if (!$this->_started) {
            ob_start(
              array(
                $this, 'transform'
              )
            );

            $this->_started = TRUE;

            if ($this->_checkDebug()) {
                $this->sendMessage(
                  'start: ' . serialize($this)
                );
            }
        }
    }

    // }}}
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */
?>
