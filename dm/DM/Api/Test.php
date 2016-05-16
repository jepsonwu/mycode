<?php

/**
 * API测试控制器
 *
 * @author Bruce
 * @since 2014/05/22
 */
class DM_Api_Test extends DM_Controller_Api
{
	public function init()
	{
		parent::init();

		//生产环境无测试
		if (APPLICATION_ENV == 'production') die('no support.');
	}

	public function indexAction()
	{
		echo <<<bbb
<html>
<head>
	<meta charset="UTF-8">
    <title>DM-Api 测试</title>
    <script type="text/javascript" src="http://static.duomai.com/jquery-1.10.2.min.js"></script>
</head>
<body>
<pre>
<a href="/api/test/field">快速获取表字段描述</a>

$.post('url',{CsrfCode:CsrfCode}, function(str){var re; try{eval('re='+str);}catch(err){re=str};console.log(re)});

注册接口：
$.post('/api/member/register',{CsrfCode:CsrfCode, email:'gogogo@163.com', password:'fff3322', name:'hanhui', name_en:'hanhui_en', mobile:'13675851253'}, function(str){var re; try{eval('re='+str);}catch(err){re=str};console.log(re)});

登录接口：
$.post('/api/member/login',{CsrfCode:CsrfCode, username:'gogogo@163.com', password:'fff3322'}, function(str){var re; try{eval('re='+str);}catch(err){re=str};console.log(re)});

退出接口：
$.post('/api/member/logout',{CsrfCode:CsrfCode}, function(str){var re; try{eval('re='+str);}catch(err){re=str};console.log(re)});

</pre>
<script type="text/javascript">
var CsrfCode='{$this->createCsrfCode()}';
</script>   
bbb;
	}

	/**
	 * 生成返回字段描述的功能
	 */
	public function fieldAction()
	{
		header('Content-Type:text/html;charset=utf-8');
		if (DM_Controller_Front::getInstance()->getHttpRequest()->isPost()) {
			$create = $this->_getParam('create', '');
			preg_match_all('/`(\w+?)`\s+(\w+)?[^\n]*?COMMENT\s+[\'"]([^\'"]+?)[\'"]/is', $create, $matches);

			//print_r($matches);

			if (empty($matches[1])) die('没有匹配的字段描述');

			$code = '';
			foreach ($matches[1] as $key => $value) {
				$code .= "<tr>
<td>{$value}</td>
<td>{$matches[2][$key]}</td>
<td>{$matches[3][$key]}</td>
</tr>\n";
			}

			echo "<a href=\"/api/test\">返回测试页</a></br><h2>内容如下:</h2><table>
<tbody>
<tr><th width=\"30%\">字段名称</th><th width=\"20%\">数据类型</th><th width=\"50%\">说明</th></tr>
{$code}
</tbody>
</table> <h2>源码如下:</h2><pre>" . htmlspecialchars($code) . '</pre>';

			die();
		}

		echo <<<bbb
<html>
<head>
	<meta charset="UTF-8">
    <title>DM-Api 生成字段描述</title>
    <script type="text/javascript" src="http://static.duomai.com/jquery-1.10.2.min.js"></script>
</head>
<body>
<a href="/api/test">返回测试页</a>
<form method="post" action="/api/test/field">
 <label>请粘贴表格的创建代码，不知道怎么用使用"show create table test;"</label><br>
 <textarea name="create" rows="15" style="width:800px">
CREATE TABLE `escrows` (
  `EscrowID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `BuyerID` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '买家会员ID',
  `SellerID` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '卖家会员ID',
  `Currency` enum('CNY','USD','EURO') DEFAULT 'CNY' COMMENT '支付货币',
  `Amount` double(12,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '交易金额 商品金额总成',
  `AddAmount` double(12,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '额外增加 用于改价等',
  `MinusAmount` double(12,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '优惠金额 红包等',
  `FinalAmount` double(12,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '实付金额 Amount+AddAmount-MinusAmount',
  `Status` enum('WAIT_BUYER_DOWNPAY','WAIT_SELLER_ACCEPT','WAIT_BUYER_PAYALL','WAIT_SELLER_SHIP','WAIT_BUYER_TAKEOVER','TAKEOVER_ARGUE','TRANSACTION_COMPLETE','TRANSACTION_CANCEL') NOT NULL DEFAULT 'WAIT_BUYER_DOWNPAY' COMMENT '待付定金(WAIT_BUER_DOWNPAY)、买手接单(WAIT_SELLER_ACCEPT)、待付尾款(WAIT_BUER_PAYALL)、买手发货(WAIT_SELLER_SHIP)、等待确认收货(WAIT_BUYER_TAKEOVER)、退款维权(TAKEOVER_ARGUE)、交易成功(TRANSACTION_COMPLETE)、交易关闭(TRANSACTION_CANCEL)',
  `CreateTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '交易发起时间',
  `DownpayTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '预付款时间',
  `AcceptTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '买手接单时间',
  `PayallTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '付尾款时间',
  `ShipTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '发货时间',
  `ShipName` varchar(100) NOT NULL DEFAULT '' COMMENT '物流公司',
  `ShipNo` varchar(100) NOT NULL DEFAULT '' COMMENT '物流单号',
  `TakeoverPeriod` smallint(6) NOT NULL COMMENT '默认确认收货周期，系统配置，可申请延长',
  `TakeoverTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '确认收货时间',
  `FinishTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '交易完成时间',
  `OrderNO` varchar(60) NOT NULL DEFAULT '' COMMENT '订单号',
  `BuyerNote` varchar(300) DEFAULT NULL COMMENT '买家备注',
  `SellerNote` varchar(300) DEFAULT NULL COMMENT '卖家备注',
  PRIMARY KEY (`EscrowID`),
  KEY `BuyerID` (`BuyerID`),
  KEY `CreateTime` (`CreateTime`),
  KEY `Status` (`Status`),
  KEY `OrderNum` (`OrderNO`),
  KEY `SellerID` (`SellerID`)
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8 COMMENT='交易订单'        
        
 </textarea><br>
 <input type="submit" value="提交">                          
</form>                
</body>
</html>
bbb;
	}

}
