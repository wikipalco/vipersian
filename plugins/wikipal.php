<?

	function gateway__wikipal($data)
	{
		$MerchantID 			= trim($data[merchant]);
		$Price 					= round($data[amount]/10);
		$Description 			= "تراکنش شماره ". $data[invoice_id];
		$InvoiceNumber 			= $data[invoice_id];
		$CallbackURL 			= $data[callback];

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'http://127.0.0.12/webservice/paymentRequest.php');
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type' => 'application/json'));
		curl_setopt($curl, CURLOPT_POSTFIELDS, "MerchantID=$MerchantID&Price=$Price&Description=$Description&InvoiceNumber=$InvoiceNumber&CallbackURL=". urlencode($CallbackURL));
		curl_setopt($curl, CURLOPT_TIMEOUT, 400);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$result = json_decode(curl_exec($curl));
		curl_close($curl);
		if ($result->Status == 100){
			header('Location: http://127.0.0.12/webservice/startPayment.php?au='. $result->Authority);
			echo '<meta http-equiv="refresh" content="0; url= http://127.0.0.12/webservice/startPayment.php?au='. $result->Authority .'">';
			exit;
		} else {
			echo $result->Status;
			$output[title] = 'خطای سیستم';
			$output[message] = '<font color="red">در اتصال به درگاه ویکی پال مشکلی پیش آمد دوباره امتحان کنید و یا به پشتیبانی خبر دهید</font>'. $result->Status .'<br /><a href="index.php" class="button">بازگشت</a>';
			return $output;
		}
	}
	

	function callback__wikipal($data)
	{
		$Status = $_POST['status'];
		$Refnumber = $_POST['refnumber'];
		$Resnumber = $_POST['resnumber'];
		
		
		$MerchantID 			= trim($data[merchant]);
		$Authority 				= $_POST['authority'];
		$InvoiceNumber 			= $_POST['InvoiceNumber'];
		
		$payment 				= mysql_fetch_array(mysql_query('SELECT * FROM `payment` WHERE `id` = "'.$InvoiceNumber.'" LIMIT 1;'));
		
		$Price 					= round($payment[amount]/10);
		
		if ($_POST['status'] == 1) {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, 'http://127.0.0.12/webservice/paymentVerify.php');
			curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type' => 'application/json'));
			curl_setopt($curl, CURLOPT_POSTFIELDS, "MerchantID=$MerchantID&Price=$Price&Authority=$Authority");
			curl_setopt($curl, CURLOPT_TIMEOUT, 400);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$result = json_decode(curl_exec($curl));
			curl_close($curl);
			if ($result->Status == 100) {
				mysql_query("UPDATE `payment` SET `status` = '1' WHERE `id` =".$payment[id]." LIMIT 1");
				$output[status]		= 1;
				$output[res_num]	= $Authority;
				$output[ref_num]	= $result->RefCode;
				$output[id] 		= $payment[id];
			} else {
				$output[status]	= 0;
				$output[message]= 'پرداخت ناموفق است. خطا';
			}
		} else {
			$output[status]	= 0;
			$output[message]= 'تراکنش لغو شده است';
		}
		return $output;
	}