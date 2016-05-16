<?php
/**
 * Sina登陆
 * User: Kitty
 * Date: 14-9-18
 */

include_once( 'saetv2.ex.class.php');

class DM_Oauth_Sina{

    private $appid;         //应用的唯一标识           
    private $appkey;        //appid的密钥
    private $callback_url;  //回调地址      
    
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
     * @return array 授权URL和状态值
     */
    public function getAuthorizeURL()
    {

        $o = new SaeTOAuthV2( $this->appid , $this->appkey );
        $code_url = $o->getAuthorizeURL( $this->callback_url );

        return $code_url;
    }

    /**
     * 获取请求token的url
     * @param $code 调用authorize时返回的code
     * @return string access_token
     */
    public function getAccessToken( $code )
    {
        if(isset($code)){
            $o = new SaeTOAuthV2( $this->appid , $this->appkey);
            $keys = array();
            $keys['code'] = $code;
            $keys['redirect_uri'] = $this->callback_url;

            try {
                $token = $o->getAccessToken( 'code', $keys ) ;
            } catch (OAuthException $e) {
                echo $e->getMessage();
                exit;
            }

            if($token){
                $_SESSION['access_token'] = $token['access_token'];
                return $token['access_token'];
            }else{
                die('error');
            }
        }   
    }

    /**
     * 获取用户access_token的授权相关信息
     * @param $access_token 用户access_token
     * @return array 授权相关信息
     */
    public function getOpenid( $access_token )
    {
        
        //请求参数列表
        $params = array(
            "access_token" => $access_token
        );
        //获取授权信息的url
        $tokenInfoUrl = 'https://api.weibo.com/oauth2/get_token_info';

        $o = new SaeTOAuthV2( $this->appid , $this->appkey);

        $response = $o->post($tokenInfoUrl, $params);
        if ( isset($response['uid']) && $response['uid'] ) {
                $_SESSION['openid'] = $response['uid'];
                return $response['uid'];
        }else{
            return false;
        }        
    }

}