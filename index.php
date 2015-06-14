<?php
echo phpinfo();

/**
 * 这个函数第一个参数可以为函数名，包含对象和方法名的数组，命名空间。
 * 第二个参数为要传入的参数数组
 */
call_user_func_array($arg,$arg1);

/**
 * 以Location:开头，有返回值
 */
header ( 'Location: http://www.example.com/' );

/**
 * 以HTTP/开头
 */
header("HTTP/1.1 404 Not Found");

