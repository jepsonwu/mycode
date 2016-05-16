<?php
/**
 * 验证器
*/
class DM_Helper_Validator
{
    /**
     * email验证
     */
    public static function isEmail($email)
    {
        if(preg_match('/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9\._-]+)+$/',$email)){
            return true;
        }
        return false;
    }
    
    /**
     * 检测手机格式
     *
     * +(86)0571-88651523-5215
     * +(86)1385621533
     * 等等，由于国际实在是太宽泛了，所以相对放松点
     */
    public static function checkmobile($mobile)
    {
        if(preg_match('/^[+\-\d\(\)\.]{6,25}$/s',$mobile)){
            return true;
        }
        return false;
    }
    
    /**
     * 检测密码格式
     * 
     * 6到20位，包含字母和数字。
     */
    public static function checkPassword($password){
        if(preg_match('/^(?![^a-zA-Z]+$)(?!\D+$).{6,20}$/', $password)){
            return true;
        }
        return false;
    }

    /**
     * 检测用户名格式
     * 
     * 2到10位，包含字母和数字。
     */
    public static function checkUsername($username){

    	   $pattern = '/(^[a-zA-Z]+[0-9]*[a-zA-Z]*$)|(^[0-9]+[a-zA-Z]+[0-9]*$)|(^[a-zA-Z0-9]*[\x{4e00}-\x{9fa5}]+[a-zA-Z0-9]*$)/u';
    		if(!preg_match($pattern, $username)){
    			return false;	
    		}
    	
        $length = mb_strlen($username,'UTF-8');
        if(preg_match('/^[0-9]*$/', $username) || strpos($username,'@') !== false || ($length < 2 || $length > 10)){
            
        	return false;
        }else{
            return true;
        }
    }
}