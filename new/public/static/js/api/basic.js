// active伪状态
var acTime, touchMove;

$('body').delegate('.boxlink',{'touchstart': function(){
    var touch = 'touch';
    var $this = $(this);
    touchMove = false;
    $('.boxlink').removeClass(touch);
    acTime = new Date();
    $this.addClass(touch);
    touchElem = $this;

},'touchmove': function(){
    touchMove = true;
},'touchend': function(){

    var $this = $(this), url;

    if(touchMove) {
        touchMove = false;
        return ;
    }

    app = $this.data('app');
    if(app) {location.href = app; return ; }

    $this.removeClass('touch');
    if((new Date()) - acTime > 300){   // 触摸的时间超过130ms，不触发触摸效果 。。。
        return ;
    }

    url = $this.data('url');
    if(url) location.href = url;
    return ;

}});

function caizhuShare(options){

    this.pic = encodeURIComponent(options.pic || 'http://fe.caizhu.com/public/imgs/share-avatar.png'),
    this.title = encodeURIComponent(options.title || ''),
    this.des = encodeURIComponent(options.description || ''),
    this.link = encodeURIComponent(options.link || location.href);
    this.str = 'http://caizhukeyjs-share?pic='+this.pic+'&title='+this.title+'&description='+this.des+'&link='+this.link;

}
caizhuShare.prototype = {
    getUrl: function(){
        document.body.appendChild(document.createTextNode('这个是跳转'));
        location.href = this.str;
    },
    getUrlStr: function(){
        document.body.appendChild(document.createTextNode('这个是返回字符转'));
        return this.str;
    },
    createUrlMeta: function(){
        var head,
            meta = document.createElement('meta');
        meta.name = 'share';
        meta.id = 'share';
        meta.content = this.str;
        head = document.getElementsByTagName('head')[0];
        head.appendChild(meta);
    }
}

function replaceBlank(str){
    if(!str) return ;
    str.toString();
    str = str.replace(/\s*/g, '');
    str = str.substr(0, 50);
    return str;
}

function parseUrl(key){
    if(!key)return false;
    key = key.toLowerCase();
    var uStr=location.search.substring(1),uArr=[];
    if(!uStr) return ;
    uStr = uStr.toLowerCase();
    uArr=uStr.split('&');
    for(var i=0,uLen=uArr.length;i<uLen;i++){
        uStr=uArr[i].split('=');
        if(uStr[0] == key) return decodeURIComponent(String(uStr[1]));
    }
    return false;
}