<?php
/**
 * 用户表操作日志对象基类
 * 
 * 
 * @author Bruce
 * @since 2014/05/28
 */
class DM_Model_Account_MemberLogs extends DM_Model_Table
{
    //登录操作
    const ACTION_LOGIN='Login';
    const ACTION_OPERATER='Operater';
    
    //修改密码
    const ACTION_EDIT_PASSWORD='EditPassword';
    
    //修改支付密码
    const ACTION_EDIT_REFUND_PASSWORD='EditRefundPassword';
    
	protected $_name = 'member_logs';
	protected $_primary = 'LogID';

    /**
     * 初始化数据库
     */
    public function __construct()
    {
        $udb = DM_Controller_Front::getInstance()->getDb('udb');
        $this->_setAdapter($udb);
    }

	
    public function add($memberId, $action, $info='', $extra=array(), $platform,$deviceID)
    {
        $new=$this->createRow();
        $new->MemberID=$memberId;
        $new->Action=$action;
        $new->Info=$info;
        $new->DeviceID=$deviceID;
        $new->Platform=$platform;
        $project = DM_Controller_Front::getInstance()->getConfig()->project;
        $new->SystemSign=$project->attr_sign;
        
        if (!is_array($extra)) $extra= $extra ? array($extra) : array();
        $extra+=$this->getExtraInfo();
       
        if ($extra){
            $new->Extra=json_encode($extra);
        }

        $new->IP=DM_Controller_Front::getInstance()->getHttpRequest()->getClientIp();
        $new->CreateTime=DM_Helper_Utility::getDateTime();
        
        return $new->save();
    }
    
    protected function getExtraInfo()
    {
        $result=array();
        
        $request=DM_Controller_Front::getInstance()->getHttpRequest();
        
        $version= trim($request->getParam('client_version',''));
        if ($version) $result['client_version'] =$version;
        
        $brand= trim($request->getParam('client_brand',''));
        if ($brand) $result['client_brand'] = $brand;
        
        return $result;
    }
}