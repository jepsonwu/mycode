function wechatShare(d, options){
    if(!d || !options) return ;
    var title = options.title || '����̫����̫����',
        description = options.des || '',
        pic = options.pic || (location.protocol+'//'+location.hostname+'/static/imgs/api/app-share.png'),
        link = location.href;

    wx.config({
        debug: false,
        appId: d.appId,
        timestamp: d.timestamp,
        nonceStr: d.nonceStr,
        signature: d.signature,
        jsApiList: ['onMenuShareTimeline', 'onMenuShareAppMessage', 'onMenuShareQQ', 'onMenuShareWeibo', 'menuItem:profile', 'menuItem:addContact']
    });
    wx.ready(function () {

        //���?����Ȧ
        wx.onMenuShareTimeline({
            title: title, // �������
            desc: description, // ��������
            link: link, // ��������
            imgUrl: pic, // ����ͼ��
            success: function () {
                // �û�ȷ�Ϸ����ִ�еĻص�����
            },
            cancel: function () {
                // �û�ȡ������ִ�еĻص�����
            }
        });

        //���������
        wx.onMenuShareAppMessage({
            title: title, // �������
            desc: description, // ��������
            link: link, // ��������
            imgUrl: pic, // ����ͼ��
            type: '', // ��������,music��video��link������Ĭ��Ϊlink
            dataUrl: '', // ���type��music��video����Ҫ�ṩ������ӣ�Ĭ��Ϊ��
            success: function () {
                // �û�ȷ�Ϸ����ִ�еĻص�����
            },
            cancel: function () {
                // �û�ȡ������ִ�еĻص�����
            }
        });

        //���?QQ
        wx.onMenuShareQQ({
            title: title, // �������
            desc: description, // ��������
            link: link, // ��������
            imgUrl: pic, // ����ͼ��
            success: function () {
                // �û�ȷ�Ϸ����ִ�еĻص�����
            },
            cancel: function () {
                // �û�ȡ������ִ�еĻص�����
            }
        });

        //���?��Ѷ΢��
        wx.onMenuShareWeibo({
            title: title, // �������
            desc: description, // ��������
            link: link, // ��������
            imgUrl: pic, // ����ͼ��
            success: function () {
                // �û�ȷ�Ϸ����ִ�еĻص�����
            },
            cancel: function () {
                // �û�ȡ������ִ�еĻص�����
            }
        });
    });
}

function replaceBlank(str){
    if(!str) return ;
    str.toString();
    str = str.replace(/\s*/g, '');
    return str.substr(0, 200);
}