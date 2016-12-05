<?php

defined('_VALID_CALL') or die('Direct Access is not allowed.');

global $xtPlugin;

if(!empty($_POST['selected_payment_quickpay']) &&
    USER_POSITION == 'store' &&
    isset($xtPlugin->active_modules['xt_quickpay']))
{
include_once _SRV_WEBROOT . _SRV_WEB_PLUGINS . 'xt_quickpay/classes/class.xt_quickpay.php';
$qp = new xt_quickpay();

    if ($qp->QuickpayActivated())
    {
        $_POST['selected_payment'] = $_POST['selected_payment_quickpay'];
        $exp = explode(':', $_POST['selected_payment_quickpay']);
        $_SESSION['quickpay_selected_payment_sub'] = $exp[1];
    }

    if (isset($_SESSION['quickpay_selected_payment_sub']))
    {
		
		
        $payment_name = $qp->qppayments[$_SESSION['quickpay_selected_payment_sub']]['name'];
        define('TEXT_PAYMENT_' . strtoupper($_SESSION['quickpay_selected_payment_sub']), $payment_name);
    }
}

if(isset($_SESSION['quickpay_selected_payment_sub']) &&
    USER_POSITION == 'store' &&
    isset($xtPlugin->active_modules['xt_quickpay']))
{
    include_once _SRV_WEBROOT . _SRV_WEB_PLUGINS . 'xt_quickpay/classes/class.xt_quickpay.php';

    $payment_name = $qp->qppayments[$_SESSION['quickpay_selected_payment_sub']]['name'];
    define('TEXT_PAYMENT_' . strtoupper($_SESSION['quickpay_selected_payment_sub']), $payment_name);
}