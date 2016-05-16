<?php
/**
 * 验证码模块
 */
class DM_Model_Table_MemberVerifys extends DM_Model_Table
{
    protected $_name = 'member_verifys';
    protected $_primary = 'VerifyID';
    
    //1：邮件激活验证码 2：手机绑定验证码 3:用户找回密码验证码 4:用户找回交易密码验证码;5:用户注册，6：账号保护 ，7、手机解绑，8，邮箱解绑 9、身份认证 10修改支付密码验证
    const TYPE_EMAIL=1;
    const TYPE_MOBILE=2;
    const TYPE_FINDPASS=3;
    const TYPE_TRADE=4;
    const TYPE_REGISTER=5;
    const TYPE_PROTECT=6;
    const TYPE_MOBILE_UNBIND=7;
    const TYPE_EMAIL_UNBIND=8;
    const TYPE_USER_AUTHENTICATE=9;
	const TYPE_MODIFY_PAYPASSWORD=10;
    
    public function createVerify($verifytype = self::TYPE_EMAIL, $identify='', $member_id='')
    {
        $code = '';
        for($i=0; $i<6; $i++){
            $code .= mt_rand(0,9);
        }
        $unit=DM_Model_Table_MemberVerifys::create()->createRow();
        $unit->MemberID=$member_id;
        $unit->VerifyType = $verifytype;
        $unit->VerifyCode=$code;
        $unit->SendTime=DM_Helper_Utility::getDateTime();
        $unit->ExpiredTime=DM_Helper_Utility::getDateTime(time()+1800);
        $unit->VerifyNum=0;
        $unit->Status='Pending';
        if(!empty($identify)){
            $unit->IdentifyID=$identify;
        }
        $unit->save();
        
        return $unit;
    }
    
    /**
     * 验证验证码
     */
    public function verifyByIdentify($identify, $code, $verifytype)
    {
        $select = $this->select();
        $select->where("IdentifyID = ?", $identify);
        $select->where("VerifyCode = ?", $code);
        $select->where("VerifyType = ?", $verifytype);
        $select->where("Status = ?", 'Pending');
        $select->where("ExpiredTime > ?", DM_Helper_Utility::getDateTime());
        $unit=$this->fetchRow($select);
        if (!$unit) return false;
        
        $unit->Status='Pass';
        $unit->save();
         return true;
    }
    
    /**
     * @param $exp
     * @return mixed
     */
    public function getverify($exp)
    {
        $select = $this->select()->setIntegrityCheck(false);
        $select->from(array('v' => $this->_name));
        if(isset($exp['VerifyID'])){
            $select->where("v.VerifyID = ?", (int)$exp['VerifyID']);
        }
        if(isset($exp['MemberID'])){
            $select->where("v.MemberID = ?", (int)$exp['MemberID']);
        }
        if(isset($exp['VerifyType'])){
            $select->where("v.VerifyType = ?", $exp['VerifyType']);
        }
        if(isset($exp['Status'])){
            $select->where("v.Status = ?", $exp['Status']);
        }
        if(isset($exp['VerifyCode'])){
            $select->where("v.VerifyCode = ?", $exp['VerifyCode']);
        }
        if(isset($exp['IdentifyID'])){
            $select->where("v.IdentifyID = ?", $exp['IdentifyID']);
        }
    
        return $select->order('VerifyID DESC')->limit(1)->query()->fetch();
    }
    
    public function updateverify($verifyId)
    {
        if(empty($verifyId)) return false;
        $where = array("VerifyID = ?"=>(int)$verifyId);
        return $this->_db->update($this->_name,array('Status'=>'Pass'),$where);
    }
    
    public function getSendCount($IdentifyID)
    {
        $select = $this->select()->setIntegrityCheck(false);
        $select->from(array('v' => $this->_name));

        $select->where("v.IdentifyID = ?", $IdentifyID)
               ->where("v.SendTime >= ?",date('Y-m-d'));
        return count($select->query()->fetchAll());
    }
}