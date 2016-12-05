<?php
/*
 #########################################################################
 #                       xt:Commerce 5 Shopsoftware
 # ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 #
 # Copyright 2007-2016 xt:Commerce International Ltd. All Rights Reserved.
 # This file may not be redistributed in whole or significant part.
 # Content of this file is Protected By International Copyright Laws.
 #
 # ~~~~~~ xt:Commerce 5 Shopsoftware IS NOT FREE SOFTWARE ~~~~~~~
 #
 # http://www.xt-commerce.com
 #
 # ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 #
 # @copyright xt:Commerce International Ltd., www.xt-commerce.com
 #
 # ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 #
 # xt:Commerce International Ltd., Kafkasou 9, Aglantzia, CY-2112 Nicosia
 #
 # office@xt-commerce.com
 #
 #########################################################################
 */

defined('_VALID_CALL') or die('Direct Access is not allowed.');


class callback_xt_quickpay extends callback {

	var $version = '1.0';
 
 function sign($base, $private_key) {
  return hash_hmac("sha256", $base, $private_key);
}

	function process() {
		global $filter;
//is this a callback? from Quickpay?
$request_body = file_get_contents("php://input");
$checksum     = $this->sign($request_body, XT_QUICKPAY_PRIVATE_KEY);
	     
if ($checksum == $_SERVER["HTTP_QUICKPAY_CHECKSUM_SHA256"]) {
	 $this->data = json_decode($request_body,true);
	 
	 if (!is_array($this->data)) return;
		
		

	//get the latest status
	$this->data["operations"] = array_reverse($this->data["operations"]);

	$this->qpstatus = $this->data["operations"][0]["qp_status_code"];


     $response = $this->_callbackProcess();
	 
	 if ($response->repost) {
			header('HTTP/1.0 404 Not Found');
		} else {
			header("HTTP/1.0 200 OK");
		}
 
 }else{
	 	
			$this->Error = '1004';
			$log_data['module'] = 'xt_quickpay';
			$log_data['class'] = 'error';
			$log_data['error_msg'] = 'Checksum sha256 check failed';
			$this->_addLogEntry($log_data);
	
	 
	 
 }


	}


	function _callbackProcess() {

		if ($this->log_callback_data == true) {
		$log_data = array();
		$log_data['module'] = 'xt_quickpay';
		$log_data['class'] = 'callback_data';
		$log_data['transaction_id'] = $this->data['id'];
		$log_data['callback_data'] = serialize($this->data);
	    $this->_addLogEntry($log_data);
		}

		// order ID already inserted ?
		$err = $this->_getOrderID();
		if (!$err)
		return false;

//subscription handling - insert and capture first payment
if(strtolower($this->data['type']) == "subscription"){
	$apikey = XT_QUICKPAY_API_USER_KEY;
	include_once _SRV_WEBROOT._SRV_WEB_PLUGINS.'xt_quickpay/classes/class.xt_quickpay.php';
	
	$apiorder= new QuickpayApi();
	$apiorder->setOptions($apikey);

	$apiorder->mode = "subscriptions/";
	$addlink = $this->data['id']."/recurring/";
	
	  //create new quickpay recurring order
	  $process_parameters["amount"]= $this->data["operations"][0]['amount'];
	  $process_parameters["order_id"]= $this->data['order_id']."-".time();
	  $process_parameters["auto_capture"]= "1";	
      $storder = $apiorder->createorder($process_parameters["order_id"], $this->data['currency'], $process_parameters, $addlink);
	
}
		$this->_setStatus();

	}



	/**
	 * Find order ID for given transaction_id
	 *
	 * @return boolean
	 */
	function _getOrderID() {
		global $db;

		$order_query = "SELECT orders_id,customers_id FROM ".TABLE_ORDERS." WHERE orders_data = ?";
		$rs = $db->Execute($order_query, array($this->data['id']));

		if ($rs->RecordCount() == 1) {
			$this->orders_id = $rs->fields['orders_id'];
			$this->customers_id = $rs->fields['customers_id'];
			return true;
		}

		$log_data = array();
		$log_data['module'] = 'xt_quickpay';
		$log_data['class'] = 'error';
		$log_data['error_msg'] = 'order id not found';
		$log_data['error_data'] = serialize(array('transaction_id'=>$this->data['id']));
		$this->_addLogEntry($log_data);
		$this->repost = true;
		return false;

	}

	function _setStatus() {


		switch ($this->qpstatus) {

			// processed or pending
			case 20000 :
			$status = XT_QUICKPAY_PROCESSED;
			
			if($this->data["operations"][0]["pending"] == true){
			$status = XT_QUICKPAY_PENDING;
			}
				
				$log_data = array();
				$log_data['orders_id'] = $this->orders_id;
				$log_data['module'] = 'xt_quickpay';
				$log_data['class'] = 'success';
				$log_data['transaction_id'] = $this->data['id'];
				$log_data['callback_data'] = array('message'=>'OK','error'=>'200','transaction_id'=>$this->data['id']);
				$txn_log_id = $this->_addLogEntry($log_data);
				break;

				// canceled
			case 40000 :
			case 40001 :
				$status = XT_QUICKPAY_CANCELED;
				$log_data = array();
				$log_data['orders_id'] = $this->orders_id;
				$log_data['module'] = 'xt_quickpay';
				$log_data['class'] = 'success';
				$log_data['transaction_id'] = $this->data['id'];
				$log_data['callback_data'] = array('message'=>'FAILED','error'=>'999','transaction_id'=>$this->data['id']);
				$txn_log_id = $this->_addLogEntry($log_data);
				break;
             
			 default:
			    $status = XT_QUICKPAY_CANCELED;
				$log_data = array();
				$log_data['orders_id'] = $this->orders_id;
				$log_data['module'] = 'xt_quickpay';
				$log_data['class'] = 'success';
				$log_data['transaction_id'] = $this->data['id'];
				$log_data['callback_data'] = array('message'=>'FAILED','error'=>'999','transaction_id'=>$this->data['id']);
				$txn_log_id = $this->_addLogEntry($log_data);
		
             break;
		}

		// update order status
		$this->_updateOrderStatus($status,'true',$txn_log_id);
	}
}