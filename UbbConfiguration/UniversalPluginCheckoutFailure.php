<?php
session_start();

$error     = $_REQUEST['error'];
$errortext = $_REQUEST['errortext'];

/* Shipping Details */
$shippingAddress = base64_decode($_SESSION['shippingAddress']);

/* Merchant Information */
$logo = $_SESSION['logo'];
$merchantName = $_SESSION['merchantName'];
$internetAddress = $_SESSION['internetAddress'];

/* Customer Information */
$customerName = $_SESSION['customerName'];

?>
<html>
    <head>
        <title>Грешка при плащане с банкова/кредитна карта</title>
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
        <form name="transactionForm" action="UniversalPluginCheckoutPaymentInit.php" method="POST" >
        <table width="80%">
                <tr>
                        <th colspan="2" style="font-size: 25px;">Грешка при плащането</th>
                </tr>
                <tr>
                        <th colspan="2"><hr/></th>
                </tr>
                <tr>
                        <td colspan="2"> Възникна неочаквана грешка при плащането!</td>
                </tr>
                <tr>
                        <th colspan="2"><hr/></th>
                </tr>
                <tr>
                        <td width="20%" align="right">Error:</td>
                        <td><font color="red"><?php echo $error ?></font></td>
                </tr>
                <tr>
                        <td width="20%"  align="right">Error Message:</td>
                        <td><font color="red"><?php echo $errortext ?></font></td>
                </tr>
                <tr>
                        <th colspan="2">&nbsp;</th>
                </tr>
        </table>
        </form>
        <table width="80%" cellspacing="0" cellpadding="0" border="0" align="center">
            <tr>
                <td align="center">
                    <button style="padding: 10px 15px; cursor: pointer;" onclick="window.location.href='<?php echo 'http://' . $_SERVER['HTTP_HOST'] . '/index.php/checkout/onepage/failure/'; ?>';">ПРОДЪЛЖИ</button>
                </td>
            </tr>
        </table>
        </center>
    </body>
</html>
