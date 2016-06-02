package controllers

import (
	"github.com/astaxie/beego"
)

type BaseController struct {
	beego.Controller
}

func (c *BaseController)Prepare() {

}

func (c *BaseController)FailedJson(code, msg string) {
	c.Data["json"] = map[string]string{"code":code, "msg":msg}
	c.ServeJSON()
	//c.StopRun()
}

func (c *BaseController)SuccessJson(data interface{}) {
	c.Data["json"] = map[string]interface{}{"code":"200", "data":data}
	c.ServeJSON()
}
