package models

import (
	"github.com/astaxie/beego/orm"
	"github.com/astaxie/beego"
	"net/url"
	"fmt"
	_ "github.com/go-sql-driver/mysql"
)

//valid 参数校验 正则不支持 \w格式
//orm 模型参数
type Demo struct {
	Uid        int64 `orm:"pk"`
	Nickname   string `orm:"unique"valid:"Required;MaxSize(50)"` //unique varchar 50
	Password   string `valid:"Match(/^[A-Za-z0-9_]{32}$/)"`      //char 32
	Age        int   `valid:"Range(1,140)"`                      //没有tint么
	Phone      string `valid:"Mobile"`
	CreateTime int    `valid:"Match(/^[0-9]+$/)"`
}

func init() {
	//注册mysql驱动
	orm.RegisterDriver("mysql", orm.DRMySQL)

	//mysql连接字符串
	mysql_dsn := beego.AppConfig.String("db.user") + ":" + beego.AppConfig.String("db.password") +
	"@tcp(" + beego.AppConfig.String("db.host") + ":" + beego.AppConfig.String("db.port") + ")/" +
	beego.AppConfig.String("db.name") + "?charset=" + beego.AppConfig.String("db.charset") +
	"&loc=" + url.QueryEscape(beego.AppConfig.String("db.timezone"))

	orm.RegisterDataBase("default", "mysql", mysql_dsn, 10, 1000);
	orm.RegisterModel(new(Demo))
	//debug 打印sql语句
	//orm.Debug = true
}

func GetAllDemos() []*Demo {
	//创建ormer，全局的
	o := orm.NewOrm()
	//使用数据库
	o.Using(beego.AppConfig.String("db.name"))

	//
	var demolist []*Demo

	num, err := o.QueryTable("demo").Limit(10).All(&demolist)
	if (err != nil) {
		fmt.Println("query error:", err)
	}else {
		fmt.Println("query num:", num)
	}

	return demolist
}

func AddDemo(d Demo) int64 {
	o := orm.NewOrm()
	o.Using(beego.AppConfig.String("db.name"))

	//返回int64 uid定义为uint 冲突了
	uid, err := o.Insert(&d)
	if err != nil {
		fmt.Println("demo insert error:", err)
	}

	return uid
}
/**
自定义表名 否则使用模型驼峰转蛇形规则
 */
//func (d *Demo)TableName() string {
//	return "user"
//}