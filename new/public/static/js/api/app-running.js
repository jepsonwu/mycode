//document.body.appendChild(document.createTextNode('ok'));

    var initUa = navigator.userAgent.toLowerCase();

    function detect(ua, platform) {
        var os = {}, browser = {},
            webkit = ua.match(/Web[kK]it[\/]{0,1}([\d.]+)/),
            android = ua.match(/(Android);?[\s\/]+([\d.]+)?/),
            osx = !!ua.match(/\(Macintosh\; Intel /),
            ipad = ua.match(/(iPad).*OS\s([\d_]+)/),
            ipod = ua.match(/(iPod)(.*OS\s([\d_]+))?/),
            iphone = !ipad && ua.match(/(iPhone\sOS)\s([\d_]+)/),
            webos = ua.match(/(webOS|hpwOS)[\s\/]([\d.]+)/),
            win = /Win\d{2}|Windows/.test(platform),
            wp = ua.match(/Windows Phone ([\d.]+)/),
            touchpad = webos && ua.match(/TouchPad/),
            kindle = ua.match(/Kindle\/([\d.]+)/),
            silk = ua.match(/Silk\/([\d._]+)/),
            blackberry = ua.match(/(BlackBerry).*Version\/([\d.]+)/),
            bb10 = ua.match(/(BB10).*Version\/([\d.]+)/),
            rimtabletos = ua.match(/(RIM\sTablet\sOS)\s([\d.]+)/),
            playbook = ua.match(/PlayBook/),
            chrome = ua.match(/Chrome\/([\d.]+)/) || ua.match(/CriOS\/([\d.]+)/),
            firefox = ua.match(/Firefox\/([\d.]+)/),
            firefoxos = ua.match(/\((?:Mobile|Tablet); rv:([\d.]+)\).*Firefox\/[\d.]+/),
            ie = ua.match(/MSIE\s([\d.]+)/) || ua.match(/Trident\/[\d](?=[^\?]+).*rv:([0-9.].)/),
            webview = !chrome && ua.match(/(iPhone|iPod|iPad).*AppleWebKit(?!.*Safari)/),
            safari = webview || ua.match(/Version\/([\d.]+)([^S](Safari)|[^M]*(Mobile)[^S]*(Safari))/);


        if (browser.webkit = !!webkit) browser.version = webkit[1]

        if (android) os.android = true, os.version = android[2]
        if (iphone && !ipod) os.ios = os.iphone = true, os.version = iphone[2].replace(/_/g, '.')
        if (ipad) os.ios = os.ipad = true, os.version = ipad[2].replace(/_/g, '.')
        if (ipod) os.ios = os.ipod = true, os.version = ipod[3] ? ipod[3].replace(/_/g, '.') : null
        if (wp) os.wp = true, os.version = wp[1]
        if (webos) os.webos = true, os.version = webos[2]
        if (touchpad) os.touchpad = true
        if (blackberry) os.blackberry = true, os.version = blackberry[2]
        if (bb10) os.bb10 = true, os.version = bb10[2]
        if (rimtabletos) os.rimtabletos = true, os.version = rimtabletos[2]
        if (playbook) browser.playbook = true
        if (kindle) os.kindle = true, os.version = kindle[1]
        if (silk) browser.silk = true, browser.version = silk[1]
        if (!silk && os.android && ua.match(/Kindle Fire/)) browser.silk = true
        if (chrome) browser.chrome = true, browser.version = chrome[1]
        if (firefox) browser.firefox = true, browser.version = firefox[1]
        if (firefoxos) os.firefoxos = true, os.version = firefoxos[1]
        if (ie) browser.ie = true, browser.version = ie[1]
        if (safari && (osx || os.ios || win)) {
            browser.safari = true
            if (!os.ios) browser.version = safari[1]
        }
        if (webview) browser.webview = true

        os.tablet = !!(ipad || playbook || (android && !ua.match(/Mobile/)) ||
        (firefox && ua.match(/Tablet/)) || (ie && !ua.match(/Phone/) && ua.match(/Touch/)))
        os.phone = !!(!os.tablet && !os.ipod && (android || iphone || webos || blackberry || bb10 ||
        (chrome && ua.match(/Android/)) || (chrome && ua.match(/CriOS\/([\d.]+)/)) ||
        (firefox && ua.match(/Mobile/)) || (ie && ua.match(/Touch/))));
        return {
            os: os,
            browser: browser
        }
    }

    var info=detect(navigator.userAgent, navigator.platform);

    var wxFlag=function(){
        if(initUa.match(/MicroMessenger/i)=="micromessenger") {
            return true;
        } else {
            return false;
        }
    }();

    var mQQBrowser = function(){                     //QQ手机浏览器
        var qqBReg = /mqqbrowser\/.{10,15}safari\//i;
        if(qqBReg.test(initUa)) {
            return true;
        } else {
            return false;
        }
    }();

    var baiduBrowser = function(){                     //百度手机浏览器 (android)
        var qqBReg = /baidubrowser/i;
        if(qqBReg.test(initUa)) {
            return true;
        } else {
            return false;
        }
    }();

    var appQQBrowserAnd = function(){                        //  And-QQ客户端自带webkit内核
        var appQQRepAnd = /mqqbrowser\/.{20,25}safari\//i;
        if(appQQRepAnd.test(initUa) && info.os.android) {
            return true;
        } else {
            return false;
        }
    }();

    var appQQBrowser = function(){                        //  IOS-QQ客户端自带webkit内核
        var appQQRep = /mobile\/.{5,12}qq\//i;
        if(appQQRep.test(initUa)) {
            return true;
        } else {
            return false;
        }
    }();

    var sinaWeibo = function(){                        //  新浪微博客户端
        var sinaReg = /mobile(\ssafari)?\/.{6,8}weibo/i;
        if(sinaReg.test(initUa)) {
            return true;
        } else {
            return false;
        }
    }();

    // 正对uc + safari， 在安装了财猪，再返回页面时，处理下载按钮位置
    var ucBrowser = function(){                        //  UC手机浏览器
        var ucReg = /ucbrowser\//i;
        if(ucReg.test(initUa)) {
            return true;
        } else {
            return false;
        }
    }();

    var safariBrowser = function(){                        //  SAFARI手机浏览器
        var saReg = /mobile\/.{6,8}safari\//i;
        if(saReg.test(initUa)) {
            return true;
        } else {
            return false;
        }
    }();



    var hide = 'hide',active = 'active';
    var $pullBox = $('#pull-wechat-box');

    if(wxFlag){

        $('body, html').click(function(){
            $pullBox.removeClass(active);
        });
        $pullBox.click(function(){
            $(this).removeClass(active);
        });

        $('body, html').delegate('.callcaizhu', 'click', function(){
        //$('.callcaizhu').click(function(){
            $pullBox.addClass(active);
            return false;
        });
    }else if(mQQBrowser || baiduBrowser) {

        var hide = 'hide',
            $qqbrowser = $('#qqbrowser'),
            $qqbclose = $('#qqbclose');

        $qqbclose.click(function(){
            $qqbrowser.addClass('hide');
        });

        $('body, html').delegate('.callcaizhu', 'click', function(){
        //$('.callcaizhu').click(function(){
            $qqbrowser.removeClass('hide');
            $('#caizhu-open-down').removeClass(hide);
            return false;
        });

    }else if(appQQBrowser || appQQBrowserAnd || sinaWeibo){

        var hide = 'hide',
            $mqqB = $('#mqqb-prompt'),
            $mqqClose = $mqqB.find('span');

        $mqqClose.click(function(){
            $mqqB.addClass(hide);
            location.href = 'http://m.caizhu.com/down/down-caizhu';
        });

        $('body, html').click(function(){
            $pullBox.removeClass(active);
        });
        $pullBox.click(function(){
            $(this).removeClass(active);
        });

        $('body, html').delegate('.callcaizhu', 'click', function(){
        //$('.callcaizhu').click(function(){
            $pullBox.addClass(active);
            return false;
        });

    }else{

        $('body, html').delegate('.callcaizhu', 'click', function(){
        //$('.callcaizhu').click(function(){

            var url = $(this).data('url');

            commonFunc.createIframe();
            commonFunc.redirect(url);

        });

    }


    //var isChrome =(/Chrome\/([\d.]+)/i.test(initUa) || /CriOS\/([\d.]+)/i.test(initUa)) && !(/360\s{0,10}aphone\s{0,10}browser/i.test(initUa));
    //if(!isChrome){ $('callcaizhu').click(); }

    $('.open-close').click(function(){
        $(this).parent().addClass(hide);
        return false;
    });


function wechatShare(d, options){
    if(!d || !options) return ;
    var title = options.title || '标题',
        description = options.des || '',
        pic = options.pic || (location.protocol+'//'+location.hostname+'/static/imgs/api/app-share.png'),
        link = location.href;

    title = decodeURIComponent(title);
    description = decodeURIComponent(description);

    wx.config({
        debug: false,
        appId: d.appId,
        timestamp: d.timestamp,
        nonceStr: d.nonceStr,
        signature: d.signature,
        jsApiList: ['onMenuShareTimeline', 'onMenuShareAppMessage', 'onMenuShareQQ', 'onMenuShareWeibo', 'menuItem:profile', 'menuItem:addContact']
    });
    wx.ready(function () {

        //分享到朋友圈
        wx.onMenuShareTimeline({
            title: title, // 分享标题
            desc: description, // 分享描述
            link: link, // 分享链接
            imgUrl: pic, // 分享图标
            success: function () {
                // 用户确认分享后执行的回调函数
            },
            cancel: function () {
                // 用户取消分享后执行的回调函数
            }
        });

        //分享给朋友
        wx.onMenuShareAppMessage({
            title: title, // 分享标题
            desc: description, // 分享描述
            link: link, // 分享链接
            imgUrl: pic, // 分享图标
            type: '', // 分享类型,music、video或link，不填默认为link
            dataUrl: '', // 如果type是music或video，则要提供数据链接，默认为空
            success: function () {
                // 用户确认分享后执行的回调函数
            },
            cancel: function () {
                // 用户取消分享后执行的回调函数
            }
        });

        //分享到QQ
        wx.onMenuShareQQ({
            title: title, // 分享标题
            desc: description, // 分享描述
            link: link, // 分享链接
            imgUrl: pic, // 分享图标
            success: function () {
                // 用户确认分享后执行的回调函数
            },
            cancel: function () {
                // 用户取消分享后执行的回调函数
            }
        });

        //分享到腾讯微博
        wx.onMenuShareWeibo({
            title: title, // 分享标题
            desc: description, // 分享描述
            link: link, // 分享链接
            imgUrl: pic, // 分享图标
            success: function () {
                // 用户确认分享后执行的回调函数
            },
            cancel: function () {
                // 用户取消分享后执行的回调函数
            }
        });
    });
}


// active伪状态
var otherTime, otherTouchMove, disabled = 'disabled';

$('body').delegate('.otherlink',{'touchstart': function(){
    var touch = 'touch';
    var $this = $(this);
    otherTouchMove = false;
    $('.otherlink').removeClass(touch);
    otherTime = new Date();
    $this.addClass(touch);
    touchElem = $this;

},'touchmove': function(){
    otherTouchMove = true;
},'touchend': function(){

    var $this = $(this), url;

    if(otherTouchMove) {
        otherTouchMove = false;
        return ;
    }

    var oDisabled = $this.data('disabed');
    if(oDisabled){ return false;}

    $this.removeClass('touch');
    if((new Date()) - otherTime > 300){   // 触摸的时间超过130ms，不触发触摸效果 。。。
        return ;
    }

    url = $this.data('url');
    if(url) location.href = url;
    return ;

}});


function replaceBlank(str){
    if(!str) return ;
    str.toString();
    str = str.replace(/\s*/g, '');
    str = str.substr(0, 50);
    return encodeURIComponent(str);
}

function parseUrl(key){
    if(!key)return false;
    key = key.toLowerCase();
    var uStr=location.search.substring(1),uArr=[];
    if(!uStr) return ;
    uStr = decodeURIComponent(uStr);
    uStr = uStr.toLowerCase();
    uArr=uStr.split('&');
    for(var i=0,uLen=uArr.length;i<uLen;i++){
        uStr=uArr[i].split('=');
        if(uStr[0] == key) return decodeURIComponent(String(uStr[1]));
    }
    return false;
}