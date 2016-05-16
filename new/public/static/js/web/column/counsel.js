var selected = 'selected';

$('.cdraft-list').click(function(e){
    var $this, $li, $target, type, className,
        typeArr = ['counsel-showall', 'counsel-up'];

    $this = $(this);
    $target = $(e.target);
    className = $target.attr('class');
    if(className == typeArr[0]){
        $li = $target.parents('li');
        $li.addClass(selected);
    }else if(className == typeArr[1]){
        $li = $target.parents('li');
        $li.removeClass(selected);
    }else{}

});

$('.nav-list, .article-tit-do, .counsel-order').click(function(e){
    var $this, type, target, $target;

    target = e.target;
    if(target.tagName == 'A'){
        $target = $(target);
        if($target.hasClass(selected)){
            return false;
        }
    }
});


/*  submit form - action */

var hide = 'hide',
    selected = 'selected',

    $kuangBox = $('#kuang-box'),
    $kuangClose = $('.kuang-close'),
    $kuangCode = $('#kuang-code'),
    $kuangDraft = $('#kuang-draft'),

    $readDraft = $('#read-draft'),
    $macSubmit = $('.mac-submit'),
    $macDraft = $('.mac-draft'),
    $macView = $('.mac-view'),
    $form = $('#form'),
    $validOne = $('.validone'),
    $submitType = $('#submit-type'),
    $articleId = $("#article-id"),

    $codePic = $('#code-pic');

$kuangClose.click(function(){
    $kuangBox.children().eq(0).siblings().addClass(hide);
    $kuangBox.addClass(hide);
});

/* $macView.click(function(){
 $kuangBox.children().eq(0).siblings().addClass(hide);
 $kuangCode.removeClass(hide);
 $kuangBox.removeClass(hide);
 }); */

$readDraft.click(function(){
    $kuangBox.children().eq(0).siblings().addClass(hide);
    $kuangDraft.removeClass(hide);
    $kuangBox.removeClass(hide);
});

$macSubmit.click(function(){

    var $this = $(this), flag;
    flag = $this.data('flag');
    if(flag){return ;}

    formSubmit(1);
});

$macDraft.click(function(){

    var $this = $(this), flag;
    flag = $this.data('flag');
    if(flag){return ;}

    formSubmit(2);
});


$macView.click(function(){

    var $this = $(this), flag;
    flag = $this.data('flag');
    if(flag){return ;}

    formSubmit(3);
});

function formSubmit(formType){
    var isSubmit = true,
        i, d, val, type, reg
        tipStr = 'tip';
    len = $validOne.length;

    for(i=0; i<len; i++){
        d = $validOne.eq(i);
        type = d.data('type');
        switch(type){
            case 'limit':
                var min = d.data('min') || 0,
                    max = d.data('max'),
                    valLen;

                val = d.find('input').val() || d.find('textarea').val() || 0;
                valLen = val.length;
                if(val && valLen>min && valLen<max){
                    d.find('.ti-box').addClass(hide);
                }else{
                    isSubmit = false;
                    d.find('.ti-box').removeClass(hide);
                }
                break;

            case 'select':
                val = d.find('select').val();
                if(val != 0 && val != undefined){
                    d.find('.ti-box').addClass(hide);
                }else{
                    isSubmit = false;
                    d.find('.ti-box').removeClass(hide);
                }
                break;

            case 'need':
                val = d.find('input').val() || d.find('textarea').val();
                if(val){
                    d.find('.ti-box').addClass(hide);
                }else{
                    isSubmit = false;
                    d.find('.ti-box').removeClass(hide);
                }
                break;

            case 'price':
                val = d.find('input').val();
                reg = /^\d+(\.\d{1,2})?$/;
                if(val && reg.test(val)){
                    d.find('.ti-box').addClass(hide);
                }else{
                    isSubmit = false;
                    d.find('.ti-box').removeClass(hide);
                }
                break;

            case 'city':
                val = d.find('input').val();
                reg = /^\d+(\.\d{1,2})?$/;
                if(val){
                    d.find('.ti-box').addClass(hide);
                }else{
                    isSubmit = false;
                    d.find('.ti-box').removeClass(hide);
                }
                break;

            case 'agree':
                val = d.find('input')[0].checked;

                if(val){
                    d.find('.ti-box').addClass(hide);
                }else{
                    isSubmit = false;
                    d.find('.ti-box').removeClass(hide);
                }
                break;
        }

    }

    if(isSubmit){

        var $form = $('#form'),
            dataClass = $form.serialize(),
            url = $form.data('url'),
            goto = $form.data('goto'),
            draftGoto = $form.data('draft-goto');


        dataClass = parseClass(dataClass);
        dataClass.formType = formType;

        $submitType.val(formType);
        $.ajax({
            url: url,
            type: 'post',
            dataType :'json',
            data: dataClass,
            /*data:{
                formType: formType,                  // 1，提交发布 2，保存草稿  3，手机预览

            },*/
            dataType: 'json',
            success: function(d){
                var msgTip;

                if(d.flag == 1){
                    $articleId.val(d.data.articleID);
                    if(formType == 1){
                        /*if($localTiming[0].checked){
                            definedPrompt(d.msg, function(){
                                location.href = '/draft/article';    //草稿列表
                            });
                        }else{*/
                        msgTip = d.msg || '创建成功';          // 本该服务端返回

                        if(d.data.isFirst == 1){
                            msgTip = '发表成功，请将财猪升级到2.4.1或更高版本，否则您无法接到咨询者的订单';
                        }

                            definedPrompt(msgTip, function(){
                                if(goto){
                                    location.href = goto;
                                }else{
                                    //location.href = '/article/index';    //文章列表
                                }

                            });
                        //}
                    }else if(formType == 2){
                        msgTip = d.msg || '创建成功';
                        definedPrompt(msgTip, function(){
                            if(draftGoto){
                                location.href = draftGoto;
                            }else{
                                location.href = '/draft/article';    //草稿列表
                            }
                        });
                    }else if(formType == 3){
                        $codePic.attr('src', d.data.qrCodeUrl);

                        $kuangBox.children().eq(0).siblings().addClass(hide);
                        $kuangCode.removeClass(hide);
                        $kuangBox.removeClass(hide);

                    }else{

                    }

                }else{
                    definedPrompt(d.msg);
                }
            },
            error: function(){
                definedPrompt('网络错误');
            },
            beforeSend: function(){
                $sigle01.removeClass(hide);

                if(formType == 1 ){
                    $macSubmit.data('flag', 'true');
                }else if(formType == 2){
                    $macDraft.data('flag', 'true');
                }else{
                    $macView.data('flag', 'true');
                }
            },
            complete: function(){
                $sigle01.addClass(hide);

                if(formType == 1 ){
                    $macSubmit.data('flag', '');
                }else if(formType == 2){
                    $macDraft.data('flag', '');
                }else{
                    $macView.data('flag', '');
                }

            }

        });

        //$form.submit();
    }
}

function parseClass(str){

    if(!str) return ;
    var a, obj={}, arr=[];
    arr = str.split('&');
    for(var i=0,len=arr.length; i<len; i++){
        a = arr[i].split('=');
        obj[a[0]] = decodeURIComponent(a[1]);
    }

    return obj;

}

/* hide tip - start */
$('.cadd-text00, .cadd-text01').focus(function(){
    //$(this).parents('.cadd-one').find('.ti-box').addClass(hide);
    hideTip($(this));
});

$('#add-btn').click(function(){
    hideTip($(this));
});

$('#select').click(function(){
    hideTip($(this));
});

function hideTip(obj){
    obj.parents('.cadd-one').find('.ti-box').addClass(hide);
}
/* hide tip - end */

