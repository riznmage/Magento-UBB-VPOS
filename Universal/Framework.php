<?php
/********************************************************************
 *  @(#)Universal/Framework.php                                     *
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
class TraceManager {
    private $traceEnabled;

    public function TraceManager($tracing = false) {
        $this->traceEnabled = $tracing;
    }

    public function setTraceOn() {
        $this->traceEnabled = true;
    }

    public function setTraceOff() {
        $this->traceEnabled = false;
    }

    public function trace($msg) {
	 	if ($this->traceEnabled != true) {
			return;
		}

		// Get the time.
		$date = date('Y-m-d');
		$time = date('h:i:s T');

		// Build the debug string.
		$data = $date . " @ " . $time . " - " . $msg . "\r\n";

		// Open and write to the log file.
		$fp = fopen("CGDebug-$date.log", "a");
		fwrite($fp, $data);

		echo "<br />$data";

		// Close the log file.
		fclose($fp);
	}
}