package v2

import (
	"github.com/astaxie/beego"
)

type UserController struct {
	beego.Controller
}


// @router / [get]
func (u *UserController) GetAll() {
	u.Data["json"] = []byte("jepson")
	u.ServeJSON()
}
