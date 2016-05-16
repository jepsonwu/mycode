var dn=dn || {};
dn.member=dn.member || {};

dn.member.view=function(primary_id){
	var self=this;
	if(!primary_id) return false;
	
	var id='member-detail-dialog-'+primary_id;
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
            'href':'/admin/member/view?member_id='+self.primary_id,
            'width':641,
            'height':410,
            'modal':true,
            'resizable':false,
            'cache':false,
            'title':'查看用户资料',
            'onLoad':function(){
			
            }
		});
	}

	return self;
}