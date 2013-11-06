<?php

/* Include files needed for the Universal Plug-in */
require_once "../Universal/UniversalPlugin.php";
require_once "../Universal/UniversalPluginXMLFileParser.php";
require_once "../Universal/Framework.php";
require_once "PHPUtils/ACIPHPUtils.php";
require_once "PHPUtils/Configuration.php";

session_start();

/* Universal Plug-in Configuration */
$Config = new Configuration('UbbConfiguration.txt');
$resourcePath = $Config->get('settings.resourcePath');
$terminalAlias = $Config->get('settings.alias');

$CGPipe = new UniversalPlugin(false);

/* Order Information */
$orderId = (int)$_REQUEST['orderId'];
$_SESSION['totalPrice'] = $_REQUEST['grandTotal'];
$_SESSION['totalDescriptive'] = $_REQUEST['totalDescriptive'];
$_SESSION['VAT'] = $_REQUEST['VAT'];
$_SESSION['orderId'] = $_REQUEST['orderId'];

/* Shipping Details */
$_SESSION['shippingTitle'] = $_REQUEST['shippingTitle'];
$_SESSION['shippingAmount'] = $_REQUEST['shippingAmount'];
$_SESSION['shippingAddress'] = $_REQUEST['shippingAddress'];

/* Products Information */
$_SESSION['name'] = $_REQUEST['name'];
$_SESSION['sku'] = $_REQUEST['sku'];
$_SESSION['unitTotalPrice'] = $_REQUEST['unitTotalPrice'];
$_SESSION['unitPriceDesc'] = $_REQUEST['unitPriceDesc'];
$_SESSION['qty'] = $_REQUEST['qty'];

/* Merchant Information */
$_SESSION['logo'] = $_REQUEST['logo'];
$_SESSION['merchantName'] = $_REQUEST['merchantName'];
$_SESSION['internetAddress'] = $_REQUEST['internetAddress'];

/* Customer Information */
$_SESSION['customerName'] = $_REQUEST['customerName'];

/* Additional Information */
$_SESSION['currencyCode'] = $_REQUEST['currencyCode'];
$_SESSION['currencyName'] = $_REQUEST['currencyName'];
$_SESSION['transactionDate'] = $_REQUEST['transactionDate'];

/* Set locale used for payment gateway page */
switch ($_REQUEST['localeCode']){
    case 'bg_BG': $localeCode = 'bg_BG'; break;
    case 'en_US': $localeCode = 'en_US'; break;
    default: $localeCode = 'en_US'; break;
}

/* Currency Code => Currency Code Number (Convertor) */
switch ($_SESSION['currencyCode']) {
//    case 'AFN': $currencyCodeN = 971; break;
//    case 'ALL': $currencyCodeN = 008; break;
//    case 'DZD': $currencyCodeN = 012; break;
//    case 'ADP': $currencyCodeN = 020; break;
//    case 'AOA': $currencyCodeN = 973; break;
//    case 'XCD': $currencyCodeN = 951; break;
//    case 'ARS': $currencyCodeN = 032; break;
//    case 'AMD': $currencyCodeN = 051; break;
//    case 'AWG': $currencyCodeN = 533; break;
//    case 'AUD': $currencyCodeN = 036; break;
//    case 'ATS': $currencyCodeN = 040; break;
//    case 'AZN': $currencyCodeN = 031; break;
//    case 'BSD': $currencyCodeN = 044; break;
//    case 'BHD': $currencyCodeN = 048; break;
//    case 'BDT': $currencyCodeN = 050; break;
//    case 'BBD': $currencyCodeN = 052; break;
//    case 'BYR': $currencyCodeN = 974; break;
//    case 'BEF': $currencyCodeN = 056; break;
//    case 'BZD': $currencyCodeN = 084; break;
//    case 'XOF': $currencyCodeN = 952; break;
//    case 'BMD': $currencyCodeN = 060; break;
//    case 'BTN': $currencyCodeN = 064; break;
//    case 'INR': $currencyCodeN = 356; break;
//    case 'BOB': $currencyCodeN = 068; break;
//    case 'BOV': $currencyCodeN = 984; break;
//    case 'BAM': $currencyCodeN = 977; break;
//    case 'BWP': $currencyCodeN = 072; break;
//    case 'NOK': $currencyCodeN = 578; break;
//    case 'BRL': $currencyCodeN = 986; break;
//    case 'BND': $currencyCodeN = 096; break;
//    case 'BGL': $currencyCodeN = 100; break;
//    case 'BIF': $currencyCodeN = 108; break;
//    case 'KHR': $currencyCodeN = 116; break;
//    case 'XAF': $currencyCodeN = 950; break;
//    case 'CAD': $currencyCodeN = 124; break;
//    case 'CVE': $currencyCodeN = 132; break;
//    case 'KYD': $currencyCodeN = 136; break;
//    case 'CLP': $currencyCodeN = 152; break;
//    case 'CLF': $currencyCodeN = 990; break;
//    case 'CNY': $currencyCodeN = 156; break;
//    case 'COP': $currencyCodeN = 170; break;
//    case 'KMF': $currencyCodeN = 174; break;
//    case 'CDF': $currencyCodeN = 976; break;
//    case 'NZD': $currencyCodeN = 554; break;
//    case 'CRC': $currencyCodeN = 188; break;
//    case 'HRK': $currencyCodeN = 191; break;
//    case 'CUP': $currencyCodeN = 192; break;
//    case 'CZK': $currencyCodeN = 203; break;
//    case 'DKK': $currencyCodeN = 208; break;
//    case 'DJF': $currencyCodeN = 262; break;
//    case 'DOP': $currencyCodeN = 214; break;
//    case 'EGP': $currencyCodeN = 818; break;
//    case 'SVC': $currencyCodeN = 222; break;
//    case 'ERN': $currencyCodeN = 232; break;
//    case 'EEK': $currencyCodeN = 233; break;
//    case 'ETB': $currencyCodeN = 230; break;
//    case 'FKP': $currencyCodeN = 238; break;
//    case 'FJD': $currencyCodeN = 242; break;
//    case 'FIM': $currencyCodeN = 246; break;
//    case 'FRF': $currencyCodeN = 250; break;
//    case 'XPF': $currencyCodeN = 953; break;
//    case 'GMD': $currencyCodeN = 270; break;
//    case 'GEL': $currencyCodeN = 981; break;
//    case 'DEM': $currencyCodeN = 280; break;
//    case 'GHC': $currencyCodeN = 288; break;
//    case 'GIP': $currencyCodeN = 292; break;
//    case 'GRD': $currencyCodeN = 300; break;
//    case 'GTQ': $currencyCodeN = 320; break;
//    case 'GNF': $currencyCodeN = 324; break;
//    case 'GWP': $currencyCodeN = 624; break;
//    case 'GYD': $currencyCodeN = 328; break;
//    case 'HTG': $currencyCodeN = 332; break;
//    case 'ITL': $currencyCodeN = 380; break;
//    case 'HNL': $currencyCodeN = 340; break;
//    case 'HKD': $currencyCodeN = 344; break;
//    case 'HUF': $currencyCodeN = 348; break;
//    case 'ISK': $currencyCodeN = 352; break;
//    case 'IDR': $currencyCodeN = 360; break;
//    case 'IRR': $currencyCodeN = 364; break;
//    case 'IQD': $currencyCodeN = 368; break;
//    case 'IEP': $currencyCodeN = 372; break;
//    case 'ILS': $currencyCodeN = 376; break;
//    case 'JMD': $currencyCodeN = 388; break;
//    case 'JPY': $currencyCodeN = 392; break;
//    case 'JOD': $currencyCodeN = 400; break;
//    case 'KZT': $currencyCodeN = 398; break;
//    case 'KES': $currencyCodeN = 404; break;
//    case 'KPW': $currencyCodeN = 408; break;
//    case 'KRW': $currencyCodeN = 410; break;
//    case 'KWD': $currencyCodeN = 414; break;
//    case 'KGS': $currencyCodeN = 417; break;
//    case 'LAK': $currencyCodeN = 418; break;
//    case 'LVL': $currencyCodeN = 428; break;
//    case 'LBP': $currencyCodeN = 422; break;
//    case 'LRD': $currencyCodeN = 430; break;
//    case 'LYD': $currencyCodeN = 434; break;
//    case 'CHF': $currencyCodeN = 756; break;
//    case 'LTL': $currencyCodeN = 440; break;
//    case 'LUF': $currencyCodeN = 442; break;
//    case 'MOP': $currencyCodeN = 446; break;
//    case 'MKD': $currencyCodeN = 807; break;
//    case 'MGF': $currencyCodeN = 450; break;
//    case 'MYR': $currencyCodeN = 458; break;
//    case 'MVR': $currencyCodeN = 462; break;
//    case 'MTL': $currencyCodeN = 470; break;
//    case 'MRO': $currencyCodeN = 478; break;
//    case 'MUR': $currencyCodeN = 480; break;
//    case 'MXN': $currencyCodeN = 484; break;
//    case 'MXV': $currencyCodeN = 979; break;
//    case 'MDL': $currencyCodeN = 498; break;
//    case 'MNT': $currencyCodeN = 496; break;
//    case 'MAD': $currencyCodeN = 504; break;
//    case 'MZM': $currencyCodeN = 508; break;
//    case 'MMK': $currencyCodeN = 104; break;
//    case 'ZAR': $currencyCodeN = 710; break;
//    case 'NAD': $currencyCodeN = 516; break;
//    case 'NPR': $currencyCodeN = 524; break;
//    case 'NLG': $currencyCodeN = 528; break;
//    case 'ANG': $currencyCodeN = 532; break;
//    case 'NIO': $currencyCodeN = 558; break;
//    case 'NGN': $currencyCodeN = 566; break;
//    case 'OMR': $currencyCodeN = 512; break;
//    case 'PKR': $currencyCodeN = 586; break;
//    case 'PAB': $currencyCodeN = 590; break;
//    case 'PGK': $currencyCodeN = 598; break;
//    case 'PYG': $currencyCodeN = 600; break;
//    case 'PEN': $currencyCodeN = 604; break;
//    case 'PHP': $currencyCodeN = 608; break;
//    case 'PLN': $currencyCodeN = 985; break;
//    case 'PTE': $currencyCodeN = 620; break;
//    case 'QAR': $currencyCodeN = 634; break;
//    case 'ROL': $currencyCodeN = 642; break;
//    case 'RUR': $currencyCodeN = 810; break;
//    case 'RUB': $currencyCodeN = 643; break;
//    case 'RWF': $currencyCodeN = 646; break;
//    case 'SHP': $currencyCodeN = 654; break;
//    case 'WST': $currencyCodeN = 882; break;
//    case 'STD': $currencyCodeN = 678; break;
//    case 'SAR': $currencyCodeN = 682; break;
//    case 'CSD': $currencyCodeN = 891; break;
//    case 'SCR': $currencyCodeN = 690; break;
//    case 'SLL': $currencyCodeN = 694; break;
//    case 'SGD': $currencyCodeN = 702; break;
//    case 'SKK': $currencyCodeN = 703; break;
//    case 'SIT': $currencyCodeN = 705; break;
//    case 'SBD': $currencyCodeN = 090; break;
//    case 'SOS': $currencyCodeN = 706; break;
//    case 'ESP': $currencyCodeN = 724; break;
//    case 'LKR': $currencyCodeN = 144; break;
//    case 'SDD': $currencyCodeN = 736; break;
//    case 'SDG': $currencyCodeN = 938; break;
//    case 'SRG': $currencyCodeN = 740; break;
//    case 'SZL': $currencyCodeN = 748; break;
//    case 'SEK': $currencyCodeN = 752; break;
//    case 'SYP': $currencyCodeN = 760; break;
//    case 'TWD': $currencyCodeN = 901; break;
//    case 'TJS': $currencyCodeN = 972; break;
//    case 'TZS': $currencyCodeN = 834; break;
//    case 'THB': $currencyCodeN = 764; break;
//    case 'TOP': $currencyCodeN = 776; break;
//    case 'TTD': $currencyCodeN = 780; break;
//    case 'TND': $currencyCodeN = 788; break;
//    case 'TRY': $currencyCodeN = 949; break;
//    case 'TMM': $currencyCodeN = 795; break;
//    case 'UGX': $currencyCodeN = 800; break;
//    case 'UAH': $currencyCodeN = 980; break;
//    case 'AED': $currencyCodeN = 784; break;
//    case 'USS': $currencyCodeN = 998; break;
//    case 'USN': $currencyCodeN = 997; break;
//    case 'USN': $currencyCodeN = 997; break;
//    case 'UYU': $currencyCodeN = 858; break;
//    case 'UZS': $currencyCodeN = 860; break;
//    case 'VUV': $currencyCodeN = 548; break;
//    case 'VEF': $currencyCodeN = 937; break;
//    case 'VEB': $currencyCodeN = 862; break;
//    case 'VND': $currencyCodeN = 704; break;
//    case 'YER': $currencyCodeN = 886; break;
//    case 'ZMK': $currencyCodeN = 894; break;
//    case 'ZWD': $currencyCodeN = 716; break;
    case 'BGN': $currencyCodeN = 975; break;
    case 'EUR': $currencyCodeN = 978; break;
    case 'USD': $currencyCodeN = 840; break;
    case 'GBP': $currencyCodeN = 826; break;
    default: $currencyCodeN = 978; break; // Euro
}



$currentContext = ACIPHPUtils::getContextPath($HTTP_SERVER_VARS);

/*  */
// Turn off ssl for this test.
//$CGPipe->setProtocol("");
$CGPipe->set("action", "1");	// 1 - Purchase, 4 - Authorization
$CGPipe->set("amt", $_SESSION['totalPrice']);
$CGPipe->set("currencycode", $currencyCodeN);
$CGPipe->set("trackid", $orderId);
$CGPipe->set("langid", $localeCode);
$CGPipe->set("responseurl", $currentContext . "UniversalPluginCheckoutNotification.php");
$CGPipe->set("errorurl", $currentContext . "UniversalPluginCheckoutFailure.php");


$CGPipe->setResourcePath($resourcePath);
$CGPipe->setTerminalAlias($terminalAlias);

$CGPipe->setTransactionType("PaymentInit");
$CGPipe->setVersion("1");

	$CGPipe->performTransaction();

	//
	// Determine if this is a 3D Secure Transaction, if so, redirect the browser to
	// the ACS.
	//
	$type = $rqst['type'];
	if (!strcmp($type, "VPAS")) {
        $vpasTran = true;
    } else {
        $vpasTran = false;
    }

	$respArray = $CGPipe->getResponseFields();

	$error = $CGPipe->get("error_code_tag");
	if (!empty($error)) {
        echo "<h2>Error: $error</h2>\r\n";
        if (!strcmp($error, "CM90100")) {
        	echo "Unable to invoke requested Command.<br/>\r\n";
        }
        ?>
            <script type="text/javascript">
                window.location.href='<?php echo $currentContext . 'UniversalPluginCheckoutFailure.php'; ?>';
            </script>
            <?php

    } else {
        performGatewayRedirect($respArray['PAYMENTPAGE'], $respArray['PAYMENTID']);
        exit;
    }


	function performGatewayRedirect($url, $paymentId) {
		$termURL = $currentContext . "TermURL.php";

    // Begin HTML CODE
?>
<!doctype html>
<html>
<head>
    <META HTTP-EQUIV="PRAGMA" CONTENT="NO-CACHE">
</head>
<body OnLoad="OnLoadEvent();">
    <form action="<?php echo $url ?>" method="post" name="form1" autocomplete="off">
        <input type="hidden" name="PaymentID" value="<?php echo $paymentId ?>"  />
    </form>
    <script language="JavaScript">

    function OnLoadEvent() {
       document.form1.submit();
       timVar = setTimeout("procTimeout()",300000);
    }

    function procTimeout() {
       location = '<?php echo $_SERVER['HTTP_REFERER']; ?>';
    }

    //
    // disable page duplication -> CTRL-N key
    //
    if (document.all) {
        document.onkeydown = function () {
            if (event.ctrlKey && event.keyCode == 78) {
                return false;
            }
        }
    }
    </script>
</body>
</html>
<?php
        // End of HTML CODE

    } //end of function performVPASRedirect()

?>

