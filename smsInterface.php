<?php 
/*
短信接口
 */
// 宽乐通信短信
define('KLTX_URL','xxx');
define('KLTX_USER','xxx');
define('KLTX_PWD','xxx');
define('KLTX_SIGN','【xxx】');


class smsInterface
{

	private $mobileTimeLimit = 60;
	private $dayIpLimit = 20;
	function __construct()
	{		
		date_default_timezone_set('PRC'); 
	}
	

	private function sendSmsKltx($mobile, $msgid, $text)
	{
		$data = array();
		$data['cmd'] = 'send';
		$data['uid'] = KLTX_USER;
		$data['psw'] = md5(KLTX_PWD);
		$data['mobiles'] = $mobile;
		$msg = $text.KLTX_SIGN;
		$msg = iconv("UTF-8","gbk//TRANSLIT",$msg);
		$data['msg'] = $msg;
		$data['msgid'] = $msgid;
		$str = $this->makeGetString($data);
		$url = KLTX_URL.$str;
		$status = @file_get_contents($url);
		
		return (isset($status) && $status == 100) ? TRUE : FALSE;

	}

	//构建get方式提交的数据链接(?a=&b=)
	private	function makeGetString($params=array())
	{
		$s = '';
		if(is_array($params) && ! empty($params))
		{
			$i = 1;
			foreach($params as $k=>$r)
			{
				if($i > 1)
				{
					$s .= "&";
				}else
				{
					$s .= "?";
				}
				$s .= urlencode($k)."=".urlencode($r);
				$i++;
			}
		}
		return $s;
	}

	private function getIp(){

		if(!empty($_SERVER['HTTP_CLIENT_IP'])){

		   return $_SERVER['HTTP_CLIENT_IP']; 

		}elseif(!empty($_SERVER['HTTP_X_FORVARDED_FOR'])){

		   return $_SERVER['HTTP_X_FORVARDED_FOR'];

		}elseif(!empty($_SERVER['REMOTE_ADDR'])){

		   return $_SERVER['REMOTE_ADDR'];

		}else{

		   return false;
		}
	}

	// 对外发送接口
	public function sendMsg( $mobile, $msgid, $text, $sendMessageWay='kltx')
	{
		$status = FALSE;
		if($this->checkSendtime($mobile,FALSE))
		{	
			if(strtolower($sendMessageWay) == 'kltx'){
				$status = $this->sendSmsKltx($mobile, $msgid, $text);
			}

			if($status == TRUE){
				$this->checkSendtime($mobile,TRUE);
				return 1;//成功
			}else{
				return 0;//失败
			}
		}		
		return -1;//太快
	}

	// 判断是否可以发送
	private function checkSendtime($mobile, $save = FALSE)
	{
		$tempDir = realpath(APPPATH.'/../').DIRECTORY_SEPARATOR.'lock'.DIRECTORY_SEPARATOR;
		!is_dir($tempDir) ? mkdir($tempDir) : null;
		
		$timestamp = time();
		$mobilePath = $tempDir.$mobile.'.lock';
		
		$ip = $this->getIp();
		$ipPath = $tempDir.'ip.'.$ip.'.lock';
		
		$dayPath = $tempDir.'day.'.date('Ymd',$timestamp).'.lock';
		$dayLimit = array();
		if(file_exists($dayPath))
		{
			$dayData = @file_get_contents($dayPath);
			$dayLimit = json_decode($dayData,TRUE);
			if(isset($dayLimit[$ip]) && $dayLimit[$ip] >= $this->dayIpLimit) return FALSE;
		}
		
		if($save){
			if(isset($dayLimit[$ip]))$dayLimit[$ip]+=1;
			else $dayLimit[$ip] = 1;
			file_put_contents($dayPath, json_encode($dayLimit));
			return (file_put_contents($mobilePath, $timestamp) && file_put_contents($ipPath, $timestamp)) ? TRUE : FALSE;
		}else{
			if(file_exists($mobilePath))
			{
				$lasttime = @file_get_contents($mobilePath);
				return ($timestamp - $lasttime > $this->mobileTimeLimit) ? TRUE : FALSE;
			}else if(file_exists($ipPath))
			{
				$lasttime = @file_get_contents($ipPath);
				return ($timestamp - $lasttime > $this->mobileTimeLimit) ? TRUE : FALSE;
			}else{
				//echo $path;
				return TRUE;
			}
		}
	}
}
?>