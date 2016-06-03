# mycode
项目开发：
【服务器配置】
见自动部署脚本

【git仓库配置】
yum install git
adduser git

cd /data
git init --bare sample.git  初始化远程裸仓库，这个仓库是不允许更改的 和git init 不同 git init可以操作 一般在测试环境生成
chown  -R git:git sample.git/

vim /etc/passwd  修改：git:x:1004:1004::/home/git:/usr/bin/git-shell
mkdir /home/git/.ssh
chown -R git:git /home/git/.ssh/
chown -R git.git /home/git/.ssh/authorized_keys

本地初始化仓库
cd /data
mkdir sample
git init
git remote add origin git@192.168.3.204:/data/sample.git  添加远程仓库
添加文件 新建项目  其他人 直接 git pull origin master即可开发
git add .
git commit -am "test"
git push origin master


【框架配置】
见project1

产品原型 设计 前端 服务端开发
