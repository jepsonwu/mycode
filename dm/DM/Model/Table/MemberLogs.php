<?php
/**
 * 用户表操作日志对象基类
 * 
 * 
 * @author Bruce
 * @since 2014/05/28
 */
class DM_Model_Table_MemberLogs extends DM_Model_Table
{
    //登录操作
    const ACTION_LOGIN='Login';
    //登录失败日志
    const ACTION_LOGIN_FAIL='LoginFail';
    
    //修改密码
    const ACTION_EDIT_PASSWORD='EditPassword';
    
    //修改支付密码
    const ACTION_EDIT_REFUND_PASSWORD='EditRefundPassword';
    
	protected $_name = 'member_logs';
	protected $_primary = 'LogID';
	
    public function add($memberId, $action, $info='', $extra=array())
    {
        $new=$this->createRow();
        $new->MemberID=$memberId;
        $new->Action=$action;
        $new->Info=$info;
        
        if (!is_array($extra)) $extra= $extra ? array($extra) : array();
        $extra+=$this->getExtraInfo();
       
        if ($extra){
            $new->Extra=json_encode($extra);
        }else{
        	$new->Extra='';
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
    

    /**
     * 测试该ip在多少时间内的错误登录次数
     *
     * 防止暴力破解
     *
     * @param int $duration 多少时间内的错误次数
     */
    public function getFailedLoginAccount($duration=15)
    {
        $select=$this->select()->from($this->_name, array('count'=>'count(1) as count'))
                                                ->where('Action =?', self::ACTION_LOGIN_FAIL)
                                                ->where('IP =?', DM_Controller_Front::getInstance()->getClientIp())
                                                ->where('CreateTime > ?', DM_Helper_Utility::getDateTime(time()-intval($duration)*60));
        //echo $select->__toString();
        return $this->fetchRow($select);
    }
}