(function(){

    var document = window.document,
        body = document.body,
        support = {
            transform3d: ("WebKitCSSMatrix" in window && "m11" in new WebKitCSSMatrix()),
            touch: ("ontouchstart" in window)
        };

    function getTranslate(x, y){
        var distX = x, distY = y;
        return support.transform3d ? "translate3d("+ distX +"px, "+ distY +"px, 0)" : "translate("+ distX +"px, "+ distY +"px)";
    }

    function getPage(event, page){
        return support.touch ? event.changedTouches[0][page] : event[page];
    }

    var ImagesShow = function(){};

    ImagesShow.prototype = {

        init: function(param){
            var self = this,
                params = param || {};

            self.initShowDom();
            var imgList = document.querySelectorAll(param.elem + ' img');

            self.buffMove = 3;    // 缓冲系数
            self.buffScale = 2;   // 放大系数
            self.finger = false;  // 触摸手指状态： false：单指 ，  true：多指

            self.destroy();

            var picSrc;
            for(var i=0,len=imgList.length; i<len; i++){
                imgList[i].addEventListener('click', function(e){

                    var _this = this;
                    var pic;

                    // 父标签是链接，不放大
                    if(articleSystem){
                        var pNode = _this.parentNode,
                            pTag = pNode.tagName.toLowerCase(),
                            pHref = pNode.getAttribute('href');

                        if(pTag == 'a' && pHref){
                            return true;
                        }
                    }


                    picSrc = _this.src;
                    picSrc = self.picUrlFilter(picSrc);
                    self.showImg = document.createElement('img');
                    pic = self.showImg;
                    pic.src = picSrc;

                    self.showMask.style.cssText = 'display:block;';
                    pic.onload = function(){

                        pic.style.cssText = 'margin-top: -' + (pic.offsetHeight/2) + 'px';
                        document.addEventListener('touchmove', self.eventStop, false);
                        self.imgBaseWidth = pic.offsetWidth;
                        self.imgBaseHeight = pic.offsetHeight;

                        self.addEventStart({
                            wrapX: self.showMask.offsetWidth,
                            wrapY: self.showMask.offsetHeight,
                            mapX: pic.width,
                            mapY: pic.height
                        });

                    };
                    self.showMask.appendChild(pic);


                }, false)

            }



        },
        picUrlFilter: function(str){
            if(!str) return ;
            var flag;

            flag = str.indexOf('?');
            if(flag > -1) return str.substring(0, flag);
            return str;

        },
        addEventStart: function(param){
            if(!param) return ;
            var self = this,
                params = param || {};

            self.wrapX = params.wrapX || 0;
            self.wrapY = params.wrapY || 0;
            self.mapX = params.mapX || 0;
            self.mapY = params.mapY || 0;

            self.outDistY = (self.mapY - self.wrapY)/2;

            self.width = self.mapX - self.wrapX;
            self.height = self.mapY - self.wrapY;

            self.showImg.addEventListener('touchstart', function(e){
                self.touchstart(e);
            }, false);

            self.showImg.addEventListener('touchmove', function(e) {
                self.touchmove(e);
            }, false);

            self.showImg.addEventListener('touchend', function(e) {
                self.touchend(e);
            }, false);


        },
        initShowDom: function(){
            var self = this;
            self.showClose = document.createElement('span');
            self.showClose.className = 'show-close';
            self.showClose.appendChild(document.createTextNode('x'));
            self.showMask = document.createElement('div');
            self.showMask.className = 'show-mask hide';

            self.showMask.appendChild(this.showClose);
            body.appendChild(self.showMask);

            self.showClose.addEventListener('click', function(e){

                self.showMask.style.cssText = 'display:none';
                self.destroy();
                self.showMask.removeChild(self.showImg);
                document.removeEventListener('touchmove', self.eventStop, false);

                e.preventDefault();
                e.stopPropagation();

            }, false);

        },
        touchstart: function(e){
            var self = this;

            e.preventDefault();

            var touchTarget = e.targetTouches.length; //获得触控点数

            self.changeData(); //重新初始化图片、可视区域数据，由于放大会产生新的计算

            if(touchTarget == 1){
                // 获取开始坐标
                self.basePageX = getPage(e, "pageX");
                self.basePageY = getPage(e, "pageY");

                self.finger = false;
            }else{
                self.finger = true;

                self.startFingerDist = self.getTouchDist(e).dist;
                self.startFingerX    = self.getTouchDist(e).x;
                self.startFingerY    = self.getTouchDist(e).y;
            }

        },
        touchmove: function(e){
            var self = this;

            e.preventDefault();
            e.stopPropagation();

            var touchTarget = e.targetTouches.length; //获得触控点数

            if(touchTarget == 1 && !self.finger){
                self.move(e);
            }

            if(touchTarget>=2){
                self.zoom(e);
            }

        },
        touchend: function(e){
            var self = this;

            self.changeData(); //重新计算数据
            if(self.finger){
                self.distX = -self.imgNewX;
                self.distY = -self.imgNewY;
            }

            if( self.distX>0 ){
                self.newX = 0;
            }else if( self.distX<=0 && self.distX>=-self.width ){
                self.newX = self.distX;
                self.newY = self.distY;
            }else if( self.distX<-self.width ){
                self.newX = -self.width;
            }
            self.reset();
        },
        move: function(e){
            var self = this,
                pageX = getPage(e, "pageX"), //获取移动坐标
                pageY = getPage(e, "pageY");

            // 获得移动距离
            self.distX = (pageX - self.basePageX) + self.newX;
            self.distY = (pageY - self.basePageY) + self.newY;

            if(self.distX > 0){
                self.moveX = Math.round(self.distX/self.buffMove);
            }else if( self.distX<=0 && self.distX>=-self.width ){
                self.moveX = self.distX;
            }else if(self.distX < -self.width ){
                self.moveX = -self.width+Math.round((self.distX+self.width)/self.buffMove);
            }
            self.movePos();
            self.finger = false;
        },
        zoom: function(e){
            var self = this;

            var nowFingerDist = self.getTouchDist(e).dist, //获得当前长度
                ratio 		  = nowFingerDist / self.startFingerDist, //计算缩放比
                imgWidth  	  = Math.round(self.mapX * ratio), //计算图片宽度
                imgHeight 	  = Math.round(self.mapY * ratio); //计算图片高度

            self.imgNewX = Math.round(self.startFingerX * ratio - self.startFingerX - self.newX * ratio);
            self.imgNewY = Math.round((self.startFingerY * ratio - self.startFingerY)/2 - self.newY * ratio);

            if(imgWidth >= self.imgBaseWidth){
                self.showImg.style.width = imgWidth + "px";
                self.refresh(-self.imgNewX, -self.imgNewY, "0s", "ease");
                self.finger = true;
            }else{
                if(imgWidth < self.imgBaseWidth){
                    self.showImg.style.width = self.imgBaseWidth + "px";
                }
            }

            self.finger = true;
        },
        movePos: function(){
            var self = this;

            if(self.height<0){
                if(self.showImg.offsetWidth == self.imgBaseWidth){
                    self.moveY = Math.round(self.distY/self.buffMove);
                }else{
                    var moveTop = Math.round((self.showImg.offsetHeight-self.imgBaseHeight)/2);
                    self.moveY = -moveTop + Math.round((self.distY + moveTop)/self.buffMove);
                }
            }else{
                var a = Math.round((self.wrapY - self.imgBaseHeight)/2),
                    b = self.showImg.offsetHeight - self.wrapY + Math.round(self.wrapY - self.imgBaseHeight)/2;

                if(self.distY >= -a){
                    self.moveY = Math.round((self.distY + a)/self.buffMove) - a;
                }else if(self.distY <= -b){
                    self.moveY = Math.round((self.distY + b)/self.buffMove) - b;
                }else{
                    self.moveY = self.distY;
                }
            }
            self.refresh(self.moveX, self.moveY, "0s", "ease");

        },
        // 重置数据
        reset: function(){
            var self = this,
                hideTime = ".2s";
            if(self.height<0){
                self.newY = -Math.round(self.showImg.offsetHeight - self.imgBaseHeight)/2;
            }else{
                var a = Math.round((self.wrapY - self.imgBaseHeight)/2),
                    b = self.showImg.offsetHeight - self.wrapY + Math.round(self.wrapY - self.imgBaseHeight)/2;

                if(self.distY >= -a){
                    self.newY = -a;
                }else if(self.distY <= -b){
                    self.newY = -b;
                }else{
                    self.newY = self.distY;
                }
            }
            self.refresh(self.newX, self.newY, hideTime, "ease-in-out");
        },
        getTouchDist: function(e){
            var x1 = 0,
                y1 = 0,
                x2 = 0,
                y2 = 0,
                x3 = 0,
                y3 = 0,
                result = {};

            x1 = e.touches[0].pageX;
            x2 = e.touches[1].pageX;
            y1 = e.touches[0].pageY - document.body.scrollTop;
            y2 = e.touches[1].pageY - document.body.scrollTop;

            if(!x1 || !x2) return;

            if(x1<=x2){
                x3 = (x2-x1)/2+x1;
            }else{
                x3 = (x1-x2)/2+x2;
            }
            if(y1<=y2){
                y3 = (y2-y1)/2+y1;
            }else{
                y3 = (y1-y2)/2+y2;
            }

            result = {
                dist: Math.round(Math.sqrt(Math.pow(x1-x2,2)+Math.pow(y1-y2,2))),
                x: Math.round(x3),
                y: Math.round(y3)
            };
            return result;

        },
        // 执行图片移动
        refresh: function(x, y, timer, type){
            this.showImg.style.webkitTransitionProperty = "-webkit-transform";
            this.showImg.style.webkitTransitionDuration = timer;
            this.showImg.style.webkitTransitionTimingFunction = type;
            this.showImg.style.webkitTransform = getTranslate(x, y);
        },
        changeData: function(){
            var self = this;
            self.mapX = self.showImg.offsetWidth;
            self.mapY = self.showImg.offsetHeight;
            self.width = this.mapX - this.wrapX;
            self.height = this.mapY - this.wrapY;


        },
        createImg: function(src){
            if(!src) return ;
            var self = this;
            self.showImg = document.createElement('img');
            self.showImg.src = src;

        },
        removeImg: function(){
            var self = this;
            if(!self.showImg) return ;
            self.showImg.parent.removeChild(self.showImg);

        },
        destroy: function(){
            this.distX = 0;
            this.distY = 0;
            this.newX = 0;
            this.newY = 0;
        },
        eventStop: function(e){
            e.preventDefault();
            e.stopPropagation();
        }

    }

    window.ImagesShow= new ImagesShow();

})();