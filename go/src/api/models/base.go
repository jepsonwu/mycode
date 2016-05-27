package models

import (
	"github.com/astaxie/beego"
	"net/url"
	"github.com/astaxie/beego/orm"
	_ "github.com/go-sql-driver/mysql"
)

var BaseOrmer orm.Ormer

func init() {
	//注册mysql驱动
	orm.RegisterDriver("mysql", orm.DRMySQL)

	//mysql连接字符串
	mysqlDsn := beego.AppConfig.String("db.user") + ":" + beego.AppConfig.String("db.password") +
	"@tcp(" + beego.AppConfig.String("db.host") + ":" + beego.AppConfig.String("db.port") + ")/" +
	beego.AppConfig.String("db.name") + "?charset=" + beego.AppConfig.String("db.charset") +
	"&loc=" + url.QueryEscape(beego.AppConfig.String("db.timezone"))

	orm.RegisterDataBase("default", "mysql", mysqlDsn, 10, 1000);

	//debug
	isDebug, err := beego.AppConfig.Bool("orm.debug")
	if err == nil&&isDebug {
		orm.Debug = true
	}

}

func GetBaseOrmer() orm.Ormer {
	if BaseOrmer == nil {
		//base ormer
		BaseOrmer = orm.NewOrm()
		BaseOrmer.Using(beego.AppConfig.String("db.name"))
	}

	return BaseOrmer
}
