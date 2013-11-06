<?php
/********************************************************************
 *  @(#)Universal/SecureResourceManager.php                         *
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

require_once "Framework.php";

class SecureResourceManager {
    private $terminalAlias;
    private $resourcePath;
    private $tempDir;
    private $xmlMapFileName;
    private $xmlMap = "not_found";
    private $xorKey = "ZiVcFA9iEloGYzQFJgktQUFtRwhKJAREBjY2Vy8dLVdWIl5HCywBEhAmMFctCjhAVy5aBh4nRVMOKjAWPQYnXBIsQQJKLwBcSTQsGGkYKVxGbUEGAyxFRQA3LBg8G2hGWjhdAw8wRVMHJ2QbIAggRlwkXQA=";
    private $keyKey = "2M3gjBe2iCDwIoH2";
	private $err;

    function SecureResourceManager($terminalAlias, $resourcePath, $xmlMapFileName, $tempDir = "/tmp") {
        $this->terminalAlias = $terminalAlias;
        $this->resourcePath = $resourcePath;
        $this->tempDir = $tempDir;
        $this->xmlMapFileName = $xmlMapFileName;

        // Decrypt the key
        $this->xorKey = $this->simpleXOR(base64_decode($this->xorKey), $this->keyKey);
    }

	function getResourcePath() {
		return $this->resourcePath;
	}
	
	function getSecureSettings() {
		// Create a temporary file.  If the tempDir doesn't exist, the file will
		// be created in the system temporary directory. (i.e. /tmp on UNIX or C:\WINDOWS\Temp)
		$filename = tempnam($this->tempDir, "cgr");

		$this->createReadableZip($filename);
		if (strlen($this->err) > 0) {
			return null;
		}
		
		$xmlStr = $this->readZip($filename);

		// Close and delete the zip.
		unlink($filename);

		$settings = "";
		if (strlen($xmlStr) > 0) {
			$settings =	$this->parseSettings($xmlStr);
		}
		return $settings;
    }

	function getTerminalAliases() {
	    // Create a temporary file.  If the tempDir doesn't exist, the file will
		// be created in the system temporary directory. (i.e. /tmp on UNIX or C:\WINDOWS\Temp)
		$filename = tempnam($this->tempDir, "cgr");
		$this->createReadableZip($filename);
		if (strlen($this->err) > 0) {
			return;
		}
		
		$filesArray = array();
 		$zip = zip_open($filename);

        // Loop through the zip file.
        $index = 0;
		while ($zip_entry = zip_read($zip)) {
    		// Get the name of the entry.
	     	$name = zip_entry_name($zip_entry);

			// Doesn't start with TRAN_ then it's an alias.
			if (strncmp($name, "TRAN_", 5)) {
				$fileBase = substr($name, 0, strrpos($name, "."));
				$filesArray[$index] = $fileBase;
				$index = $index + 1;
			}

	  		// Close.
	        zip_entry_close($zip_entry);
		}
	    zip_close($zip);


		// Close and delete the zip.
		unlink($filename);

		return $filesArray;
    }

	function getTransactions() {
	    // Create a temporary file.  If the tempDir doesn't exist, the file will
		// be created in the system temporary directory. (i.e. /tmp on UNIX or C:\WINDOWS\Temp)
		$filename = tempnam($this->tempDir, "cgr");
		$this->createReadableZip($filename);
		if (strlen($this->err) > 0) {
			return;
		}

		$filesArray = array();
 		$zip = zip_open($filename);

        // Loop through the zip file.
        $index = 0;
		while ($zip_entry = zip_read($zip)) {
    		// Get the name of the entry.
	     	$name = zip_entry_name($zip_entry);

			// Doesn't start with TRAN_ then it's an alias.
			if (!strncmp($name, "TRAN_", 5)) {
				$fileBase = substr($name, 0, strrpos($name, "."));
				$filesArray[$index] = $fileBase;
				$index = $index + 1;
			}

	  		// Close.
	        zip_entry_close($zip_entry);
		}
	    zip_close($zip);


		// Close and delete the zip.
		unlink($filename);

		return $filesArray;
    }

    function getXMLMap() {
        return $this->xmlMap;
    }
    
	// return tru if $str ends with $sub
	function endsWith( $str, $sub ) {
		return ( substr( $str, strlen( $str ) - strlen( $sub ) ) == $sub );
	}

	function cleanupResourcePath () {
		// Replace all forward slashes with backslashes.
		while (strpos($this->resourcePath, "\\")) {
			$this->resourcePath = str_replace("\\", "/", $this->resourcePath);
		}
		// Replace double backslashes with one backslash
		while (strpos($this->resourcePath, "//")) {
			$this->resourcePath = str_replace("//", "/", $this->resourcePath);
		}
		
		// Now if required, add any missing "/" on the end.
		if ($this->endsWith($this->resourcePath, ".cgn")) {
			$this->resourcePath = $this->resourcePath;
		} else if (!$this->endsWith($this->resourcePath, "/") && !$this->endsWith($this->resourcePath, "\\")) {
			$this->resourcePath = $this->resourcePath . "/" . "resource.cgn";
		} else {
			$this->resourcePath = $this->resourcePath . "resource.cgn";
		}

		return $resourcePath;
	}

	private function createReadableZip($filename) {
		$this->cleanupResourcePath();
		
		$fileToOpen = $this->resourcePath;
		if (!file_exists ( $fileToOpen )) {
			$this->err = "Unable to Open [" . $fileToOpen . "]";
			return;
		}

		// Open and XOR the secure zip file.
		$file = $this->simpleXOR(file_get_contents($fileToOpen), $this->xorKey);

		// Write the contents.
		file_put_contents($filename, $file);
	}

    private function readZip($filename) {
		$alias = $this->terminalAlias . ".xml";
            $xml = "not_found";
		$zip = zip_open($filename);

        // Loop through the zip file.
		while ($zip_entry = zip_read($zip)) {
    		// Get the name of the entry.
	     	$name = zip_entry_name($zip_entry);

	     	// Is this the entry we are looking for?
         	if (!strcmp($name, $alias)) {
		        if (zip_entry_open($zip, $zip_entry, "r")) {
		   	        $xml = $this->simpleXOR(zip_entry_read($zip_entry, zip_entry_filesize($zip_entry)), $this->xorKey);
        		}
        	}

        	// Is this the XML Map?
        	if (!strcmp($name, $this->xmlMapFileName)) {
		        if (zip_entry_open($zip, $zip_entry, "r")) {
		   	        $this->xmlMap = $this->simpleXOR(zip_entry_read($zip_entry, zip_entry_filesize($zip_entry)), $this->xorKey);
        		}

        	}


	  		// Close.
	        zip_entry_close($zip_entry);
       }
	   zip_close($zip);

       if (!strcmp($xml, "not_found")) {
            $alias = $this->terminalAlias;
        	$this->err = "Error: SecureResource for alias <$alias> not found";
        	return "";
	   }

	   if (!strcmp($this->xmlMap, "not_found")) {
        	$this->err = "<br />Error: SecureResource XML Map not found for: $this->xmlMapFileName";
        	return "";
	   }

	   return $xml;
    }


    private function simpleXOR($input, $key) {
        $m = 0;
        $inputLength = strlen($input);
        $keyLength = strlen($key);

        while ($m < $inputLength) {
            for($k = 0; $k < $keyLength; $k++ ) {
                $result .= $input{$m}^$key{$k};

                $m++;
                if($m == $inputLength){
                    break;
                }

            }
        }
        return $result;
    }

    private function parseSettings($xmlStr) {
		$idOpen = "<id>";
		$pwdOpen = "<password>";
		$webAddrOpen = "<webaddress>";
		$portOpen = "<port>";
    	$contextOpen = "<context>";
    	$pwdHashOpen = "<passwordhash>";
    	$idClose = "</id>";
		$pwdClose = "</password>";
		$webAddrClose = "</webaddress>";
		$portClose = "</port>";
    	$contextClose = "</context>";
    	$pwdHashClose = "</passwordhash>";

    	$settings = new SecureSettings();
    	$settings->id = $this->subxmlstr($xmlStr, $idOpen, $idClose);
    	$settings->password = $this->subxmlstr($xmlStr, $pwdOpen, $pwdClose);
    	$settings->passwordHash = $this->subxmlstr($xmlStr, $pwdHashOpen, $pwdHashClose);
    	$settings->webAddress = $this->subxmlstr($xmlStr, $webAddrOpen, $webAddrClose);
    	$settings->port = $this->subxmlstr($xmlStr, $portOpen, $portClose);
    	$settings->context = $this->subxmlstr($xmlStr, $contextOpen, $contextClose);

    	return $settings;
	}

	private function subxmlstr($haystack, $start, $end) {
   		if (strpos($haystack, $start) === false || strpos($haystack, $end) === false) {
       		return false;
		} else {
			$start_position = strpos($haystack, $start) + strlen($start);
			$end_position = strpos($haystack, $end);

			return substr($haystack,$start_position,$end_position - $start_position);
   		}
	}

	function getErrorText() {
	    return $this->err;
	}

}

class SecureSettings {
	var $id;
	var $password;
	var $passwordhash;
	var $webAddress;
	var $port;
	var $context;
}
?>
