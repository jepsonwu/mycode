<?php
/**
 * Tengxun登陆
 * User: Kitty
 * Date: 14-9-10
 */
class DM_Oauth_Tengxun
{
    private $appid;         //应用的唯一标识           
    private $appkey;        //appid的密钥
    private $callback_url;  //回调地址    
    
    const GET_AUTH_CODE_URL = 'https://open.t.qq.com/cgi-bin/oauth2/authorize?';       //授权url
    const GET_ACCESS_TOKEN_URL = 'https://open.t.qq.com/cgi-bin/oauth2/access_token?'; //获取access_token
   
    //接口url
    const API_URL_HTTP = 'http://open.t.qq.com/api/';
    const API_URL_HTTPS= 'https://open.t.qq.com/api/';

    /**
     * 初始化配置数据
     * @param array $options
     */
    public function __construct($options)
    {
        $this->appid  = isset($options['appid']) ? $options['appid'] : '';
        $this->appkey = isset($options['appkey']) ? $options['appkey'] : '';
        $this->callback_url = isset($options['callback_url']) ? $options['callback_url'] : '';
    }

    /**
     * 获取授权URL
     * @param $wap 用于指定手机授权页的版本，默认PC，值为1时跳到wap1.0的授权页，为2时跳转至wap2.0授权页
     * @return string 授权URL
     */
    public function getAuthorizeURL($wap = false)
    {
        $params = array(
            'client_id' => $this->appid,
            'redirect_uri' => $this->callback_url,
            'response_type' => 'code',
            'wap' => $wap
        );
        return self::GET_AUTH_CODE_URL.http_build_query($params);
    }

    /**
     * 获取请求token的url
     * @param $code 调用authorize时返回的code
     * @return string
     */
    public function getAccessToken($code)
    {
        $params = array(
            'client_id' => $this->appid,
            'client_secret' => $this->appkey,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->callback_url
        );

        $url = self::GET_ACCESS_TOKEN_URL.http_build_query($params);
        $r = $this->request($url);
        parse_str($r, $out);
        if ($out['access_token']) {//获取成功
            $_SESSION['access_token'] = $out['access_token'];
            return $out['access_token'];
        } else {
            return false;
        }
    }
    
    /**
     * 刷新授权信息
     * 此处以SESSION形式存储做演示，实际使用场景请做相应的修改
     */
    public function refreshToken()
    {
        $params = array(
            'client_id' => $this->appid,
            'client_secret' => $this->appkey,
            'grant_type' => 'refresh_token',
            'refresh_token' => $_SESSION['t_refresh_token']
        );
        $url = self::GET_ACCESS_TOKEN_URL.http_build_query($params);
        $r = $this->request($url);
        parse_str($r, $out);
        if ($out['access_token']) {//获取成功
            $_SESSION['t_access_token'] = $out['access_token'];
            $_SESSION['t_refresh_token'] = $out['refresh_token'];
            $_SESSION['t_expire_in'] = $out['expires_in'];
            return $out;
        } else {
            return $r;
        }
    }
    
    /**
     * 验证授权是否有效
     */
    public function checkOAuthValid($returnData = false,$app = array())
    {
        $r = json_decode($this->api('user/info',array(),'GET',false,false,$app), true);
        if ($r['data']['name']) {
            return $returnData ? $r['data'] :true;
        } else {
            $this->clearOAuthInfo();
            return false;
        }
    }
    
    /**
     * 清除授权
     */
    public function clearOAuthInfo()
    {
        if (isset($_SESSION['t_access_token'])) unset($_SESSION['t_access_token']);
        if (isset($_SESSION['t_expire_in'])) unset($_SESSION['t_expire_in']);
        if (isset($_SESSION['t_code'])) unset($_SESSION['t_code']);
        if (isset($_SESSION['t_openid'])) unset($_SESSION['t_openid']);
        if (isset($_SESSION['t_openkey'])) unset($_SESSION['t_openkey']);
        if (isset($_SESSION['t_oauth_version'])) unset($_SESSION['t_oauth_version']);
    }
  
    /**
     * 发起一个腾讯API请求
     * @param $command 接口名称 如：t/add
     * @param $params 接口参数  array('content'=>'test');
     * @param $method 请求方式 POST|GET
     * @param $multi 图片信息
     * @param $debug 调试模式
     * @return string
     */
    public function api($command, $params = array(), $method = 'GET', $multi = false, $debug =false,$app = array())
    {

        if (isset($_SESSION['t_access_token']) || $app['access_token']) {//OAuth 2.0 方式
            //鉴权参数
            $params['access_token'] = $app['access_token']?$app['access_token']:$_SESSION['t_access_token'];
            $params['oauth_consumer_key'] = $this->appid;
            $params['openid'] = $app['openid']?$app['openid']:$_SESSION['t_openid'];
            $params['oauth_version'] = '2.a';
            $params['clientip'] = $this->getClientIp();
            $params['scope'] = 'all';
            $params['appfrom'] = 'php-sdk2.0beta';
            $params['seqid'] = time();
            $params['serverip'] = $_SERVER['SERVER_ADDR'];
            
            $url = self::API_URL_HTTPS.trim($command, '/');
        } elseif (isset($_SESSION['t_openid']) && isset($_SESSION['t_openkey'])) {//openid & openkey方式
            $params['appid'] = $this->appid;
            $params['openid'] = $_SESSION['t_openid'];
            $params['openkey'] = $_SESSION['t_openkey'];
            $params['clientip'] = $this->getClientIp();
            $params['reqtime'] = time();
            $params['wbversion'] = '1';
            $params['pf'] = 'php-sdk2.0beta';
            
            $url = self::$API_URL_HTTP.trim($command, '/');
            //生成签名
            $urls = @parse_url($url);
            $sig = $this->makeSig($method, $urls['path'], $params, $this->appkey.'&');
            $params['sig'] = $sig;
        }
        
        //请求接口
        $r = $this->request($url, $params, $method, $multi);
        $r = preg_replace('/[^\x20-\xff]*/', "", $r); //清除不可见字符
        $r = iconv("utf-8", "utf-8//ignore", $r); //UTF-8转码
        //调试信息
        if ($debug) {
            echo '<pre>';
            echo '接口：'.$url;
            echo '<br>请求参数：<br>';
            print_r($params);
            echo '返回结果：'.$r;
            echo '</pre>';
        }
        return $r;
    }

    /**
     * 生成签名
     * @param string    $method 请求方法 "get" or "post"
     * @param string    $url_path 
     * @param array     $params 表单参数
     * @param string    $secret 密钥
     */
    public function makeSig($method, $url_path, $params, $secret) 
    {
        $mk = $this->makeSource ( $method, $url_path, $params );
        $my_sign = hash_hmac ( "sha1", $mk, strtr ( $secret, '-_', '+/' ), true );
        $my_sign = base64_encode ( $my_sign );
        return $my_sign;
    }
    
    private function makeSource($method, $url_path, $params) 
    {
        ksort ( $params );
        $strs = strtoupper($method) . '&' . rawurlencode ( $url_path ) . '&';
        $str = ""; 
        foreach ( $params as $key => $val ) { 
            $str .= "$key=$val&";
        }   
        $strc = substr ( $str, 0, strlen ( $str ) - 1 );
        return $strs . rawurlencode ( $strc );
    }

    /*
     * 获取客户端IP
     */
    public function getClientIp()
    {
        if (getenv ( "HTTP_CLIENT_IP" ) && strcasecmp ( getenv ( "HTTP_CLIENT_IP" ), "unknown" ))
            $ip = getenv ( "HTTP_CLIENT_IP" );
        else if (getenv ( "HTTP_X_FORWARDED_FOR" ) && strcasecmp ( getenv ( "HTTP_X_FORWARDED_FOR" ), "unknown" ))
            $ip = getenv ( "HTTP_X_FORWARDED_FOR" );
        else if (getenv ( "REMOTE_ADDR" ) && strcasecmp ( getenv ( "REMOTE_ADDR" ), "unknown" ))
            $ip = getenv ( "REMOTE_ADDR" );
        else if (isset ( $_SERVER ['REMOTE_ADDR'] ) && $_SERVER ['REMOTE_ADDR'] && strcasecmp ( $_SERVER ['REMOTE_ADDR'], "unknown" ))
            $ip = $_SERVER ['REMOTE_ADDR'];
        else
            $ip = "unknown";
        return ($ip);
    }

    /**
     * 发起一个HTTP/HTTPS的请求
     * @param $url 接口的URL 
     * @param $params 接口参数   array('content'=>'test', 'format'=>'json');
     * @param $method 请求类型    GET|POST
     * @param $multi 图片信息
     * @param $extheaders 扩展的包头信息
     * @return string
     */
    public function request( $url , $params = array(), $method = 'GET' , $multi = false, $extheaders = array())
    {
        if(!function_exists('curl_init')) exit('Need to open the curl extension');
        $method = strtoupper($method);
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_USERAGENT, 'PHP-SDK OAuth2.0');
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 3);
        $timeout = $multi?30:3;
        curl_setopt($ci, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ci, CURLOPT_HEADER, false);
        $headers = (array)$extheaders;
        switch ($method)
        {
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, TRUE);
                if (!empty($params))
                {
                    if($multi)
                    {
                        foreach($multi as $key => $file)
                        {
                            $params[$key] = '@' . $file;
                        }
                        curl_setopt($ci, CURLOPT_POSTFIELDS, $params);
                        $headers[] = 'Expect: ';
                    }
                    else
                    {
                        curl_setopt($ci, CURLOPT_POSTFIELDS, http_build_query($params));
                    }
                }
                break;
            case 'DELETE':
            case 'GET':
                $method == 'DELETE' && curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (!empty($params))
                {
                    $url = $url . (strpos($url, '?') ? '&' : '?')
                        . (is_array($params) ? http_build_query($params) : $params);
                }
                break;
        }
        curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE );
        curl_setopt($ci, CURLOPT_URL, $url);
        if($headers)
        {
            curl_setopt($ci, CURLOPT_HTTPHEADER, $headers );
        }

        $response = curl_exec($ci);
        curl_close ($ci);
        return $response;
    }
}