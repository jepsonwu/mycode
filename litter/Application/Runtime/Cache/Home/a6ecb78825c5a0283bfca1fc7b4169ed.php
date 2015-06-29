<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="description" content=""/>
<meta name="keywords" content=""/>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>welcome to the payMin's blog</title>
<link rel="stylesheet" type="text/css" href="/Public/css/base.css" />
<!--<link rel="stylesheet" type="text/css" href="/Public/bootstrap_3/css/bootstrap.min.css" />-->
</head>

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
    <form action="" method="post">
        <div class="form-group">
            <label for="inputEmail3" class="col-sm-2 control-label">Email</label>

            <div class="col-sm-10">
                <input type="email" class="form-control" id="inputEmail3" placeholder="Email">
            </div>
        </div>
        <div class="form-group">
            <label for="inputPassword3" class="col-sm-2 control-label">Password</label>

            <div class="col-sm-10">
                <input type="password" class="form-control" id="inputPassword3" placeholder="Password">
            </div>
        </div>
        <div class="form-group">
            <label for="inputVerify" class="col-sm-2 control-label">Verify</label>

            <div class="col-sm-10">
                <input type="text" class="form-control" id="inputVerify" placeholder="Verify">
            </div>
            <img src="/index.php/Home/Public/Verify" title="点击从新生成验证码">
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <div class="checkbox">
                    <label>
                        <input type="checkbox"> Remember me
                    </label>
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <button type="submit" class="btn btn-default">Sign in</button>
            </div>
        </div>
    </form>
</div>

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
<script type="text/javascript" src="/Public/jquery/jquery.js"></script>
<script type="text/javascript" src="/Public/js/common.js"></script>
</body>

</html>