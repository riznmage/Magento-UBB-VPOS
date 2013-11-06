<?php
// CVS: $Id: example.php,v 1.3 2005/10/12 12:38:13 toggg Exp $
require_once 'XML/CSSML.php';
error_reporting(E_ALL);
$cssml = '<cssml:CSSML xmlns:cssml="http://pear.php.net/cssml/1.0">
  <style browserInclude="not(gecko)" filterInclude="admin">
    <selector>p</selector>
    <declaration property="color">blue</declaration>
  </style>
</cssml:CSSML>';
$cssml = & XML_CSSML::factory('libxslt', $cssml, 'string', array('browser' => 'ns4', 'filter' => 'admin', 'comment' => 'hello there!', 'output' => 'foo.css'));
if (PEAR::isError($cssml)) {
	die($cssml->getMessage().' / '.$cssml->getUserInfo()."\n");
}
//var_dump($cssml);
$OK = $cssml->process();
//var_dump($OK);
?>
