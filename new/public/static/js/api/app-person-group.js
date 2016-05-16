
$(function(){

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

    var wxFlag=function(){
        var ua = navigator.userAgent.toLowerCase();
        if(ua.match(/MicroMessenger/i)=="micromessenger") {
            return true;
        } else {
            return false;
        }
    }();

    var mQQBrowser = function(){                                   //QQ手机浏览器
        var ua = navigator.userAgent.toLowerCase();
        if(ua.match(/MQQBrowser/i)=="mqqbrowser") {
            return true;
        } else {
            return false;
        }
    }();

    var info=detect(navigator.userAgent, navigator.platform);


    var hide = 'hide';
    var $osAnd = $('#os-android'), $osIos = $('#os-ios');
    var winW = window.innerWidth, winH = window.innerHeight;
    $osAnd.css({'width': winW, 'height': winH});
    $osIos.css({'width': winW, 'height': winH});
    $('.os-close').click(function(){
        $(this).parent().addClass(hide);
    });

    if(wxFlag){
        $('footer').click(function(){
            if(info.os.android){
                $osAnd.removeClass(hide)
            }else if(info.os.ios){
                $osIos.removeClass(hide);
                // $('.po-des').eq(0).append(document.createTextNode(' + '));
            }
        });
    }else if(mQQBrowser){
        var hide = 'hide',
            $mqqB = $('#mqqb-prompt'),
            $mqqClose = $mqqB.find('span');

        $mqqClose.click(function(){
            $mqqB.addClass(hide);
            location.href = '/api/public/down-load';
        });

        $('footer').click(function(){
            $mqqB.removeClass(hide);
            setTimeout(function(){
                $mqqB.addClass(hide);
                location.href = '/api/public/down-load';
            }, 5000);
        });

    }else{

        var $this = $(this), param = $this.data('param');

        var loadIframe = null;
        function createIframe(){
            var iframe = document.createElement("iframe");
            iframe.style.cssText = "display:none;width:0px;height:0px;";
            document.body.appendChild(iframe);
            loadIframe = iframe;
        }

        // 应用页面重写
        /*
        function redirect(){
            var rUa = navigator.userAgent;
            isChrome =rUa.match(/Chrome\/([\d.]+)/) || rUa.match(/CriOS\/([\d.]+)/);
            if(isChrome){
                if(info.os.android){
                    window.location.href = 'intent://caizhu/userInfo?id=' + memberID + '#Intent;scheme=caizhu;package=com.caizhu.caizhu;end';
                }else{
                    window.location.href = 'caizhu://caizhu/userInfo?id='+memberID;
                }
            }else{
                loadIframe.src="caizhu://caizhu/userInfo?id="+memberID;
            }
            var t = Date.now();
            setTimeout(function(){
                if(Date.now()-t < 600){
                    location.href="/api/public/down-load";
                }
            },500)
        }
        */

        $('footer').click(function(){

            createIframe();
            redirect();

        })
    }

});
