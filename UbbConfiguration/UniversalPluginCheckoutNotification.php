<?php

	require_once "PHPUtils/ACIPHPUtils.php";
	require_once "PHPUtils/Configuration.php";

	$currentContext = ACIPHPUtils::getContextPath($HTTP_SERVER_VARS);

	$paymentID  = $_REQUEST['paymentid'];
	$error      = $_REQUEST['Error'];			// The Notification servlet/page, unlike the Universal Servlet's replies, still uses: Error instead of error_code_tag
	$errortext  = $_REQUEST['ErrorText'];       // The Notification servlet/page, unlike the Universal Servlet's replies, still uses: ErrorText instead of error_text
    try {
		// You Would NOT EVER DO THIS IN PRODUCTION as it is a security concern.. instead, merchants should serialize the order data to a database.
		if (!strcmp($error, "")) {
			$Config = new Configuration('orders.lst');
			$Config->set($paymentID . '.result',    $_REQUEST['result']);
			$Config->set($paymentID . '.error',     $_REQUEST['error']);
			$Config->set($paymentID . '.errortext', $_REQUEST['errortext']);
			$Config->set($paymentID . '.ref',       $_REQUEST['ref']);
			$Config->set($paymentID . '.responsecode', $_REQUEST['responsecode']);
			$Config->set($paymentID . '.cvv2response', $_REQUEST['cvv2response']);
			$Config->set($paymentID . '.postdate',  $_REQUEST['postdate']);
			$Config->set($paymentID . '.udf1',      $_REQUEST['udf1']);
			$Config->set($paymentID . '.udf2',      $_REQUEST['udf2']);
			$Config->set($paymentID . '.udf3',      $_REQUEST['udf3']);
			$Config->set($paymentID . '.udf4',      $_REQUEST['udf4']);
			$Config->set($paymentID . '.udf5',      $_REQUEST['udf5']);
			$Config->set($paymentID . '.tranid',    $_REQUEST['tranid']);
			$Config->set($paymentID . '.auth',      $_REQUEST['auth']);
			$Config->set($paymentID . '.avr',       $_REQUEST['avr']);
			$Config->set($paymentID . '.trackid',   $_REQUEST['trackid']);
			$Config->save();

			$reply      = "REDIRECT=" . $currentContext . "UniversalPluginCheckoutReceipt.php?paymentid=" . $paymentID;
	    } else {
	        $reply      = "REDIRECT=" . $currentContext . "UniversalPluginCheckoutFailure.php?error=" .  $error . "&errortext=" .  $errortext;
	    }
    } catch (Exception $e) {
		$reply      = "Error Occurred During Notification: " . $e;
    }

	// Now reply with the redirection value.
	echo $reply;

	// Note: There is no Carriage Return after this block as Commerce Gateway does not handle Carriage Returns in the REDIRECT instruction well.
 ?>