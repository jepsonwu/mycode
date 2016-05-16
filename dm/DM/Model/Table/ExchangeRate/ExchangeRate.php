<?php
class DM_Model_Table_ExchangeRate_ExchangeRate extends Zend_Db_Table {

    protected $_name = 'exchange_rates';
    
    public function add($data){
    	
    	//$data['Date'] = time ();  			
    	$return = $this->insert($data);
    	//return $return?true:false;
    	return $data;
    }

    /* @param  UsdBuy字段   dn美元买入价   
     * @param  UsdSell字段  dn美元卖出价  
     * @param  EurBuy字段   dn欧元买入价   
       @param  EurSell字段  dn欧元卖出价
     */
    public function getlast(){
    	$sql = "select * from exchange_rates order by Id desc limit 0,1";
    	return $this->_db->fetchAll($sql);	 
    }

}
