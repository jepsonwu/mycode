<?php
class DM_Helper_Fee{	
	/*
	 * $param $h =0 无中转行费用  =1 有中转行费用
	 *$param $m =0 普通 =1 高级
	 */
	private $fee1=0.008;
	private $fee2=0.02;
	private $fee3=0.02;
	private $fee4=0.04;
	private $fee5=0.035;
	private $fee6=0.05;
	private $rate=null;
	
	public function __construct(){
		$feeConfig=$config = Zend_Registry::get('config')->toArray();
	}
    
    public function calculate($money,$m=0,$h=0){
      $feelow = array($this->fee1,$this->fee2);    //25000美元以上服务费率
  	  $feemid = array($this->fee3,$this->fee4);     //5000-20000美元的服务器费率
  	  $feehigh = array($this->fee5,$this->fee6);   //0-5000美元的服务费率	  
  	  
  	  $money = intval($money);
  	  if($money > 0 && $money <= 5000 ){
  	      $result = $money * $feehigh[$m];
  	  }elseif($money > 5000 && $money < 25000){
  	  	  $result = $money * $feemid[$m];	  	
  	  }elseif($money >= 25000){
  	  	  $result = $money * $feelow[$m];
  	  }else{
  	  	  exit;
  	  }
  	  if(@$result < 25){
  	  	$result = 25;
  	  }
  	  if($h == 1)$result = $result + 25;
  	  @$result = sprintf('%.1f', (float)$result);
  	  $result = round($result);
  	  return $result;  	  	  
  	}
  	
  	public function USDToCNY($money){
  		$res = $money*floatval($this->rate[0]["UsdBuy"])/100;
  		@$res = sprintf('%.1f', (float)$res);
  		return $res;
  	}
}