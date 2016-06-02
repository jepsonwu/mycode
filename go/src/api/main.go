package main

import (
	_ "api/docs"
	_ "api/routers"
	"github.com/astaxie/beego"
	"api/prepare"
)

func main() {
	if beego.BConfig.RunMode == "dev" {
		beego.BConfig.WebConfig.DirectoryIndex = true
		beego.BConfig.WebConfig.StaticDir["/swagger"] = "swagger"
	}

	//解析controller注释
	prepare.ParseParamFilters()

	beego.Run()
}
