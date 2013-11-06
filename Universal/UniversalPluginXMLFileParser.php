<?php
/********************************************************************
 *  @(#)Universal/UniversalPluginXMLFileParser.php                  *
 *                                                                  *
 *  Copyright (c) 2000 - 2007 by ACI Worldwide Inc.                 *
 *  330 South 108th Avenue, Omaha, Nebraska, 68154, U.S.A.          *
 *  All rights reserved.                                            *
 *                                                                  *
 *  This software is the confidential and proprietary information   *
 *  of ACI Worldwide Inc ("Confidential Information").  You shall   *
 *  not disclose such Confidential Information and shall use it     *
 *  only in accordance with the terms of the license agreement      *
 *  you entered with ACI Worldwide Inc.                             *
 ********************************************************************/

require_once "TransactionData.php";

class UniversalPluginXMLFileParser {
	private $xml_parser = null;
	private $attrs;
	private $tranData;
	private $currObjPtr;
	private $tranDataRequest;
	private $tranDataResponse;
	private $field;
	private $err;

	function UniversalPluginXMLFileParser() {
		$this->tranData = new TransactionData();
		$this->currObjPtr =& $this->tranData;
		$this->xml_parser = xml_parser_create();
	    xml_set_object($this->xml_parser,$this);
	    xml_set_character_data_handler($this->xml_parser, 'dataHandler');
	    xml_set_element_handler($this->xml_parser, "startHandler", "endHandler");
	   // xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, true);
	}

	function getErrorText() {
	    return $this->err;
	}


    function getTransactionData() {
		if (isset($this->tranData)) {
			return $this->tranData;
		}
    }

    function parse($data) {
        if (!xml_parse($this->xml_parser, $data)) {
        	$this->err = (sprintf("XML error: %s at line %d",
            	xml_error_string(xml_get_error_code($this->xml_parser)),
            	xml_get_current_line_number($this->xml_parser)));
            xml_parser_free($this->xml_parser);
        	return false;
        }

       return true;
    }

	function startHandler($parser, $name, $attribs) {
		if (!strcmp($name, "TRANSACTION")) {
			$this->tranData->name = $attribs['NAME'];
			$this->tranData->className = $attribs['CLASS'];
			$this->tranData->method = $attribs['METHOD'];
			$this->tranData->version = $attribs['VERSION'];
		} else if (!strcmp($name, "REQUEST")) {
			$this->tranDataRequest = new TransactionDataRequest();
		 	$this->tranDataRequest->action = $attribs['ACTION'];
		 	$this->currObjPtr->request = $this->tranDataRequest;
		 	$tmp = $this->tranData->request;
		 	$this->currObjPtr =& $this->tranDataRequest;  // Set the pointer to the request object.
		} else if (!strcmp($name, "RESPONSE")) {
			$this->tranDataResponse = new TransactionDataResponse();
		 	$this->tranDataResponse->type = $attribs['TYPE'];
		 	array_push($this->tranData->responses, $this->tranDataResponse);
		 	$this->currObjPtr =& $this->tranDataResponse;  // Set the pointer to the request object.
		} else if (!strcmp($name, "FIELD")) {
		 	$this->field = new TransactionDataField();
		 	$this->field->id = $attribs['ID'];
		 	$this->field->refID = $attribs['REFID'];
		 	$this->field->type = $attribs['TYPE'];
		 	$this->field->required = $attribs['REQUIRED'];
		 	$this->field->testValue = $attribs['TESTVALUE'];
		 	$tmp = get_class($this->currObjPtr);
			array_push($this->currObjPtr->fields, $this->field);
		} else {
			$this->err = "Unknown tag name in startHandler(): $name";
			return;
		}
   }

   function dataHandler($parser, $data){

   }

   function endHandler($parser, $name){

   }

}
?>
