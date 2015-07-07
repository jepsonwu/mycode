<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="description" content=""/>
<meta name="keywords" content=""/>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>welcome to the payMin's blog</title>
<link rel="stylesheet" type="text/css" href="/Public/css/base.css" />
<link rel="stylesheet" type="text/css" href="/Public/css/jquery-ui.css" />
<!--<link rel="stylesheet" type="text/css" href="/Public/bootstrap_3/css/bootstrap.min.css" />-->
</head>

<script type="text/javascript" src="/Public/jquery/jquery.js"></script>
<script type="text/javascript" src="/Public/jquery/jquery-ui.js"></script>
<script type="text/javascript" src="/Public/js/common.js"></script>

<script>
    var GROUP = "/index.php/Home";
    var URL = "/index.php/Home/Public";
</script>

<body>
<nav id="main-nav" class="navbar navbar-default" role="navigation">
    <div class="container">

        <div class="navbar-header">
            <a href="/"><img id="navbar-logo" src="/Public/img/logo.png"></a>
        </div>


        <div class="collapse navbar-collapse navbar-top-collapse">
            <ul class="nav navbar-nav navbar-right">

                <li class=""><a href="/"><span class='nav-home'></span></a></li>
                <li class=""><a href="#">功能</a></li>
                <li class=""><a href="#">今日看点</a></li>
                <li class=""><a href="#">加入我们</a></li>

                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">资源 <span class="caret"></span></a>
                    <ul class="dropdown-menu dropdown-main-nav dropdown-mega">
                        <li class="dropdown-third">
                            <ul>
                                <li><a href="/docs/getting-started">Getting Started</a></li>
                                <li><a href="/docs/migrate-to-linode/migrate-from-shared-hosting">Migrating to
                                    Linode</a></li>
                                <li><a href="/docs/websites/hosting-a-website">Hosting a Website</a></li>
                                <li class="divider"></li>
                                <li class="big"><a href="/docs"><i class="fa fa-book"></i> Guides &amp;
                                    Tutorials</a></li>
                                <li class="divider visible-xs"></li>
                            </ul>
                        </li>
                        <li class="dropdown-third middle">
                            <ul>
                                <li><a href="/api">API</a></li>
                                <li><a href="/stackscripts">StackScripts</a></li>
                                <li><a href="/mobile">Mobile</a></li>
                                <li><a href="/cli" target="_blank">CLI</a></li>

                                <li class="divider"></li>

                                <li><a href="/chat"><i class="fa fa-bullhorn gray"></i> Chat</a></li>
                                <li><a href="https://forum.linode.com"><i class="fa fa-comments"></i> Community
                                    Forum</a></li>
                                <li class="divider visible-xs"></li>
                            </ul>
                        </li>
                        <li class="dropdown-third">
                            <ul>
                                <li><a href="https://blog.linode.com">Blog</a></li>
                                <li><a href="http://status.linode.com">System Status</a></li>
                                <li><a href="/speedtest">Speed Test</a></li>
                                <li><a href="/about">About Us</a></li>
                                <li class="divider"></li>
                                <li><a href="/contact"><i class="fa fa-user"></i> Contact Support</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>

                <li role="presentation" class="divider-vertical"><span>|</span></li>


                <li class=""><a href="https://manager.linode.com/">登录 <span class="login-caret"></span></a></li>
                <li class="visible-xs"><a href="https://manager.linode.com/session/signup">注册</a></li>

                <li class="hidden-xs">
                    <div><a id="btn-signup-top" class="btn btn-white btn-sm navbar-btn hidden-xs"
                            href="https://manager.linode.com/session/signup">注册</a></div>
                </li>

            </ul>
        </div>
    </div>
</nav>

<div class="container">
    <form action="URL/User_login" method="post">
        <table>
            <tr>
                <td>用户名：</td>
                <td><input type="text" name="uname" value=""></td>
            </tr>
            <tr>
                <td>密码：</td>
                <td><input type="password" name="password" value=""></td>
            </tr>
            <tr>
                <td>验证码：</td>
                <td><input type="text" name="verify" value=""></td>
                <td><img id="verify" title="点击刷新验证码" src="/index.php/Home/Public/Verify"></td>
            </tr>
        </table>
    </form>
    <div><input id="btn_login" type="button" value="登录"></div>
</div>

<script>
    $(function () {
        $("#btn_login").click(function () {
            show_dialog("degnluy");
        });
    });
</script>

<section class="dark">
    <div class="container">

        <div class="row">
            <div class="footer-col">
                <h5><a href="#">综述</a></h5>
                <ul>
                    <li><a href="#">最近计划</a></li>
                    <li><a href="#">最近新闻</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h5>友情链接</h5>
                <ul>
                    <li><a href="http://www.zhibo8.cc">直播吧</a></li>
                    <li><a href="http://www.baidu.com">百度</a></li>
                </ul>
            </div>


            <div class="footer-col">
                <h5><a href="#">公司</a></h5>
                <ul>
                    <li><a href="#">关于我们</a></li>
                    <li><a href="#">了解我们</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h5><a href="#">联系我</a></h5>
                <ul>
                    <li>18258438129</li>
                    <li>wjp13671142513@163.com</li>
                </ul>
            </div>
        </div>

    </div>
</section>


<section class="dark-moar">
    <div class="container">
        <div id="footer-copyright" class="row">
            Copyrigth&copy; 2015 www.litter.com
        </div>
    </div>
</section>
<div id="dialog" title="系统消息"></div>

</body>

</html>