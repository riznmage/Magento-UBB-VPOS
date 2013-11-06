<?php
/**
 * XML_Wddx : WDDX serializer and deserializer (works with or without the wddx extension)
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
 * @package    XML_Wddx
 * @author     Alan Knowles <alan@akbkhome.com>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    $Id: Wddx.php,v 1.8 2005/03/30 02:44:56 alan_k Exp $
 * @link       http://pear.php.net/package/XML_Wddx
 *
 *
 * @abstract
 * serialization is done by   $string = XML_Wddx::serialize($data);
 * deserialization is done by $data   = XML_Wddx::deserialize($string);
 *
 */
 
require_once 'Parser.php';

class XML_Wddx extends XML_Parser {


    /**
    * 
    *
    * serialize a value
    * usage:
    *       echo XML_Wddx::serialize($array);
    * 
    * @param   mixed    value to serialize
    * 
    *
    * @return   string   Serialize data.
    * @access   public
    * @static
    */
  

    function serialize($value) 
    {
        $x = new XML_Wddx;
        return  "<wddxPacket version='1.0'><header/><data>\n". 
            $x->indent(1) . trim($x->_serializeValue($value)) . "\n". 
            $x->indent(-1) . "</data></wddxPacket>\n";
    }
    
    /**
    * 
    *
    * de-serialize a value (uses wddx_deserialize if it is built in..)
    * usage:
    *       echo XML_Wddx::deserialize($some_wddx_data);
    * 
    * @param   mixed    value to serialize
    * 
    *
    * @return   mixed   deserialized data..
    * @access   public
    * @static

    */
    
    function deserialize($data) 
    {
        if (function_exists('wddx_deserialize')) {
            return wddx_deserialize($data);
        }
        $t = &new XML_Wddx;
        $t->XML_Parser();
        
        $t->parseString($data);
        return $t->result['data'];
    }
    
    
    /**
    * The core method.. that serializes data.
    * 
    * @param   mixed  value to serialize
    *
    * @return   string   serialized value.
    * @access   private
    * @see      see also methods.....
    */
  
    
    function _serializeValue($value) 
    {
        switch (gettype($value)) {
            case 'string':
                if ( is_numeric ($value) &&  (intval(0+$value) ==  $value) )   {
                    return "<number>$value</number>";
                }    
                //$this->indent(1);
                return  preg_match('/[^a-z0-9_ ]/i',$value) ? 
                    "\n".$this->indent(0).'<string><![CDATA['.$value."]]></string>\n" : 
                    "<string>$value</string>";
                //$this->indent(-1);
                
            case 'integer':
            case 'float':
            case 'double':
                return "<number>$value</number>";
                
            case 'boolean':
                return  sprintf("<boolean value='%s'/>",$value ? 'true':'false');

            case 'object':
                // sleep - ignored ATM
                $ret = "\n".$this->indent()."<struct>\n".
                    $this->indent(1)."<var name='php_class_name'><string>".get_class($value)."</string></var>\n";
                
                foreach(get_object_vars($value) as $k=>$v) {
                    $ret .= $this->indent(0).sprintf("<var name='%s'>",$k);
                    $this->indent(1);
                    $ret .= $this->_serializeValue($v);
                    $this->indent(-1);
                    $ret .= ($ret{strlen($ret)-1} == "\n") ? $this->indent() : '';
                    $ret .= "</var>\n";
                }
                
                $this->indent(-1);
                return $ret .  $this->indent() . "</struct>\n"; 
                
            case 'array':

                $is_struct = (array_keys($value) !== range(0,sizeof($value)-1));
                $ret = "\n".$this->indent();
                $ret .= $is_struct ? "<struct>\n" : sprintf("<array length='%d'>",count($value)). "\n";
                $this->indent(1);
                foreach($value as $k=>$v) {
                    $ret .= $this->indent(0);
                    $ret .= $is_struct ? sprintf("<var name='%s'>",$k) : '';
                    $this->indent(1);
                    $ret .= $this->_serializeValue($v) ;
                    $this->indent(-1);
                    $ret .= ($ret{strlen($ret)-1} == "\n") ? $this->indent() : '';
                    $ret .= $is_struct ? "</var>\n" : "\n";
                }
                
                $ret .= $this->indent(-1);
                $ret .= $is_struct ? '</struct>' : '</array>';
                return $ret . "\n";
            case 'resource': // BIG KLUDGE!!!!
            case 'NULL':
                return  "<null/>";
               
            default:
                echo "not handled " . gettype($value);
                exit;

        }
        
    }
    
        
    /**
    * Current indent level.
    *
    * @var int level
    * @access private
    */
    var $_indent = 0;
    
    
    
    /**
    * get an indent string
    * 
    * @param   int change (indent increment or decrement)
    * 
    * @return   string spaces 
    * @access   private
    */
  
    function indent($add=0) 
    {
        $this->_indent += $add;
        if ($add < 0) { // should not happen!!
            $add = 0;
        }
        return str_repeat('  ',$this->_indent);
    }
    
    
      
    /**
    * expat start handler.
    * 
    * @return   none
    * @access   private
    * @see      XML_Parser:startHandler
    */
    function startHandler($xp, $element, $attribs) 
    {    
        $ent = array('type'=>strtolower($element));
       // echo "S:";print_r(func_get_args());
      
        switch (strtolower($element)) {
            case 'wddxpacket':
            case 'header':
                break;
            case 'string':
            case 'binary':
                $ent['data'] = '';
                array_push($this->_stack,$ent);
                break;
            
            case 'number':
                $ent['data'] = 0;
                array_push($this->_stack,$ent);
                break;
            case 'boolean':
                $ent['data'] = false;
                array_push($this->_stack,$ent);
                break;
            case 'null':
                $ent['data'] = null;
                array_push($this->_stack,$ent);
                break;
                
                
            case 'char':
                if (isset($attribs['CODE'])) {
                    $e = $this->_stackTop();
                    $e['data'] .= chr(hexdec($attribs['CODE']));
                    $this->_stackTop($e);
                }
                break;
            
            case 'struct':
            case 'array':
                $ent['data'] = array();
                array_push($this->_stack,$ent);
                break;
            case 'var':
                $ent['name'] = @$attribs['NAME'];
                
                array_push($this->_stack,$ent);
                break;
            case 'recordset':
                break; // not handled yet...
        }
        
        //echo "STACK:";print_r($this->stack);
        //echo "S:";print_r(func_get_args());
    }
    /**
    * expat end handler.
    * 
    * @return   none
    * @access   private
    * @see      XML_Parser:startHandler
    */
    function endHandler($xp, $element) 
    {        
        //echo "E:";print_r(func_get_args());
    
        if (!count($this->_stack)) {
            return;
        }
        $parent = null;
        switch (strtolower($element)) {
            case 'packet':
      
            
            case 'char':
            case 'recordset':
                return;
                
                
            case 'string':
            case 'binary':
            case 'number':
            case 'boolean':
            case 'null':
            case 'array':
            case 'struct':
            case 'var':
                

                $ent = array_pop($this->_stack);
                $parent = false;
                $parent = $this->_stackTop();
                if (!$parent) {
                    $this->result = $ent;
                    break;
                }
                
                // if this is a struct + php_class_name is set...
                if (($ent['type'] == 'struct') && isset($ent['data']['php_class_name'])) {
                    $class = $ent['data']['php_class_name'];
                    $obj = new $class;
                    unset($ent['data']['php_class_name']);
                    foreach($ent['data'] as $k=>$v) {
                    
                        $obj->$k = $v;
                    }
                    $ent['data'] = $obj;
                }
                
                
                // add ent to parent...
                
                if ($parent['type'] == 'var') {
                    $parent['data'] = $ent['data'];
                    break;
                }
                
                if ($ent['type'] == 'var') {
                    if ($parent['type'] == 'struct') {
                        if ($ent['name']) {
                            $parent['data'][$ent['name']] = $ent['data'];
                        } else {
                            $parent['data'][] = $ent['data'];
                        }
                        break;
                    }
                }
                if ($parent['type'] == 'array') {
                    $parent['data'][] = $ent['data'];
                    break;
                }
                
                $parent['data'] = $ent['data'];
                
                
                break;
                
        } 
        // put it back .. 
        $this->_stackTop($parent);
    
       // echo "STACK:";print_r($this->stack);
    
    
        //echo "E:";print_r(func_get_args());
    }
    
    /**
    * expat cdata handler.
    * 
    * @return   none
    * @access   private
    * @see      XML_Parser:cdataHandler
    */
    function cdataHandler($xp, $cdata)      
    {    
        //$ent = array('type'=>false);
        if (!count($this->_stack)) {
            return;
        }
    
        $ent = $this->_stackTop();
        
        //var_dump($ent);
        switch($ent['type']) {
            case 'string':
            case 'binary':
                $ent['data'] .= $cdata;
                break;
            case 'number':
                $ent['data'] = $cdata;
                break;
            case 'boolean':
                $ent['data'] = $cdata == 'true' ? true : false;
                break;
                
            case 'datetime': // not really handled...
                $ent['data'] = $cdata;
                break;
            default:
                return;
            
                
                
        }
        $this->_stackTop($ent);
        //echo "C:";print_r(func_get_args());
        //echo "STACK: "; print_r($this->stack);
        //echo "C:";print_r(func_get_args());
    }
    /**
    * expat default handler.
    * 
    * @return   none
    * @access   private
    * @see      XML_Parser::defaultHandler
    */
    function defaultHandler($xp, $cdata) 
    {
        //echo "D:";print_r(func_get_args());
    }
    /**
    * Current indent level.
    *
    * @var array stack
    * @access private
    */
     
    var $_stack = array();
     /**
    * get/set top of stack values..
    * 
    * @param   array optional  (array if it is to be changed..)
    * @return   array|none   (empty parameter = get)
    * @access   private
    */
    function _stackTop($ent = null) 
    {
        
        if ($ent != null) {
            $this->_stack[count($this->_stack)-1] = $ent;
            return;
        }
        if (count($this->_stack)) {
            return  $this->_stack[count($this->_stack)-1];
        
        }
        return;
    }
            
    
    
    
}
/*
// test...
$o = new StdClass;
$o->x = "vvvv";
$ar = array(
    'a' => 1,
    'b' => "TESTING \n 123\n",
    'c' => $o,
    'd' => array('x','y','z')
);
print_r(XML_Wddx::serialize($ar));

echo wddx_serialize_value($ar);

*/