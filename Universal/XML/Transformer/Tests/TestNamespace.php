<?php
//
// +---------------------------------------------------------------------------+
// | PEAR :: XML :: Transformer                                                |
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
// $Id: TestNamespace.php,v 1.1 2004/11/27 12:27:43 sebastian Exp $
//

require_once 'XML/Transformer/Namespace.php';

class TestNamespace extends XML_Transformer_Namespace {
    function start_body($attributes) {
        return '<body>text';
    }

    function end_body($cdata) {
        return $cdata . '</body>';
    }

    function start_bold($attributes) {
        return '<b>';
    }

    function end_bold($cdata) {
        return $cdata . '</b>';
    }

    function start_boldbold($attributes) {
        return '<bold>';
    }

    function end_boldbold($cdata) {
        return $cdata . '</bold>';
    }
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */
?>
