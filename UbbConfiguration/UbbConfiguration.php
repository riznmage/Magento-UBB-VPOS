<?php
	require_once('../Universal/SecureResourceManager.php');
	require_once "./PHPUtils/Configuration.php";

	$savedMessage = "";
	$files = null;
	if (!strcmp($_REQUEST['Save'], "Save")) {
		$resourcePath = $_REQUEST['resourcePath'];
		
		$alias        = $_REQUEST['alias'];
		// Save Settings.
		$Config = new Configuration('UbbConfiguration.txt');
		$Config->set('settings.resourcePath', $resourcePath);
		$Config->set('settings.alias',        $alias);
		$Config->save();
		$savedMessage = "Configuration Saved Successfully.";
	} else if (strcmp($_REQUEST['TestResource'], "")) {
		$resourcePath = $_REQUEST['resourcePath'];
		$terminalAlias = "";	// Unknown at this time
		$xmlMapFileName = "";   // Unknown at this time.
		if (strlen($resourcePath) > 0) {
			$srm = new SecureResourceManager($terminalAlias, $resourcePath, $xmlMapFileName);
			$files = $srm->getTerminalAliases();
			$resourcePath = $srm->getResourcePath();
		}                       
	} else {
		// Load the Settings.
		$Config = new Configuration('UbbConfiguration.txt');
		$resourcePath = $Config->get('settings.resourcePath');
		$alias = $Config->get('settings.alias');
	}

	if (strlen($resourcePath) > 0) {
		$srm = new SecureResourceManager($terminalAlias, $resourcePath, $xmlMapFileName);
		$files = $srm->getTerminalAliases();
	}

	function cleanupResourcePath ($resourcePath) {
		$resourcePath = $_REQUEST['resourcePath'];
		while (strpos($resourcePath, "\\")) {
			$resourcePath = str_replace("\\", "/", $resourcePath);
		}
		while (strpos($resourcePath, "//")) {
			$resourcePath = str_replace("//", "/", $resourcePath);
		}
		return $resourcePath;
	}


?>
<!doctype html>
<html>
<head>
	<title>Rizn - UBB Configuration</title>
</head>
<body>
<form name="ConfigurationForm" action="UbbConfiguration.php" method="post">
<table width="100%" cellspacing="0" cellpadding="0" border = "0">
	<tr>
		<td colspan="2">Configuration Settings:</td>
	</tr>
	<tr>
		<th colspan="2"><hr/></th>
	</tr>
	<tr>
		<td align="right">Resource Path:</td>
		<td ><input name="resourcePath" type="input" size="80" value="<?php echo $resourcePath ?>"/><input type="submit" name="TestResource" value="Load resource file"/></td>
	</tr>
	<tr>
		<td align="right">Alias:</td>
		<td >

<?php	if ($files == null) {
			echo "			<input name=\"alias\" type=\"input\" size=\"40\" value=\"" .$alias . "\"/>";
        }  else {
			echo "			<select name=\"alias\"/>";
			$firstFileFound = "";
			foreach ($files as $key => $value) {
				if (!strcmp($value, $alias)) {
					$selected = "selected";
				} else {
				    $selected = "";
				}
				echo "			    <option value=\"" . $value . "\" " . $selected . ">" . $value . "</option>";
			}
			$alias = $firstFileFound;
			echo "			</select>";

 		}
?>
		</td>
	</tr>
	<tr>
		<td></td>
		<td>
			<input type="submit" name="Save" value="Save" class="checkoutButton"/>
		</td>
	</tr>
	<tr>
		<td align="center" colspan="2"><?php echo $savedMessage ?></td>
	</tr>
</table>
</form>
</body>
</html>