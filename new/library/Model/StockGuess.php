<?php
/**
 *  猜股票涨跌活动
 * @author Jeff
 *
 */
class Model_StockGuess extends Zend_Db_Table
{
	protected $_name = 'stock_guess';
	protected $_primary = 'SID';
	
	/**
	 * 添加信息
	 * @param unknown $data
	 */
	public function addInfo($memberID,$guess)
	{
		$data = array(
				'MemberID'=>$memberID,
				'Guess'=>$guess,
				'GuessDate'=>date('Y-m-d',strtotime("+1 day"))
		);
		$newID = 0;
		$info = $this->getInfo($memberID);
		if(empty($info)){
			$newID = $this->insert($data);
		}
		return $newID;
	}
	
	/**
	 * 获取信息
	 */
	public function getInfo($memberID)
	{
		return $this->select()->from($this->_name)->where('MemberID = ?',$memberID)->where("date_format(CreateTime, '%Y-%m-%d') = ? ",date("Y-m-d"))
				->query()->fetch();
	}
	
	/**
	 * 获取中奖名单
	 */
	public function getWinList()
	{
		$result = array();
		$udb = DM_Controller_Front::getInstance()->getConfig()->resources->multidb->udb->dbname;
		$select = $this->_db->select();
		$select->from($this->_name." as a",array('GuessDate','TmpUserName','Mobile'));
		$select->joinleft($udb.".members as b","a.MemberID=b.MemberID",array('MemberID','MobileNumber','UserName'));
		$select->where("a.IsWin=?",1);
		$info = $select->order('GuessDate desc')->query()->fetchAll();
		if(!empty($info)){
			foreach ($info as $k=>$val){
				$result[$k]['UserName'] = empty($val['UserName'])?$val['TmpUserName']:$val['UserName'];
				$result[$k]['MobileNumber'] = empty($val['UserName'])?$val['Mobile']:substr_replace($val['MobileNumber'], '****', 3, 4);
				$result[$k]['GuessDate'] = $val['GuessDate'];
			}
		}
		return $result;
	}
	
	/**
	 * 通知获奖者
	 */
	public function noticeWinAction()
	{
		$sysMemberID = DM_Controller_Front::getInstance()->getConfig()->system->constant->caizhu->member_id;
		$easeModel = new Model_IM_Easemob();
		$content = '尊敬的财猪用户：恭喜您成为本次“猜涨跌赢话费”活动的幸运得主，20元话费将在2个小时内为您充值，请您留意。部分地区可能会存在延迟，请耐心等待！明日您还可以继续参加本活动，记得继续支持财猪哦！';
		$lastID = 0;
		while(true){
			$select = $this->select();
			$select->from('stock_guess')->where('IsWin = 1')->where('IsNoticed = 0')->where('SID  > ? ',$lastID)->order('SID asc')->limit(20);
			$result = $select->query()->fetchAll();
			if(!empty($result)){
				$tmp = array();
				foreach($result as $item){
					$tmp[] = $item['MemberID'];
					$lastID = $item['SID'];
				}
				$ret = $easeModel->yy_hxSend($tmp, $content,'text','users',array('optionRand'=>1),$sysMemberID);
				$retArr = json_decode($ret,true);
				if(is_array($retArr) && !empty($retArr['data'])){
					foreach($retArr['data'] as $memberID=>$resSign){
						if($resSign == 'success'){
							$this->update(array('IsNoticed'=>1),array('MemberID = ?'=>$memberID));
						}
					}
				}
			}
			if(count($result < 20)){
				break;
			}
		}
	}
	
}
