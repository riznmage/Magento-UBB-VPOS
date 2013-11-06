<?php
/********************************************************************
 *  @(#)Universal/UniversalPluginXMLResponseParser.php              *
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

class UniversalPluginXMLResponseParser {
	private $xml_parser = null;
	private $attrs;
	private $tranData;
	private $currObjPtr;
	private $currFieldPtr;
	private $tranDataRequest;
	private $tranDataResponse;
	private $field;


	function UniversalPluginXMLResponseParser() {
		$this->tranData = new TransactionData();
		$this->currObjPtr =& $this->tranData;
		$this->xml_parser = xml_parser_create();
	    xml_set_object($this->xml_parser,$this);
	    xml_set_character_data_handler($this->xml_parser, 'dataHandler');
	    xml_set_element_handler($this->xml_parser, "startHandler", "endHandler");
	   // xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, true);
	}

	function parse($data) {
		if (!xml_parse($this->xml_parser, $data, true)) {
        	$this->err = sprintf("XML error: %s at line %d",
            	xml_error_string(xml_get_error_code($this->xml_parser)),
            	xml_get_current_line_number($this->xml_parser));
            xml_parser_free($this->xml_parser);
            return false;
		}
	    return true;
   }

	function startHandler($parser, $name, $attribs) {
		if (!strcmp($name, "TRANSACTION")) {
			// Do nothing
		} else if (!strcmp($name, "RESPONSE")) {
			$this->tranDataResponse = new TransactionDataResponse();
		 	$this->tranDataResponse->type = $attribs['TYPE'];
		 	array_push($this->tranData->responses, $this->tranDataResponse);
		 	$this->currObjPtr =& $this->tranDataResponse;  // Set the pointer to the response object.
		} else {
		 	$this->field = new TransactionDataField();
		 	$this->field->id = $name;
		 	$this->currObjPtr->fields[$name] = $this->field;
			$this->currFieldPtr =& $this->field;
		}
   }

   function dataHandler($parser, $data){
      	$data = trim($data);
    	if(!empty($data) && $data !="\n" && $data != "&") {
			$this->currFieldPtr->content = $data;
		}
   }

   function endHandler($parser, $name){

   }

	function getTransactionData() {
		if (isset($this->tranData)) {
			return $this->tranData;
		}
	}
}
?>
