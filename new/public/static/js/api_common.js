
+ function($){
    $.dm = {};
    /**
     * 对话模态框
     *
     * @param object _options 配置参数
     */
    $.dm.model = function(_options){

        var options = {
            tempType : 0 ,
            _title : '提示' ,
            _body : '' ,
            submit : '确认' ,
            cancel : '取消' ,
            btnRemove : false ,
            submitCallback : null
        };
        var $body           = $(document.body) ;
        var $modal          = null;
        var $backdrop       = null;
        var template        = '' ;
        var scrollbarWidth  = 0 ;

        for (i in _options) options[i] = _options[i];


        if($('.modal').length == 0){
            createModal();
        }else{
            setTimeout(function(){
                createModal();
            }, 500);
        }

        function createModal(){
            getTemplate();
            var backdrop = '<div class="modal-backdrop in"></div>';

            $body.addClass('modal-open')
                .append(template)
                .append(backdrop);

            $modal = $('.modal');
            $backdrop = $('.modal-backdrop');

            $modal.addClass('in').click(function(){
                if(!options.btnRemove){
                    removeModal();
                }
            });
            $('.modal .modal-content').click(function(){
                return false;
            });
            if(options.tempType == 1 || options.tempType == 2){
                var cancelBtn = $('.modal-footer .modal-cancel');
                cancelBtn.click(removeModal);
            }
            if(options.tempType == 2){
                var submitBtn = $('.modal-footer .modal-submit');
                if(typeof options.submitCallback == 'function'){
                    submitBtn.click(function(){
                        options.submitCallback();
                        removeModal();
                    });
                }else{
                    submitBtn.click(removeModal);
                }
            }
        }

        function getTemplate(){
            var template_default = '<div class="modal fade"><div class="modal-dialog">'+
                    '<div class="modal-content"><div class="modal-header">'+options._title+
                    '</div><div class="modal-body">'+options._body+
                    '</div></div></div></div>';
            var template_c = '<div class="modal fade"><div class="modal-dialog">'+
                    '<div class="modal-content"><div class="modal-body">'+options._body+
                    '</div><div class="modal-footer">'+
                    '<a href="javascript:void(0)" class="modal-btn modal-cancel">'
                    +options.submit+'</a></div>'+
                    '</div></div></div>';
            var template_c_s = '<div class="modal fade"><div class="modal-dialog">'+
                    '<div class="modal-content"><div class="modal-body">'+options._body+
                    '</div><div class="modal-footer"><div class="btn-group">'+
                    '<a href="javascript:void(0)" class="modal-btn modal-cancel">'
                    +options.cancel+'</a>'+
                    '<a href="javascript:void(0)" class="modal-btn modal-submit">'
                    +options.submit+'</a>'+
                    '</div></div></div></div></div>';

            switch(options.tempType){
                case 0 : template = template_default ; break;
                case 1 : template = template_c ; break;
                case 2 : template = template_c_s ; break;
                default  : template = template_default ; break;
            }

        }

        function removeModal(){
            $modal.removeClass('in');
            $backdrop.removeClass('in').addClass('fade');
            $body.removeClass('modal-open');
            setTimeout(function(){
                $modal.remove();
                $backdrop.remove();
            }, 300);
        }

    }

    $.dm.message = function(msg,title){
        if(title == undefined) title = '提示';
        return $.dm.model({
            tempType : 0 ,
            _title : title ,
            _body : msg
        });
    }

    $.dm.dialogRemove = function(){
        var $body = $(document.body) ,
            $modal = $('.modal'),
            $backdrop = $('.modal-backdrop');
        $modal.removeClass('in');
        $backdrop.removeClass('in').addClass('fade');
        $body.removeClass('modal-open');
        setTimeout(function(){
            $modal.remove();
            $backdrop.remove();
        }, 300);
    }

    $.dm.dialog = function(msg,_submit){
        if(_submit == undefined) _submit = '确认';
        return $.dm.model({
            tempType : 1 ,
            _body : msg ,
            submit : _submit
        });
    }

}(jQuery)



