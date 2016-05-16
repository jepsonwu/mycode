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

    $('.callcaizhu').click(function(){
        $pullBox.addClass(active);
        return false;
    });
}else if(mQQBrowser) {

    var hide = 'hide',
        $qqbrowser = $('#qqbrowser'),
        $qqbclose = $('#qqbclose');

    $qqbclose.click(function(){
        $qqbrowser.addClass('hide');
    });

    $('.callcaizhu').click(function(){
        $qqbrowser.removeClass('hide');
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

    $('.callcaizhu').click(function(){
        $pullBox.addClass(active);
        return false;
    });

}else{

    $('.callcaizhu').click(function(){
        var $this = $(this),
            url = $this.data('url');

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


function replaceBlank(str){
    if(!str) return ;
    str.toString();
    str = str.replace(/\s*/g, '');
    str = str.substr(0, 50);
    return encodeURIComponent(str);
}