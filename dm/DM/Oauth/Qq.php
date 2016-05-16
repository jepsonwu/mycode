<?php
/**
 * QQ登陆
 * User: Kitty
 * Date: 14-9-10
 */

class DM_Oauth_Qq{

    private $appid;         //应用的唯一标识           
    private $appkey;        //appid的密钥
    private $callback_url;  //回调地址      
    
    const GET_AUTH_CODE_URL    = 'https://graph.qq.com/oauth2.0/authorize?';   //PC端获取code
    const GET_ACCESS_TOKEN_URL = 'https://graph.qq.com/oauth2.0/token?';       //PC端获取access_token
    const GET_OPENID_URL       = 'https://graph.qq.com/oauth2.0/me?';          //PC端获取openId
    const GET_USER_INFO        = 'https://graph.qq.com/user/get_user_info?';   //PC端获取用户信息

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
     * @param $state 状态值
     * @param $scope 授权列表
     * @return array 授权URL和状态值
     */
    public function getAuthorizeURL($state = NULL, $scope = NULL )
    {    
        //构造请求参数列表
        $params = array(
            "response_type" => "code",
            "client_id"     => $this->appid,
            "redirect_uri"  => $this->callback_url,
            "state"         => $state,
            "scope"         => $scope
        );

        return self::GET_AUTH_CODE_URL.http_build_query($params);
    }

    /**
     * 获取请求token的url
     * @param $code 调用authorize时返回的code
     * @param $state 状态值
     * @return string access_token
     */
    public function getAccessToken( $state, $code )
    {
        //验证state防止CSRF攻击
        if($_GET['state'] != $state){
            exit("The state does not match. You may be a victim of CSRF.");
        }

        //请求参数列表
        $keysArr = array(
            "grant_type"    => "authorization_code",
            "client_id"     => $this->appid,
            "redirect_uri"  => urlencode($this->callback_url),
            "client_secret" => $this->appkey,
            "code" => $code
        );
        
        //构造请求access_token的url
        $response = $this->get(self::GET_ACCESS_TOKEN_URL, $keysArr);

        if(strpos($response, "callback") !== false){

            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response  = substr($response, $lpos + 1, $rpos - $lpos -1);
            $msg = json_decode($response);

            if(isset($msg->error)){
                echo "<h3>error:</h3>" . $msg->error;
                echo "<h3>msg  :</h3>" . $msg->error_description;
                exit;
            }
        }

        $params = array();
        parse_str($response, $params);
        $_SESSION['access_token'] = $params["access_token"];
        return $params["access_token"];
    }

    /**
     * 获取openid
     * @param  $access_token 
     * @return string  openid
     */
    public function getOpenid($access_token)
    {
        //请求参数列表
        $keysArr = array(
            "access_token" => $access_token
        );

        $response = $this->get(self::GET_OPENID_URL, $keysArr);

        //检测错误是否发生
        if(strpos($response, "callback") !== false){

            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response = substr($response, $lpos + 1, $rpos - $lpos -1);
        }

        $user = json_decode($response);
        if(isset($user->error)){
            echo "<h3>error:</h3>" . $user->error;
            echo "<h3>msg  :</h3>" . $user->error_description;
            exit;
        }

        //记录openid
        $_SESSION['openid'] = $user->openid;
        return $user->openid;
    }

    /**
     * 获取用户信息
     * @param string $access_token   授权的access_token
     * @param string $openid         第三方用户的唯一标识
     * @return string                用户信息
     */
    public function get_user_info($access_token, $openid)
    {
        //请求参数列表
        $keysArr = array(
            "access_token"       => $_SESSION['access_token'],
            "oauth_consumer_key" => $this->appid,
            "openid"             => $_SESSION['opendi'],
            "format"             => 'json'
        );
        $response = $this->get(self::GET_USER_INFO, $keysArr);

        //检查返回ret判断api是否成功调用
        if($response->ret != 0){
            echo "<h3>error:</h3>" . $response->ret;
            echo "<h3>msg  :</h3>" . $response->msg;
            exit;  
        }
        $response = json_decode($response);
        $responseArr = $this->objToArr($response);
        return $responseArr;
 
    }

    /**
     * 对象到数组转换
     * @param  object $obj   需转换的对象
     * @return array         返回转换后的数组
     */
    private function objToArr($obj)
    {
        if(!is_object($obj) && !is_array($obj)) {
            return $obj;
        }
        $arr = array();
        foreach($obj as $k => $v){
            $arr[$k] = $this->objToArr($v);
        }
        return $arr;
    }

    /**
     * 拼接url
     * @param string $baseURL   基于的url
     * @param array  $keysArr   参数列表数组
     * @return string           返回拼接的url
     */
    public function combineURL($baseURL,$keysArr)
    {
        $combined = $baseURL;
        $valueArr = array();

        foreach($keysArr as $key => $val){
            $valueArr[] = "$key=$val";
        }

        $keyStr = implode("&",$valueArr);
        $combined .= ($keyStr);
        
        return $combined;
    }

    /**
     * 服务器通过get请求获得内容
     * @param string $url       请求的url,拼接后的
     * @return string           请求返回的内容
     */
    public function get_contents($url)
    {
        if (ini_get("allow_url_fopen") == "1") {
            $response = file_get_contents($url);
        }else{
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_URL, $url);
            $response =  curl_exec($ch);
            curl_close($ch);
        }

        //请求为空
        if(empty($response)){
            return false;
        }
        return $response;
    }

    /**
     * get方式请求资源
     * @param string $url     基于的baseUrl
     * @param array $keysArr  参数列表数组      
     * @return string         返回的资源内容
     */
    public function get($url, $keysArr){
        $combined = $this->combineURL($url, $keysArr);
        return $this->get_contents($combined);
    }

    /**
     * post方式请求资源
     * @param string $url       基于的baseUrl
     * @param array $keysArr    请求的参数列表
     * @param int $flag         标志位
     * @return string           返回的资源内容
     */
    public function post($url, $keysArr, $flag = 0){

        $ch = curl_init();
        if(! $flag) curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
        curl_setopt($ch, CURLOPT_POST, TRUE); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, $keysArr); 
        curl_setopt($ch, CURLOPT_URL, $url);
        $ret = curl_exec($ch);

        curl_close($ch);
        return $ret;
    }
}