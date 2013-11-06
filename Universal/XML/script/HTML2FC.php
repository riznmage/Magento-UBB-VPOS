#!/usr/bin/php
<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Convertion tool to convert a HTML page to the XML_FastCreate syntax.
 *
 * Syntaxe : 
 *     HTML2FC <source_file.html> [<destination_file.php>]
 * 
 * Or by selecting text with your prefered editor. 
 * With VIM, add this line into your ~/.vimrc :
 *      map ,fc :!/usr/local/bin/HTML2FC.php<CR>
 * Select your HTML text and call the script with ,fc
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
 * @version    CVS: $Id: HTML2FC.php,v 1.3 2005/03/31 14:37:50 guillaume Exp $
 * @link       http://pear.php.net/package/XML_FastCreate
 */

require_once('XML/XML_HTMLSax.php');
error_reporting(E_ALL);

$header = "
require_once 'XML/FastCreate/Text.php';

".'$x'." =& XML_FastCreate_Text(
    array(
        'doctype'   => XML_FASTCREATE_DOCTYPE_XHTML_1_0_STRICT,
        'quote'     => false
    )
);
";

$footer = '
$x->toXML();
';


// Define a customer handler class
class MyHandler {

 var $output = '';
 var $tab = 0;
 var $singleTag = array(
                        'area' => 1,
                        'basefont' => 1,
                        'base' => 1,
                        'br' => 1,
                        'hr' => 1,
                        'img' => 1,
                        'input' => 1,
                        'link' => 1,
                        'meta' => 1,
                    );
    
    // Make indentation
    function addTab() 
    {
        $html = '';
        for ($i=0; $i < $this->tab; $i++) {
            $html .= "\t";
        }
        return "\n".$html;
    }


    // Opening tags
    function openHandler(& $parser, $tagname, $attr) 
    {
        $tag = $tagname;
        $html = '$x->'.$tag.'(';
        $html_attr = '';
        foreach ($attr as $key => $val) {
            $html_attr .= "'$key'=>\"$val\", ";
        }
        if ($html_attr) {
            $html_attr = substr($html_attr, 0, -2);
            $html .= "array($html_attr)";
        }
        if (isSet($this->singleTag[$tagname])) {
            $html .= "),";
        } else {
            if ($html_attr) {
                $html .= ',';
            }
        }
        $this->output .= $this->addTab().$html;

        if (!isSet($this->singleTag[$tagname])) {
            $this->tab++;
        }
    }

    // Closing tags
    function closeHandler(& $parser, $tagname) 
    {
        $html = $join = '';
        if (!isSet($this->singleTag[$tagname])) {
            if (preg_match("/x->$tagname\($/", $this->output)) {
                $html .= "), ";
                $this->tab--;
            } else {
                $this->output = substr($this->output, 0, 
                    strlen($this->output)-1);
                $this->tab--;
                $html .= $this->addTab()."), ";
            }
            if (preg_match("/[\.,]\s*$/", $this->output)) {
                $this->output = preg_replace("/[\.,](\s)*$/", "$1", 
                                    $this->output);
            }
            $this->output .= $html;
        }
    }

    // Text node handler
    function dataHandler(& $parser, $text) 
    {
        $text = preg_replace("/^\s+/", "", $text);
        $text = preg_replace("/\s+$/", "", $text);
        $text = preg_replace("/\r/", "", $text);
        if ($text) {
            $this->output .= $this->addTab().'"'.$text.'". ';
        }
    }

    // XML escape handler (e.g. HTML comments)
    function escapeHandler(& $parser,$data) 
    {
        $this->output .= $this->addTab().'$x->comment("'
            .str_replace('"', '\"', $data).'"),';
    }

    // Processing instruction handler
    function piHandler(& $parser,$target,$data) 
    {
        $this->output .= '<?php'.$data.'?>';
    }

    // Return output
    function getOutput()
    {
        return $this->output;
    }
}

$doc = '';
// Get the content from a file 
if (isSet($_SERVER['argv'][1])) {
    $doc = file_get_contents($_SERVER['argv'][1]);
    if ($doc == FALSE) {
        die("File not found !");
    }
    
// Or get the content by STDIN
} else {
    $stdin = fopen('php://stdin', 'r');
    while (!feof($stdin)) {
        $doc .= fgets($stdin);
    }
}


// Instantiate the handler
$handler=new MyHandler;

// Instantiate the parser
$parser=& new XML_HTMLSax;

// Register the handler with the parser
$parser->set_object($handler);

// Set the callback handlers (MyHandler methods)
$parser->set_element_handler('openHandler','closeHandler');
$parser->set_data_handler('dataHandler');
$parser->set_escape_handler('escapeHandler');
$parser->set_pi_handler('piHandler');

// Parse the document
$parser->parse($doc);
$output = $handler->getOutput();
if (isSet($_SERVER['argv'][1])) {
    $output = substr($output, 0, -2).";\n\n";
    $output = "<?php\n".$header.$output.$footer."\n?>";
}

// Send output to a new file 
if (isSet($_SERVER['argv'][2])) {
    $filename = $_SERVER['argv'][2];
    if (!$handle = fopen($filename, 'w+')) {
        echo "Cannot open file ($filename)\n";
        exit;
    }
    if (fwrite($handle, $output) === FALSE) {
        echo "Cannot write to file ($filename)\n";
        exit;
    }
    fclose($handle);

// Or send output to STDOUT
} else {
    echo $output;
}

?>
