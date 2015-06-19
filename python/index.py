#!/usr/bin/env python3
#-*- coding:utf-8 -*-


#input print

# name=input("please input your name:")

# print("hello ",name)


#python 大小写敏感

a=10

if a>1:
	print(a)
else:
	print(-a)

#中文测试
print("中文测试")

#数据类型
print(1.23e9)

#''' ''' 特殊换行  \n也行
print('''this one line\nthis two line
this three line''')

#True False  注意大小写
print(True)

#特殊字符  None  null
print(None)

#常量  常量的值还是可以改变的
TP='aaa'
print(TP)

TP='bbb'
print(TP)

#四则运算

#除法 结果为浮点数
print(10/3)
print(10/2)

#取整除
print(10//3)

#取余
print(10%3)

#格式化字符串
str_var="test"
print("this is %s %%" % str_var)

#字符编码转换

#有序集合

#list 集合  可变
class_list=["a","b","c"]
print(class_list)
print(class_list[0],class_list[-2])

print(len(class_list))

class_list.append("f")
print(class_list)

class_list.insert(1,"rr")
print(class_list)

class_list.pop()
print(class_list)

class_list.pop(3)
print(class_list)

#tuple集合  不可变
class_list=("dd","dd","eee")
print(class_list)

# class_list.append("rrr")
# print(class_list)

#一个集合 加上， 区分
class_list=("ee",)
print(class_list)


