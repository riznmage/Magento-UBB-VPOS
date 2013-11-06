<?php

require_once "PHPUtils/Configuration.php";

session_start();

$paymentID  = $_REQUEST['paymentid'];

if (strlen($paymentID) == 0) {
    $paymentID = "nonkeyedsection";
}

$Config = new Configuration('orders.lst');
$result       = $Config->get($paymentID . '.result');
$error        = $Config->get($paymentID . '.error');
$errortext    = $Config->get($paymentID . '.errortext');
$ref          = $Config->get($paymentID . '.ref');
$responsecode = $Config->get($paymentID . '.responsecode');
$cvv2response = $Config->get($paymentID . '.cvv2response');
$postdate     = $Config->get($paymentID . '.postdate');
$udf1         = $Config->get($paymentID . '.udf1');
$udf2         = $Config->get($paymentID . '.udf2');
$udf3         = $Config->get($paymentID . '.udf3');
$udf4         = $Config->get($paymentID . '.udf4');
$udf5         = $Config->get($paymentID . '.udf5');
$tranid       = $Config->get($paymentID . '.tranid');
$auth         = $Config->get($paymentID . '.auth');
$avr          = $Config->get($paymentID . '.avr');
$trackid      = $Config->get($paymentID . '.trackid');

/* Order Information */
$totalPrice = $_SESSION['totalPrice'];
$totalDescriptive = base64_decode($_SESSION['totalDescriptive']);
$VAT = base64_decode($_SESSION['VAT']);
$orderId = $_SESSION['orderId'];

/* Shipping Details */
$shippingTitle = $_SESSION['shippingTitle'];
$shippingAmount = base64_decode($_SESSION['shippingAmount']);
$shippingAddress = base64_decode($_SESSION['shippingAddress']);

/* Products Information */
$productsName = $_SESSION['name'];
$productsSku = $_SESSION['sku'];
$productsUnitTotalPrice = $_SESSION['unitTotalPrice'];
$productsUnitPriceDesc = $_SESSION['unitPriceDesc'];
$productsQty = $_SESSION['qty'];

/* Merchant Information */
$logo = $_SESSION['logo'];
$merchantName = $_SESSION['merchantName'];
$internetAddress = $_SESSION['internetAddress'];

/* Customer Information */
$customerName = $_SESSION['customerName'];

/* Additional Information  */
$currencyName = $_SESSION['currencyName'];
$productsCount = count($productsName);
$transactionDate = $_SESSION['transactionDate'];

?>

<html>
<head>
    <title>Плащане с банкова/кредитна карта</title>
    <META HTTP-EQUIV="PRAGMA" CONTENT="NO-CACHE">
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
</head>
<body>
<center>
<table width="80%" style="border: 1px solid darkred;">
	<tr>
            <td>
                <img src="<?php echo $logo; ?>"/>
            </td>
            <td>
                <table>
                    <tr>
                        <td style="font-weight: bold;">Име на търговеца:</td>
                        <td><?php echo $merchantName; ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Интернет адрес:</td>
                        <td><?php echo $internetAddress; ?></td>
                    </tr>
                </table>
            </td>
            <td>
                <table>
                    <tr>
                        <td style="font-weight: bold;">Име на купувача:</td>
                        <td><?php echo $customerName; ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;" valign="top">Адрес за доставка:</td>
                        <td><?php echo $shippingAddress; ?></td>
                    </tr>
                </table>
            </td>
	</tr>
</table>
<form name="transactionForm" action="UbbInit.php" method="POST" >

<table width="80%" cellspacing="0" cellpadding="0" border="0">
	<tr>
		<td colspan="2"><h3>Информация за плащането</h3></td>
	</tr>
	<td colspan="2">
	<table width="100%" cellspacing="0" cellpadding="0" style="border: 1px solid black;">
		<tr style="background-color: lightblue;">
			<td>Track ID</td>
			<td>Order ID</td>
			<td>Reference #</td>
			<td>Post Date</td>
			<td>Transaction ID</td>
			<td>Auth Code#</td>
		</tr>
		<tr>
			<td><?php echo $trackid ?>&nbsp;</td>
			<td><?php echo $paymentID ?>&nbsp;</td>
			<td><?php echo $ref ?>&nbsp;</td>
			<td><?php echo $postdate ?>&nbsp;</td>
			<td><?php echo $tranid ?>&nbsp;</td>
			<td><?php echo $auth ?>&nbsp;</td>
		</tr>
	</table>
	</td>
        <tr>
            <td colspan="2">
            <table width="100%" cellspacing="0" cellpadding="0" style="border: 1px solid black;">
                    <tr style="background-color: lightblue;">
                            <td>Метод на плащане</td>
                            <td>Тип на транзакцията</td>
                            <td>Транзакционна сума</td>
                            <td>Валута</td>
                            <td>Дата на транзакцията</td>
                    </tr>
                    <tr>
                            <td>Банкова карта&nbsp;</td>
                            <td>Покупка&nbsp;</td>
                            <td><?php echo $totalDescriptive; ?>&nbsp;</td>
                            <td><?php echo $currencyName; ?>&nbsp;</td>
                            <td><?php echo $transactionDate; ?>&nbsp;</td>
                    </tr>
            </table>
            </td>
        </tr>
	<tr>
		<th colspan="2"><hr /></th>
	</tr>
	<tr>
		<td colspan="2"><h3>Поръчка № <?php echo $orderId; ?></h3></td>
	</tr>
	<tr>
		<td colspan="2">
		<table width="100%" cellspacing="0" cellpadding="0" style="border: 1px solid black;">
			<tr style="background-color: lightblue;">
				<td>Артикул #</td>
				<td>Име на продукт</td>
				<td align="right">Количество</td>
				<td align="right">Единична цена</td>
				<td align="right">Стойност</td>
			</tr>
                        <?php for($c=0; $c<$productsCount; $c++): ?>
			<tr>
				<td align="left" style="border-bottom: 1px dotted #333; padding: 4px 0;"><?php echo $productsSku[$c] ?></td>
				<td align="left" style="border-bottom: 1px dotted #333; padding: 4px 0;"><?php echo $productsName[$c] ?></td>
				<td align="right" style="border-bottom: 1px dotted #333; padding: 4px 0;"><?php echo $productsQty[$c] ?>&nbsp;</td>
				<td align="right" style="border-bottom: 1px dotted #333; padding: 4px 0;"><?php echo base64_decode($productsUnitPriceDesc[$c]) ?>&nbsp;</td>
				<td align="right" style="font-weight: bold; border-bottom: 1px dotted #333; padding-bottom: 5px;"><?php echo base64_decode($productsUnitTotalPrice[$c]) ?>&nbsp;</td>
			</tr>
                        <?php endfor; ?>
                        <tr>
                            <td style="border-bottom: 1px dotted #333; padding: 4px 0;">&nbsp;</td>
                            <td style="border-bottom: 1px dotted #333; padding: 4px 0;">&nbsp;</td>
                            <td style="border-bottom: 1px dotted #333; padding: 4px 0;">&nbsp;</td>
                            <td align="right" style="border-bottom: 1px dotted #333; padding: 4px 0;"><?php echo $shippingTitle; ?>:</td>
                            <td align="right" style="font-weight: bold; border-bottom: 1px dotted #333; padding: 4px 0;"><?php echo $shippingAmount; ?></td>
                        </tr>
                        <?php if(substr($VAT, 0, 4)!='0.00'): ?>
                        <tr>
                            <td style="border-bottom: 1px dotted #333; padding: 4px 0;">&nbsp;</td>
                            <td style="border-bottom: 1px dotted #333; padding: 4px 0;">&nbsp;</td>
                            <td style="border-bottom: 1px dotted #333; padding: 4px 0;">&nbsp;</td>
                            <td align="right" style="border-bottom: 1px dotted #333; padding: 4px 0;">ДДС:</td>
                            <td align="right" style="font-weight: bold; border-bottom: 1px dotted #333; padding: 4px 0;"><?php echo $VAT; ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td style="padding: 4px 0;">&nbsp;</td>
                            <td style="padding: 4px 0;">&nbsp;</td>
                            <td style="padding: 4px 0;">&nbsp;</td>
                            <td align="right" style="font-size: 22px; padding: 4px 0;">Общо:</td>
                            <td align="right" style="font-weight: bold; font-size: 22px; padding: 4px 0;"><?php echo $totalDescriptive; ?></td>
                        </tr>
		</table>
		</td>
	</tr>
	<tr>
		<th colspan="2"><hr /></th>
	</tr>
	<tr>
		<td colspan="2"><h3>Transmission Information</h3></td>
	</tr>
	<tr>
		<td colspan="2">
			<table width="100%" cellspacing="0" cellpadding="2" style="border: 1px solid black;">
				<tr>
					<td width="10%" align="right"style="background-color: lightblue;">Result Code:</td>
					<td width="40%" ><?php echo $result ?>&nbsp;</td>
				</tr>
<?php
	if (strlen($pares) > 0) {
	    echo "				<tr>\r\n";
	    echo "				    <th colspan=\"4\">&nbsp;</th>\r\n";
	    echo "				</tr>\r\n";
	    echo "				<tr>\r\n";
	    echo "				    <td>PARes:</td><td colspan=\"3\"><textarea cols=\"80\" rows=\"10\">" . $pares . "</textarea></h4></td>\r\n";
	    echo "				</tr>\r\n";
	}
?>
			</table>
		</td>
	</tr>
	<tr>
		<th colspan="2">&nbsp;</th>
	</tr>
	<tr>
		<td colspan="2" align="center">
		</td>
	</tr>
</table>

</form>
    <table width="80%" cellspacing="0" cellpadding="0" border="0" align="center">
        <tr>
            <td>
                <a href="javascript:window.print()">Принтирай страницата</a>
            </td>
        </tr>
        <tr>
            <td align="center" style="padding-bottom: 10px;">
                <?php
                switch ($responsecode) {
                    case '00':
                        echo 'Плащането беше извършено успешно!';
                        break;
                    case '05':
                        echo 'Въвели сте грешен CVV код.';
                        echo '<br/>';
                        echo 'Плащането беше неуспешно!';
                        break;
                    case '54':
                        echo 'Въвели сте грешна дата на изтичане на картата.';
                        echo '<br/>';
                        echo 'Плащането беше неуспешно!';
                        break;
                    case '61':
                        echo 'Нямате достатъчно наличност по сметката си, за да извършите това плащане.';
                        echo '<br/>';
                        echo 'Плащането беше неуспешно!';
                        break;
                    default:
                        echo 'Възникна грешка при плащането.';
                        echo '<br/>';
                        echo 'Плащането беше неуспешно!';
                        break;
                }
                ?>
            </td>
        </tr>
        <tr>
            <td align="center">
                <button style="padding: 10px 15px; cursor: pointer;" onclick="window.location.href='<?php echo ($responsecode=='00') ? 'http://' . $_SERVER['HTTP_HOST'] . '/index.php/checkout/onepage/success/' : 'http://' . $_SERVER['HTTP_HOST'] . '/index.php/checkout/onepage/failure/'; ?>';">ПРОДЪЛЖИ</button>
            </td>
        </tr>
    </table>
</center>
</body>
</html>
