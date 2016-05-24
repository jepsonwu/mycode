package models

import (
	"github.com/astaxie/beego/orm"
	"github.com/astaxie/beego"
	"net/url"
	"fmt"
	_ "github.com/go-sql-driver/mysql"
)

type UserDemo struct {
	UID      uint `orm:"pk;column(UID)"`
	Nickname string `orm:"column(Nickname)"`
	Password string `orm:"column(Password)"`
	//Profile  *UserProfile
}

//type UserProfile struct {
//	Age        int
//	Phone      string
//	CreateTime int
//}

func init() {
	//注册mysql驱动
	orm.RegisterDriver("mysql", orm.DRMySQL)

	//mysql连接字符串
	mysql_dsn := beego.AppConfig.String("db.user") + ":" + beego.AppConfig.String("db.password") +
	"@tcp(" + beego.AppConfig.String("db.host") + ":" + beego.AppConfig.String("db.port") + ")/" +
	beego.AppConfig.String("db.name") + "?charset=" + beego.AppConfig.String("db.charset") +
	"&loc=" + url.QueryEscape(beego.AppConfig.String("db.timezone"))

	orm.RegisterDataBase("default", "mysql", mysql_dsn, 10, 1000);
	orm.RegisterModel(new(UserDemo))
	orm.Debug = true
}

func GetAllUsersDemo() []*UserDemo {
	//创建ormer，全局的
	o := orm.NewOrm()
	//使用数据库
	o.Using(beego.AppConfig.String("db.name"))

	//
	var userlist []*UserDemo
	num, err := o.QueryTable("user_demo").Limit(10).All(&userlist, "UID", "Nickname", "Password")
	if (err != nil) {
		fmt.Println("query error:", err)
	}else {
		fmt.Println("query num:", num)
	}

	return userlist
}