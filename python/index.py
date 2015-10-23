#!/usr/bin/env python2
#coding:utf-8
import urllib
import re
def getHtml(url):
	page=urllib.urlopen(url)
	html=page.read()
	return html

def getImg(html):
	reg=r'src="(.+?\.jpg)" pic_ext'
	img_reg=re.compile(reg)
	img_list=img_reg.findall(img_reg,html)
	print img_list
	return None

	x=0
	for img_url in img_list:
		urllib.urlretrieve(img_url,'%s.jpg' % x)
		x+=1

html=getHtml("http://tieba.baidu.com/p/2460150866")
#print html
