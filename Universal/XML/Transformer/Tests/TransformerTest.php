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
// $Id: TransformerTest.php,v 1.1 2004/11/27 12:27:43 sebastian Exp $
//

require_once 'PHPUnit2/Framework/TestCase.php';

require_once 'XML/Transformer/Tests/TestNamespace.php';
require_once 'XML/Transformer.php';

/**
 * @author      Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @author      Kristian Köhntopp <kris@koehntopp.de>
 * @copyright   Copyright &copy; 2002-2004 Sebastian Bergmann <sb@sebastian-bergmann.de> and Kristian Köhntopp <kris@koehntopp.de>
 * @license     http://www.php.net/license/3_0.txt The PHP License, Version 3.0
 * @category    XML
 * @package     XML_Transformer
 */
class XML_Transformer_Tests_TransformerTest extends PHPUnit2_Framework_TestCase {
    private $t;

    public function  setUp() {
        $this->t = new XML_Transformer;
    }

    public function testNoRecursion() {
        $this->t->overloadNamespace(
          '&MAIN',
          new TestNamespace
        );

        $this->assertEquals(
          '<p><b>text</b></p>',

          $this->t->transform(
            '<p><bold>text</bold></p>'
          )
        );
    }

    public function testRecursion() {
        $this->t->overloadNamespace(
          '&MAIN',
          new TestNamespace
        );

        $this->assertEquals(
          '<p><b>text</b></p>',

          $this->t->transform(
            '<p><boldbold>text</boldbold></p>'
          )
        );
    }

    public function testSelfReplacing() {
        $this->t->overloadNamespace(
          '&MAIN',
          new TestNamespace
        );

        $this->assertEquals(
          '<html><body>text</body></html>',

          $this->t->transform(
            '<html><body/></html>'
          )
        );
    }

    public function testNamespace() {
        $this->t->overloadNamespace(
          'test',
          new TestNamespace
        );

        $this->assertEquals(
          '<p><b>text</b></p>',

          $this->t->transform(
            '<p><test:bold>text</test:bold></p>'
          )
        );
    }

    public function testNamespaceURI() {
        $this->t->overloadNamespace(
          'test',
          new TestNamespace
        );

        $this->assertEquals(
          '<p><b>text</b></p>',

          $this->t->transform(
            '<p><test:bold>text</test:bold></p>'
          )
        );
    }
}

/*
 * vim600:  et sw=2 ts=2 fdm=marker
 * vim<600: et sw=2 ts=2
 */
?>
