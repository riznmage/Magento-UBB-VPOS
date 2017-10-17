<?php
/********************************************************************
 *  @(#)Universal/UniversalPlugin.php                               *
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

require_once "UniversalPluginXMLFileParser.php";
require_once "UniversalPluginXMLResponseParser.php";
require_once "SecureResourceManager.php";
require_once "Framework.php";
require_once("XML/Tree.php");

//
// Constants for the XML and Servlet arrays.
//
define(TRAN_TYPE_TRANPORTAL,                "TranPortal");
define(TRAN_TYPE_CARD_MANAGEMENT,           "CardManagement");
define(TRAN_TYPE_MPI_VERIFY_ENROLLMENT,     "MPIVerifyEnrollment");
define(TRAN_TYPE_MPI_PAYER_AUTHENTICATION,  "MPIPayerAuthentication");
define(TRAN_TYPE_PAYMENT_INIT,              "PaymentInit");
define(TRAN_TYPE_PAYMENT_TRAN,              "PaymentTran");


class UniversalPlugin {
	//
	// Public variables.
	//
	var $dataMap = array();
	var $tranType;
	var $responseData = array();
	var $err;

	//
	// SecureResource variables.
	//
	private $terminalAlias;
	private $resourcePath;
	private $tempDir;
	private $version;

	private $protocol = "ssl://";
	private $settings;
	private $timeout;
	private $traceManager;

	// The servlet to send the request.
	private $servlet = "servlet/UniversalXMLServlet";
	//private $servlet = "request.php";

	// Parsed XML file
	private $xmlMap;

	function UniversalPlugin($tracing = false) {
        $this->traceManager = new TraceManager();
        if ($tracing == true) {
            $this->traceManager->setTraceOn();
        } else {
            $this->traceManager->setTraceOff();
        }
	}

 	function set($name, $value) {
        $this->traceManager->trace("set(): $name = $value");
		$this->dataMap[$name] = $value;
	}

	function get($name) {
		return $this->responseData[strtoupper($name)];
	}

	function getResponseFields() {
        return $this->responseData;
    }

	function getErrorText() {
	    return $this->err;
	}

	function setTerminalAlias($alias) {
        $this->traceManager->trace("setTerminalAlias(): $alias");
		$this->terminalAlias = $alias;
	}

	function setResourcePath($path) {
        $this->traceManager->trace("setResourcePath(): $path");
		$this->resourcePath = $path;
	}

	function setTemporaryDirectory($dir) {
        $this->traceManager->trace("setTemporaryDirectory(): $dir");
		$this->tempDir = $dir;
	}

	function setTransactionType($type) {
        $this->traceManager->trace("setTransactionType(): $type");
		$this->tranType = $type;
	}

	function setVersion($ver) {
        $this->traceManager->trace("setVersion(): $ver");
        $this->version = $ver;
    }

    // Override the ssl:// protocol -- NOT RECOMMENDED!!
    function setProtocol($p) {
        $this->traceManager->trace("setProtocol(): $p");
        $this->protocol = $p;
    }

    function setTrace($bool) {
        if ($bool) {
            $this->traceManager->traceOn();
        } else {
            $this->traceManager->traceOff();
        }
    }

	function performTransaction() {
        $this->traceManager->trace("Entered performTransaction()");

        // Check that the required settings are set.
        $this->checkRequired();

        // Build the XML File Name
        $xmlFile = "TRAN_" . $this->tranType . "VER_" . $this->version . ".xml";

	  	// Get the secure resource settings.
	  	$this->traceManager->trace("Opening the SecureResource for $this->terminalAlias.");
	 	if (isset($this->tempDir)) {
			 $srm = new SecureResourceManager($this->terminalAlias, $this->resourcePath, $xmlFile, $this->tempDir);
		} else {
			 $srm = new SecureResourceManager($this->terminalAlias, $this->resourcePath, $xmlFile);
		}
		$this->settings = $srm->getSecureSettings();
		$this->err = $srm->getErrorText();
		if (strlen($this->err) > 0) {
		    return;
		}

		$xmlMapStr = $srm->getXMLMap();
 		$this->traceManager->trace("SecureSettings: " .
 		                           $this->settings->webAddress .
 		                           ":" .
 		                           $this->settings->port .
 		                           "/" .
 		                           $this->settings->context .
 		                           " Terminal ID: " .
 		                           $this->settings->id .
 		                           " Password: " .
 		                           $this->settings->password .
 		                           " PwdHash: " .
 		                           $this->settings->passwordHash);

		// Set the ID and password.
		$this->set('id', $this->settings->id);
		$this->set('password', $this->settings->password);
		$this->set('passwordhash', $this->settings->passwordHash);


	 	// Load the parse the XML file.
		$this->loadAndParseXMLFile($xmlMapStr);
		if (strlen($this->err) > 0) {
		    return;
		}

		// Build the request.
		$requestStr = $this->buildRequest();

		// Send the transaction.
		$responseStr = $this->sendTransaction($requestStr);

		// Parse the response.
		$this->parseResponse($responseStr);
	}

	function getTransactionDefinition() {
	    // Build the XML File Name
        $xmlFile = "TRAN_" . $this->tranType . "VER_" . $this->version . ".xml";

	  	// Get the secure resource settings.
	  	$this->traceManager->trace("Opening the SecureResource for $this->terminalAlias.");
	 	if (isset($this->tempDir)) {
			 $srm = new SecureResourceManager($this->terminalAlias, $this->resourcePath, $xmlFile, $this->tempDir);
		} else {
			 $srm = new SecureResourceManager($this->terminalAlias, $this->resourcePath, $xmlFile);
		}

		$this->settings = $srm->getSecureSettings();
		$xmlMapStr = $srm->getXMLMap();

		// Load the parse the XML file.
		$this->loadAndParseXMLFile($xmlMapStr);

		if (strlen($this->err) > 0) {
		    return "";
		}


		return $this->xmlMap;
	}

	function clearTransactionData() {
        $this->traceManager->trace("Entered clearTransactionData()");

		$this->dataMap = array();
		unset($this->tranType);
		unset($this->xmlMap);
		unset($this->version);
	}

	private function checkRequired() {
        $this->traceManager->trace('Entered checkRequired()');

		if (!isset($this->tranType)) {
            $this->err = 'Error: You need to set a transaction type.';
            $this->traceManager->trace($this->err);
			return;
		}

		if (!isset($this->version)) {
			$this->err = 'Error: You need to set a version.';
			$this->traceManager->trace($this->err);
			return;
		}

		if (!isset($this->terminalAlias)) {
			$this->err = 'Error: You need to set a Terminal Alias.';
			$this->traceManager->trace($this->err);
			return;
		}

	}

	private function loadAndParseXMLFile($xmlMapStr) {
        $this->traceManager->trace('Entered loadAndParseXMLFile()');

		$parser = new UniversalPluginXMLFileParser();
		$parser->parse($xmlMapStr);
		$this->err = $parser->getErrorText();
		if (strlen($this->err) > 0) {
		    return;
		}
		$this->xmlMap = $parser->getTransactionData();
	}

	private function buildRequest() {
		$this->traceManager->trace('Entered buildRequestXML()');
		$tree = new XML_Tree();

		// Add the <transaction name="name"> tag
		$attributes = array();
		$attributes["name"] = $this->tranType;
		$attributes["version"] = $this->version;
		$root = $tree->addRoot("transaction", "", $attributes);

		// Add the request tag.
		$root = $root->addChild("request");

		// Loop through all the request fields in the XML Map.  For each
		// field, add it to the request XML.
		foreach ($this->xmlMap->request->fields as $field) {
            $data = $this->dataMap[$field->id];
            if (!empty($data)) {
                $root->addChild($field->id, htmlspecialchars($data));
            }
		}

		// Get and return the completed request.
		$requestStr = $tree->get();
		$this->traceManager->trace("Request: <textarea cols=80 rows=30>$requestStr</textarea>");
		return $requestStr;
	}

	private function sendTransaction($data, $useragent=false) {
		//http://www.faqts.com/knowledge_base/view.phtml/aid/12039/fid/51
		$this->traceManager->trace('Entered sendTransaction()');

		$context = $this->settings->context;
		$port = $this->settings->port;
		$host = $this->settings->webAddress;
		$method = "POST";

		// If no port was specified in the resource file, default it to 443.
		if (empty($port)) {
			$port = "443";
		}


		if ($timeout <= 0) {
		    $timeout = 10 * 60000;	// 10 seconds
		}

    	// Open a socket connection to the host.
  //--- set transport protocol in dependence of PHP version
		$phpVers = substr(phpversion(), 0, 1);
		if ($phpVers == '5') $this->protocol = "sslv3://"; 
		//---------------------------------------------------------------------------------------------------
		$socket = @fsockopen("$this->protocol$host", $port, $errno, $errstr, $timeout);
    	if (!$socket) {
 			$this->err = "$errstr ($errno) - " . $socket;
 			$this->traceManager->trace("Unable to connect to $this->protocol$host:$port. $this->err");
			return;
		} else {
			$this->traceManager->trace("Connected to $this->protocol$host:$port");
		}

		// Write the headers.
    	fputs($socket, "$method /$context/$this->servlet HTTP/1.1\r\n");
    	fputs($socket, "Host: $host\r\n");
    	fputs($socket, "Content-type: text/xml\r\n");
    	fputs($socket, "Content-length: " . strlen($data) . "\r\n");

	    if ($useragent) {
    	    fputs($socket, "User-Agent: ACI Universal Plugin\r\n");
    	}
	    fputs($socket, "Connection: close\r\n\r\n");

	    if (!empty($tranType)) {
			fputs($socket, "CG-TranType: $tranType\r\n");
		}

		$this->traceManager->trace("Sending: <textarea cols=80 rows=30>$data</textarea>");

    	// Send the XML stream.
	   	fputs($socket, $data);

		// Set the timeout for reading responses
		if ($timeout > 0) {
			stream_set_timeout($socket, $timeout);
		}

		// Read the response.
	    while (!feof($socket)) {
    	    $buf .= @fgets($socket,128);
	    }

    	fclose($socket);
	    return $buf;
	}


	private function parseResponse($responseStr) {
        $this->traceManager->trace('Entered parseResponse()');
        $this->traceManager->trace("Response: <textarea cols=80 rows=30>$responseStr</textarea>");

		// Strip off the headers.
		$pos = strpos($responseStr, "<?");
		$responseStr = substr($responseStr, $pos);

		// Parse the XML.
		$parser = new UniversalPluginXMLResponseParser();
		$parser->parse($responseStr);
		$td = $parser->getTransactionData();

        $idx = -1;
		// Determine whether this was a valid or error response.
		$respType = $td->responses[0]->type;
		foreach($this->xmlMap->responses as $rowNum => $row) {
			if (!strcmp($row->type, $respType)) {
					$idx = $rowNum;
					break;
			}
		}

		// If $idx wasn't set, there wasn't a valid type included
		// on the response.  Processing will stop now.
		if ($idx == -1) {
            $this->err = 'Error: The response index was never set!';
            $this->traceManager->trace($this->err);
			return;
		}

		// There will be only one response in this tranData.
		foreach($this->xmlMap->responses[$idx]->fields as $field) {
		 	$name = strtoupper($field->id);
		 	$data = $td->responses[0]->fields[$name]->content;

			// Set the response array with the data.
			$this->responseData[$name] = $data;
		}
	}

	function setTimeout($timeout) {
		if ($timeout < 0) {
			$timeout = 0;
		}

		$this->timeout = $timeout;
	}

}

?>
