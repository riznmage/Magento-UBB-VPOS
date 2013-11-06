<?php

class ACIPHPUtils {
    public static function getContextPath($HTTP_SERVER_VARS) {
	if ( isset($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) ){
   		$protocol = "https://";
	} else {
		$protocol = "http://";
	}


	$uri = $_SERVER ["REQUEST_URI"];
	$host = $_SERVER ['HTTP_HOST'];
	$port = $_SERVER ['SERVER_PORT'];
	$uri = substr($uri, 0, strrpos($uri, "/"));
	if ($port == 80) {
	    $port = "";
	} else {
	    $port = ":" . $port;
	}

	return $protocol . $host . $port . $uri . "/";
	}
}

?>
