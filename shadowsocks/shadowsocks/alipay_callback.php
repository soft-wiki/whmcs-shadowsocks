<?php
# Required File Includes
include("../../../dbconnect.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");
require_once("./alipay.class.php");
$gatewaymodule = "alipay"; # Enter your gateway module name here replacing template
$GATEWAY = getGatewayVariables($gatewaymodule);
if (!$GATEWAY["type"]) die("Module Not Activated"); # Checks gateway module is active before accepting callback
$need_confirm=$GATEWAY['need_confirm'];
$auto_send=$GATEWAY['auto_send'];
$debug=$GATEWAY['debug'];

$alipay_config['input_charset']= "utf-8";
$alipay_config['sign_type']    = "MD5";
$alipay_config['transport']    = $GATEWAY['ssl'] ? "https" :"http";
$alipay_config['partner']      = $GATEWAY['partnerID'];
$alipay_config['key']          = $GATEWAY['security_code'];
$alipay_config['seller_email'] = $GATEWAY['seller_email'];
$alipay_config['cacert']       = getcwd().'/cacert.pem';

$alipayNotify = new AlipayNotify($alipay_config);
$alipayNotify->debug=$debug;
$verify_result = $alipayNotify->verifyNotify();
if ($debug) logResult(serialize($_POST));
if(!$verify_result) { 
	logTransaction($GATEWAY["name"],$_POST,"Unsuccessful1");
	exit;
}
# Get Returned Variables
$status    = $_POST['trade_status'];    //获取支付宝传递过来的交易状态
$invoiceid = $_POST['out_trade_no']; //获取支付宝传递过来的订单号
$transid   = $_POST['trade_no'];       //获取支付宝传递过来的交易号
$amount    = $_POST['total_fee'];       //获取支付宝传递过来的总价格
$fee       = 0;

if($status == 'TRADE_FINISHED' || $status == 'TRADE_SUCCESS') { //交易完成  直接入帐
	if ($debug) logResult("订单 $invoiceid  支付成功.");
	$invoiceid = checkCbInvoiceID($invoiceid,$GATEWAY["name"]); # Checks invoice ID is a valid invoice number or ends processing
	checkCbTransID($transid);
	addInvoicePayment($invoiceid,$transid,$amount,$fee,$gatewaymodule);
	logTransaction($GATEWAY["name"],$_POST,"Successful-A");
}
elseif($status == 'WAIT_BUYER_CONFIRM_GOODS' ){  //已付款,已发货,等确认收货=>判断是否入帐
	if ($debug) logResult("订单 $invoiceid  等待收货.");
	if (!$need_confirm){
		$invoiceid = checkCbInvoiceID($invoiceid,$GATEWAY["name"]); # Checks invoice ID is a valid invoice number or ends processing
		checkCbTransID($transid);
		addInvoicePayment($invoiceid,$transid,$amount,$fee,$gatewaymodule);
		logTransaction($GATEWAY["name"],$_POST,"Successful-B");
		if ($debug) logResult("订单 $invoiceid  直接入帐.");
	}
}
elseif($status == 'WAIT_SELLER_SEND_GOODS' ){     //已付款.未发货   => 自动发货+判断是否入帐
	if ($debug) logResult("订单 $invoiceid  已收款.准备发货");
	if ($auto_send){
		$parameter = array(
			"service"			=> "send_goods_confirm_by_platform",
			"partner"			=> $GATEWAY['partnerID'],
			"_input_charset"	=> trim(strtolower($alipay_config['input_charset'])),
			"trade_no"			=> $transid,
			"logistics_name"	=> "AUTO_WHMCS",
			"invoice_no"		=> $invoiceid,
			"transport_type"	=> "EXPRESS"
		);
		//自动发货
		$alipaySubmit = new AlipaySubmit($alipay_config);
		$html_text = $alipaySubmit->buildRequestHttp($parameter);
		if ($debug) logResult("订单 $invoiceid 发货信息:".$html_text);
	}
	
}

?>