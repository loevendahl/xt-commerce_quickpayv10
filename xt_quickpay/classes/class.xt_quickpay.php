<?php 
defined("_VALID_CALL") or exit( "Direct Access is not allowed." );

 include('QuickpayApi.php');

 
class xt_quickpay
{

    public $data = array();
    public $external = true;
    public $version = "1.0";
    public $qpsubpayments = true;
    public $iframe = false;
    private $orders_id = 0;
    public $position;
    public $qppayments;
	public $cardinfo;
	public $plink;
	public $subscription = "0";
 
		 
public function __construct(){
	global $db, $xtLink, $store_handler;
	
	  $this->ids= explode(",","3d-mastercard,3d-mastercard-debet,3d-visa,3d-visa-electron,3d-jcb,3d-maestro,american-express,dankort,diners,fbg1886,jcb,mastercard,mastercard-debet,mobilepay,visa,visa-electron,paypal,sofort,viabill,klarna");

       $this->names =  explode(",","3D Mastercard,3D Mastercard Debet,3D Visa,3D Visa debet,3D JCB,3D maestro,American express,Dankort (DK),Diners,Forbrugsforeningen af 1886 (DK),JCB,Mastercard,Mastercard debet,Mobilepay (DK),Visa,Visa debet,Paypal,Sofort,Viabill (DK),Klarna");
	
	    $this->qppayments = $this->setPayments();    
        $this->RETURN_URL = $xtLink->_link(array( "page" => "checkout", "paction" => "payment_process" ));
        $this->ERROR_URL = $xtLink->_link(array( "page" => "checkout", "paction" => "payment", "params" => "error=ERROR_PAYMENT" ));
        $this->CANCEL_URL = $xtLink->_link(array( "page" => "checkout", "paction" => "payment" ));
        $this->NOTIFY_URL = $xtLink->_link(array( "page" => "callback", "paction" => "xt_quickpay" ));
		
		$val = $db->Execute("SELECT config_value FROM " . TABLE_CONFIGURATION_PAYMENT . " WHERE config_key='XT_QUICKPAY_ACTIVATED_PAYMENTS' AND shop_id=?", array( $store_handler->shop_id ));
		$this->default_qpsubpayments = $val->fields["config_value"];
	//and for selection purpose
		$this->allowed_qpsubpayments = array_unique(array_filter(explode(",", $val->fields["config_value"])));	
	     
		 $val = $db->Execute("SELECT config_value FROM " . TABLE_CONFIGURATION_PAYMENT . " WHERE config_key='XT_QUICKPAY_API_USER_KEY' AND shop_id=?", array( $store_handler->shop_id ));
		$this->apiuser = $val->fields["config_value"];
		
		
        
		
		$this->trtype = "payments/";
		$this->trsearch = "payments?order_id=";
		
		$val = $db->Execute("SELECT config_value FROM " . TABLE_CONFIGURATION_PAYMENT . " WHERE config_key='XT_QUICKPAY_SHOP_TYPE' AND shop_id=?", array( $store_handler->shop_id ));
		
		if($val->fields["config_value"] && $val->fields["config_value"] == 1){
		$this->subscription = '1';	
		$this->trtype = "subscriptions/";
		$this->trsearch = "subscriptions?order_id=";	
		}
		
		$this->actionimages = "../plugins/xt_quickpay/images/";
		$this->orderprefix = "xt_".$store_handler->shop_id."_";
       
}

public function setPayments(){
	
$arr = array();
$ids= $this->ids;
$names =  $this->names;
	
	for($i=0; $i < count($ids); $i++){
		
	$arr[$ids[$i]] = array('name' => $names[$i],'icon' => $ids[$i].".png");
	
	}
		
		return $arr;
	}
public function dropdownAllPayments(){
	
$arr = array();
$ids= $this->ids;
$names =  $this->names;
	
	for($i=0; $i < count($ids); $i++){
		
	$arr[] = array('id' =>$ids[$i], 'name' => $names[$i]);
	
	}

		
		return $arr;


	}

public function dropdownshoptype(){
	
$arr = array();
         $arr[] = array('id' => '0', 'name' => 'standard');
         $arr[] = array('id' => '1', 'name' => 'subscriptions');

		
		return $arr;


}


    public function setPosition($pos)
    {
        $this->position = $pos;
    }

    public function _getParams()
    {
        return false;
    }

    public function build_payment_info($data)
    {
    }

    public function pspRedirect($processed_data = array())
    {
        global $xtLink;
        global $filter;
        global $db;
        global $countries;
        global $language;
		global $store_handler;
		
        $orders_id = (int) $_SESSION["last_order_id"];
        $rs = $db->Execute("SELECT customers_id FROM " . TABLE_ORDERS . " WHERE orders_id=?", array( $orders_id ));
        $order = new order($orders_id, $rs->fields["customers_id"]);
        
        
		$subpayment = $_SESSION["selected_payment_sub"];
       
	    $data = array();
		$data["continueurl"] = $this->RETURN_URL;
        $data["cancelurl"] = $this->CANCEL_URL;
        $data["callbackurl"] = $this->NOTIFY_URL;
		$data["language"] = $language->code;
		$data["order_id"] = "xt_".$store_handler->shop_id."_".$orders_id;
	    $data["amount"]   = ($order->order_total["total"]["plain"])*100;
        $data["vat_amount"] = ($order->order_total["tax"]["tax_value"]["plain"]? $order->order_total["tax"]["tax_value"]["plain"]: "");
		$data["currency"] = $order->order_data["currency_code"];
        $data["description"] = TEXT_ORDER_NUMBER . " " . $orders_id."\n".TEXT_ORDER_DATE.": ".$db->BindDate(time())."\n".TEXT_TOTAL . ": ".round($order->order_total["total"]["plain"], 2);
		$data["payment_methods"] = ($subpayment? $subpayment : $this->default_qpsubpayments);
		$data["subscription"] = $this->subscription;
        $data["version"] = "v10";
        $data["variables[firstname]"] = $order->order_data["delivery_firstname"];
        $data["variables[lastname]"] = $order->order_data["delivery_lastname"];
        $data["variables[address]"] = $order->order_data["delivery_street_address"];
        $data["variables[postal_code]"] = $order->order_data["delivery_postcode"];
        $data["variables[city]"] = $order->order_data["delivery_city"];
        $data["variables[state]"] = $order->order_data["delivery_state"];
		$data["variables[order]"] = $data["description"];
        if( !is_object($countries) ) 
        {
            $countries = new countries("true");
        }

        $data["variables[country]"] = $order->order_data["billing_country_code"];
		

		//order exists?if so return quickpay transaktion id
		$trid = $this->qpstatus($data["order_id"]);
		
		//reset mode
		 $api= new QuickpayApi();
         $api->setOptions($this->apiuser);
		 $api->mode = $this->trtype;
		
		if(!$trid){
        //create new quickpay order	
        $prepare = $api->createorder($data["order_id"],$data["currency"],$data);
      
		$trid = $prepare["id"];
		}
		//create/update qp link	
		$prepare = $api->link($trid, $data);
	
		if( !$prepare["url"]) 
        {
            global $xtLink;
         $xtLink->_redirect($this->ERROR_URL);
        }

     $db->Execute("UPDATE " . TABLE_ORDERS . " SET orders_data=? WHERE orders_id=?", array($trid, $orders_id ));
   
	 
	return $prepare["url"];
    }

public function qpstatus($o){		


  try {
	$api= new QuickpayApi();
    $api->setOptions($this->apiuser); 
	$api->mode = $this->trsearch;

    // Commit the status request, checking valid transaction id
    $st = $api->status($o);
	if($st[0]["id"]){
	return $st[0]["id"];
	}else{
	return false;	
	}
  
  } catch (Exception $e) {
    return $e->getMessage();
	
  }
  
}
    public function pspSuccess()
    {
        return true;
    }


    public function saved_QP_ACTIVATED_PAYMENTS($data)
    {
        
        global $db, $store_handler;
 
        $obj = new stdClass();
        $obj->topics = array();
        $obj->totalCount = 0;
        $val = $db->GetOne("SELECT config_value FROM " . TABLE_CONFIGURATION_PAYMENT . " WHERE config_key='XT_QUICKPAY_ACTIVATED_PAYMENTS' AND shop_id=?", array( $store_handler->shop_id ));
        if( $val ) 
        {
            $array = explode(",", $val);
            if( !empty($array) ) 
            {
                foreach( $array as $key ) 
                {
                    $value = $this->qppayments[$key];
                    $obj->topics[] = array( "id" => $key, "name" => $value["name"], "desc" => "" );
                }
            }

        }

        $obj->totalCount = count($obj->topics);
        return json_encode($obj);
    }

    public function QuickpayActivated()
    {
        global $db;
        static $r = NULL;
        if( $r === NULL ) 
        {
            $r = $db->GetOne("SELECT status FROM " . TABLE_PAYMENT . " WHERE payment_code='xt_quickpay'");
            $r = (empty($r) ? false : true);
        }

        return $r;
    }

    public function getQuickpayPaymentId()
    {
        global $db;
        static $r = NULL;
        if( $r === NULL ) 
        {
            $r = $db->GetOne("SELECT payment_id FROM " . TABLE_PAYMENT . " WHERE payment_code='xt_quickpay'");
            $r = (empty($r) ? false : true);
        }

        return $r;
    }

    public function setOrdersId($orders_id)
    {
        $this->orders_id = $orders_id;
    }

    public function _addCallbackLog($log_data)
    {
        global $db;
        $log_data["module"] = "xt_quickpay";
        $log_data["orders_id"] = (isset($this->orders_id) ? $this->orders_id : 0);
        if( is_array($log_data["callback_data"]) ) 
        {
            $log_data["callback_data"] = serialize($log_data["callback_data"]);
        }

        if( is_array($log_data["error_data"]) ) 
        {
            $log_data["error_data"] = serialize($log_data["error_data"]);
        }

        if( $log_data["error_data"] == "" ) 
        {
            $log_data["error_data"] = "";
        }

        $db->AutoExecute(TABLE_CALLBACK_LOG, $log_data, "INSERT");
    }


public function setstatusinfo($info){
	
	$this->statusinfo = $info;
	
}

public function tools(){
	
	if(!$this->apiuser){
		return "Please set API User key";
		
	}
	
	$api= new QuickpayApi();
    $api->setOptions($this->apiuser); 
	$api->mode = $this->trsearch;
	
	
	 try {

  $statusinfo = $api->status($this->orderprefix.$this->orders_id); 

  $ostatus['amount'] = $statusinfo[0]["operations"][0]["amount"];
  $ostatus['balance'] = $statusinfo[0]["balance"];
  $ostatus['currency'] = $statusinfo[0]["currency"];
  
  //get the latest operation
  $operations= array_reverse($statusinfo[0]["operations"]);
  
  $amount = $operations[0]["amount"];
  $ostatus['qpstat'] = $operations[0]["qp_status_code"];

  $ostatus['type'] = $operations[0]["type"];
  $resttocap = $ostatus['amount'] - $ostatus['balance'];
  $resttorefund = $statusinfo[0]["balance"];
  $allowcapture = ($operations[0]["pending"] == false ? true : false);
  $allowcancel = true;
  $testmode = $statusinfo[0]["test_mode"];
  $type = $statusinfo[0]["type"];
  $id = $statusinfo[0]["id"];


  $qp_aq_status_code = $statusinfo[0]["aq_status_code"];
  $qp_aq_status_msg = $statusinfo[0]["aq_status_msg"];
  
 
  $qp_cardtype = $statusinfo[0]["metadata"]["brand"];
  $qp_cardhash_nr = $statusinfo[0]["metadata"]["hash"];
  $qp_status_msg = $statusinfo[0]["operations"][0]["qp_status_msg"]."\n"."Cardhash: ".$qp_cardhash_nr."\n";
  $qp_cardnumber = "xxxx-xxxxxx-".$statusinfo[0]["metadata"]["last4"];
  $qp_exp = $statusinfo[0]["metadata"]["exp_month"]."-".$statusinfo[0]["metadata"]["exp_year"];

  $cinfo = "<ul>";
  $cinfo .= "<li>Info: <b>Type:</b> ".$qp_cardtype.($testmode ? " (TESTCARD)" : "")."</li>";
  $cinfo .= "<li><b>Nr:</b> ".$qp_cardnumber."</li>";
  $cinfo .= "<li><b>Exp.:</b> ".$qp_exp."</li>";
  $cinfo .= "<li><b>#:</b> ".$qp_cardhash_nr."</li>";
  $cinfo .= "</ul>";
  $this->cardinfo = $cinfo;

 
  //settings for split payments and split refunds
  if(($ostatus['type'] == "capture" ) ){
				
					$allowcancel = false;
	  }
    if(($ostatus['type'] == "refund" ) ){
			         
					$resttocap = 0;
	  }
	  
	
  $ostatus['time'] = $operations[0]["created_at"];
  $ostatus['qpstatmsg'] = $operations[0]["qp_status_msg"];
   
   
   //reset mode
  //  $api->mode = $this->trtype;

} catch (Exception $e) {
  $error = $e->getCode(); // The code is the http status code
  $error .= $e->getMessage(); // The message is json

}

 if ($ostatus['qpstat'] == 20000 && $this->trtype == "subscriptions/" ) {
	return ($error ? $error : 'Subscription transaction admin and subscription product handling is not implemented in this standard version. Please contact developer at <a href=\"mailto:kl@blkom.dk\">kl@blkom.dk</a> for implementation. Use the Quickpay manager for subscription handling');
} 


if ($ostatus['qpstat'] == 20000 && $this->trtype == "payments/" ) {
	 
	   $formatamount= explode(',',number_format($amount/100,2,',',''));
	    $amount_big = $formatamount[0];
        $amount_small = $formatamount[1];
	  
	  
	    switch ($ostatus['type']) {
		
            case 'authorize': // Authorized
			
			    if($allowcapture){
				$out .= '<div>';
                $out .= '<form id="capture_form'.$this->orders_id.'" name="capture_form'.$this->orders_id.'" method="get" action="capture" >';
				$out .= '<input type="text" name="amount_big" value="'.$amount_big.'" size="11" style="text-align:right" />';
                $out .= ' , ';
                $out .= '<input type="text" name="amount_small" value="'.$amount_small.'" size="3" maxlength="2" />' . ' ' . $ostatus['currency'] . '&nbsp;&nbsp;';
			
			    $out .= '<a id="'.$id.'" class="actionbutton" valbig="' . $amount_big.'" valsmall="' . $amount_small.'" name="capture" href="#"><img src="'.$this->actionimages.'icon_transaction_capture.gif" title="capture" style="margin-left:5px;" /></a>';

		        }else{
	            $out .= TEXT_XT_QUICKPAY_PENDING_STATUS;
	
                }   
			   
			     
			    $out .= '</form>';
              	$out .= '</div>';
			    			
				
				if($allowcancel){
			$out .= '<div>';
			$out .= '<form id="reverse_form'.$this->orders_id.'" name="reverse_form'.$this->orders_id.'" >';	
			$out .= '<a id="'.$id.'" class="actionbutton" name="reverse" href="#"><img src="'.$this->actionimages.'icon_transaction_reverse.gif" title="cancel"  /></a>';
			$out .= '</form>';
			$out .= '</div>';	
				}
                
         
                break;
            case 'capture': // Captured or refunded
			case 'refund':
			
				if($resttocap > 0 ){
		$formatamount= explode(',',number_format($resttocap/100,2,',',''));
	    $amount_big = $formatamount[0];
        $amount_small = $formatamount[1];
		        $out .= '<div>';
				$out .= TEXT_XT_QUICKPAY_CAPTURE;
                $out .= '<form id="capture_form'.$this->orders_id.'" name="capture_form'.$this->orders_id.'" method="get" action="capture" >';
                $out .= '<input type="text" name="amount_big" value="'.$amount_big.'" size="11" style="text-align:right" >';
                $out .= ' , ';
                $out .= '<input type="text" name="amount_small" value="'.$amount_small.'" size="3" maxlength="2" >' . ' ' . $ostatus['currency'] . '&nbsp;&nbsp;';
			  
				if($allowcapture){		
				
         	   	$out .= '<a id="'.$id.'" class="actionbutton" valbig="' . $amount_big.'" valsmall="' . $amount_small.'" name="capture" href="#"  ><img src="'.$this->actionimages.'icon_transaction_capture.gif" title="capture" /></a>';
		               }else{
	                    echo TEXT_XT_QUICKPAY_PENDING_STATUS;
	
                          } 
				
				
			  $out .= '</form>';      
           $out .= "</div><br>";
             
				}
		$formatamount= explode(',',number_format($resttorefund/100,2,',',''));
	    $amount_big = $formatamount[0];
        $amount_small = $formatamount[1];	
			
			if($resttorefund > 0){
	          
			    $out .= '<div>';
			    $out .= TEXT_XT_QUICKPAY_REFUND;	
                $out .=  '<form id="refund_form'.$this->orders_id.'"  name="refund_form'.$this->orders_id.'" action="refund" >';
			    $out .= '<input type="text" name="amount_big" value="'.$amount_big.'" size="11" style="text-align:right" >';
                $out .= ' , ';
                $out .= '<input type="text" name="amount_small" value="'.$amount_small.'" size="3" maxlength="2" >' . ' ' . $ostatus['currency'] . '&nbsp;&nbsp;';
			
                $out .= '<a id="'.$id.'" class="actionbutton" valbig="' . $amount_big.'" valsmall="' . $amount_small.'" name="refund" href="#" ><img src="'.$this->actionimages.'icon_transaction_credit.gif" title="refund"  /></a>';
                $out .= '</form>';
			    $out .= '</div>';
			}else{
	            $out .= '<div>';
				$out .= TEXT_XT_QUICKPAY_AMOUNT_REFUNDED;
				$out .=  '<form>';
				$out .= '<input type="text" name="amount_big" value="'.$amount_big.'" size="11" style="text-align:right" disabled>';
                $out .= ' , ';
                $out .= '<input type="text" name="amount_small" value="'.$amount_small.'" size="3"  disabled>' . ' ' . $ostatus['currency'] . '&nbsp;&nbsp;';
				$out .= '<img src="'.$this->actionimages.'icon_transaction_capture_grey.gif" title="capture"  />';
                $out .= '<img src="'.$this->actionimages.'icon_transaction_reverse_grey.gif", title="cancel" />';
				$out .= '</form>';
			    $out .= '</div>';
			}

				
				break;
          case 'cancel': // Reversed
		  
		  
                $amount_big = $formatamount[0];
                $amount_small = $formatamount[1];
				 $out .= '<div>';
				$out .= TEXT_XT_QUICKPAY_AMOUNT_CREDITED;
				$out .=  '<form>';
				$out .= '<div style="float:left;" >';
                $out .= '<input type="text" name="amount_big" value="'.$amount_big.'" size="11" style="text-align:right" disabled>';
                $out .= ' , ';
                $out .= '<input type="text" name="amount_small" value="'.$amount_small.'" size="3" disabled>' . ' ' . $ostatus['currency'] . '&nbsp;&nbsp;';
                $out .= '</div>';
				$out .= '<img src="'.$this->actionimages.'icon_transaction_capture_grey.gif" title="capture" style="float:left" />';
                $out .= '<img src="'.$this->actionimages.'icon_transaction_reverse_grey.gif", title="cancel" style="float:left" />';
                $out .=  '<form>';
			 $out .= '</div>';
                break;
            default:
                $this->plink = $statusinfo[0]["link"]["url"];
				$out .= '<font color="red">' .$statustext[$ostatus['type']].' ('. $ostatus['qpstatmsg'] . ')</font>';
                break;
        }// end case
    }//end if

//info set from ajax call to actions?
($this->statusinfo ? $out .= '<br><div class="statusinfo" >Status: '.$this->statusinfo.'</div>' : '');
//always
$this->setstatusinfo($api->log_operations($operations, $ostatus['currency']));
$link = ($this->plink ? '<div class="statusinfo" >Payment link: <a target="_blank" href="'.$this->plink.'" >'.$this->plink.'</a></div>' : '');
$out .= ($this->statusinfo ? '<br><div class="statusinfo" >Operations:'.$this->statusinfo.'</div>'.$link : '');
	


return ($error ? $error : $out);
	

	
}//end payment tools


public function get_transaction_actions(){
	//called by ajax , adminhandler

    $this->setOrdersId($_GET['orders_id']);	
	
$qpaction= $_GET['qp_action'];
$id = $_GET['id'];
// convert amount from local currency-format to required quickpay format 
$amount = $_GET['amount_big'] . sprintf('%02d', $_GET['amount_small']);

if (isset($qpaction)) {
   
    switch ($qpaction) {

        case 'reverse':

            $result = $this->get_quickpay_reverse($id);

            break;
        case 'capture':
            
            $result = $this->get_quickpay_capture($id, $amount);

            break;
        case 'refund':
           
            $result = $this->get_quickpay_credit($id, $amount);

            break;

    }

        $this->setstatusinfo($result);
		
		return $this->tools();
}

}

function json_message($input){
	
	$dec = json_decode($input,true);
	
	$message= $dec["message"];
	//get last error
	$text = $dec["errors"]["amount"][0];
	return $message. " amount ".$text;
	
	
}
 
 

function get_quickpay_reverse($id) {
   

  try {
	  $qpapi = new QuickpayApi;
	  $qpapi->setOptions($this->apiuser); 
    
	// Commit
     $eval = $qpapi->cancel($id);
      $result = ' QuickPay Reverse ';
    if ($eval) {
		$operations = array_reverse($eval["operations"]);
 
          // The reversal was completed
          $result .= ' OK: ' . $operations[0]["qp_status_msg"];
          $result .= ' : ' . number_format($amount/100,2,',','.')." ".$eval["currency"];
    }
  
  
  } catch (Exception $e) {
      		// An error occured with the reversal
         $result .= 'Failure: ' . json_message($e->getMessage()) ;
		    $log_data = array();
            $log_data["class"] = "error_quickpay";
            $log_data["transaction_id"] = $id;
            $log_data["error_msg"] = $result;
            $this->_addCallbackLog($log_data);
        
  }

   return $result;

  }  

  
  function get_quickpay_capture($id, $amount) {
   

  try {
    $qpapi = new QuickpayApi;
	$qpapi->setOptions($this->apiuser);  

    // Commit 
    $eval = $qpapi->capture($id,$amount);
      $result = ' QuickPay Capture ';
    
	if ($eval) {
		$operations= array_reverse($eval["operations"]);
          // The capture was completed
          $result .= 'OK: ' . $operations[0]["qp_status_msg"];
          $result .= ' : ' . number_format($amount/100,2,',','.')." ".$eval["currency"];
 
    }
  
  
  } catch (Exception $e) {
		         // An error occured with the capture
          $result .= 'Failure: ' . $this->json_message($e->getMessage()) ;
            $log_data = array();
            $log_data["class"] = "error_quickpay";
            $log_data["transaction_id"] = $id;
            $log_data["error_msg"] = $result;
            $this->_addCallbackLog($log_data);
  }
 

return $result;
  
  }  

  
  function get_quickpay_credit($id, $amount) {
    
    try {
    $qpapi = new QuickpayApi;
	$qpapi->setOptions($this->apiuser);  

      // Commit 
      $eval = $qpapi->refund($id, $amount);
    $result = ' QuickPay Credit ';
      if ($eval) {
		$operations= array_reverse($eval["operations"]);
 
            // The credit was completed
            $result .= 'OK: ' . $operations[0]["qp_status_msg"];
			$result .= ' : ' . number_format($amount/100,2,',','.')." ".$eval["currency"];
          

      }
   
    } catch (Exception $e) {
     
	   // An error occured with the credit
            $result .= ' Failure: ' . json_message($e->getMessage());
            $log_data = array();
            $log_data["class"] = "error_quickpay";
            $log_data["transaction_id"] = $id;
            $log_data["error_msg"] = $result;
            $this->_addCallbackLog($log_data);
     
    }    
       return $result;
  }






}