<?php
class DM_Helper_Exchange{
	public $rate=null;
	
	public function __construct(){
		$Model = new DM_Model_Table_ExchangeRate_ExchangeRate();
		$this->rate=$Model->getlast();
	}

    /**
     * 美元兑换人民币
     * 卖出价：100美元能买入多少人民币
     * @param $money
     * @return float
     */
    public function USDToCNY($money){
		$res = $money/floatval($this->rate[0]["UsdBuy"])*100;
		@$res = $this->valfloat((float)$res);
		return $res;
	}

    /**
     * 人民币兑换美元
     * 买入价：要买100美元需要花多少人民币
     * @param $money
     * @return float
     */
    public function CNYToUSD($money){
		$res = $money/100*floatval($this->rate[0]["UsdSell"]);
		@$res =$this->valfloat((float)$res);
		return $res;
	}

    /**
     * 欧元兑换人民币
     * @param $money
     * @return float
     */
    public function EUROToCNY($money){
		$res = $money/floatval($this->rate[0]["EurBuy"])*100;
		@$res =$this->valfloat((float)$res);
		return $res;
	}

    /**
     * 人民币兑换欧元
     *
     * @param $money
     * @return float
     */
    public function CNYToEURO($money){
		$res = $money/100*floatval($this->rate[0]["EurSell"]);
		@$res = $this->valfloat((float)$res);
		return $res;
	}
	
	public function USDconvCNY($money){
		$res = $money*floatval($this->rate[0]["UsdBuy"])/100;
		@$res =$this->valfloat((float)$res);
		return $res;
	}

    /**
     * EUROExCNY 
     * 欧元兑换人民币，源币种金额
     * 
     * @param mixed $euroAmount 
     * @access public
     * @return void
     */
    public function EUROExCNY($euroAmount){
        $res = $euroAmount * floatval($this->rate[0]['EurBuy']) / 100;
        return $this->valfloat($res);
    }

    /**
     * CNYExUSD 
     * 人民币兑换美元，源币种金额
     * 
     * @param mixed $cnyAmount 
     * @access public
     * @return void
     */
    public function CNYExUSD($cnyAmount){
        $res = $cnyAmount / floatval($this->rate[0]['UsdSell']) * 100;
        return $this->valfloat($res);
    }

    /**
     * USDExCNY 
     * 美元兑换人民币，源币种金额
     * 
     * @param mixed $usdAmount 
     * @access public
     * @return void
     */
    public function USDExCNY($usdAmount){
        $res = $usdAmount * floatval($this->rate[0]['UsdBuy']) / 100;
        return $this->valfloat($res);
    }

    /**
     * CNYExEURO 
     * 人民币兑换欧元，源币种金额
     * 
     * @param mixed $cnyAmount 
     * @access public
     * @return void
     */
    public function CNYExEURO($cnyAmount){
        $res = $cnyAmount / floatval($this->rate[0]['EurSell']) * 100;
        return $res;
    }
	 
    /**
     * exchange 
     * 兑换货币
     * 
     * @param mixed $money 兑出金额
     * @param mixed $currencyout 兑出币种
     * @param mixed $currencyin 兑入币种
     * @access public
     * @return void
     */
	public function exchange($money,$currencyout,$currencyin){
		if($currencyout=="USD" && $currencyin=="CNY"){
			return $this->USDToCNY($money);
		}elseif($currencyout=="CNY" && $currencyin=="USD"){
            return $this->CNYToUSD($money);
		}elseif($currencyout=="EURO" && $currencyin=="CNY"){
			return $this->EUROToCNY($money);
		}elseif($currencyout=="CNY" && $currencyin=="EURO"){
			return $this->CNYToEURO($money);
		}else{
			return false;
		}
	}
	
	public function valfloat($money){
        if($money<0.01){
        	$money=0.010;      	
        }    
        $money = sprintf('%.3f', $money);
        $money = $money*100;
        $money = ceil($money);
        $money = $money/100;
        return $money;
	}
}
