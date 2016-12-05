<?php

defined('_VALID_CALL') or die('Direct Access is not allowed.');

global $xtPlugin;
 include_once _SRV_WEBROOT . _SRV_WEB_PLUGINS . 'xt_quickpay/classes/class.xt_quickpay.php';
    $qp= new xt_quickpay();

if(USER_POSITION == 'store' &&
    $key == 'xt_quickpay' &&
    isset($xtPlugin->active_modules['xt_quickpay']))
{
    include_once _SRV_WEBROOT . _SRV_WEB_PLUGINS . 'xt_quickpay/classes/class.xt_quickpay.php';
    $qp= new xt_quickpay();
		
    if ($qp->QuickpayActivated())
    {
        $arr_qpall = $qp->qppayments;
        $arr_qpselected = $qp->allowed_qpsubpayments;
        $arr_qpunselected = array_diff(array_keys($arr_qpall), $arr_qpselected);
        $available_qpsub_payments = array();
        $payment_currency_code = strtoupper($_SESSION['customer']->customer_info["customers_default_currency"]);
       
		foreach ($arr_qpselected as $v)
        {
            define('XT_QUICKPAY_ACTIVATED_PAYMENTS_' . strtoupper($v), true);
          if (($v == 'mobilepay' || $v == 'viabill' || $v == 'dankort' || $v == 'fbg1886') && ($payment_currency_code != 'DKK'))
            {
                continue;
            }
		 
            $available_qpsub_payments[$v] = $arr_qpall[$v];
        }
        foreach ($arr_qpunselected as $v)
        {
            define('XT_QUICKPAY_ACTIVATED_PAYMENTS_' . strtoupper($v), false);
        }
      	
        $tpl_data['sub_payments'] = $available_qpsub_payments;
     
        // finally check xt version and change template
   {
            if (version_compare(5,_SYSTEM_VERSION) == 1)
            {
                $template = new Template();
                $template->getTemplatePath($tpl, $value['payment_dir'], '4200', 'payment');
            }
       } 
    }
}