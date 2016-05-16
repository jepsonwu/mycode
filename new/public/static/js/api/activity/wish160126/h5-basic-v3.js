(function(){

    var acTime, touchMove;

    $('body').delegate('.boxapp',{'touchstart': function(){
        var touch = 'touch';
        var $this = $(this);
        touchMove = false;
        $('.boxapp').removeClass(touch);
        acTime = new Date();
        $this.addClass(touch);
        touchElem = $this;

    },'touchmove': function(){
        touchMove = true;
    },'touchend': function(){

        var $this = $(this), url, opts={};

        var other = 'other',
            mode = $this.data('mode');
        if(mode == other && caizhuAPP) return true;

        if(touchMove) {
            touchMove = false;
            return ;
        }

        var oDisabled = $this.data('disabled');
        if(oDisabled){ return false;}

        $this.removeClass('touch');
        if((new Date()) - acTime > 300){   // 触摸的时间超过130ms，不触发触摸效果 。。。
            return ;
        }

        opts.type = $this.data('type');
        opts.id = $this.data('id');
        opts.url = $this.data('url');
        opts.author = $this.data('author');
        opts.itype = $this.data('itype');
        url = parseAppUrl(opts);

        if(url) location.href = url;
        return ;

    }});

})();


function caizhuShare(options){

    this.pic = encodeURIComponent(options.pic || 'http://fe.caizhu.com/public/imgs/share-avatar.png'),
    this.title = encodeURIComponent(options.title || ''),
    this.des = encodeURIComponent(options.description || ''),
    this.link = encodeURIComponent(options.link || location.href);
    this.str = 'http://caizhukeyjs-share?pic='+this.pic+'&title='+this.title+'&description='+this.des+'&link='+this.link;

}
caizhuShare.prototype = {
    getUrl: function(){
        //document.body.appendChild(document.createTextNode('这个是跳转'));
        location.href = this.str;
    },
    getUrlStr: function(){
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