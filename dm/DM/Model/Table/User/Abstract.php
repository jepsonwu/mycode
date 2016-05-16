<?php
abstract class DM_Model_Table_User_Abstract extends DM_Model_Table
{

    public function login ()
    {}

    public function logout ()
    {}

    public function buildPassword ($password)
    {
        $password = Zend_Filter::get($password, 'StringTrim');
        $empty_validate = new Zend_Validate_NotEmpty();
        $empty_validate->setMessage(
                array(
                        Zend_Validate_NotEmpty::INVALID => '无效字符',
                        Zend_Validate_NotEmpty::IS_EMPTY => '密码不能为空'
                ));
        if ($empty_validate->isValid($password)){
            
        }
    }
}