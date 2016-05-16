# 短信接口说明文档 #
- API地址：http://sms.duomai.com

## API参数说明
- **key** - string类型，访问接口的密钥。

- **phone** - int类型，目的手机号

- **message** - string类型，短信内容
 
- **name** - string类型，可选，主体名称
## 示例说明
message = 'message content'

name = 'XX网'

短信内容：

message content【XX网】

## 注意事项
1. 同一手机号60秒以内只能发送一条短信，请注意应用中发送短信的频率。
2. 发送内容必须要有主体(不是必须要name参数)，否则会发送失败,比如【您好，您正在申请绑定手机操作，验证码：877085】，这样的短信内容是不合格的，可以修改为【您好，亲爱的XX网用户，您正在申请绑定手机操作，验证码：877085】或者加上name参数(XX网)。
