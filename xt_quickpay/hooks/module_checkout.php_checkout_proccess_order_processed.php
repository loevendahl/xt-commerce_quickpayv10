<?php

defined('_VALID_CALL') or die('Direct Access is not allowed.');

global $xtPlugin;

if($payment_code == 'xt_quickpay' &&
    USER_POSITION == 'store' &&
    isset($xtPlugin->active_modules['xt_quickpay']))
{
    include_once _SRV_WEBROOT . _SRV_WEB_PLUGINS . 'xt_quickpay/classes/class.xt_quickpay.php';

    $qp= new xt_quickpay();
    if ($qp->QuickpayActivated())
    {
        $payment_module_data->setOrdersId($order->oID);
    }
}