<?php
class DM_Model_Table_Finance_Logs extends DM_Model_Table
{
	protected $_name = 'admin_finance_logs';
	protected $_primary = 'LogID';

    /**
     * 添加日志
     * @param int $member_id
     * @param int $admin_id
     * @param int $info_id
     * @param string $content
     */
    public function addLog($member_id,$admin_id,$info_sign,$info_id,$content,$ip='')
    {
        $data = array(
                'MemberID'		=>	$member_id,
                'AdminID'		=>	$admin_id,
                'InfoID'		=>	$info_id,
                'InfoSign'		=>	$info_sign,
                'Content'		=>	$content,
        		'UpdateIp'      =>  $ip
        );
        return $this->insert($data);
    }

}