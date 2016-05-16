$(function() {
    // 新闻列表鼠标滑入
    $('.main .news-list ul li').hover(function(){
        $(this).addClass('hover');
    },function(){
        $(this).removeClass('hover');
    })

    // 选项卡
    $('.main .tab ul li').click(function() {
        $(this).addClass('hover').siblings().removeClass('hover');
        $('.tab-content').find('.list').hide().eq($(this).index()).show();
    });

    $('.tab-content').find('.list').eq(0).show();


})
