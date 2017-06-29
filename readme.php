<?php

namespace mobileapi\Controller;

use Think\Controller;

/*京东支付*/

class PayJdController extends Controller
{
		//四要素申请接口
		public function jd_apply(){
			$conf_arr = $this->conf_arr();
			$arr['orderNo'] = payJDSn();//业务方系统中订单编号，最长50个字符
			$arr['version'] = '1.0';
			$arr['charset'] = 'UTF-8';
			$arr['time'] = $_SERVER['REQUEST_TIME'];

			//data 数据
			$data['name']=   '';   //姓名
			$data['cardNum'] =     //身份证号码
			$data['bankCardNo'] =  //银行卡号
			$data['mobile'] =  //预留电话
			$data['bankCardType'] = 'D';//借贷标示(信用卡“C”,借记卡“D”)
			$data['bankCode'] = //发卡行(例如CMB，统一银行编码)
			$data_json = json_encode($data);

			import("@.Think.CryptDes");

			$des = new \CryptDes("abcdefgh","");//（秘钥向量，混淆向量）

			$arr['data'] = $des->encrypt("$data_json");//加密字符串
			var_dump($arr['data']);
			$arr['checkSign'] = md5($arr['version'].$arr['charset'].$arr['time'].$arr['orderNo'].$arr['data']);//将version+charset+time +orderNo+data这几个数据连接在一起使用MD5运算（通用MD5）获得签名sign数据，data参数为加密后参数

			$data_json = json_encode($arr);

			var_dump($data_json);
			$ch = curl_init();//初始化curl

			curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_setopt($ch, CURLOPT_URL,$conf_arr['apply']);//抓取指定网页
			curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type=application/json;","charset=utf-8","merchantNo=$conf_arr[merchant];"));//头部设置
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
			curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);

			$data = curl_exec($ch);//运行curl

			if($data === false)

			{
				return  'Curl error: ' . curl_error($ch);
			}


			return $data;
			$date_feedback = $des->decrypt("$data[data]");//解密字符串
			var_dump($date_feedback);
			$date_feedback = json_decode($date_feedback,true);
			$date_feedback['token'];//接口返回唯一识别码
			$date_feedback['code'];//参见结果返回码
			$date_feedback['msg'];//结果返回描述


		}

		public function  jd_confirm(){
			$conf_arr = $this->conf_arr();
			$arr['orderNo'] = '';//业务方系统中订单编号，最长50个字符
			$arr['version'] = '1.0';//版本号
			$arr['charset'] = 'UTF-8';//字符编码
			$arr['time'] = $_SERVER['REQUEST_TIME'];//时间戳
			$data['verfyCode'] = '';//短信验证码
			$data['token'] = '';//唯一标识
			$data_json = json_encode($data);
			import("@.Think.CryptDes");
			$des = new \CryptDes("abcdefgh","");//（秘钥向量，混淆向量）
			$arr['data'] = $des->encrypt("$data_json");//加密字符串
			$arr['checkSign'] = $arr['version'].$arr['charset'].$arr['time'].$arr['orderNo'].$arr['data'];//数据签名
			var_dump($arr);

			$ch = curl_init();//初始化curl

			curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_setopt($ch, CURLOPT_URL,$conf_arr['confirm']);//抓取指定网页
			curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type=application/json;","charset=utf-8","merchantNo=$conf_arr[merchant];"));//头部设置
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
			curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);

			$data = curl_exec($ch);//运行curl

			if($data === false)

			{
				return  'Curl error: ' . curl_error($ch);
			}

			var_dump($data);
			return $data;
			$date_feedback = $des->decrypt("$data[data]");//解密字符串
			$date_feedback = json_decode($date_feedback,true);
			$date_feedback['code'];//参见结果返回码
			$date_feedback['msg'];//结果返回描述



		}
		/*配置信息*/
		private function conf_arr()
		{	

			return array(
				'version'=>'1.0.0',
				'terminal'=>'00000001',
				'merchant'=>'110223219002', //商户号
				'des'=>'MdZX4wsspE/7zgd/pD5XgJebq62/Fv6R',
				'md5'=>'BhXkhurdghnE3fmnZgxGxmpWd1ehulet',
				'url'=>'https://wapi.jd.com/express.htm',
				'apply'=>'https://credit-trade.jdpay.com/api/bank/apply',//四要素申请接口
				'confirm'=>'https://credit-trade.jdpay.com/api/bank/confirm',//四要素确认接口
				);

		}

	
		/*签约(充值时向用户发送短信)*/
	   public  function getPhoneCode($card_bank,$card_no,$card_name,$card_idno,$card_phone,
							$trade_id,$trade_amount){

	   	$card_idtype='I';   //持卡人证件类型 身份证：I
	   	$card_type='D';   //持卡人支付卡号卡类型 信用卡：C / 借记卡：D
		$card_exp='';     //持卡人信用卡有效期
		$card_cvv2='';    //持卡人信用卡校验码
		$trade_currency='CNY'; //交易币种 人民币：CNY
		$trade_type='V';

		$v_data = '<?xml version="1.0" encoding="UTF-8"?>'.
					'<DATA>'.
						'<CARD>'.
							'<BANK>'.$card_bank.'</BANK>'.
							'<TYPE>'.$card_type.'</TYPE>'.
							'<NO>'.$card_no.'</NO>'.
							'<EXP>'.$card_exp.'</EXP>'.
							'<CVV2>'.$card_cvv2.'</CVV2>'.
							'<NAME>'.$card_name.'</NAME>'.
							'<IDTYPE>'.$card_idtype.'</IDTYPE>'.
							'<IDNO>'.$card_idno.'</IDNO>'.
							'<PHONE>'.$card_phone.'</PHONE>'.
						'</CARD>'.
						'<TRADE>'.
							'<TYPE>'.$trade_type.'</TYPE>'.
							'<ID>'.$trade_id.'</ID>'.
							'<AMOUNT>'.$trade_amount.'</AMOUNT>'.
							'<CURRENCY>'.$trade_currency.'</CURRENCY>'.
						'</TRADE>'.
					'</DATA>';

		return  $this->trade($v_data);
	}


	public 	function topUpJd($card_bank,$card_no,$card_name,
								$card_idno,$card_phone,
								$trade_id,$trade_amount,$trade_code){
		$trade_date=date('Ymd'); //日期 *
		$trade_time=date('His'); //时间 *
		$trade_notice=''; //通知地址  如果填写，则异步发送结果通知到指定地址） 
		$trade_note='我要消费'; //备注

		$card_idtype='I';   //持卡人证件类型 身份证：I
	   	$card_type='D';   //持卡人支付卡号卡类型 信用卡：C / 借记卡：D
		$card_exp='';     //持卡人信用卡有效期
		$card_cvv2='';    //持卡人信用卡校验码
		$trade_currency='CNY'; //交易币种 人民币：CNY
		$trade_type='S';

		$v_data = '<?xml version="1.0" encoding="UTF-8"?>'.
					'<DATA>'.
						'<CARD>'.
							'<BANK>'.$card_bank.'</BANK>'.
							'<TYPE>'.$card_type.'</TYPE>'.
							'<NO>'.$card_no.'</NO>'.
							'<EXP>'.$card_exp.'</EXP>'.
							'<CVV2>'.$card_cvv2.'</CVV2>'.
							'<NAME>'.$card_name.'</NAME>'.
							'<IDTYPE>'.$card_idtype.'</IDTYPE>'.
							'<IDNO>'.$card_idno.'</IDNO>'.
							'<PHONE>'.$card_phone.'</PHONE>'.
						'</CARD>'.
						'<TRADE>'.
							'<TYPE>'.$trade_type.'</TYPE>'.
							'<ID>'.$trade_id.'</ID>'.
							'<AMOUNT>'.$trade_amount.'</AMOUNT>'.
							'<CURRENCY>'.$trade_currency.'</CURRENCY>'.
							'<DATE>'.$trade_date.'</DATE>'.
							'<TIME>'.$trade_time.'</TIME>'.
							'<NOTICE>'.$trade_notice.'</NOTICE>'.
							'<NOTE>'.$trade_note.'</NOTE>'.
							'<CODE>'.$trade_code.'</CODE>'.
						'</TRADE>'.
					'</DATA>';
		return  $this->trade($v_data);
	}



/**
		 * 发起快捷支付方法
		 * @param $data_xml交易的xml格式数据
		 */
		private function trade($data_xml)
		{	
			
			$config = $this->conf_arr();

			$dataDES = $this->encrypt($data_xml);

			$sign = md5($config['version'].$config['merchant'].$config['terminal'].$dataDES.$config['md5']);
			
			$xml = $this->xml_create($config['version'],$config['merchant'],$config['terminal'],$dataDES,$sign);
			//使用方法
			$param ='charset=UTF-8&req='.urlencode(base64_encode($xml));

			$resp = request_post($config['url'],$param);

			return $resp;
		}



		/*创建支付xml*/
		private	function xml_create($version,$merchant,$terminal,$data,$sign)
		{
			$xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><chinabank/>');
			$xml->addChild('version',$version);
			$xml->addChild('merchant',$merchant);
			$xml->addChild('terminal',$terminal);
			$xml->addChild('data',$data);
			$xml->addChild('sign',$sign);
			return $xml->asXML();
		}


		/**
		 * @param $resp 网银在线返回的数据
		 * 数据的解析步骤：
		 * 1：截取resp=后面的xml数据
		 * 2: base64解码
		 * 3: 验证签名
		 * 4: 解析交易数据处理自己的业务逻辑
		 */
		public function operate($resp)
		{
			$config =$this->conf_arr();

			$temResp = base64_decode(substr($resp,5));
			$xml = simplexml_load_string($temResp);
			//验证签名, version.merchant.terminal.data
			$text = $xml->VERSION.$xml->MERCHANT.$xml->TERMINAL.$xml->DATA;


			$md5Text=md5($text.$config['md5']);


			if ($md5Text != $xml->SIGN) 
			{
				echo "service.php================没通过验证";
				return ;//表示没通过验证
			}

			$decodedXML=$this->decrypt($xml->DATA);

			$dataXml = simplest_xml_to_array($decodedXML);

			return $dataXml;
		}


		/*代付*/
	public function defrayjd($name,$phone,$money,$bankCode,$bankName,$bankNo,$out_trade_no){
		$data["payee_account_name"]=$name;//收款人姓名
		$data["payee_mobile"]=$phone;//银行预留电话
		$data["trade_amount"]=$money;//交易金额，单位分
		$data["payee_bank_code"]=$bankCode;//收款银行编码
		$data["payee_bank_fullname"]=$bankName;
		$data["payee_account_no"]=$bankNo;//银行卡账号

		$data["sign_type"]="SHA-256";
		$data["trade_currency"]="CNY";//交易币种类型
		$data["customer_no"]="360080004001063422"; //提交者会员号
		$data["biz_trade_no"]="2015003456"; //业务订单流水号
		$data["payee_account_type"]="P";
		$data["pay_tool"]="TRAN"; //TRAN代付到银行卡
				
		$data["category_code"]="20jd222"; //可传可不传
		$data["notify_url"]="http://xxx/";//商户处理数据的异步通知地址
		$data["return_params"]="1234ssddffgghhj";
		$data["trade_source"]="OUT_APP"; //提交业务渠道*
		$data["out_trade_no"]=$out_trade_no;//外部交易号
		$data["trade_subject"]="黄金树代付";
		$data["payee_card_type"]="DE"; //借记卡=DE
		$data["out_trade_date"]=date('Y-m-d').'T'.date('His');
		$data["request_datetime"]=date('Y-m-d').'T'.date('His');

		$data["seller_info"]="{\"customer_code\":\"360080004001063422\",\"customer_type\":\"CUSTOMER_NO\"}";

		$data["extend_params"]="{\"ssss\":\"ssss\"}";

		$UtilsPayJd = A("UtilsPayJd");
		file_put_contents('./auto/log/defrayjd_tF_before.txt',$out_trade_no);
		$result=$UtilsPayJd->tradeRequest($data,'RSA');
        file_put_contents('./auto/log/defrayjd_tF_after.txt',$result);
		if ($result == null) {
			
			echo "签名不成功";
		}else{
			
			return $result;
			//$this->rescode($result,false);
		}

	}


	/*加密*/


	function encrypt($input) {
        $size = mcrypt_get_block_size('des', 'ecb');
        $input = $this->pkcs5_pad($input, $size);
        
        $config = $this->conf_arr();

        $key = base64_decode($config['des']);

        $td = mcrypt_module_open('des', '', 'ecb', '');
        $iv = @mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        @mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = base64_encode($data);
        return $data;
    }

    function decrypt($encrypted) {
        $encrypted = base64_decode($encrypted);
        
        $config = $this->conf_arr();

        $key = base64_decode($config['des']);

        $td = mcrypt_module_open('des','','ecb','');
        //使用MCRYPT_DES算法,cbc模式
        $iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        $ks = mcrypt_enc_get_key_size($td);
        @mcrypt_generic_init($td, $key, $iv);
        //初始处理
        $decrypted = mdecrypt_generic($td, $encrypted);
        //解密
        mcrypt_generic_deinit($td);
        //结束
        mcrypt_module_close($td);
        $y=$this->pkcs5_unpad($decrypted);
        return $y;
    }
    
    function pkcs5_pad ($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    function pkcs5_unpad($text) {
        $pad = ord($text{strlen($text)-1});
        if ($pad > strlen($text))
            return false;
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad)
            return false;
        return substr($text, 0, -1 * $pad);
    }





}











?>