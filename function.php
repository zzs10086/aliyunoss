<?php
//打印日志
function debug_log($msg,$level='')
{
	$logdir=defined('LOGS_PATH')?LOGS_PATH:'/tmp';
	$level=strtolower($level);
	if($level=='' || $level=='debug')
	{
		$f='debug';
	}
	else
	{
		$f='debug_'.$level;
	}
	$flag=file_put_contents(rtrim($logdir,DS).DS.$f.'.'.date('Ymd'),(is_array($msg)?print_r($msg,true):$msg)."\t".date('Y-m-d H:i:s')."\n",FILE_APPEND);
	if($flag===false)
	{
		if(!file_exists($logdir) || !is_dir($logdir))
		{
			mkdir(LOGS_PATH,0777,true);
		}
		file_put_contents(rtrim($logdir,DS).DS.$f.'.'.date('Ymd'),(is_array($msg)?print_r($msg,true):$msg)."\t".date('Y-m-d H:i:s')."\n",FILE_APPEND);
	}
}

//判断是否是新用户
function isNewUser($imei)
{
	
	if($GLOBALS["db"]->getVar("select count(*) from st_imei where imei='$imei'")==0) //新用户
	{
		#$GLOBALS["db"]->exec("insert into st_imei(imei)values('$imei')");
		return true;
	}
	return false;
}

/**
 * 生成汉字
 * @param $num
 * @return string
 */
function getChar($num)  // $num为生成汉字的数量
{
	$b = '';
	for ($i=0; $i<$num; $i++) {
		// 使用chr()函数拼接双字节汉字，前一个chr()为高位字节，后一个为低位字节
		$a = chr(mt_rand(0xB0,0xD0)).chr(mt_rand(0xA1, 0xF0));
		// 转码
		$b .= iconv('GB2312', 'UTF-8', $a);
	}
	return $b;
}

function getUserName(){

	$pref = array('130','131','132','133','134','135','136','137','138','139','151','157','158','187','189');

	return $pref[array_rand($pref,1)] . '****' . rand(1000,9999);
}

#下载文件直接输出文件
function download_remote_file_with_curl($file_url,$save_to)
{
	//global $ch;
	$downloaded_file = fopen($save_to, 'w');

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_POST, 0);
	curl_setopt($ch,CURLOPT_URL,$file_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0); #设置1或true 输出到变量也就是内存   false直接输出到文件
	#curl_setopt($ch, CURLOPT_HEADER, false); //不取得返回头信息
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  #追踪重定向
	curl_setopt($ch, CURLOPT_FILE, $downloaded_file);
	curl_exec($ch);
	curl_close($ch);
	fclose($downloaded_file);

}

function curl_get_contents($url)
{

	$ch=curl_init();
	$this_header = array(
		"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8",
		"Host:sj.qq.com",
		"Referer:http://sj.qq.com/myapp/",
		"Accept-Encoding:gzip, deflate",
		"Accept-Language:zh-CN,zh;q=0.9",
		"Cache-Control:max-age=0",
		"Connection:keep-alive",
		"Upgrade-Insecure-Requests:1",
		"User-Agent:Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36",
	);

	curl_setopt($ch, CURLOPT_HTTPHEADER,false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  #追踪重定向
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_TIMEOUT, 60);

	$response=curl_exec($ch);

	curl_close($ch);

	return $response;
}

//代理爬取
function curl_proxy_get_contents($url,$data=array(),$method='GET',$proxy='')
{
	$request_data=http_build_query($data);

	$ch=curl_init();
	$this_header = array(
		"content-type: application/x-www-form-urlencoded;charset=UTF-8"
	);
	if($data && strtoupper($method)=='GET')
	{
		$url .= '?'.$request_data;
	}
	elseif($data && strtoupper($method)=='POST')
	{
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$request_data);
	}

	if(!empty($proxy))
	{
		debug_log("====proxy====".$proxy,'img');
		curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);//使用http代理模式
		curl_setopt ($ch, CURLOPT_PROXY, $proxy);
	}

	curl_setopt($ch,CURLOPT_HTTPHEADER,$this_header);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:39.0) Gecko/20100101 Firefox/39.0");

	curl_setopt($ch, CURLOPT_TIMEOUT, 60);

	$response=curl_exec($ch);
	if(curl_errno($ch))
	{
		echo 'Error:'.curl_errno($ch),' ',$url."\n";
		return false;
	}
	else
	{
		curl_close($ch);
	}

	return $response;
}
?>