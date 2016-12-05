<?php

defined('_VALID_CALL') or die('Direct Access is not allowed.');


if($this->url_data['edit_id'] && $this->url_data['new'] != true)
{
    include_once _SRV_WEBROOT._SRV_WEB_PLUGINS.'xt_quickpay/classes/class.xt_quickpay.php';

    global $db;
	$qp = new xt_quickpay();
    $quickpayId = $qp->getQuickpayPaymentId();
	
    if($quickpayId == $this->url_data['edit_id'])
    {
        foreach($header as $k => $v)
        {
            if(strpos($k, 'conf_XT_QUICKPAY_ACTIVATED_PAYMENTS_shop_',0) === 0)
            {
		
                preg_match("/[0-9]+$/", $k, $out);
                $header[$k]['valueUrl'] = 'adminHandler.php?plugin=xt_quickpay&load_section=xt_quickpay&pg=saved_QP_ACTIVATED_PAYMENTS&shop_id='.$out[0];
				
            }
        }
    }
}
