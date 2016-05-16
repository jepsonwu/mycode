(function(){
var _LANG={};	
var locale=window.locale ? window.locale : 'zh';

_LANG.zh=
	{
		//交易信息状态
		"escrow.Status.WAIT_AGREE":'等待同意条款',
		"escrow.Status.WAIT_BUYER_PAYMENT":'等待买家付款',
		"escrow.Status.WAIT_SELLER_TRANSFER":'等待卖家转移',
		"escrow.Status.WAIT_BUYER_ACCEPT":'等待买家接收',
		"escrow.Status.WAIT_DN_PAYMENT":'等待DN打款',
		"escrow.Status.TRANSACTION_COMPLETED":'交易结束',
		"escrow.Status.TRANSACTION_CANCELLED":'交易取消',
		//服务类型
		"escrow.ServiceType.STANDARD":'标准服务',
		"escrow.ServiceType.ADVANCED":'高级服务',
		//信息披露
		"escrow.DisclosureInfo.BUYER":'向买方披露',
		"escrow.DisclosureInfo.SELLER":'向卖方披露',
		"escrow.DisclosureInfo.BOTH":'向双方披露',
		"escrow.DisclosureInfo.CONFIDENTIAL":'完全保密'
	};	
	
var translate=function(token){
	if(_LANG[locale][token]){
		return _LANG[locale][token];
	}else{
		return '';
	}
}

window.translate=translate;
/**
 * 触发机制：
 * <span class="translate-trigger" key="escrow.Status.TRANSACTION_COMPLETED">交易结束</span>
 * 在每个有显示的页尾调用translate.trigger();
 */
translate.trigger=function(){
	$('.translate-trigger').each(function(index, element){
		$(element).text(translate($(element).attr('key')));
		$(element).removeClass("translate-trigger").addClass("translate-done");
	})
};
})();