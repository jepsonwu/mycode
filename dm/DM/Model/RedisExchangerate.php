<?php
class DM_Model_RedisExchangerate  {

    /* param int $i  1 美元  2 欧元
     *  param  str $mode  currency 币种  transfer_buy 现汇卖价 cash_buy 现钞买价 transfer_sell 现汇买 
     *                   cash_sell 现钞买  boc_conv  中行折算 date 日期  time 时间
     */
    public function getRate($i=1,$mode="boc_conv"){
        $redis=DM_Module_ZendRedis::getInstance();      
        $tmp=$redis->hget("exchange:$i",$mode);
        exit($tmp);
    }

    public function update($arr){ 
        foreach($arr[0] as $value){
            $value=substr($value,1,strlen($value)-3);
            $arrtmp=explode(",",$value);
            $arrtmp2['currency']=$arrtmp[0];
            $arrtmp2['transfer_buy']=$arrtmp[1];
            $arrtmp2['cash_buy']=$arrtmp[2];
            $arrtmp2['transfer_sell']=$arrtmp[3];
            $arrtmp2['cash_sell']=$arrtmp[4];
            $arrtmp2['boc_conv']=$arrtmp[5];
            $arrtmp2['date']=$arrtmp[6];
            $arrtmp2['time']=$arrtmp[7];
            $arrrate[]=$arrtmp2;
        }
        $redis=DM_Module_ZendRedis::getInstance();
        for($i=0;$i<count($arrrate);$i++){
            $redis->hmset("exchange:$i",$arrrate[$i]);
        }      
    }

    public function listRate(){
        $redis=DM_Module_ZendRedis::getInstance();
        for($i=0;$i<=1;$i++){
            $tmp=$redis->hgetall("exchange:$i");
            unset($tmp[0]);unset($tmp[1]);$tmp["keyid"]=$i;
            $list[]=$tmp;
        }
        return $list;
    }


}