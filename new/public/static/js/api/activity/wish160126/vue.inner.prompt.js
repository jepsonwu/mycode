
var VMIP, VMIPCallback;

Vue.component('app-innerprompt', {
    props: ['ipstr', 'ipflag', 'ipflag00', 'ipflag04', 'ipflag09','ipflag06', 'ipflag01', 'ipflagrule', 'ipflagtel', 'ipflagform', 'ipflagwish', 'formtel', 'formname', 'formtel2', 'formarea'],

    template: '<section id="inner-prompt"class="inner-prompt":class="{hide: ipflag}">' +
    '' +
    '<div class="ip-box ip-box06":class="{hide: ipflag09}"><span class="ip-close"@click="closeIp0"></span><span class="ip-t"></span><div class="ip-m"><p>{{{ipstr}}}</p><span class="ip-close02"@click="closeIp00">确认提交</span><span class="ip-close02"@click="closeIp1">重新许愿</span></div><span class="ip-b"></span></div>' +
    
    '' +
    '<div class="ip-box ip-box00":class="{hide: ipflag00}"><span class="ip-close"@click="closeIp0"></span><span class="ip-t"></span><div class="ip-m"><p>{{{ipstr}}}</p><span class="ip-close02"@click="closeIp00">好哒</span></div><span class="ip-b"></span></div>' +
    '' +
    '<div class="ip-box ip-box01":class="{hide: ipflag04}"><span class="ip-close"@click="closeIp01"></span><span class="ip-t"></span><div class="ip-m"><p>{{{ipstr}}}~</p><span class="ip-close02"@click="closeIp00">好哒</span></div><span class="ip-b"></span></div>' +
    '' +
//  '<div class="ip-box ip-box01":class="{hide: ipflag04}"><span class="ip-close"@click="closeIp01"></span><span class="ip-t"></span><div class="ip-m"><p>{{{ipstr}}}~</p><span class="ip-close02"@click="closeIp00">好哒</span></div><span class="ip-b"></span></div>' +
//  '' +
//  '<div class="ip-box ip-box01":class="{hide: ipflag04}"><span class="ip-close"@click="closeIp01"></span><span class="ip-t"></span><div class="ip-m"><p>{{{ipstr}}}</p><span class="ip-close02"@click="closeIp00">好哒</span></div><span class="ip-b"></span></div>' +
//  '' +
    '<div class="ip-box ip-box02":class="{hide: ipflagrule}"><span class="ip-close"@click="closeIpRule"></span><span class="ip-t"></span><div class="ip-m"><span class="ip-ruletit">活动规则</span><ul class="ip-faq"><li><strong>怎样去许愿？</strong><p>答：点击首页我要去许愿，就可以选择你心仪的商品。</p></li><li><strong>许完愿后怎么玩？</strong><p>答：每名小伙伴选完商品后，即可收集愿望财猪，帮助拿到奖品。</p></li><li><strong>如何实现心愿？</strong><p>答：集满500只财猪即可兑换所选商品的优惠券，集满1000只财猪即有机会免费将愿望车里的商品带回家。</p></li><li><strong>签到和分享能获得什么？</strong><p>答：通过页面抽奖可以随机获得1-600只财猪。<br>通过登录APP签到可获得200-500只财猪。<br>邀请小伙伴帮忙，每名小伙伴最低可以帮你获得20只财猪。</p></li><li><strong>其他你需要知道的：</strong><p>活动期间（2月2日-2月22日）若发现作弊行为，财猪有权取消作弊用户的奖励资格，免单机会将在2月22日元宵节进行开奖，财猪也会在第一时间联系获奖用户。<br>如有其他问题可以在app内询问财猪小秘书。<br>或直接拨打客服电话400-681-2858</p></li></ul></div><span class="ip-b"></span></div>' +
    '' +
    '<div class="ip-box ip-box03":class="{hide: ipflagtel}"><span class="ip-close"@click="closeIpTel"></span><span class="ip-t"></span><div class="ip-m"><p>请填写手机号码：</p><div class="tel-box"><input name="tel"v-model="formtel"placeholder="请输入手机号码"></div><span class="ip-close02"@click="ajaxTel">好哒</span></div><span class="ip-b"></span></div>' +
    '' +
    '<div class="ip-box ip-box04":class="{hide: ipflagform}"><span class="ip-close"@click="closeIpForm"></span><span class="ip-t"></span><div class="ip-m"><ul class="coup-flist"><li class="coup-fone"><p>亲爱的，填好信息。<br>我们将在第一时间寄出你的礼品</p></li><li class="coup-fone"><input name="name"placeholder="请输入姓名"></li><li class="coup-fone"><input name="tel"palceholder="请输入手机号码"></li><li class="coup-fone"><input name="area"placeholder="请输入寄存地址"></li><li class="coup-fone"><span class="coup-btn ip-close02"@click="ajaxForm">提交</span></li></ul></div><span class="ip-b"></span></div>' +
    '' +
    '<div class="ip-box ip-box05":class="{hide: ipflagwish}"><span class="ip-close"@click="closeIpWish"></span><span class="ip-t"></span><div class="ip-m"><p>亲爱的许下愿望后，将不能更改愿望啦！是否确认许下这个愿望</p><span class="ip-close02"@click="closeIp05">确认提交</span><span class="ip-close02"@click="closeIp05">重新许愿</span></div><span class="ip-b"></span></div></section>',



    methods: {
        closeIp1: function(e){
            this.ipflag00 = true;
            this.ipflag = true;
            if(VMIPCallback){
            	//console.log('VMIPCallback',VMIPCallback);
               // VMIPCallback();
                VMIPCallback = '';
            }
            if(VMIPCallback2){
                VMIPCallback2();
                VMIPCallback2 = '';
            }
            e.stopPropagation();
        },
        closeIp0: function(e){
            this.ipflag00 = true;
            this.ipflag = true;
            this.ipflag09= true;
            if(VMIPCallback){
                //VMIPCallback();
                //VMIPCallback = '';
            }
            e.stopPropagation();
        },
        closeIp00: function(e){
        	this.ipflag09= true;
            this.ipflag00 = true;
            this.ipflag = true;
            if(VMIPCallback){
                VMIPCallback();
                VMIPCallback = '';
            }
            e.stopPropagation();
        },
        closeIp01: function(e){
            this.ipflag01 = true;
            this.ipflag04 = true;
            this.ipflag05 = true;
            this.ipflag = true;
            e.stopPropagation();
        },
        closeIpRule: function(e){
            this.ipflagrule = true;
            this.ipflag = true;
            e.stopPropagation();
        },
        closeIpTel: function(e){
            this.ipflagtel = true;
            this.ipflag = true;
            e.stopPropagation();
        },
        closeIpForm: function(e){
            this.ipflagform = true;
            this.ipflag = true;
            e.stopPropagation();
        },
        closeIpWish: function(e){
            this.ipflagwish = true;
            this.ipflag = true;
            e.stopPropagation();
        },

        //
        ajaxTel: function(){
            var _this = this;
debug(_this.formtel)
            var val = _this.formtel,
                reg = /1\d{10}/;
            if(!reg.test(val)){VMPrompt('请填写有效的的手机号码'); return false;}

            $.ajax({
                url: '/api/activity-wish/wish-login',
                dataType: 'json',
                data: {
                    phone: _this.formtel
                },
                success: function(d){
                    if(d.flag == 1){
                        location.reload();
                    }else{
                        VMPrompt(d.msg);
                    }
                },
                error: function(err){
                    debug('网络错误!!!');
                }
            });
        },
        ajaxForm: function(){
        }
    }
});

VMIP = new Vue({
    el: '#app-innerprompt',
    data: {
        ipstr: '财猪提示：',
        ipflag: true,
        ipflag00: true,
        ipflag01: true,
        ipflag02: true,
        ipflag04: true,
        ipflag05: true,
        ipflag09: true,
        ipflagrule: true,
        ipflagtel: true,
        ipflagform: true,
        ipflagwish: true,
        formtel: '',            // 电话
        formname: ''
    }
});

function  VMIPgitpt(str, fn,fn2){
    if(!str) return ;
    VMIP.ipstr = str;
    VMIP.ipflag09 = false;
    VMIP.ipflag = false;
    if(fn){
        VMIPCallback = fn;
        VMIPCallback2 =fn2;
    }
}

function  VMIPrompt(str, fn){
    if(!str) return ;
    VMIP.ipstr = str;
    VMIP.ipflag00 = false;
    VMIP.ipflag = false;
    if(fn){
        VMIPCallback = fn;
    }
}
function  VMIPromptfi(str, fn){
    if(!str) return ;
    VMIP.ipstr = str;
	VMIP.ipflag01 = false;
	VMIP.ipflag = false;
    if(fn){
        VMIPCallback = fn;
    }
}
function  VMIPromptsc(str, fn){
    if(!str) return ;
    VMIP.ipstr = str;
    VMIP.ipflag04 = false;
	VMIP.ipflag = false;
    if(fn){
        VMIPCallback = fn;
    }
}
function  VMIPromptth(str, fn){
    if(!str) return ;
    VMIP.ipstr = str;
    VMIP.ipflag05 = false;
    VMIP.ipflag = false;
    if(fn){
        VMIPCallback = fn;
    }
}
function VMIPOpenTel(){
    VMIP.ipflag = false;
    VMIP.ipflagtel = false;
}
function VMIPOpenForm(){
    VMIP.ipflag = false;
    VMIP.ipflagform = false;
}

Vue.filter('filterYuan', function(a){
    //return ((parseInt(a) / 100).toFixed);
    if(!a) return 0;
    var b = (parseInt(a) / 100).toFixed();
    return b;
})