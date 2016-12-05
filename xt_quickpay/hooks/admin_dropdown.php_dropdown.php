<?php

defined('_VALID_CALL') or die('Direct Access is not allowed.');
       
    include_once _SRV_WEBROOT._SRV_WEB_PLUGINS.'xt_quickpay/classes/class.xt_quickpay.php';
        $qp = new xt_quickpay();	   
		
switch ($request['get'])
{
    case "all_QP_ACTIVATED_PAYMENTS":
    
	    $result = $qp->dropdownAllPayments();
        break;
	
	case "QP_subscriptions":
	     $result = $qp->dropdownshoptype();
		
	break;
}