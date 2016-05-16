<?php
/**
 * 第三方账号
 * 
 * @author Kitty
 * @since 2014/09/15
 */
class Model_Oauth extends Zend_Db_Table
{
	protected $_name = 'member_oauths';
	protected $_primary = 'OauthId';	

    /**
     * 获取绑定帐号详情
     * 
     * @param string $site   第三方类型
     * @param string $openid 第三方身份唯一标识
     */
	    public function getOauthInfo($site, $openid)
    {
        $sql = "select * from {$this->_name}
                where Site = '{$site}' and Openid = '{$openid}'";
        return $this->_db->fetchRow($sql);
    }
    
}