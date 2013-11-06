<?php
/********************************************************************
 *  @(#)Universal/TransactionData.php                               *
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

class TransactionData {
 	var $name;
 	var $className;
 	var $method;
 	var $version;
 	var $action;
 	var $request;
 	var $responses = array();

}

class TransactionDataRequest {
	var $action;
	var $fields = array();
}

class TransactionDataResponse {
	var $type;
	var $fields = array();
}

class TransactionDataField {
	var $id;
	var $refID;
	var $type;
	var $required;
	var $content;
	var $testValue;
}

?>