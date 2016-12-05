<?php
//only show if payment is through quickpay
if($this->orderData["order_data"]["payment_code"]=='xt_quickpay'){
	
require_once(_SRV_WEBROOT . _SRV_WEB_PLUGINS . 'xt_quickpay/classes/class.xt_quickpay.php');
$qp = new xt_quickpay;

if($qp->apiuser){
	$qp->setOrdersId($this->oID);
	$tools = preg_replace( "/\r|\n/", "", addslashes($qp->tools()));
	$cardinfo = $qp->cardinfo;
}else{
	$tools = "Please set API User key";
	
}

$ajaxload = "adminHandler.php";
$ajaxgetdefault = "'plugin': 'xt_quickpay' , 'load_section': 'xt_quickpay', 'pg': 'get_transaction_actions', 'orders_id': ".$this->oID;


$js .= "

Ext.onReady(function(){
 $(\"#orderhistoryContainer".$this->oID."\").prepend(\"<div id='qptools' style='background:#fff;'><div id='qptoolslabel' >".XT_QUICKPAY_TRANSACTIONS."</div><div id='qptoolsbox' >$tools</div></div><div class='cardinfo' >$cardinfo</div>\");



qpinit();

function qpinit(){

	
$('.actionbutton').click(function(e){
	var action = $(this).attr('name');
	var valbig = $(this).attr('valbig');
	var valsmall = $(this).attr('valsmall');
	var id = $(this).attr('id');
 	
	var f = '#'+action+'_form".$this->oID."';
	var b= $(f+' input[name=\"amount_big\"]').val();
    var s= $(f+' input[name=\"amount_small\"]').val();
	if(b && s){
	b = b.replace(/(\D)+/g,'');
	s = s.replace(/(\D)+/g,'');
    s = s.replace('00','0');
	}
	
	doaction(action,valbig,valsmall,b,s,id);
	
	
});
}
  function doaction(action,valbig,valsmall,b,s,id) {
    
	
 switch (action) { 
	case 'capture': 
	
	if(qp_check_capture(valbig,valsmall, b, s, '".XT_QUICKPAY_CONFIRM_CAPTURE."')){
       dotransaction(action,b,s,id);

	}
		break;
	case 'reverse': 
	
	if(qp_check_confirm('".XT_QUICKPAY_CONFIRM_CANCEL."')){
		
		 dotransaction(action,b,s,id);
	}
       break;
	case 'refund': 
		if(qp_check_confirm('".XT_QUICKPAY_CONFIRM_REFUND."')){
		
		 dotransaction(action,b,s,id);
	}	
		break;		
	default:
		//;
} 

  }
 

function qp_check_confirm(confirm_text) {
        return confirm(confirm_text);
    }

function qp_check_capture(valbig, valsmall, amount_big, amount_small, confirm_text) {
				
        if (Number(amount_big) == Number(valbig) && Number(amount_small) == Number(valsmall)) {
            return true;
        } else {
            return confirm(confirm_text);
        }
    }


function dotransaction(action,b=0,s=0,id){
			
				var conn = new Ext.data.Connection();
				 conn.request({
				 url: '".$ajaxload."?qp_action='+action+'&amount_big='+b+'&amount_small='+s+'&id='+id,
				 method:'GET',
				 params: {".$ajaxgetdefault."},
				 success: function(responseObject) {
					 
					 $('#orderhistoryContainer".$this->oID." #qptoolsbox').html(responseObject.responseText);
					 qpinit();
						  }
				 });
				 
				 	} 
						
});

";
					
}


