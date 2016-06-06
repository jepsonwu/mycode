package controllers

import (
	"github.com/astaxie/beego"
	"os"
	"api/prepare"
	"api/tools"
)

type BaseController struct {
	beego.Controller
}

func (c *BaseController)Prepare() {
	//todo 参数校验、签名、异常捕获  unmarshal报错怎么办 定义interface{}基本类型结构 json解析成基本类型结构之后再解析成model结构
	for _, val := range prepare.ParamFilters {
		tools.PrintStruct(val)
	}

	os.Exit(2)
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
