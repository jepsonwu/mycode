var dn=dn || {};
dn.escrow=dn.escrow || {};

dn.escrow.view=function(primary_id){
	var self=this;
	if(!primary_id) return false;
	
	var id='escrow-detail-dialog-'+primary_id;
	self.selector='#'+id;
	self.primary_id=primary_id;
	
	if($(self.selector).length){
		$(self.selector).dialog('open');
		$(self.selector).dialog('refresh');
		return self;
	}else{
		var tpl='<div id="'+id+'"></div>';
		$('body').append(tpl);
		
		$(self.selector).dialog({
            'href':'/admin/escrow/view?escrow_id='+self.primary_id,
            'width':600,
            'height':520,
            'modal':true,
            'resizable':false,
            'cache':false,
            'title':'查看交易详情',
            'zIndex':8000,
            'onLoad':function(){
            }
		});
	}

	return self;
}