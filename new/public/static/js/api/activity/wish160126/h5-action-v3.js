
var hide = 'hide',
    show = 'show',
    active = 'active',
    selected = 'selected',
    UAgent = navigator.userAgent.toLowerCase(),

    VM = initComponent();
    PVM = initPromptComponent();

function  VMPrompt(str){
    if(!str) return ;

    PVM.pstr = str;
    PVM.pflag = false;
    PVM.pboxflag = false;
}

function detectAppVersion(s){                // 最大三级
    if(!s) return ;
    var a0, a1, a2, s0, s1, s2, aExp, sExp, aArr, sArr;

    aExp = /caizhuapp(?:ios)?\/(\d)\.(\d{1,2})(?:\.(\d{0,2}))?/;
    aArr = UAgent.match(aExp);

    if(aArr == null) return false;

    aArr = dConvert(aArr);
    a0 = aArr[1], a1 = aArr[2], a2 = aArr[3];

    sExp = /^(\d)(?:\.(\d{1,2}))?(?:\.(\d{0,2}))?$/;
    sArr = s.match(sExp);   console.log(sArr);
    sArr = dConvert(sArr);
    s0 = sArr[1], s1 = sArr[2], s2 = sArr[3];

    if(a0 > s0){
        return false;
    }else if(a0 == s0){

        if(s1 == undefined) return true;   // 2级版，s=1
        if(a1 > s1){
            return true;
        }else if(a1 == s1){

            // 三级比较
            if(s2 == undefined) return true;   // 2级版，s=1.1
            if(a2 >= s2){
                return true;
            }else{
                return false;
            }

        }else{
            return false;
        }

    }else{
        return false;
    }

    function dConvert(arr){
        if(!arr) return ;
        var arr2 = [];
        for(var i= 0,len=arr.length; i<len; i++){
            arr2 = arr.map(function(n){
                if(n){
                    return parseInt(n);
                }
                return undefined;
            })
        }
        return arr2;
    }

}

Vue.filter('filterAvatar', function(a){
    if(a) return a;
    return 'http://fe.caizhu.com/public/imgs/avatar-user.png';
});

Vue.filter('filterEmoji', function(str){
   if(!str) return '';

    str = str.replace(/\n/ig, '<br>');

    var path = emojiObj.path,
        data = emojiObj.data;

    for(var i=0,m,key,key2,exp,len=data.length; i<len; i++) {
        m = data[i];
        for (key in m) {

            key2 = key.replace(/[\[\]]/gi, '');
            exp = new RegExp('\\['+key2+ '\\]', 'g');
            str = str.replace(exp, '<img width=22 height=22 src="' + emojiObj.path + m[key] + '" />');

        }
    }

    return str;
});

var caizhuAPP = function(){
    var reg = /caizhuapp/i;
    if(reg.test(UAgent)) {
        return true;
    } else {
        return false;
    }
}();

function initAction(opts){

    if(!opts) return ;

    var sharePic,
        shareTitle,
        shareDes,
        shareLink;

    function valueOfShare(){
        var shareOpts = initShareData();

        sharePic = shareOpts.sharePic;
        shareTitle = shareOpts.shareTitle;
        shareDes = shareOpts.shareDes;
        shareLink = shareOpts.shareLink;
    }

    function initPageShow(){
        new Vue({
            el: '#init-pageshow',
            data: {
                pageshowflag: true
            }
        });console.log('mbmbmb');
    }

    caizhuAPP = opts.appDebug ? 1 : caizhuAPP;

    if(caizhuAPP){    // 财猪里执行动作

        callbackBasic = function(){

            initPageShow();
            opts.caizhuRun();
            opts.commonRun();
            valueOfShare();

            var caizhukeyjsShare = new caizhuShare({
                pic: sharePic,
                title: replaceBlank(shareTitle),
                description: replaceBlank(shareDes),
                //link: location.href
                link: shareLink
            });
            caizhukeyjsShare.createUrlMeta();
        }

        createJs('/static/js/api/activity/wish160126/h5-basic-v3.js', callbackBasic);


    }else {

        callbackOther = function(){

            initPageShow();
            opts.otherRun();
            opts.commonRun();
            valueOfShare();

            jsonpCallback = function(){
                var d = arguments[0];

                if(d.flag == 1){
                    var d = d.data;

                    callbackOtherWechat = function(){
                        wechatShare(d, {title: replaceBlank(shareTitle), des: replaceBlank(shareDes), pic: sharePic, link: shareLink});
                    }
                    createJs('http://res.wx.qq.com/open/js/jweixin-1.0.0.js', callbackOtherWechat);

                }
            }

            // wechat share - start
            $.ajax({
                url: 'http://cz.caizhu.com/api/wechat/get-signature?url=' + location.href,
                type: 'get',
                dataType: 'jsonp',
                data: {
                    url: location.href,
                    _callback: 'jsonpCallback'
                }
            });
            // wechat - share - end

        }

        createJs('/static/js/api/activity/wish160126/h5-other-v3.js', callbackOther)

    }

}

function parseAppUrl(opts) {
    if (!opts) return '';

    var url, str, id, type, itype, param, index;

    type = opts.type.toLowerCase();
    index = caizhuAPP ? 1 : 0;

    if(typeof SCHEMAJSON == 'undefined') {
        if (index) {
            return 'caizhu://caizhu/home';
        } else {
            return 'http://caizhukeyjs-home';
        }
    }

    if (type == 'webview') {
        param = opts.url || (encodeURIComponent(location.href));
        url = SCHEMAJSON[type][0]+'?url='+param;

    } else if (type == 'home') {
        return SCHEMAJSON['home'][0];

    } else if (type == 'alltopic'){
        return SCHEMAJSON['alltopic'][index];

    }else if(type == 'rewardlist'){
        id = opts.id || '';
        type = opts.type || '';
        param = opts.author || '';
        itype = opts.itype || '';
        url = SCHEMAJSON[type] ? (SCHEMAJSON[type][index]+'?id='+id+'&type='+itype+'&author='+param) : '';

    } else {
        id = opts.id || '';
        url = SCHEMAJSON[type] ? (SCHEMAJSON[type][index]+'?id='+id) : '';
    }

    if(!url) return SCHEMAJSON.home[index];

    return url

}

function initComponent(){

    Vue.component('app-prompt', {

        props: ['wechatflag', 'qqbflag', 'downflag'],

        template: '<section id="pull-wechat-box"class="pull-wechat-box" :class="{active: wechatflag}"><ul class="pull-wechat clearfix"><li class="pw-info fl"><p>点击右上角菜单</p><p>在默认浏览器中打开并安装应用</p></li><li class="pw-arrow fr"><img src="http://fe.caizhu.com/public/imgs/share-pull.png"alt="指示图"></li></ul></section>' +
        '' +
        '<section id="qqbrowser"class="qqbrowser p-rela" :class="{hide: qqbflag}"><span id="qqbclose"class="qqbclose">X</span><p>QQ、百度浏览器不支持启动App</p><p>建议使用:欧朋、UC、360浏览器等</p></section>' +
        '' +
        '<section id="caizhu-open"class="caizhu-open" :class="{hide: downflag}"><ul class="czo-list"><li class="czo-one logo"><img src="http://fe.caizhu.com/public/imgs/share-avatar.png"></li><li class="czo-two"><strong>财猪</strong><p>理财社交平台</p></li><li class="czo-three"><a href="http://m.caizhu.com/down/down-caizhu" id="czo-open-down">下载</a></li><li></li></ul><span id="open-close-open"class="open-close">X</span></section>'

    });

    Vue.component('app-loading', {
        template: 'loading ...'
    });

    var vm = new Vue({
        el: '#app-prompt',
        data: {
            wechatflag: false,
            qqbflag: true,
            downflag: true
        }
    });

    return vm;

}

function initPromptComponent(){
    Vue.component('app-defineprompt', {
        props: ['pboxflag', 'pflag', 'pstr'],
        methods: {
            close: function(){
                this.pboxflag = true;
                this.pflag = true;
                event.stopPropagation();
            }
        },
        template: '<section id="prompt-box"class="m-prompt-box" :class="{hide: pboxflag}"><div id="prompt"class="m-prompt" :class="{hide: pflag}"><p class="prompt-p"click="is OK">{{pstr}}</p><span class="prompt-one" @click="close">确定</span></div></section>'
    });

    var dprompt = new Vue({
        el: '#define-prompt',
        data: {
            pstr : '您的操作有错误',
            pboxflag: true,
            pflag: true
        }
    });

    return dprompt;
}

function jsonpCallback(){
    var d = arguments[0];

    if(d.flag == 1){
        var d = d.data;

        var wechatJs = document.createElement('script');
        wechatJs.type = 'text/javascript';
        wechatJs.src = 'http://res.wx.qq.com/open/js/jweixin-1.0.0.js';
        wechatJs.onload = function(){
            wechatShare(d, {title: replaceBlank(shareTitle), des: replaceBlank(shareDes), pic: sharePic, link: shareLink});
        };
        document.body.appendChild(wechatJs);

    }
}