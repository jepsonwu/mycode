<?php
/**
 * 用户表操作日志对象基类
 * 
 * 
 * @author Bruce
 * @since 2014/05/28
 */
class DM_Model_Table_MemberConnect extends DM_Model_Table
{
    //登录操作
    const ACTION_LOGIN='Login';
    
    //修改密码
    const ACTION_EDIT_PASSWORD='EditPassword';
    
    //修改支付密码
    const ACTION_EDIT_REFUND_PASSWORD='EditRefundPassword';
    
    const PLATFORM_4CN='4CN';
    const PLATFORM_WEIBO='WEIBO';
    const PLATFORM_QQ='QQ';
    
	protected $_name = 'member_connect';
	protected $_primary = 'ConnectID';
	
    public function add($memberId, $platform, $openid, $accessToken, $extra=array())
    {
        $new=$this->createRow();
        $new->MemberID=$memberId;
        $new->ConnectSource=$platform;
        $new->OpenID=$openid;
        $new->AccessToken=$accessToken;
        
        if (!is_array($extra)) $extra= $extra ? array($extra) : array();
        if ($extra){
            $new->ExtraInfo=json_encode($extra);
        }
        $new->AddTime=DM_Helper_Utility::getDateTime();
        $new->save();
        
        return $new;
    }
    
    public function getByPlatformID($platform, $openid)
    {
        $select=$this->select()->where('ConnectSource=?', $platform)->where('OpenID=?', $openid)->limit(1, 0);
        $unit=$this->fetchRow($select);

        return $unit;
    }
}